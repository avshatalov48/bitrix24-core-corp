;(function ()
{
	var GRID_ID = "voximplant_blacklist";

	BX.namespace("BX.Voximplant");

	BX.Voximplant.Blacklist = {
		settingsPopup: null,
		numbersInput: null,

		elements: {
			numbersContainer: null,
			numberInput: null,
			addButton: null,
			settingsForm: null,
			saveSettingsButton: null
		},
		init: function (config)
		{
			this.settingsPopup = null;
		},

		showSettings: function()
		{
			if(this.settingsPopup)
			{
				return;
			}

			BX.ajax.runComponentAction('bitrix:voximplant.blacklist', 'getSettings', {mode: "class"}).then(function(response)
			{
				this.settingsPopup = new SettingsPopup({
					data: response.data,
					onDestroy: function()
					{
						this.settingsPopup = null;
					}.bind(this),
					onSave: this.saveSettings.bind(this)
				});
				this.settingsPopup.show();
			}.bind(this));
		},

		saveSettings: function (e)
		{
			this.settingsPopup.showWait();

			BX.ajax.runComponentAction('bitrix:voximplant.blacklist', 'setSettings', {
				mode: "class",
				data: {
					settings: e.data
				}
			}).then(function()
			{
				this.settingsPopup.destroy();
			}.bind(this))
		},

		showNumberInput: function()
		{
			if(this.numbersInput)
			{
				return;
			}

			this.numbersInput = new NumbersSlider({
				onDestroy: this.onNumbersSliderDestroy.bind(this)
			});
			this.numbersInput.show();
		},

		onNumbersSliderDestroy: function()
		{
			this.numbersInput = null;
			var grid = BX.Main.gridManager.getInstanceById(GRID_ID);
			if(grid)
			{
				grid.reload();
			}
		},

		deleteNumber: function (numberId)
		{
			BX.Voximplant.confirm(" ", BX.message("BLACKLIST_DELETE_CONFIRM")).then(function(result)
			{
				if (!result)
				{
					return;
				}
				BX.ajax.runComponentAction('bitrix:voximplant.blacklist', 'deleteNumber', {
					mode: "class",
					data: {
						numberId: numberId
					}
				}).then(
					function()
					{
						var grid = BX.Main.gridManager.getInstanceById(GRID_ID);
						if(grid)
						{
							grid.reload();
						}
					}.bind(this),
					function()
					{
						BX.Voximplant.alert(BX.message('BLACKLIST_ERROR_TITLE'), BX.message('BLACKLIST_DELETE_ERROR'));
					}
				)
			});
		}
	};

	var SettingsPopup = function (config)
	{
		this.data = config.data;
		this.popup = null;

		this.callbacks = {
			onDestroy: BX.type.isFunction(config.onDestroy) ? config.onDestroy : BX.DoNothing,
			onSave: BX.type.isFunction(config.onSave) ? config.onSave : BX.DoNothing,
		}
	};

	SettingsPopup.prototype = {
		show: function ()
		{
			this.popup = new BX.PopupWindow("voximplant-blacklist-settings", null, {
				autoHide: true,
				closeByEsc: true,
				closeIcon: true,
				contentNoPaddings: true,
				contentColor: "white",
				events: {
					onPopupClose: function ()
					{
						this.destroy()
					}.bind(this),
					onPopupDestroy: function ()
					{
						this.popup = null;
					}.bind(this)
				},
				titleBar: BX.message("BLACKLIST_SETTINGS_TITLE"),
				content: this.render(),
				buttons: [
					new BX.PopupWindowCustomButton({
						text: BX.message("BLACKLIST_SAVE"),
						className: "ui-btn ui-btn-md ui-btn-primary",
						events: {
							click: this.onSaveButtonClick.bind(this)
						}
					})
				]
			});
			this.popup.show();
		},

		renderRingCountOption: function(count)
		{
			return BX.create("option", {
				props: {value: count},
				attrs: {selected: this.data.ringsCount == count},
				text: count
			});
		},

		renderIntervalOption: function(interval)
		{
			return BX.create("option", {
				props: {value: interval},
				attrs: {selected: this.data.interval == interval},
				text: interval
			});
		},

		render: function ()
		{
			return BX.create("div", {
				props: {className: "voximplant-blacklist-popup"},
				children: [
					BX.create("div", {
						props: {className: "voximplant-blacklist-input-box"},
						children: [
							BX.create("input", {
								props: {
									id: "voximplant-blacklist-settings-register-crm",
									className: "voximplant-blacklist-checkbox",
									type: "checkbox",
									checked: this.data.registerInCRM == "Y"
								},
								events: {
									bxchange: function(e)
									{
										this.data.registerInCRM = e.currentTarget.checked ? "Y" : "N";
									}.bind(this)
								}
							}),
							BX.create("label", {
								attrs: {for: "voximplant-blacklist-settings-register-crm"},
								props: {className: "voximplant-blacklist-label"},
								text: BX.message("BLACKLIST_REGISTER_IN_CRM_2")
							})
						]
					}),
					BX.create("div", {
						props: {className: "voximplant-blacklist-input-box"},
						children: [
							BX.create("input", {
								props: {
									id: "voximplant-blacklist-settings-auto-block",
									className: "voximplant-blacklist-checkbox",
									type: "checkbox",
									checked: this.data.autoBlock == "Y"
								},
								events: {
									bxchange: function(e)
									{
										this.data.autoBlock = e.currentTarget.checked ? "Y" : "N";
									}.bind(this)
								}
							}),
							BX.create("label", {
								attrs: {for: "voximplant-blacklist-settings-auto-block"},
								props: {className: "voximplant-blacklist-label"},
								text: BX.message("BLACKLIST_ENABLE")
							})
						]
					}),
					BX.create("div", {
						props: {className: "voximplant-blacklist-popup-row"},
						children: [
							BX.create("div", {
								props: {className: "voximplant-control-row"},
								children: [
									BX.create("div", {
										props: {className: "voximplant-control-subtitle"},
										text: BX.message("VOX_BLACKLIST_RINGS_COUNT")
									}),
									BX.create("select", {
										props: {className: "voximplant-control-select"},
										events: {
											bxchange: function(e)
											{
												this.data.ringsCount = parseInt(e.currentTarget.value);
											}.bind(this)
										},
										children: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map(this.renderRingCountOption.bind(this))
									})
								]
							}),
							BX.create("div", {
								props: {className: "voximplant-control-row"},
								children: [
									BX.create("div", {
										props: {className: "voximplant-control-subtitle"},
										text: BX.message("VOX_BLACKLIST_INTERVAL_IN_MINUTES")
									}),
									BX.create("select", {
										props: {className: "voximplant-control-select"},
										events: {
											bxchange: function(e)
											{
												this.data.interval = parseInt(e.currentTarget.value);
											}.bind(this)
										},
										children: [1, 5, 10, 15, 30, 60].map(this.renderIntervalOption.bind(this))
									})
								]
							}),
						]
					})
				]
			})
		},

		showWait: function()
		{
			var button = this.popup.buttons[0];

			BX.addClass(button.buttonNode, "ui-btn-wait");
		},

		onSaveButtonClick: function(e)
		{
			this.callbacks.onSave({
				data: this.data
			});
		},

		destroy: function()
		{
			if(this.popup)
			{
				this.popup.destroy();
			}

			this.popup = null;

			this.callbacks.onDestroy();
		}
	};

	var NumbersSlider = function(config)
	{
		this.slider = null;
		this.elements = {
			textarea: null,
			saveButton: null,
			cancelButton: null
		};

		this.callbacks = {
			onDestroy: BX.type.isFunction(config.onDestroy) ? config.onDestroy : BX.DoNothing
		}
	};

	NumbersSlider.prototype = {
		show: function()
		{
			BX.SidePanel.Instance.open("voximplant:blacklist-add", {
				events: {
					onLoad: this.onSliderLoad.bind(this),
					onClose: this.onSliderClose.bind(this),
					onSliderCloseComplete: this.onSliderCloseComplete.bind(this),
					onDestroy: this.onSliderDestroy.bind(this)
				},
				contentCallback: (slider) => {
					this.slider = slider;
					return new Promise((resolve) => {
						top.BX.loadExt('voximplant.common').then(() => {
							resolve(this.render());
						})
					})
				}
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
									text: BX.message("VOX_BLACKLIST_NUMBERS_TITLE")
								})
							]
						})
					]
				}),
				BX.create("div", {
					props: {className: "voximplant-container"},
					children: [
						BX.create("div", {
							props: {className: "voximplant-blacklist-box"},
							children: [
								BX.create("div", {
									props: {className: "voximplant-blacklist-subtitle"},
									text: BX.message("VOX_BLACKLIST_NUMBERS_SUBTITLE")
								}),
								this.elements.textarea = BX.create("textarea", {
									props: {className: "voximplant-blacklist-textarea"},
									attrs: {
										cols: 30,
										rows: 30,
										placeholder: BX.message("VOX_BLACKLIST_VALUE")
									}
								})
							]
						}),
						BX.create("div", {
							props: {className: "ui-alert ui-alert-warning"},
							text: BX.message("VOX_BLACKLIST_NUMBERS_HINT")
						}),
					]
				}),
				BX.create("div", {
					props: {className: "voximplant-button-panel"},
					children: [
						this.elements.saveButton = BX.create("button", {
							props: {className: "ui-btn ui-btn-success"},
							text: BX.message("BLACKLIST_SAVE"),
							events: {
								click: this.onSaveButtonClick.bind(this)
							}
						}),
						this.elements.cancelButton = BX.create("button", {
							props: {className: "ui-btn ui-btn-link"},
							text: BX.message("BLACKLIST_CANCEL"),
							events: {
								click: this.onCancelButtonClick.bind(this)
							}
						}),
					]
				})
			]);
		},

		onSliderLoad: function()
		{
			this.elements.textarea.focus();
		},

		onSliderClose: function(e)
		{
			if(this.elements.textarea.value != "")
			{
				e.denyAction();
			}
			else
			{
				this.slider.destroy();
			}
		},

		onSliderCloseComplete: function(e)
		{
			this.slider.destroy();
		},

		onSliderDestroy: function(e)
		{
			this.slider = null;
			this.callbacks.onDestroy();
		},

		onSaveButtonClick: function(e)
		{
			var numbers = this.elements.textarea.value.split("\n");

			BX.ajax.runComponentAction('bitrix:voximplant.blacklist', 'addNumbers', {
				mode: "class",
				data: {
					numbers: numbers
				}
			}).then(function(response)
			{
				this.elements.textarea.value = "";
				this.slider.close();
			}.bind(this));
		},

		onCancelButtonClick: function(e)
		{
			this.elements.textarea.value = "";
			this.slider.close();
		},

	};
})();