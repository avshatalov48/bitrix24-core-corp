;(function ()
{
	BX.namespace("BX.Voximplant");

	if (BX.Voximplant.CallerIdSlider)
	{
		return;
	}
	
	var States = {
		New: 1,
		Verification: 2,
		Verified: 3
	};

	BX.Voximplant.CallerIdSlider = function (config)
	{
		this.slider = null;

		if(!BX.type.isPlainObject(config))
		{
			config = {};
		}

		if(config.dataPromise instanceof Promise)
		{
			this.configPromise = config.dataPromise;
		}
		else
		{
			this.init(config);
		}


		this.verificationCode = '';

		this.elements = {
			number: null,
			errorContainer: null,
			hint: null,
			confirmation: null,
			buttons: null,
			saveButton: null,
			confirmButton: null,
			repeatButton: null,
			prolongButton: null,
		};

		this.callbacks = {
			onClose: BX.type.isFunction(config.onClose) ? config.onClose : BX.DoNothing
		}
	};

	BX.Voximplant.CallerIdSlider.prototype =
	{
		init: function (config)
		{
			this.phoneNumber = config.phoneNumber || '';
			this.verified = config.verified || false;
			this.verifiedUntil = config.verifiedUntil || '';

			if (!this.phoneNumber)
			{
				this.state = States.New;
			}
			else
			{
				this.state = this.verified ? States.Verified : States.Verification;
			}
		},

		show: function ()
		{
			BX.SidePanel.Instance.open("voximplant:callerId-add", {
				width: 400,
				events: {
					onClose: this.onSliderClose.bind(this),
					onDestroy: this.onSliderDestroy.bind(this)
				},
				contentCallback: function (slider)
				{
					var promise = new BX.Promise();
					this.slider = slider;

					if(this.configPromise)
					{
						this.configPromise.then(function(config)
						{
							this.init(config);
							return top.BX.loadExt("voximplant.common");
						}.bind(this)).then(function()
						{
							var layout = this.render();
							this.initLayout();
							promise.resolve(layout);
						}.bind(this)).catch(function(error)
						{
							slider.close();
							BX.Voximplant.alert(" ", error.message);
						});
					}
					else
					{
						top.BX.loadExt("voximplant.common").then(function()
						{
							var layout = this.render();
							this.initLayout();
							promise.resolve(layout);
						}.bind(this));
					}

					return promise;
				}.bind(this)
			});
		},

		render: function ()
		{
			return BX.createFragment([
				BX.create("div", {
					props: {className: "voximplant-slider-pagetitle-wrap"},
					children: [
						BX.create("div", {
							props: {className: "voximplant-slider-pagetitle"},
							children: [
								BX.create("span", {
									text: BX.message("VOX_CALLER_ID_TITLE")
								})
							]
						})
					]
				}),
				BX.create("div", {
					props: {className: "voximplant-container voximplant-options-popup"},
					children: [
						BX.create("div", {
							props: {className: "voximplant-control-row"},
							children: [
								BX.create("div", {
									props: {className: "voximplant-control-subtitle"},
									text: BX.message("VOX_CALLER_ID_PROVIDE_INTERNATIONAL_NUMBER")
								}),
								this.elements.number = BX.create("input", {
									props: {className: "voximplant-control-input"},
									attrs: {
										type: "text",
										placeholder: "+7 495 111-22-33",
										value: this.phoneNumber,
										disabled: this.state !== States.New
									}
								})
							]
						}),
						this.elements.errorContainer = BX.create("div", {
							props: {className: "voximplant-control-row"}
						}),
						this.elements.hint = BX.create("div", {
							props: {className: "voximplant-control-row"},
							children: this.renderHint()
						}),
						this.elements.confirmation = BX.create("div", {
							children: this.renderConfirmation()
						}),
					]
				}),
				this.elements.buttons = BX.create("div", {
					props: {className: "voximplant-button-panel"},
					children: this.renderButtons()

				})
			]);
		},

		renderHint: function ()
		{
			if (this.state === States.New || this.state === States.Verification)
			{
				return [
					BX.create("p", {
						text: BX.message("VOX_CALLER_ID_HINT_P1")
					}),
					BX.create("p", {
						text: BX.message("VOX_CALLER_ID_HINT_P2")
					}),
					BX.create("p", {
						text: BX.message("VOX_CALLER_ID_HINT_P3")
					})
				]
			}
			else if (this.state === States.Verified)
			{
				if (this.verified)
				{
					return [
						BX.create("div", {
							props: {className: "ui-alert ui-alert-primary"},
							children: [
								BX.create("span", {
									props: {className: "ui-alert-message"},
									text: BX.message("VOX_CALLER_ID_VERIFIED_UNTIL").replace("#DATE#", this.verifiedUntil)
								})
							]
						})
					];
				}
				else
				{
					return [
						BX.create("div", {
							props: {className: "ui-alert ui-alert-error"},
							children: [
								BX.create("span", {
									props: {className: "ui-alert-message"},
									text: BX.message("VOX_CALLER_ID_UNVERIFIED")
								})
							]
						})
					]
				}
			}
		},

		initLayout: function()
		{
			this.phoneInput = new BX.PhoneNumber.Input({
				node: this.elements.number,
				onChange: function(e)
				{
					this.phoneNumber = e.value;
				}.bind(this)
			})
		},

		updateHint: function ()
		{
			BX.cleanNode(this.elements.hint);
			BX.adjust(this.elements.hint, {
				children: this.renderHint()
			})
		},

		renderButtons: function ()
		{
			var result = [];

			if (this.state === States.New)
			{
				this.elements.saveButton = BX.create("button", {
					props: {className: "ui-btn ui-btn-primary"},
					text: BX.message("VOX_CALLER_ID_BUTTON_LINK"),
					events: {
						click: this.onAddButtonClick.bind(this)
					}
				});

				result.push(this.elements.saveButton);
			}
			else if (this.state === States.Verification)
			{
				this.elements.confirmButton = BX.create("button", {
					props: {className: "ui-btn ui-btn-primary"},
					text: BX.message("VOX_CALLER_ID_BUTTON_CONFIRM"),
					events: {
						click: this.onConfirmButtonClick.bind(this)
					}
				});
				result.push(this.elements.confirmButton);

				this.elements.repeatButton = BX.create("button", {
					props: {className: "ui-btn ui-btn-default"},
					text: BX.message("VOX_CALLER_ID_BUTTON_REPEAT_CALL"),
					events: {
						click: this.onRepeatButtonClick.bind(this)
					}
				});
				result.push(this.elements.repeatButton);
			}
			else if (this.state === States.Verified)
			{
				this.elements.prolongButton = BX.create("button", {
					props: {className: "ui-btn ui-btn-primary"},
					text: BX.message("VOX_CALLER_ID_BUTTON_PROLONG"),
					events: {
						click: this.onProlongButtonClick.bind(this)
					}

				});
				result.push(this.elements.prolongButton);
			}

			result.push(this.elements.cancelButton);

			return result;
		},

		updateButtons: function ()
		{
			BX.cleanNode(this.elements.buttons);
			BX.adjust(this.elements.buttons, {
				children: this.renderButtons()
			})
		},

		renderConfirmation: function ()
		{
			if (this.state === States.Verification)
			{
				return [
					BX.create("div", {
						props: {className: "voximplant-control-row"},
						children: [
							BX.create("div", {
								props: {className: "voximplant-control-subtitle"},
								text: BX.message("VOX_CALLER_ID_ENTER_CODE")
							}),
							BX.create("input", {
								props: {className: "voximplant-control-input"},
								attrs: {
									type: "text",
								},
								events: {
									bxchange: function (e)
									{
										this.verificationCode = e.currentTarget.value;
									}.bind(this)
								}
							})
						]
					})
				]
			}
			else
			{
				return [];
			}
		},

		updateConfirmation: function ()
		{
			BX.cleanNode(this.elements.confirmation);
			BX.adjust(this.elements.confirmation, {
				children: this.renderConfirmation()
			})
		},

		setState: function (state)
		{
			this.state = state;

			this.elements.number.disabled = this.state !== States.New;

			this.updateHint();
			this.updateConfirmation();
			this.updateButtons();
		},

		showError: function (message)
		{
			BX.adjust(this.elements.errorContainer, {
				children: [
					BX.create("div", {
						props: {className: "ui-alert ui-alert-danger ui-alert-icon-danger"},
						children: [
							BX.create("span", {
								props: {className: "ui-alert-message"},
								text: message
							})
						]
					})
				]
			});
		},

		hideError: function ()
		{
			BX.cleanNode(this.elements.errorContainer);
		},

		onSliderClose: function (e)
		{
			this.slider.destroy();
			this.callbacks.onClose();
		},

		onSliderDestroy: function (e)
		{
			this.slider = null;
		},

		onAddButtonClick: function (e)
		{
			this.hideError();
			BX.addClass(this.elements.saveButton, "ui-btn-wait");

			BX.ajax.runAction("voximplant.callerId.add", {
				data: {
					phoneNumber: this.phoneNumber,
					requestVerification: true
				}
			}).then(function (response)
			{
				BX.removeClass(this.elements.saveButton, "ui-btn-wait");
				var data = response.data;

				this.verified = data.verified === 'Y';
				this.verifiedUntil = data.verifiedUntil;

				if (this.verified)
				{
					this.setState(States.Verified);
				}
				else
				{
					this.setState(States.Verification);
				}

			}.bind(this)).catch(function (response)
			{
				var error = response.errors[0];

				BX.removeClass(this.elements.saveButton, "ui-btn-wait");
				console.error(error);
				this.showError(error.message);
			}.bind(this));
		},

		onRepeatButtonClick: function (e)
		{
			this.hideError();
			BX.addClass(this.elements.repeatButton, "ui-btn-wait");

			BX.ajax.runAction("voximplant.callerId.requestVerification", {
				data: {phoneNumber: this.phoneNumber}
			}).then(function (response)
			{
				BX.removeClass(this.elements.repeatButton, "ui-btn-wait");
			}.bind(this)).catch(function (response)
			{
				var error = response.errors[0];

				BX.removeClass(this.elements.repeatButton, "ui-btn-wait");
				console.error(error);
				this.showError(error.message);
			}.bind(this));
		},

		onConfirmButtonClick: function (e)
		{
			this.hideError();
			BX.addClass(this.elements.confirmButton, "ui-btn-wait");

			BX.ajax.runAction("voximplant.callerId.verify", {
				data: {
					phoneNumber: this.phoneNumber,
					code: this.verificationCode
				}
			}).then(function (response)
			{
				var data = response.data;
				this.verified = data.verified === 'Y';
				this.verifiedUntil = data.verifiedUntil;
				this.setState(States.Verified);
			}.bind(this)).catch(function (response)
			{
				var error = response.errors[0];

				BX.removeClass(this.elements.confirmButton, "ui-btn-wait");
				console.error(error);
				this.showError(error.message);
			}.bind(this));
		},

		onProlongButtonClick: function (e)
		{
			this.hideError();
			BX.addClass(this.elements.prolongButton, "ui-btn-wait");

			BX.ajax.runAction("voximplant.callerId.requestVerification", {
				data: {phoneNumber: this.phoneNumber}
			}).then(function (response)
			{
				BX.removeClass(this.elements.prolongButton, "ui-btn-wait");
				this.setState(States.Verification);
			}.bind(this)).catch(function (response)
			{
				var error = response.errors[0];

				BX.removeClass(this.elements.prolongButton, "ui-btn-wait");
				console.error(error);
				this.showError(error.message);
			}.bind(this));
		}
	};

})();