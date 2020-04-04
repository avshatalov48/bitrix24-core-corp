;(function ()
{
	'use strict';
	BX.namespace('BX.Salescenter.ControlPanel');

	BX.Salescenter.ControlPanel.init = function(options)
	{
		this.panelGrid = options.panelGrid;
		this.selfFolderUrl = "/shop/settings/";
		if (BX.SidePanel.Instance)
		{
			BX.SidePanel.Instance.bindAnchors({
				rules: [
					{
						condition: [
							BX.Salescenter.ControlPanel.selfFolderUrl+"sale_delivery_service_edit/",
							this.selfFolderUrl + "sale_pay_system_edit/"
						],
						handler: BX.Salescenter.ControlPanel.adjustSidePanelOpener
					},
					{
						condition: [
							"/shop/orders/details/(\\d+)/",
							"/shop/orders/payment/details/(\\d+)/",
							"/shop/orders/shipment/details/(\\d+)/"
						]
					},
					{
						condition: [
							"/crm/configs/sale/"
						]
					}
				]
			});
		}

		if (!top.window["adminSidePanel"] || !BX.is_subclass_of(top.window["adminSidePanel"], top.BX.adminSidePanel))
		{
			top.window["adminSidePanel"] = new top.BX.adminSidePanel({
				publicMode: true
			});
		}
	};

	BX.Salescenter.ControlPanel.adjustSidePanelOpener = function(event, link)
	{
		if (BX.SidePanel.Instance)
		{
			var isSidePanelParams = (link.url.indexOf("IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER") >= 0);
			if (!isSidePanelParams || (isSidePanelParams && !BX.SidePanel.Instance.getTopSlider()))
			{
				event.preventDefault();
				link.url =	BX.util.add_url_param(link.url, {"publicSidePanel": "Y"});
				BX.SidePanel.Instance.open(link.url, {
					allowChangeHistory: false
				});
			}
		}
	};

	BX.Salescenter.ControlPanel.showMenu = function(item, menu)
	{
		BX.PopupMenu.show(menu.id, item.layout.content, menu.items, {
			offsetLeft: 0,
			offsetTop: 0,
			closeByEsc: true,
			className: 'salescenter-panel-menu'
		});
	};

	BX.Salescenter.ControlPanel.reloadStoreChatsMenu = function()
	{
		BX.Salescenter.ControlPanel.hideMenu('store-chat-menu');
		var storeChatsItem = BX.Salescenter.ControlPanel.getSalesChatItem();
		if(storeChatsItem)
		{
			delete storeChatsItem.data.menu;
		}

		BX.Salescenter.ControlPanel.hideMenu('store-sms-menu');
		var storeSmsItem = BX.Salescenter.ControlPanel.getSalesSmsItem();
		if(storeSmsItem)
		{
			delete storeSmsItem.data.menu;
		}
	};

	BX.Salescenter.ControlPanel.hideMenu = function(menuId)
	{
		BX.PopupMenu.destroy(menuId);
	};

	BX.Salescenter.ControlPanel.getSalesChatItem = function()
	{
		return this.panelGrid.getItem('store-chats');
	};

	BX.Salescenter.ControlPanel.getSalesSmsItem = function()
	{
		return this.panelGrid.getItem('store-sms');
	};

	BX.Salescenter.ControlPanel.connectShop = function(params)
	{
		var chatItem = BX.Salescenter.ControlPanel.getSalesChatItem();
		var smsItem = BX.Salescenter.ControlPanel.getSalesSmsItem();
		if(!chatItem)
		{
			return;
		}
		BX.Salescenter.Manager.startConnection(params).then(function()
		{
			BX.Salescenter.Manager.loadConfig().then(function(result)
			{
				if(result.isSiteExists)
				{
					BX.Salescenter.Manager.showAfterConnectPopup();
					this.itemSelected = true;
					this.setSelected();
					BX.Salescenter.ControlPanel.reloadStoreChatsMenu();

					if(smsItem)
					{
						smsItem.itemSelected = true;
						smsItem.setSelected();
					}
				}
			}.bind(chatItem));
		}.bind(chatItem));
	};

	BX.Salescenter.ControlPanel.Item = function (options)
	{
		BX.TileGrid.Item.apply(this, arguments);

		this.title = options.title;
		this.image = options.image;
		this.itemSelected = options.itemSelected;
		this.itemSelectedColor = options.itemSelectedColor;
		this.itemSelectedImage = options.itemSelectedImage;
		this.comingSoon = options.comingSoon || false;
		this.layout = {
			container: null,
			image: null,
			title: null,
			clipTitle: null,
			company: null,
			controls: null,
			buttonAction: null,
			price: null
		};
		this.data = options.data || {};
	};

	BX.Salescenter.ControlPanel.Item.prototype =
	{
		__proto__: BX.TileGrid.Item.prototype,
		constructor: BX.TileGrid.Item,

		getContent: function()
		{
			if(this.layout.container)
				return this.layout.container;

			this.layout.container = BX.create('div', {
				props: {
					className: 'salescenter-item'
				},
				children: [
					BX.create('div', {
						props: {
							className: 'salescenter-item-content'
						},
						children: [
							this.getImage(),
							this.getTitle(),
							this.getStatus(),
							this.getLabel()
						]
					})
				],
				events: {
					click: function()
					{
						if (!this.comingSoon)
						{
							this.onClick();
						}
					}.bind(this)
				}
			});

			this.setSelected();
			this.setDisabled();

			return this.layout.container;
		},

		getImage: function()
		{
			if(this.layout.image)
				return this.layout.image;

			this.layout.image = BX.create('div', {
				props: {
					className: 'salescenter-item-image'
				},
				style: {
					backgroundImage: this.image ? 'url(' + this.image + ')' : null
				}
			});

			return this.layout.image;
		},

		getStatus: function()
		{
			if(!this.itemSelected || this.comingSoon)
				return;

			this.layout.itemSelected = BX.create('div', {
				props: {
					className: 'salescenter-item-status-selected'
				}
			});

			return this.layout.itemSelected;
		},

		getLabel: function()
		{
			if (!this.comingSoon)
				return;

			this.layout.itemLabel = BX.create('div', {
				props: {
					className: 'salescenter-item-label'
				},
				children: [
					BX.create('span', {
						props: {
							className: 'salescenter-item-label-text'
						},
						text: BX.message('SALESCENTER_CONTROL_PANEL_ITEM_LABEL_COMMING_SOON')
					})
				]
			});

			return this.layout.itemLabel;
		},

		setDisabled: function()
		{
			if (!this.comingSoon)
				return;

			var contentNode = this.layout.container.querySelector('.salescenter-item-content');
			var content = contentNode.parentNode;
			BX.addClass(content, "salescenter-item-disabled");
		},

		setSelected: function()
		{
			if(!this.itemSelected || this.comingSoon)
				return;

			var contentNode = this.layout.container.querySelector('.salescenter-item-content');
			var content = contentNode.parentNode;
			BX.addClass(content, "salescenter-item-selected");

			BX.adjust(this.layout.image, {
				style: {
					backgroundImage: this.itemSelectedImage ? 'url(' + this.itemSelectedImage + ')' : this.image
				}
			});

			if(this.itemSelectedColor)
			{
				BX.adjust(content, {
					style: {
						backgroundColor: this.itemSelectedColor ? this.itemSelectedColor : null
					}
				});
			}

			this.layout.itemSelected = BX.create('div', {
				props: {
					className: 'salescenter-item-status-selected'
				}
			});

			contentNode.appendChild(this.layout.itemSelected);
		},

		setUnselected: function()
		{
			if(this.itemSelected)
			{
				return;
			}

			var contentNode = this.layout.container.querySelector('.salescenter-item-content');
			var content = contentNode.parentNode;
			BX.removeClass(content, "salescenter-item-selected");

			BX.adjust(this.layout.image, {
				style: {
					backgroundImage: 'url(' + this.image + ')'
				}
			});

			BX.adjust(content, {
				style: {
					backgroundColor: null
				}
			});

			var itemSelected = content.querySelector('.salescenter-item-status-selected');
			if(itemSelected)
			{
				itemSelected.parentNode.removeChild(itemSelected);
			}
		},

		getTitle: function()
		{
			if(this.layout.title)
				return this.layout.title;

			this.layout.title = BX.create('div', {
				props: {
					className: 'salescenter-item-title'
				},
				html: this.title
			});

			return this.layout.title;
		},

		afterRender: function()
		{

		},

		onClick: function()
		{
			var sliderOptions = {};

			if(this.id === 'store-chats')
			{
				if(!this.itemSelected)
				{
					BX.Salescenter.ControlPanel.connectShop({
						context: 'salescenter_chat',
					});
				}
				else
				{
					if(!this.data.menu)
					{
						BX.ajax.runComponentAction('bitrix:salescenter.control_panel', 'getChatTileMenu', {mode: 'class'}).then(function(response)
						{
							if(response.data.items && response.data.items.length > 0)
							{
								this.data.menu = response.data;
								BX.Salescenter.ControlPanel.showMenu(this, this.data.menu);
							}
							else
							{
								this.itemSelected = false;
								BX.Salescenter.Manager.isSiteExists = false;
								BX.Salescenter.Manager.isSitePublished = false;
								this.setUnselected();
								this.onClick();

								var smsItem = BX.Salescenter.ControlPanel.getSalesSmsItem();
								if(smsItem)
								{
									smsItem.itemSelected = false;
									smsItem.setUnselected();
								}
							}
						}.bind(this));
					}
					else
					{
						BX.Salescenter.ControlPanel.showMenu(this, this.data.menu);
					}
				}
			}
			else if(this.id === 'store-sms')
			{
				if(!this.itemSelected)
				{
					BX.Salescenter.ControlPanel.connectShop({
						context: 'salescenter_sms',
					});
				}
				else
				{
					if(!this.data.menu)
					{
						BX.ajax.runComponentAction('bitrix:salescenter.control_panel', 'getSmsTileMenu', {mode: 'class'}).then(function(response)
						{
							if(response.data.items && response.data.items.length > 0)
							{
								this.data.menu = response.data;
								BX.Salescenter.ControlPanel.showMenu(this, this.data.menu);
							}
							else
							{
								this.itemSelected = false;
								BX.Salescenter.Manager.isSiteExists = false;
								BX.Salescenter.Manager.isSitePublished = false;
								this.setUnselected();
								this.onClick();

								var chatItem = BX.Salescenter.ControlPanel.getSalesChatItem();
								if(chatItem)
								{
									chatItem.itemSelected = false;
									chatItem.setUnselected();
								}
							}
						}.bind(this));
					}
					else
					{
						BX.Salescenter.ControlPanel.showMenu(this, this.data.menu);
					}
				}
			}
			else if(this.data.type === 'paysystem')
			{
				BX.Salescenter.Manager.addAnalyticAction({
					analyticsLabel: 'salescenterClickPaymentTile',
					isConnected: this.itemSelected ? 'y' : 'n',
					type: this.data.paySystemType,
				});
				this.formData = '';
				sliderOptions = {
					allowChangeHistory: false,
					width: 1000,
					events: {
						onClose: function (e)
						{
							var slider = e.getSlider();
							var additionalParams = [];
							if (this.data.additionalParams)
							{
								additionalParams = this.data.additionalParams;
							}
							this.setPaySystemItemHandler(slider, this.data.paySystemType, additionalParams);
						}.bind(this)
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
			else if(this.data.type === 'cashbox')
			{
				this.formData = '';
				sliderOptions = {
					allowChangeHistory: false,
					width: 1000,
					events: {
						onLoad: function(e)
						{
							var slider = e.getSlider();
							this.formData = this.getAllFormData(slider);
						}.bind(this),
						onClose: function (e)
						{
							if(this.onCloseSlider(e))
							{
								this.reloadCashboxItem(this.data.handler);
							}
						}.bind(this),
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
			else if(this.data.type === 'delivery')
			{
				this.formData = '';
				sliderOptions = {
					allowChangeHistory: false,
					events: {
						onLoad: function(e) {
							var slider = e.getSlider();
							this.formData = this.getAllFormData(slider);
							if (slider.isOpen() && slider.url.indexOf('CREATE') > -1)
							{
								this.prepareDeliveryForm(slider);
							}
							else
							{
								var url = this.data.connectPath;
								this.setDeliveryListAddButton(slider, url);
							}
						}.bind(this),
						onClose: function (e)
						{
							this.onCloseSlider(e);

							var slider = e.getSlider();
							this.setDeliveryItemHandler(slider);
						}.bind(this),
						onDestroy: function (e)
						{
							var slider = e.getSlider();
							this.setDeliveryItemHandler(slider);
						}.bind(this)
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
			else if(this.data.type === 'delivery_extra' || this.data.type === 'paysystem_extra')
			{
				sliderOptions = {
					allowChangeHistory: false,
					events: {
						onClose: function (e) {
							top.BX.onCustomEvent('SalescenterPaysystemPanelReload');
						}
					}
				};
				BX.SidePanel.Instance.open(this.data.connectPath, sliderOptions);
			}
			else if(this.data.type === 'userconsent')
			{
				sliderOptions = {
					allowChangeHistory: false,
					events: {
						onClose: function (e)
						{
							var slider = e.getSlider();
							this.setUserConsentItemHandler();
						}.bind(this)
					}
				};

				if(!this.itemSelected)
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
		},

		showItemMenu: function(item, options)
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
					else if (item.data.menuItems[menuItemIndex].FILTER)
					{
						filter = item.data.menuItems[menuItemIndex].FILTER;
						menu.push({
							text: item.data.menuItems[menuItemIndex].NAME,
							link: item.data.menuItems[menuItemIndex].LINK,
							onclick: function (e, tile) {
								item.moreTabsMenu.close();
								BX.ajax.runComponentAction(
									'bitrix:salescenter.control_panel',
									'setDeliveryListFilter',
									{
										mode: 'class',
										data: {
											filter: filter
										}
									}
								).then(function (response) {
									BX.SidePanel.Instance.open(tile.options.link, item.sliderOptions);
								});
							}
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

		prepareDeliveryForm: function(slider)
		{
			var sliderIframe, innerDoc, deliveryName, deliveryHandler, deliveryStores;

			sliderIframe = slider.iframe;
			innerDoc = sliderIframe.contentDocument || sliderIframe.contentWindow.document;
			deliveryName = innerDoc.getElementsByName('NAME')[0];
			if (deliveryName)
			{
				deliveryName.value = this.title;
			}

			if (this.id === 'pickup')
			{
				deliveryStores = innerDoc.getElementsByName('STORES_SHOW')[0];
				if (deliveryStores)
				{
					deliveryStores.checked = true;
					var eventChange = new Event('change');
					deliveryStores.dispatchEvent(eventChange);
				}
			}
		},

		setDeliveryListAddButton: function(slider, url)
		{
			var sliderIframe, innerDoc, addButtonWrapper, addButtonMenu, addButton, addButtonText;

			sliderIframe = slider.iframe;
			innerDoc = sliderIframe.contentDocument || sliderIframe.contentWindow.document;
			addButtonWrapper = innerDoc.getElementsByClassName('ui-btn-double ui-btn-primary');
			if (addButtonWrapper)
			{
				addButtonMenu = addButtonWrapper[0].getElementsByClassName('ui-btn-main')[0];
				if (addButtonMenu)
				{
					addButtonText = addButtonMenu.innerText;
					addButton = BX.create('a', {
						props: {
							className: 'ui-btn-main',
							href: url
						},
						text: addButtonText
					});

					addButtonWrapper[0].replaceChild(addButton, addButtonMenu);
				}
			}
		},

		setPaySystemItemHandler: function(slider, type, additionalParams)
		{
			var sliderIframe, innerDoc, paySystemId, actionFile, psMode, url;
			sliderIframe = slider.iframe;
			innerDoc = sliderIframe.contentDocument || sliderIframe.contentWindow.document;

			url = new URL(window.location.href + slider.url);
			paySystemId = url.searchParams.get("ID");
			actionFile = url.searchParams.get("ACTION_FILE");
			if(!actionFile && type)
			{
				actionFile = type;
			}
			psMode = url.searchParams.get("PS_MODE");

			if (paySystemId || actionFile)
			{
				this.reloadPaySystemItem(paySystemId, actionFile, psMode, additionalParams);
			}
		},

		reloadPaySystemItem: function(paySystemId, actionFile, psMode, additionalParams)
		{
			var self = this;
			BX.ajax.runComponentAction(
				'bitrix:salescenter.control_panel',
				'reloadPaySystemItem',
				{
					mode: 'class',
					data: {
						paySystemId: paySystemId,
						actionFile: actionFile,
						psMode: psMode,
						additionalParams: additionalParams
					}
				}
			).then(function(response)
			{
				self.itemSelected = response.data.itemSelected;
				if (self.itemSelected)
				{
					self.setSelected();
				}
				else
				{
					self.setUnselected();
				}

				self.data.menuItems = response.data.menuItems;
				self.data.showMenu = response.data.showMenu;
			});
		},

		reloadCashboxItem: function(handler)
		{
			var self = this;
			BX.ajax.runComponentAction(
				'bitrix:salescenter.control_panel',
				'reloadCashboxItem',
				{
					mode: 'class',
					data: {
						handler: handler,
					}
				}
			).then(function(response)
			{
				self.itemSelected = response.data.itemSelected;
				if (self.itemSelected)
				{
					self.setSelected();
				}
				else
				{
					self.setUnselected();
				}

				self.data.menuItems = response.data.menuItems;
				self.data.showMenu = response.data.showMenu;
			});
		},

		setDeliveryItemHandler: function(slider)
		{
			var sliderIframe, innerDoc, className, serviceType, url;
			sliderIframe = slider.iframe;
			innerDoc = sliderIframe.contentDocument || sliderIframe.contentWindow.document;

			url = new URL(window.location.href + slider.url);
			className = url.searchParams.get("CLASS_NAME");
			serviceType = url.searchParams.get("SERVICE_TYPE");

			this.reloadDeliveryItem(className, serviceType);
		},

		reloadDeliveryItem: function(className, serviceType)
		{
			var self = this;
			BX.ajax.runComponentAction(
				'bitrix:salescenter.control_panel',
				'reloadDeliveryItem',
				{
					mode: 'class',
					data: {
						className: className,
						serviceType: serviceType,
					}
				}
			).then(function(response)
			{
				if(response.data.menuItems && response.data.menuItems.length > 0)
				{
					self.itemSelected = response.data.itemSelected;
					if (self.itemSelected)
					{
						self.setSelected();
					}
					else
					{
						self.setUnselected();
					}

					self.data.menuItems = response.data.menuItems;
					self.data.showMenu = response.data.showMenu;
				}
			});
		},

		setUserConsentItemHandler: function()
		{
			this.reloadUserConsentItem();
		},

		reloadUserConsentItem: function()
		{
			var self = this;
			BX.ajax.runComponentAction(
				'bitrix:salescenter.control_panel',
				'reloadUserConsent',
				{
					mode: 'class'
				}
			).then(function(response)
			{
				self.itemSelected = response.data.itemSelected;
				if (self.itemSelected)
				{
					self.setSelected();
				}
				else
				{
					self.setUnselected();
				}

				self.data.menuItems = response.data.menuItems;
			});
		},

		getSliderDocument: function(slider)
		{
			var sliderIframe, innerDoc;
			sliderIframe = slider.iframe;
			innerDoc = sliderIframe.contentDocument || sliderIframe.contentWindow.document;

			return innerDoc;
		},

		getAllFormData: function(slider)
		{
			var innerDoc = this.getSliderDocument(slider);
			var formNode = innerDoc.getElementsByTagName('form');

			if (formNode && formNode.length > 0)
			{
				var prepared = BX.ajax.prepareForm(formNode[0]),
					i;

				for (i in prepared.data)
				{
					if (prepared.data.hasOwnProperty(i) && i === '')
					{
						delete prepared.data[i];
					}
				}

				return !!prepared && prepared.data ? JSON.stringify(prepared.data) : '';
			}

			return '';
		},

		onCloseSlider: function(event)
		{
			var sliderDocument = this.getSliderDocument(event.slider);
			var savedInput = sliderDocument.getElementById('salescenter-form-is-saved');
			if(savedInput && savedInput.value === 'y')
			{
				return true;
			}
			var formData = this.getAllFormData(event.slider);
			if (this.formData === formData || this.isClose === true)
			{
				this.isClose = false;
				return false;
			}

			event.action = false;

			this.popup = new BX.PopupWindow(
				"salescenter_slider_close_confirmation",
				null,
				{
					autoHide: false,
					draggable: false,
					closeByEsc: false,
					offsetLeft: 0,
					offsetTop: 0,
					zIndex: event.slider.zIndex + 100,
					bindOptions: { forceBindPosition: true },
					titleBar: BX.message('SALESCENTER_CONTROL_PANEL_POPUP_TITLE'),
					content: BX.message('SALESCENTER_CONTROL_PANEL_POPUP_CONTENT'),
					buttons: [
						new BX.PopupWindowButton(
							{
								text : BX.message('SALESCENTER_CONTROL_PANEL_POPUP_BUTTON_CLOSE'),
								className : "ui-btn ui-btn-success",
								events: { click: BX.delegate(this.onCloseConfirmButtonClick.bind(this, 'close')) }
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text : BX.message('SALESCENTER_CONTROL_PANEL_POPUP_BUTTON_CANCEL'),
								className : "ui-btn ui-btn-link",
								events: { click: BX.delegate(this.onCloseConfirmButtonClick.bind(this, 'cancel')) }
							}
						)
					],
					events: {
						onPopupClose: function()
						{
							this.destroy();
						}
					}
				}
			);
			this.popup.show();

			return false;
		},

		onCloseConfirmButtonClick: function(button)
		{
			this.popup.close();
			if (BX.SidePanel.Instance.getTopSlider())
			{
				BX.SidePanel.Instance.getTopSlider().focus();
			}

			if(button === "close")
			{
				this.isClose = true;
				BX.SidePanel.Instance.getTopSlider().close();
			}
		},
	};
})();