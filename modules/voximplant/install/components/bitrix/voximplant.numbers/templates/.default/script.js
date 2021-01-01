;(function(){

	"use strict";
	BX.namespace("BX.Voximplant");

	var defaults = {
		gridId: '',
		numbers: {},
		users: {},
		isPhoneAllowed: false,
		lastGridUrl: null,
	};

	BX.Voximplant.UserEditor = function(config)
	{
		this.userId = config.userId;
		this.popup = null;
		this.loader = null;
		this.lastGridUrl = null;

		this.passwordEdit = false;

		this.elements = {
			deviceSettings: null,
			deviceOverlay: null,
			server: null,
			login: null,
			passwordBox: null,
			passwordInput: null,
			passwordSpan: null,
		};

		Object.defineProperty(this, "defaults", {
			get: function()
			{
				return defaults;
			}
		});
	};

	BX.Voximplant.UserEditor.setDefaults = function(params)
	{
		defaults.numbers = BX.prop.getObject(params, "numbers", defaults.numbers);
		defaults.gridId = BX.prop.getString(params, "gridId", defaults.gridId);
		defaults.users = BX.prop.getObject(params, "users", defaults.users);
		defaults.isPhoneAllowed = BX.prop.getBoolean(params, "isPhoneAllowed", defaults.isPhoneAllowed);
		defaults.lastGridUrl = BX.prop.getString(params, "lastGridUrl", defaults.lastGridUrl)
	};

	BX.Voximplant.UserEditor.prototype = {
		show: function()
		{
			BX.ajax.runComponentAction('bitrix:voximplant.numbers', 'getUserOptions', {data: {userId: this.userId}}).then(function(response)
			{
				this.data = response.data;
				this.popup = this.createPopup();
				this.popup.show();
			}.bind(this));
		},

		createPopup: function()
		{
			return new BX.PopupWindow("voximplant-user-settings", null, {
				autoHide: true,
				closeByEsc: true,
				closeIcon: false,
				contentNoPaddings: true,
				//noAllPaddings: true,
				contentColor: "white",
				events: {
					onPopupClose: function ()
					{
						this.destroy()
					},
					onPopupDestroy: function ()
					{
						this.popup = null;
					}.bind(this)
				},
				content: this.render(),
				buttons: [
					new BX.PopupWindowCustomButton({
						id: "save",
						text: BX.message("VI_NUMBERS_SAVE"),
						className: "ui-btn ui-btn-md ui-btn-primary",
						events: {
							click: this.onSaveButtonClick.bind(this)
						}
					})
				]
			});
		},

		render: function()
		{
			return BX.create("div", {
				props: {className: "voximplant-options-popup"},
				events: {
					click: this.onPopupBodyClick.bind(this)
				},
				children: [
					BX.create("div", {
						props: {className: "voximplant-control-row"},
						children: [
							BX.create("div", {
								props: {className: "voximplant-control-subtitle"},
								text: BX.message("VI_NUMBERS_GRID_CODE")
							}),
							BX.create("input", {
								props: {className: "voximplant-control-input"},
								attrs: {
									type: "text",
									value: BX.type.isNotEmptyString(this.data.extension) ? BX.util.htmlspecialchars(this.data.extension) : ""
								},
								events: {
									bxchange: function(e)
									{
										this.data.extension = e.currentTarget.value
									}.bind(this)
								}
							})
						]
					}),
					BX.create("div", {
						props: {className: "voximplant-control-row"},
						children: [
							BX.create("div", {
								props: {className: "voximplant-control-subtitle"},
								text: BX.message("VI_NUMBERS_GRID_PHONE")
							}),
							BX.create("select", {
								props: {className: "voximplant-control-select"},
								children: this.renderLineOptions(this.data.userLine),

								events: {
									bxchange: function(e)
									{
										this.data.userLine = e.currentTarget.value
									}.bind(this)
								}
							})
						]
					}),
					BX.create("div", {
						props: {className: "voximplant-control-row"},
						children: [
							BX.create("div", {
								props: {className: "voximplant-control-subtitle"},
								text: BX.message("VI_NUMBERS_GRID_PHONE_DEVICE")
							}),
							BX.create("select", {
								props: {className: "voximplant-control-select"},
								children: [
									BX.create("option", {
										attrs: {
											value: 'N',
											selected: this.data.phoneEnabled === 'N'
										},
										text: BX.message("VI_NUMBERS_PHONE_DEVICE_DISABLE")
									}),
									BX.create("option", {
										attrs: {
											value: 'Y',
											selected: this.data.phoneEnabled === 'Y'
										},
										text: BX.message("VI_NUMBERS_PHONE_DEVICE_ENABLE")
									}),
								],
								events: {
									bxchange: this.onDeviceEnabledChange.bind(this)
								}
							})
						]
					}),
					BX.create("div", {
						props: {className: "voximplant-control-row"},
						children: [
							this.elements.deviceSettings = BX.create("div", {
								props: {className: "voximplant-control-settings"},
								style: {
									maxHeight: this.data.phoneEnabled === 'Y' ? "200px" : 0,
								},
								children: [
									BX.create("div", {
										props: {className: "voximplant-control-subtitle"},
										text: BX.message("VI_NUMBERS_PHONE_CONNECT_INFO") + ":"
									}),
									BX.create("div", {
										props: {className: "voximplant-control-settings-row"},
										children: [
											BX.create("div", {
												props: {className: "voximplant-control-settings-name"},
												text: BX.message("VI_NUMBERS_PHONE_CONNECT_SERVER") + ":"
											}),
											this.elements.server = BX.create("div", {
												props: {className: "voximplant-control-settings-value-box"},
												text: this.data.phoneServer
											})
										]
									}),
									BX.create("div", {
										props: {className: "voximplant-control-settings-row"},
										children: [
											BX.create("div", {
												props: {className: "voximplant-control-settings-name"},
												text: BX.message("VI_NUMBERS_PHONE_CONNECT_LOGIN") + ":"
											}),
											this.elements.login = BX.create("div", {
												props: {className: "voximplant-control-settings-value-box"},
												text: this.data.phoneLogin
											})
										]
									}),
									BX.create("div", {
										props: {className: "voximplant-control-settings-row"},
										children: [
											BX.create("div", {
												props: {className: "voximplant-control-settings-name"},
												text: BX.message("VI_NUMBERS_PHONE_CONNECT_PASSWORD") + ":"
											}),
											this.elements.passwordBox = BX.create("div", {
												props: {className: "voximplant-control-settings-value-box"},
												children: [
													this.elements.passwordInput = BX.create("input", {
														attrs: {type: "text"},
														props: {className: "voximplant-control-settings-input"},
														events: {
															bxchange: function(e)
															{
																this.data.phonePassword = e.currentTarget.value;
																this.elements.passwordSpan.innerText = this.data.phonePassword;
															}.bind(this)
														}
													}),
													this.elements.passwordSpan = BX.create("span", {
														props: {className: "voximplant-control-settings-value"},
														text: this.data.phonePassword
													}),
													BX.create("span", {
														props: {className: "voximplant-control-settings-btn"},
														events: {
															click: this.onEditPasswordClick.bind(this)
														}
													})
												]
											})
										]
									}),
									this.elements.deviceOverlay = BX.create("div", {
										props: {className: "voximplant-control-settings-overlay"}
									})
								]
							})
						]
					})
				]
			})
		},

		renderLineOptions: function(currentValue)
		{
			var result = [];

			for (var lineId in defaults.numbers)
			{
				if(!defaults.numbers.hasOwnProperty(lineId))
				{
					continue;
				}

				var node = BX.create("option", {
					attrs: {
						value: lineId
					},
					html: defaults.numbers[lineId]
				});

				if(lineId === currentValue)
				{
					node.selected = true;
				}
				result.push(node);
			}
			return result;
		},

		showLoader: function(e)
		{
			this.elements.deviceOverlay.classList.add("active");
			if(!this.loader)
			{
				this.loader = new BX.Loader({target: this.elements.deviceOverlay});
			}
			this.loader.show();
		},

		hideLoader: function(e)
		{
			this.elements.deviceOverlay.classList.remove("active");
			this.loader.hide();
		},

		onDeviceEnabledChange: function(e)
		{
			this.data.phoneEnabled = e.currentTarget.value;

			if(this.data.phoneEnabled === 'Y')
			{
				this.elements.deviceSettings.style.maxHeight = "200px";
				if(true || !this.data.phonePassword)
				{
					this.showLoader();
					BX.ajax.runComponentAction("bitrix:voximplant.numbers", "getPhoneAuth", {
						data: {
							userId: this.userId
						}
					}).then(function(response)
					{
						this.hideLoader();
						this.data.phonePassword = response.data.phonePassword;
						this.elements.passwordSpan.innerText = this.data.phonePassword ? this.data.phonePassword : "";
						this.elements.passwordInput.value = this.data.phonePassword;

					}.bind(this));
				}
			}
			else
			{
				this.elements.deviceSettings.style.maxHeight = "0";
			}
		},

		onEditPasswordClick: function(e)
		{
			if(this.passwordEdit)
			{
				this.passwordEdit = false;
				this.elements.passwordBox.classList.remove("voximplant-control-settings-active");
			}
			else
			{
				this.passwordEdit = true;
				this.elements.passwordInput.value = this.data.phonePassword;
				this.elements.passwordBox.classList.add("voximplant-control-settings-active");
				this.elements.passwordInput.focus();
			}
			e.stopPropagation()
		},

		onPopupBodyClick: function(e)
		{
			if (this.passwordEdit && e.target !== this.elements.passwordInput)
			{
				this.passwordEdit = false;
				this.elements.passwordBox.classList.remove("voximplant-control-settings-active");
			}
		},

		onSaveButtonClick: function()
		{
			var saveButton = this.popup.getButton('save');
			saveButton.buttonNode.classList.add('ui-btn-wait');
			BX.ajax.runComponentAction("bitrix:voximplant.numbers", "saveUserOptions", {
				data: {
					userId: this.userId,
					options: this.data
				}
			}).then(function()
			{
				var grid = BX.Main.gridManager.getInstanceById(defaults.gridId);
				if(grid)
				{
					grid.reload(defaults.lastGridUrl);
				}

				saveButton.buttonNode.classList.remove('ui-btn-wait');
				this.popup.close();
			}.bind(this)).catch(function(response)
			{
				var error = response.errors[0];

				BX.Voximplant.alert(BX.message("VI_NUMBERS_ERROR"), BX.util.htmlspecialchars(error.message));
				saveButton.buttonNode.classList.remove('ui-btn-wait');
			});
		}
	};
})();
