;(function()
{
	BX.namespace("BX.Voximplant");

	var gridId = "voximplant_lines";

	BX.Voximplant.Lines = {
		init: function(config)
		{
			this.isTelephonyAvailable = config.isTelephonyAvailable === 'Y';

			BX.bind(BX("add-group"), "click", this.addGroup.bind(this));
		},

		reloadGrid: function()
		{
			if(!BX.Main || !BX.Main.gridManager)
			{
				return;
			}

			var gridData = BX.Main.gridManager.getById(gridId);
			if(!gridData)
			{
				return;
			}

			gridData.instance.reload()
		},

		showConfig: function(configId)
		{
			BX.SidePanel.Instance.open("/telephony/edit.php?ID=" + configId, {
				cacheable: false,
				allowChangeHistory: false,
				events: {
					onClose: this.reloadGrid.bind(this)
				}
			});
		},

		addGroup: function()
		{
			var editor = new BX.Voximplant.NumberGroup({
				onClose: this.reloadGrid.bind(this)
			});
			editor.show();
		},

		deleteGroup: function(id)
		{
			BX.Voximplant.showLoader();

			BX.ajax.runComponentAction("bitrix:voximplant.lines", "deleteGroup", {
				data: {id: id}
			}).then(function(response)
			{
				BX.Voximplant.hideLoader();
				this.reloadGrid();
			}.bind(this)).catch(function(response)
			{
				BX.Voximplant.hideLoader();
				var error = response.errors[0];
				BX.Voximplant.alert(BX.message("VOX_LINES_ERROR"), error.message);
			});
		},

		editGroup: function(id)
		{
			var editor = new BX.Voximplant.NumberGroup({
				groupId: id
			});

			editor.show();
		},

		addNumberToGroup: function(number, groupId)
		{
			BX.Voximplant.showLoader();

			BX.ajax.runComponentAction("bitrix:voximplant.lines", "addToGroup", {
				data: {
					number: number,
					groupId: groupId
				}
			}).then(function(response)
			{
				BX.Voximplant.hideLoader();
				this.reloadGrid();
			}.bind(this)).catch(function(response)
			{
				BX.Voximplant.hideLoader();
				var error = response.errors[0];
				BX.Voximplant.alert(BX.message("VOX_LINES_ERROR"), error.message);
			});
		},

		removeNumberFromGroup: function(number)
		{
			BX.Voximplant.showLoader();

			BX.ajax.runComponentAction("bitrix:voximplant.lines", "removeFromGroup", {
				data: {
					number: number,
				}
			}).then(function(response)
			{
				BX.Voximplant.hideLoader();
				this.reloadGrid();
			}.bind(this)).catch(function(response)
			{
				BX.Voximplant.hideLoader();
				var error = response.errors[0];
				BX.Voximplant.alert(BX.message("VOX_LINES_ERROR"), error.message);
			});
		},

		getCallerIdFields: function(number)
		{
			return new Promise(function(resolve, reject)
			{
				BX.ajax.runAction("voximplant.callerid.get", {data: {phoneNumber: number}}).then(function(response)
				{
					resolve(response.data);
				}).catch(function(response)
				{
					reject(response.errors[0]);
				});
			})
		},

		verifyCallerId: function(number)
		{
			var configPromise = this.getCallerIdFields(number);

			var callerIdForm = new BX.Voximplant.CallerIdSlider({
				dataPromise: configPromise,
				onClose: this.reloadGrid.bind(this)
			});
			callerIdForm.show();
		},

		deleteCallerId: function(number)
		{
			var self = this;
			BX.Voximplant.confirm(
				BX.message("VOX_LINES_CONFIRM_ACTION"),
				BX.message("VOX_LINES_CALLERID_DELETE_CONFIRM").replace("#NUMBER#", number)
			).then(function(result)
			{
				if(!result)
				{
					return;
				}

				BX.Voximplant.showLoader();
				BX.ajax.runAction("voximplant.callerId.delete", {
					data: {
						phoneNumber: number
					}
				}).then(function()
				{
					BX.Voximplant.hideLoader();
					self.reloadGrid();
				}).catch(function(response)
				{
					BX.Voximplant.hideLoader();
					var error = response.errors[0];
					BX.Voximplant.alert(BX.message("VOX_LINES_ERROR"), error.message);
				})
			});
		},

		deleteSip: function(id)
		{
			var self = this;
			BX.Voximplant.confirm(BX.message("VOX_LINES_CONFIRM_ACTION"), BX.message("VOX_LINES_SIP_DELETE_CONFIRM")).then(function (result)
			{
				if(!result)
				{
					return;
				}

				BX.Voximplant.showLoader();
				BX.ajax.runAction("voximplant.sip.delete", {
					data: {
						id: id
					}
				}).then(function()
				{
					BX.Voximplant.hideLoader();
					self.reloadGrid();
				}).catch(function(response)
				{
					BX.Voximplant.hideLoader();
					var error = response.errors[0];
					BX.Voximplant.alert(BX.message("VOX_LINES_ERROR"), error.message);
				})
			});
		},

		deleteNumber: function(number)
		{
			BX.Voximplant.NumberRent.create().deleteNumber(number).then(function()
			{
				this.reloadGrid();
			}.bind(this));
		},

		cancelNumberDeletion: function(number)
		{
			BX.Voximplant.NumberRent.create().cancelNumberDeletion(number).then(function()
			{
				this.reloadGrid();
			}.bind(this));
		}
	};

	BX.Voximplant.NumberGroup = function(config)
	{
		this.slider = null;

		this.id = config.id || null;

		this.groupName = "";
		this.selectedNumbers = {};
		this.unassignedNumbers = [];

		this.elements = {
			groupName: null,
			error: null,
			numbersContainer: null,
			createButton: null,
			cancelButton: null,
		};

		this.callBacks = {
			onClose: BX.type.isFunction(config.onClose) ? config.onClose : BX.DoNothing
		}
	};

	BX.Voximplant.NumberGroup.prototype = {
		fetchUnassignedNumbers: function()
		{
			return new Promise(function(resolve,reject)
			{
				BX.ajax.runComponentAction("bitrix:voximplant.lines", "getUnassignedNumbers").then(function(response)
				{
					this.unassignedNumbers = response.data;
					resolve();
				}.bind(this)).catch(function(e)
				{
					console.error(e.errors[0]);
					reject();
				})
			}.bind(this))
		},

		show: function()
		{
			BX.SidePanel.Instance.open("voximplant:number-group-add", {
				width: 400,
				events: {
					onClose: this.onSliderClose.bind(this),
					onDestroy: this.onSliderDestroy.bind(this)
				},
				contentCallback: function (slider)
				{
					var promise = new BX.Promise();
					this.slider = slider;

					top.BX.loadExt("voximplant.common").then(function()
					{
						this.fetchUnassignedNumbers().then(function()
						{
							var layout = this.render();
							promise.resolve(layout);
						}.bind(this));
					}.bind(this), 0);

					return promise;
				}.bind(this)
			});
		},

		render: function()
		{
			return BX.createFragment([
				BX.create("div", {
					props: {className: "voximplant-slider-pagetitle-wrap"},
					children: [
						BX.create("div", {
							props: {className: "voximplant-slider-pagetitle"},
							children: [
								BX.create("span", {
									text: BX.message("VOX_LINES_ADD_NUMBER_GROUP")
								})
							]
						})
					]
				}),
				BX.create("div", {
					props: {className: "voximplant-container voximplant-options-popup"},
					children: [
						BX.create("h2", {
							props: {className: "voximplant-control-row"},
							children: [
								BX.create("div", {
									props: {className: "voximplant-control-title-editable"},
									children: [
										this.elements.groupName = BX.create("input", {
											props: {className: "voximplant-control-input"},
											attrs: {
												type: "text",
												value: this.groupName,
												placeholder: BX.message("VOX_LINES_NUMBER_GROUP_NAME")
											},
											events: {
												change: function(e)
												{
													this.groupName = e.currentTarget.value;
												}.bind(this)
											}
										}),
										BX.create('span', {
											props: {className: "voximplant-control-btn-edit"},
										})
									]
								})
							]
						}),
						this.elements.error = BX.create("div", {
							props: {className: "voximplant-control-row"}
						}),
						BX.create("div", {
							props: {className: "voximplant-control-row"},
							children: [
								BX.create("h5", {
									props: {className: "voximplant-control-title-grey-sm"},
									text: BX.message("VOX_LINES_SELECT_UNASSIGNED_NUMBERS")
								}),

								this.elements.numbersContainer = BX.create("div", {
									props: {className: "voximplant-number-group-numbers voximplant-group-create"},
									children: this.renderUnassignedNumbers()
								})
							]
						}),
						BX.create("div", {
							props: {className: "voximplant-control-row"},
							children: [
								this.elements.createButton = BX.create("button", {
									props: {className: "ui-btn ui-btn-primary" + (this.unassignedNumbers.length === 0 ? " ui-btn-disabled" : "")},
									text: BX.message("VOX_LINES_BUTTON_CREATE"),
									events: {
										click: this.onCreateButtonClick.bind(this)
									}
								}),
								this.elements.cancelButton = BX.create("button", {
									props: {className: "ui-btn ui-btn-primary"},
									text: BX.message("VOX_LINES_BUTTON_CANCEL"),
									events: {
										click: this.onCancelButtonClick.bind(this)
									}
								})
							]
						})
					]
				}),

			]);
		},

		renderUnassignedNumbers: function()
		{
			if (this.unassignedNumbers.length === 0)
			{
				return [BX.create("div", {
					props: {className: "ui-alert ui-alert-danger"},
					children: [
						BX.create("span", {
							props: {className: "ui-alert-message"},
							text: BX.message("VOX_LINES_NO_UNASSIGNED_NUMBERS")
						})
					]
				})]
			}
			return this.unassignedNumbers.map(function(numberFields)
			{
				return BX.create("span", {
					props: {className: "tel-set-list-item"},
					children: [
						BX.create("input", {
							props: {
								id: "phone" + numberFields["ID"],
								className: "tel-set-list-item-checkbox",
								type: "checkbox",
								value: numberFields["ID"]
							},
							events: {
								change: this.onNumberChanged.bind(this)
							}
						}),
						BX.create("label", {
							props: {
								className: "tel-set-list-item-num"
							},
							attrs: {
								"for": "phone" + numberFields["ID"]
							},
							text: numberFields["NAME"]
						})
					]
				})
			}, this);
		},

		showError: function(message)
		{
			BX.adjust(this.elements.error, {
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

		hideError: function()
		{
			BX.cleanNode(this.elements.error);
		},

		onSliderClose: function (e)
		{
			this.slider.destroy();
			this.callBacks.onClose();
		},

		onSliderDestroy: function (e)
		{
			this.slider = null;
		},

		onNumberChanged: function(e)
		{
			var number = e.currentTarget.value;
			var checked = e.currentTarget.checked;

			if(checked)
			{
				this.selectedNumbers[number] = true;
			}
			else
			{
				delete this.selectedNumbers[number];
			}
		},

		onCreateButtonClick: function(e)
		{
			this.hideError();
			if (this.unassignedNumbers.length === 0)
			{
				return;
			}
			BX.addClass(this.elements.createButton, "ui-btn-wait");

			BX.ajax.runComponentAction("bitrix:voximplant.lines", "createGroup", {
				data: {
					name: this.groupName,
					numbers: Object.keys(this.selectedNumbers)
				}
			}).then(function(response)
			{
				BX.removeClass(this.elements.createButton, "ui-btn-wait");
				this.slider.close();
			}.bind(this)).catch(function(response)
			{
				BX.removeClass(this.elements.createButton, "ui-btn-wait");

				var error = response.errors[0];
				this.showError(error.message);
			}.bind(this));
		},

		onCancelButtonClick: function(e)
		{
			this.slider.close();
		},
	}
})();