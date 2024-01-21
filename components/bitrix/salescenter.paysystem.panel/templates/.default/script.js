;(function () {
	'use strict';

	BX.namespace('BX.SaleCenterPaySystem');

	BX.SaleCenterPaySystem = {
		paySystem: null,
		paySystemApp: null,
		mode: "main",
		signedParameters: null,

		init: function(config)
		{
			this.mode = config.mode;
			this.paySystemParams = config.paySystemParams;
			this.paySystemAppParams = config.paySystemAppParams;
			this.signedParameters = config.signedParameters;

			this.paySystem = new BX.TileGrid.Grid(this.paySystemParams);
			this.paySystem.draw();

			if (this.mode === "main")
			{
				this.paySystemApp = new BX.TileGrid.Grid(this.paySystemAppParams);
				this.paySystemApp.draw();
			}

			if (!top.window["adminSidePanel"] || !BX.is_subclass_of(top.window["adminSidePanel"], BX.adminSidePanel))
			{
				top.window["adminSidePanel"] = new BX.adminSidePanel({
					publicMode: true
				});
			}
		},

		reloadSlider: function(responseData)
		{
			if(responseData.paySystemPanelParams)
			{
				this.paySystem.redraw(responseData.paySystemPanelParams);
			}

			if(responseData.paySystemAppPanelParams && (this.mode === "main"))
			{
				this.paySystemApp.redraw(responseData.paySystemAppPanelParams);
			}
		}
	};

	/**
	 *
	 * @param options
	 * @extends {BX.TileGrid.Item}
	 * @constructor
	 */
	BX.SaleCenterPaySystem.TileGrid = function(options)
	{
		BX.TileGrid.Item.apply(this, arguments);

		this.title = options.title;
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

	BX.SaleCenterPaySystem.TileGrid.prototype =
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
						className: 'salescenter-paysystem-item'
					},
					children: [
						BX.create('div', {
							props: {
								className: 'salescenter-paysystem-item-content'
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
						className: 'salescenter-paysystem-item-image'
					},
					style: {
						top: this.data.recommendation ? '25px' : null,
						backgroundSize: this.outerImage ? '50px' : '',
						backgroundImage: this.image ? 'url("' + encodeURI(this.image) + '")' : null
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
					className: 'salescenter-paysystem-item-status-selected'
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
			BX.addClass(this.layout.wrapper, 'salescenter-paysystem-item-selected');

			if(this.itemSelectedImage)
			{
				this.layout.image.style.backgroundImage = 'url(' + this.itemSelectedImage + ')';
			}

			if(this.itemSelectedColor)
			{
				this.layout.wrapper.style.backgroundColor = this.itemSelectedColor;
			}
		},

		isDarkColor: function(hex)
		{
			if(typeof(hex) === 'string' && hex.substring(0,1) === '#')
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

			var bigint = parseInt(hex, 16);
			var red = (bigint >> 16) & 255;
			var green = (bigint >> 8) & 255;
			var blue = bigint & 255;

			var brightness = (red * 299 + green * 587 + blue * 114) / 1000;
			return brightness < 200;
		},

		isValidColor: function(hex)
		{
			return BX.type.isNotEmptyString(hex) && hex.match(/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/);
		},

		getTitle: function()
		{
			if(!this.layout.title)
			{
				this.layout.title = BX.create('div', {
					props: {
						className: this.data.type === 'marketplaceApp'
							? 'salescenter-paysystem-marketplace-app-item-title'
							: 'salescenter-paysystem-item-title',
					},
					text: this.title
				});

				if(this.itemSelected && this.itemSelectedColor)
				{
					this.layout.title.style.color = this.isDarkColor(this.itemSelectedColor) ? '#fff': '#525c69';
				}
			}

			return this.layout.title;
		},

		getItemCounter: function()
		{
			return BX.create("div", {
				props: {
					className: "salescenter-paysystem-item salescenter-paysystem-integration-marketplace-tile-item salescenter-paysystem-integration-marketplace-tile-counter"
				},
				children: [
					BX.create('div', {
						props: {
							className: "salescenter-paysystem-integration-marketplace-tile-counter-head"
						},
						children: [
							BX.create('div', {
								props: {
									className: "salescenter-paysystem-integration-marketplace-tile-counter-name"
								},
								text: this.title
							}),
							BX.create('div', {
								props: {
									className: "salescenter-paysystem-integration-marketplace-tile-counter-value"
								},
								text: this.data.count
							}),
						]
					}),
					BX.create('div', {
						props: {
							className: "salescenter-paysystem-integration-marketplace-tile-counter-link-box"
						},
						children: [
							BX.create('div', {
								props: {
									className: "salescenter-paysystem-integration-marketplace-tile-counter-link"
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
			BX.ajax.runComponentAction("bitrix:salescenter.paysystem.panel", "getRestApp", {
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
			BX.UI.Dialogs.MessageBox.alert(
				text,
				title,
				(messageBox) => messageBox.close(),
				BX.Loc.getMessage('SPP_SALESCENTER_JS_POPUP_CLOSE'),
			);
		},

		onClick: function()
		{
			if(this.data.type === 'paysystem')
			{
				BX.Salescenter.Manager.addAnalyticAction({
					analyticsLabel: 'salescenterClickPaymentTile',
					isConnected: this.itemSelected ? 'y' : 'n',
					type: this.data.paySystemType,
				});
				var sliderOptions = {
					cacheable: false,
					allowChangeHistory: false,
					width: 1000,
					events: {
						onClose: this.reload.bind(this, BX.SaleCenterPaySystem.mode, BX.SaleCenterPaySystem.signedParameters)
					}
				};

				if(!this.itemSelected && !this.data.showMenu)
				{
					BX.SidePanel.Instance.open(this.data.connectPath, sliderOptions);
				}
				else
				{
					this.showItemMenu(this, {
						sliderOptions: sliderOptions
					});
				}
			}
			else if(this.data.type === 'paysystem_extra')
			{
				var sliderOptions = {
					allowChangeHistory: false,
					events: {
						onClose: this.reload.bind(this, "main", BX.SaleCenterPaySystem.signedParameters)
					}
				};
				BX.SidePanel.Instance.open(this.data.connectPath, sliderOptions);
			}
			else if(this.data.type === "counter")
			{
				BX.SidePanel.Instance.open(this.data.connectPath);
			}
			else if(this.data.type === "integration")
			{
				window.open(this.data.url);
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
			else if(this.data.type === 'recommend')
			{
				BX.SidePanel.Instance.open(this.data.connectPath, {width: 735});
			}
		},

		showItemMenu: function (item, options)
		{
			var menu = [],
				menuItemIndex,
				itemNode = item.layout.container,
				menuitemId = 'salescenter-item-menu-' + BX.util.getRandomString(),
				filter;

			item.sliderOptions = {};
			if (options.sliderOptions)
			{
				item.sliderOptions = options.sliderOptions;
			}

			for (menuItemIndex in item.data.menuItems)
			{
				if (item.data.menuItems.hasOwnProperty(menuItemIndex))
				{
					if (item.data.menuItems[menuItemIndex].DELIMITER)
					{
						menu.push({
							delimiter: true
						});
					}
					else
					{
						menu.push({
							text: item.data.menuItems[menuItemIndex].NAME,
							link: item.data.menuItems[menuItemIndex].LINK,
							onclick: function(e, tile)
							{
								item.moreTabsMenu.close();
								BX.SidePanel.Instance.open(tile.options.link, item.sliderOptions);
							}
						});
					}

				}
			}

			item.moreTabsMenu = BX.PopupMenu.create(
				menuitemId,
				itemNode,
				menu,
				{
					autoHide: true,
					offsetLeft: 0,
					offsetTop: 0,
					closeByEsc: true,
					events: {
						onPopupClose : function()
						{
							item.moreTabsMenu.popupWindow.destroy();
							BX.PopupMenu.destroy(menuitemId);
						},
						onPopupDestroy: function ()
						{
							item.moreTabsMenu = null;
						}
					}
				}
			);
			item.moreTabsMenu.popupWindow.show();
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

		showRestApplication: function(appCode)
		{
			var applicationUrlTemplate = "/marketplace/detail/#app#/";
			var url = applicationUrlTemplate.replace("#app#", encodeURIComponent(appCode));
			BX.SidePanel.Instance.open(url, {
				allowChangeHistory: false,
				events: {
					onClose: this.reload.bind(this, "main", BX.SaleCenterPaySystem.signedParameters)
				}
			});
		},

		reload: function(mode, signedParameters)
		{
			BX.ajax.runComponentAction(
				"bitrix:salescenter.paysystem.panel",
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
				BX.SaleCenterPaySystem.reloadSlider(response.data);
			});
		}
	};
})();
