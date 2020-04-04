;(function () {
	'use strict';

	BX.namespace('BX.SaleCenterDelivery.TileGrid');

	/**
	 *
	 * @param options
	 * @extends {BX.TileGrid.Item}
	 * @constructor
	 */
	BX.SaleCenterDelivery.TileGrid.Item = function(options)
	{
		BX.TileGrid.Item.apply(this, arguments);

		this.title = options.title;
		this.image = options.image;
		this.itemSelected = options.itemSelected;
		this.itemSelectedColor = options.itemSelectedColor;
		this.itemSelectedImage = options.itemSelectedImage;
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

	BX.SaleCenterDelivery.TileGrid.Item.prototype =
	{
		__proto__: BX.TileGrid.Item.prototype,
		constructor: BX.TileGrid.Item,

		getContent: function()
		{
			if(!this.layout.wrapper)
			{
				this.layout.wrapper = BX.create('div', {
					props: {
						className: 'salescenter-delivery-item'
					},
					children: [
						BX.create('div', {
							props: {
								className: 'salescenter-delivery-item-content'
							},
							children: [
								this.getImage(),
								this.getTitle(),
								this.getStatus()
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

			this.itemSelected ? this.setSelected() : null;

			return this.layout.wrapper;
		},

		getImage: function()
		{
			if(!this.layout.image)
			{
				this.layout.image = BX.create('div', {
					props: {
						className: 'salescenter-delivery-item-image'
					},
					style: {
						backgroundImage: this.image ? 'url(' + this.image + ')' : null
					}
				});
			}

			return this.layout.image;
		},

		getStatus: function()
		{
			if(!this.itemSelected)
				return;

			this.layout.itemSelected = BX.create('div', {
				props: {
					className: 'salescenter-delivery-item-status-selected'
				}
			});

			return this.layout.itemSelected;
		},

		setSelected: function()
		{
			if(!this.itemSelected)
				return;

			BX.addClass(this.layout.wrapper, 'salescenter-delivery-item-selected');

			if(this.itemSelectedImage)
			{
				this.layout.image.style.backgroundImage = 'url(' + this.itemSelectedImage + ')';
			}

			if(this.itemSelectedColor)
			{
				this.layout.wrapper.style.backgroundColor = this.itemSelectedColor;
			}

			this.layout.itemSelected = BX.create('div', {
				props: {
					className: 'salescenter-delivery-item-status-selected'
				}
			});

			this.layout.wrapper.appendChild(this.layout.itemSelected);
		},

		setUnselected: function()
		{
			if(this.itemSelected)
			{
				return;
			}

			BX.removeClass(this.layout.wrapper, 'salescenter-delivery-item-selected');

			if(this.image)
			{
				this.layout.image.style.backgroundImage = 'url(' + this.image + ')';
			}

			this.layout.wrapper.style.backgroundColor = '';

			var itemSelected = content.querySelector('.salescenter-delivery-item-status-selected');
			if(itemSelected)
			{
				itemSelected.parentNode.removeChild(itemSelected);
			}
		},

		getTitle: function()
		{
			if(!this.layout.title)
			{
				this.layout.title = BX.create('div', {
					props: {
						className: 'salescenter-delivery-item-title'
					},
					text: this.title
				});
			}

			return this.layout.title;
		},

		onClick: function()
		{
			var sliderOptions = {
				allowChangeHistory: false,
				events: {
					onLoad: function(e) {
						var slider = e.getSlider();
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
		}
	};
})();