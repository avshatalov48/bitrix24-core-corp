;(function () {
	'use strict';

	BX.namespace('BX.SaleCenterSmsProvider');

	BX.SaleCenterSmsProvider = {
		smsProvider: null,
		smsProviderApp: null,
		mode: "main",
		signedParameters: null,

		init: function(config)
		{
			this.mode = config.mode;
			this.smsProviderParams = config.smsProviderParams;
			this.smsProviderAppParams = config.smsProviderAppParams;
			this.signedParameters = config.signedParameters;
			this.smsProvider = new BX.TileGrid.Grid(this.smsProviderParams);
			this.smsProvider.draw();

			if (this.mode === "main")
			{
				this.smsProviderApp = new BX.TileGrid.Grid(this.smsProviderAppParams);
				this.smsProviderApp.draw();
			}
		},

		reloadSlider: function(responseData)
		{
			if(responseData.smsProviderPanelParams)
			{
				this.smsProvider.redraw(responseData.smsProviderPanelParams);
			}

			if(responseData.smsProviderAppPanelParams && (this.mode === "main"))
			{
				this.smsProviderApp.redraw(responseData.smsProviderAppPanelParams);
			}
		}
	};

	/**
	 *
	 * @param options
	 * @extends {BX.TileGrid.Item}
	 * @constructor
	 */
	BX.SaleCenterSmsProvider.TileGrid = function(options)
	{
		BX.TileGrid.Item.apply(this, arguments);

		this.title = options.title;
		this.titleColor = options.titleColor;
		this.image = options.image;
		this.itemSelected = options.itemSelected;
		this.itemSelectedColor = options.itemSelectedColor;
		this.itemSelectedImage = options.itemSelectedImage;
		this.outerImage = options.outerImage || false;
		this.layout = {
			container: null,
			image: null,
			title: null,
		};
		this.data = options.data || {};
	};

	BX.SaleCenterSmsProvider.TileGrid.prototype =
	{
		__proto__: BX.TileGrid.Item.prototype,
		constructor: BX.TileGrid.Item,

		getContent: function()
		{
			if(this.data.type === "counter")
			{
				return this.getItemCounter();
			}

			if(!this.layout.wrapper)
			{
				this.layout.wrapper = BX.create('div', {
					props: {
						className: 'salescenter-smsprovider-item'
					},
					children: [
						BX.create('div', {
							props: {
								className: 'salescenter-smsprovider-item-content'
							},
							children: [
								this.getImage(),
								this.getTitle(),
								this.getStatus(),
								this.getLabel(),
							],
						})
					],
					events: {
						click: function()
						{
							this.onClick();
						}.bind(this)
					}
				});
			}

			if (this.itemSelected || this.data.type === 'actionbox')
			{
				this.setSelected();
			}

			return this.layout.wrapper;
		},

		getImage: function()
		{
			if(!this.layout.image)
			{
				var logo = BX.create('div', {
					props: {
						className: 'salescenter-smsprovider-item-image'
					},
					style: {
						backgroundSize: this.outerImage ? '50px' : '',
						backgroundImage: this.image ? 'url(\'' + this.image + '\')' : null
					}
				});

				if (this.data.type === 'marketplaceApp' && this.data.hasOwnIcon)
				{
					logo.style.backgroundSize = '35% auto';
				}
				this.layout.image = logo;
			}

			return this.layout.image;
		},

		getStatus: function()
		{
			if(!this.itemSelected)
				return;

			this.layout.itemSelected = BX.create('div', {
				props: {
					className: 'salescenter-smsprovider-item-status-selected'
				}
			});

			return this.layout.itemSelected;
		},

		getLabel: function()
		{
			if (!this.data.recommendation)
				return;

			this.layout.itemLabel = BX.create('div', {
				props: {
					className: 'salescenter-item-label'
				},
				children: [
					BX.create('div', {
						props: {
							className: 'salescenter-item-label-text'
						},
						text: BX.message('SALESCENTER_CONTROL_PANEL_ITEM_LABEL_RECOMMENDATION')
					})
				]
			});

			return this.layout.itemLabel;
		},

		setSelected: function()
		{
			BX.addClass(this.layout.wrapper, 'salescenter-smsprovider-item-selected');

			if(this.itemSelectedImage)
			{
				this.layout.image.style.backgroundImage = 'url(' + this.itemSelectedImage + ')';
			}

			if(this.itemSelectedColor)
			{
				if(this.itemSelectedColor.substring(0,1) !== '#')
				{
					this.layout.wrapper.style.backgroundColor = '#' + this.itemSelectedColor;
				}
				else
				{
					this.layout.wrapper.style.backgroundColor = this.itemSelectedColor;
				}
			}
		},

		isValidColor: function(hex)
		{
			return BX.type.isNotEmptyString(hex) && hex.match(/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/);
		},

		isDarkColor: function(hex)
		{
			if(!hex)
			{
				return false;
			}

			if(hex.substring(0,1) === '#')
			{
				hex = hex.substring(1);
			}

			if (!this.isValidColor(hex))
			{
				return false;
			}

			if (hex.length === 3)
			{
				hex = hex.replace(/([a-f0-9])/gi, "$1$1");
			}

			hex = hex.toLowerCase();

			var defaultColors = [
				"8eb807",
				"188a98",
				"f12e45",
				"1ec6fa",
			];

			if (BX.util.in_array(hex, defaultColors))
			{
				return true;
			}

			var bigint = parseInt(hex, 16);
			var red = (bigint >> 16) & 255;
			var green = (bigint >> 8) & 255;
			var blue = bigint & 255;

			var brightness = (red * 299 + green * 587 + blue * 114) / 1000;
			return brightness < 128;
		},

		getTitle: function()
		{
			if(!this.layout.title)
			{
				this.layout.title = BX.create('div', {
					props: {
						className: 'salescenter-smsprovider-item-title'
					},
					text: this.title
				});

				if(this.itemSelected)
				{
					var isTitleDark = this.data.isSelectedItemTitleDark || this.isDarkColor(this.itemSelectedColor);
					this.layout.title.style.color = isTitleDark ? '#fff': '#525c69';
				}
			}

			return this.layout.title;
		},

		getItemCounter: function()
		{
			return BX.create("div", {
				props: {
					className: "salescenter-smsprovider-item salescenter-smsprovider-integration-marketplace-tile-item salescenter-smsprovider-integration-marketplace-tile-counter"
				},
				children: [
					BX.create('div', {
						props: {
							className: "salescenter-smsprovider-integration-marketplace-tile-counter-head"
						},
						children: [
							BX.create('div', {
								props: {
									className: "salescenter-smsprovider-integration-marketplace-tile-counter-name"
								},
								text: this.title
							}),
							BX.create('div', {
								props: {
									className: "salescenter-smsprovider-integration-marketplace-tile-counter-value"
								},
								text: this.data.count
							}),
						]
					}),
					BX.create('div', {
						props: {
							className: "salescenter-smsprovider-integration-marketplace-tile-counter-link-box"
						},
						children: [
							BX.create('div', {
								props: {
									className: "salescenter-smsprovider-integration-marketplace-tile-counter-link"
								},
								text: this.data.description
							})
						]
					})
				],
				events: {
					click: function()
					{
						this.onClick();
					}.bind(this)
				}
			})
		},

		openRestAppLayout: function(applicationId, appCode)
		{
			BX.ajax.runComponentAction("bitrix:salescenter.smsprovider.panel", "getRestApp", {
				data: {
					code: appCode
				}
			}).then(function(response)
			{
				var app = response.data;
				if(app.TYPE === "A")
				{
					this.showRestApplication(appCode);
				}
				else
				{
					BX.rest.AppLayout.openApplication(applicationId);
				}
			}.bind(this)).catch(function(response)
			{
				this.restAppErrorPopup(" ", response.errors.pop().message);
			}.bind(this));
		},

		restAppErrorPopup: function(title, text)
		{
			var popup = new BX.PopupWindow('rest-app-error-alert', null, {
				closeIcon: true,
				closeByEsc: true,
				autoHide: false,
				titleBar: title,
				content: text,
				zIndex: 16000,
				overlay: {
					color: 'gray',
					opacity: 30
				},
				buttons: [
					new BX.PopupWindowButton({
						'id': 'close',
						'text': BX.message('SPP_SALESCENTER_JS_POPUP_CLOSE'),
						'events': {
							'click': function(){
								popup.close();
							}
						}
					})
				],
				events: {
					onPopupClose: function() {
						this.destroy();
					},
					onPopupDestroy: function() {
						popup = null;
					}
				}
			});
			popup.show();
		},

		onClick: function()
		{
			if(this.data.type === 'smsprovider')
			{
				BX.Salescenter.Manager.addAnalyticAction({
					analyticsLabel: 'salescenterClickSmsProviderTile',
					isConnected: this.itemSelected ? 'y' : 'n',
					type: this.data.type,
				});
				var sliderOptions = {
					allowChangeHistory: false,
					width: 1000,
					events: {
						onClose: this.reload.bind(this, BX.SaleCenterSmsProvider.mode, BX.SaleCenterSmsProvider.signedParameters)
					}
				};

				BX.SidePanel.Instance.open(this.data.connectPath, sliderOptions);
			}
			else if(this.data.type === "counter")
			{
				BX.SidePanel.Instance.open(this.data.connectPath);
			}
			else if(this.data.type === "marketplaceApp")
			{
				if (this.itemSelected)
				{
					this.openRestAppLayout(this.id, this.data.code);
				}
				else
				{
					this.showRestApplication(this.data.code);
				}
			}
			else if(this.data.type === 'recommend')
			{
				BX.Salescenter.Manager.openFeedbackFormParams(event, {feedback_type: 'smsprovider_offer'}, {width: 735});
			}
			else if(this.data.type === 'actionbox')
			{
				if (this.data.handler === 'anchor')
				{
					window.open (this.data.move);
				}
				else if (this.data.handler === 'marketplace')
				{
					BX.rest.Marketplace.open({PLACEMENT: this.data.move});
				}
				else if (this.data.handler === 'landing')
				{
					var dataMove = this.data.move;
					BX.SidePanel.Instance.open('salecenter', {
						contentCallback: function () {
							return "<iframe src='" + dataMove + "'" +
								" style='width: 100%; height:" +
								" -webkit-calc(100vh - 20px); height:" +
								" calc(100vh - 20px);'></iframe>";
						}
					});
				}
			}
			else if(this.data.type === 'bitrix24')
			{
				var self = this;
				BX.Salescenter.SenderConfig.openSliderFreeMessages(this.data.connectPath)().then(function () {
					self.reload.call(self, BX.SaleCenterSmsProvider.mode, BX.SaleCenterSmsProvider.signedParameters);
				});
			}
		},

		showRestApplication: function(appCode)
		{
			var applicationUrlTemplate = "/marketplace/detail/#app#/";
			var url = applicationUrlTemplate.replace("#app#", encodeURIComponent(appCode));
			BX.SidePanel.Instance.open(url, {
				allowChangeHistory: false,
				events: {
					onClose: this.reload.bind(this, "main", BX.SaleCenterSmsProvider.signedParameters)
				}
			});
		},

		reloadPage: function()
		{
			var previousSlider = BX.SidePanel.Instance.getPreviousSlider(BX.SidePanel.Instance.getSliderByWindow(window));
			var parentWindow = (
				previousSlider
					? previousSlider.getWindow()
					: top
			);

			if (parentWindow)
			{
				parentWindow.window.location.reload();
			}
		},

		reload: function(mode, signedParameters)
		{
			BX.ajax.runComponentAction(
				"bitrix:salescenter.smsprovider.panel",
				"getComponentResult",
				{
					mode: "ajax",
					data: {
						mode: mode,
						signedParameters: signedParameters,
					}
				}
			).then(function(response)
			{
				BX.SaleCenterSmsProvider.reloadSlider(response.data);
			});
		}
	};
})();