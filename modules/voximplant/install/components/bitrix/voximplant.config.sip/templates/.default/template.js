;(function()
{
	BX.namespace("BX.Voximplant");

	if(BX.Voximplant.Sip)
	{
		return;
	}

	BX.Voximplant.Sip =
	{
		type: '', // office|cloud
		publicFolder: '',
		sipConnections: [],
		connectionsPlaceholder: null,
		addConnectionButton: null,
		linkToBuy: '',

		init: function(params)
		{
			this.type = params.type;
			this.publicFolder = params.publicFolder;
			this.sipConnections = params.sipConnections;
			this.linkToBuy = params.linkToBuy;
			this.connectionsPlaceholder = BX('phone-config-sip-wrap');
			this.addConnectionButton = BX('add-connection');

			this.connectionsPlaceholder.appendChild(this.renderSipConnections());
			BX.bind(this.addConnectionButton, 'click', this.onAddConnectionButtonClick.bind(this));
		},

		renderSipConnections: function()
		{
			var connectionNodes = this.sipConnections.map(function(connection)
			{
				return BX.create("div", {
					props: {className: "voximplant-connected-ats-item"},
					children: [
						BX.create("div", {
							props: {className: "voximplant-connected-ats-name"},
							text: connection['PHONE_NAME'],
						}),
						BX.create("div", {
							props: {className: "voximplant-connected-ats-control-box"},
							children: [
								BX.create("div", {
									props: {className: "ui-btn ui-btn-light-border"},
									text: BX.message("VI_CONFIG_SIP_CONFIGURE_2"),
									dataset: {
										configId: connection['ID'],
									},
									events: {
										click: this.onShowConnectionConfigClick.bind(this)
									}
								}),
								BX.create("div", {
									props: {className: "ui-btn ui-btn-link"},
									text: BX.message("VI_CONFIG_SIP_DELETE_3"),
									dataset: {
										configId: connection['ID'],
									},
									events: {
										click: this.onDeleteSipConnectionClick.bind(this)
									}
								})
							]
						})
					]
				})
			}, this);

			return BX.createFragment([
				BX.create("div", {
					props: {className: "voximplant-title-dark"},
					text: BX.message("VI_CONFIG_SIP_PHONES"),
				}),
				BX.create("div", {
					props: {className: "voximplant-connected-ats"},
					children: connectionNodes
				})
			]);
		},

		openConnectionConfig: function(configId)
		{
			configId = parseInt(configId);
			BX.SidePanel.Instance.open(this.publicFolder + "edit.php?ID=" + configId, {
				cacheable: false,
				events: {
					onClose: this.onEditorSliderClose.bind(this)
				}
			})
		},

		onEditorSliderClose: function()
		{
			this.updateConnections();
		},

		updateConnections: function()
		{
			BX.ajax.runComponentAction("bitrix:voximplant.config.sip", "getSipConnections", {
				data: {
					type: this.type
				}
			}).then(function(response)
			{
				this.sipConnections = response.data;

				BX.cleanNode(this.connectionsPlaceholder);
				this.connectionsPlaceholder.appendChild(this.renderSipConnections());
			}.bind(this)).catch(function(response)
			{
				var error = response.errors[0];

				console.error(error);
			});
		},

		onShowConnectionConfigClick: function(e)
		{
			var configId = e.currentTarget.dataset['configId'];

			this.openConnectionConfig(configId);
		},

		onDeleteSipConnectionClick: function(e)
		{
			var configId = e.currentTarget.dataset['configId'];

			BX.Voximplant.confirm(
				BX.message("VI_CONFIG_SIP_CONFIRM_ACTION"),
				BX.message("VI_CONFIG_SIP_DELETE_CONFIRM_2")
			).then(function(result)
			{
				if(!result)
				{
					return;
				}

				return BX.Voximplant.showLoader();
			}).then(function()
			{
				return BX.ajax.runComponentAction("bitrix:voximplant.config.sip", "deleteSipConnection", {
					data: {
						configId: configId
					}
				});
			}).then(function(response)
			{
				this.updateConnections();
				BX.Voximplant.hideLoader();
			}.bind(this)).catch(function (response)
			{
				BX.Voximplant.hideLoader();
				var error = response.errors[0];

				if(error)
				{
					BX.Voximplant.alert(
						BX.message("VI_CONFIG_SIP_ERROR"),
						error.message
					)
				}
			});
		},

		onAddConnectionButtonClick: function(e)
		{
			this.editor = new BX.Voximplant.PbxEditor({
				mode: this.type,
				onSuccess: this.onSipConnectionSaved.bind(this),
				onClose: function()
				{
					this.editor = null
				}.bind(this)
			});

			this.editor.show();

			if(this.type === 'cloud')
			{
				BX.ajax.runComponentAction("bitrix:voximplant.config.sip", "showSipCloudForm", {
					analyticsLabel: "showSipCloudForm"
				});
			}
			else
			{
				BX.ajax.runComponentAction("bitrix:voximplant.config.sip", "showSipOfficeForm", {
					analyticsLabel: "showSipOfficeForm"
				});
			}
		},

		onSipConnectionSaved: function(data)
		{
			var configId = data.configId;

			this.editor.close();
			this.updateConnections();
			this.openConnectionConfig(configId);
		},

		connectModule: function(url)
		{
			//statistics

			BX.ajax.runComponentAction("bitrix:voximplant.config.sip", "buySipConnector", {
				analyticsLabel: "buySipConnector"
			});

			BX.Voximplant.confirm(
				BX.message("VI_CONFIG_SIP_CONFIRM_ACTION"),
				BX.message('VI_CONFIG_SIP_CONNECT_NOTICE_2')
			).then(function(result)
			{
				if(result)
				{
					window.open(url, '_top');
				}
			});
		}
	};

	BX.Voximplant.PbxEditor = function(options)
	{
		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}
		this.slider = null;

		this.mode = options.mode === 'office' ? 'office' : 'cloud';
		this.title = '';
		this.server = '';
		this.login = '';
		this.password = '';
		this.authName = '';
		this.outboundProxy = '';

		this.elements = {
			error: null,
			title: null,
			server: null,
			login: null,
			password: null,
			authName: null,
			outboundProxy: null,
			saveButton: null,
			cancelButton: null
		};

		this.callbacks = {
			onSuccess: BX.type.isFunction(options.onSuccess) ? options.onSuccess : BX.DoNothing,
			onClose: BX.type.isFunction(options.onClose) ? options.onClose : BX.DoNothing
		};
	};

	BX.Voximplant.PbxEditor.prototype =
	{
		show: function()
		{
			BX.SidePanel.Instance.open('vi-add-sip-connection', {
				allowChangeHistory: false,
				width: 600,
				events: {
					onClose: this.onSliderClose.bind(this),
					onDestroy: this.onSliderDestroy.bind(this)
				},
				contentCallback: function(slider) {
					var promise = new BX.Promise();
					this.slider = slider;

					top.BX.loadExt("voximplant.common").then(function()
					{
						promise.resolve(this.render());
					}.bind(this));
					
					return promise;
				}.bind(this)
			});
		},

		close: function()
		{
			if(this.slider)
			{
				this.slider.close();
			}
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
									text: this.mode === "cloud" ? BX.message("VI_CONFIG_SIP_CONNECT_CLOUD") : BX.message("VI_CONFIG_SIP_CONNECT_OFFICE")
								})
							]
						})
					]
				}),
				BX.create("div", {
					props: {className: "voximplant-container voximplant-options-popup"},
					children: [
						this.elements.error = BX.create("div", {
							props: {className: "voximplant-control-row"}
						}),
						BX.create("div", {
							props: {className: "voximplant-control-row"},
							children: [
								BX.create("div", {
									props: {className: "voximplant-control-subtitle"},
									text: BX.message("VI_CONFIG_SIP_OUT_NC")
								}),
								this.elements.title = BX.create("input", {
									props: {className: "voximplant-control-input"},
									attrs: {
										type: "text",
										placeholder: BX.message("VI_CONFIG_SIP_VALUE"),
									},
									events: {
										change: function(e)
										{
											this.title = this.elements.title.value;
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
									text: BX.message("VI_CONFIG_SIP_OUT_SERVER")
								}),
								this.elements.server = BX.create("input", {
									props: {className: "voximplant-control-input"},
									attrs: {
										type: "text",
										placeholder: BX.message("VI_CONFIG_SIP_VALUE"),
									},
									events: {
										change: function(e)
										{
											this.server = this.elements.server.value;
										}.bind(this)
									}
								}),
								BX.create("div", {
									props: {className: "voximplant-control-description"},
									text: this.mode === "cloud" ? BX.message("VI_CONFIG_SIP_OUT_SERVER_DESC_2") : BX.message("VI_CONFIG_SIP_OUT_SERVER_DESC")
								})
							]
						}),
						BX.create("div", {
							props: {className: "voximplant-control-row"},
							children: [
								BX.create("div", {
									props: {className: "voximplant-control-subtitle"},
									text: BX.message("VI_CONFIG_SIP_OUT_LOGIN")
								}),
								this.elements.login = BX.create("input", {
									props: {className: "voximplant-control-input"},
									attrs: {
										type: "text",
										placeholder: BX.message("VI_CONFIG_SIP_VALUE"),
									},
									events: {
										change: function(e)
										{
											this.login = this.elements.login.value;
										}.bind(this)
									}
								}),
								BX.create("div", {
									props: {className: "voximplant-control-description"},
									text: this.mode === "cloud" ? BX.message("VI_CONFIG_SIP_OUT_LOGIN_DESC_2") : BX.message("VI_CONFIG_SIP_OUT_LOGIN_DESC")
								})
							]
						}),
						BX.create("div", {
							props: {className: "voximplant-control-row"},
							children: [
								BX.create("div", {
									props: {className: "voximplant-control-subtitle"},
									text: BX.message("VI_CONFIG_SIP_OUT_PASSWORD")
								}),
								this.elements.password = BX.create("input", {
									props: {className: "voximplant-control-input"},
									attrs: {
										type: "text",
										placeholder: BX.message("VI_CONFIG_SIP_VALUE"),
									},
									events: {
										change: function(e)
										{
											this.password = this.elements.password.value;
										}.bind(this)
									}
								})
							]
						}),
						this.mode === 'cloud' ? BX.create("div", {
							props: {className: "voximplant-control-row"},
							children: [
								BX.create("div", {
									props: {className: "voximplant-control-subtitle"},
									text: BX.message("VI_CONFIG_SIP_OUT_AUTH_USER")
								}),
								this.elements.authName = BX.create("input", {
									props: {className: "voximplant-control-input"},
									attrs: {
										type: "text",
										placeholder: BX.message("VI_CONFIG_SIP_VALUE"),
									},
									events: {
										change: function(e)
										{
											this.authName = this.elements.authName.value;
										}.bind(this)
									}
								}),
								BX.create("div", {
									props: {className: "voximplant-control-description"},
									text: BX.message("VI_CONFIG_SIP_OUT_AUTH_USER_DESC")
								})
							]
						}) : null,
						this.mode === 'cloud' ? BX.create("div", {
							props: {className: "voximplant-control-row"},
							children: [
								BX.create("div", {
									props: {className: "voximplant-control-subtitle"},
									text: BX.message("VI_CONFIG_SIP_OUT_OUTBOUND_PROXY")
								}),
								this.elements.outboundProxy = BX.create("input", {
									props: {className: "voximplant-control-input"},
									attrs: {
										type: "text",
										placeholder: BX.message("VI_CONFIG_SIP_VALUE"),
									},
									events: {
										change: function(e)
										{
											this.outboundProxy = this.elements.outboundProxy.value;
										}.bind(this)
									}
								}),
								BX.create("div", {
									props: {className: "voximplant-control-description"},
									text: BX.message("VI_CONFIG_SIP_OUT_OUTBOUND_PROXY_DESC")
								})
							]
						}) : null,
					]
				}),

				this.elements.buttons = BX.create("div", {
					props: {className: "voximplant-button-panel"},
					children: [
						this.elements.saveButton = BX.create("button", {
							props: {className: "ui-btn ui-btn-success"},
							text: BX.message("VI_CONFIG_SIP_OUT_SAVE"),
							events: {
								click: this.onSaveButtonClick.bind(this)
							}
						}),
						this.elements.cancelButton = BX.create("button", {
							props: {className: "ui-btn ui-btn-link"},
							text: BX.message("VI_CONFIG_SIP_CANCEL_CREATE"),
							events: {
								click: this.onCancelButtonClick.bind(this)
							}
						}),
					]
				})
			]);
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
								html: message
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

		onSaveButtonClick: function()
		{
			BX.addClass(this.elements.saveButton, "ui-btn-wait");
			this.hideError();

			var request = {
				type: this.mode,
				title: this.title,
				server: this.server,
				login: this.login,
				password: this.password,
			};
			if(this.mode === "cloud")
			{
				request.authUser = this.authName;
				request.outboundProxy = this.outboundProxy;
			}

			BX.ajax.runComponentAction("bitrix:voximplant.config.sip", "createSipConnection", {
				data: request
			}).then(function(response)
			{
				BX.removeClass(this.elements.saveButton, "ui-btn-wait");
				this.callbacks.onSuccess({
					configId: response.data.configId
				});

			}.bind(this)).catch(function(response)
			{
				BX.removeClass(this.elements.saveButton, "ui-btn-wait");

				var error = response.errors[0];
				this.showError(error.message);
			}.bind(this));
		},

		onCancelButtonClick: function()
		{
			this.slider.close();
		},

		onSliderClose: function()
		{
			this.slider.destroy();
			this.callbacks.onClose();
		},

		onSliderDestroy: function()
		{
			this.slider = null;
		}
	}
})();




