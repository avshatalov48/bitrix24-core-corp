;(function () {
	'use strict';

	BX.namespace('BX.SaleCenterPaySystem.TileGrid');

	/**
	 *
	 * @param options
	 * @extends {BX.TileGrid.Item}
	 * @constructor
	 */
	BX.SaleCenterPaySystem.TileGrid.Item = function(options)
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

	BX.SaleCenterPaySystem.TileGrid.Item.prototype =
	{
		__proto__: BX.TileGrid.Item.prototype,
		constructor: BX.TileGrid.Item,

		getContent: function()
		{
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
						className: 'salescenter-paysystem-item-image'
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
					className: 'salescenter-paysystem-item-status-selected'
				}
			});

			return this.layout.itemSelected;
		},

		setSelected: function()
		{
			if(!this.itemSelected)
				return;

			BX.addClass(this.layout.wrapper, 'salescenter-paysystem-item-selected');

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
					className: 'salescenter-paysystem-item-status-selected'
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

			BX.removeClass(this.layout.wrapper, 'salescenter-paysystem-item-selected');

			if(this.image)
			{
				this.layout.image.style.backgroundImage = 'url(' + this.image + ')';
			}

			this.layout.wrapper.style.backgroundColor = '';

			var itemSelected = this.layout.wrapper.querySelector('.salescenter-paysystem-item-status-selected');
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
						className: 'salescenter-paysystem-item-title'
					},
					text: this.title
				});
			}

			return this.layout.title;
		},

		onClick: function()
		{
			BX.Salescenter.Manager.addAnalyticAction({
				analyticsLabel: 'salescenterClickPaymentTile',
				isConnected: this.itemSelected ? 'y' : 'n',
				type: this.data.paySystemType,
			});
			var sliderOptions = {
				allowChangeHistory: false,
				width: 1000,
				events: {
					onLoad: function (e)
					{
						this.itemData = JSON.stringify({
							itemSelected: this.itemSelected,
							menuItems: this.data.menuItems,
							showMenu: this.data.showMenu
						});
					}.bind(this),
					onClose: function (e)
					{
						var slider = e.getSlider();
						this.setPaySystemItemHandler(slider);
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

		setPaySystemItemHandler: function(slider)
		{
			var sliderIframe, innerDoc, paySystemId, actionFile, psMode, url;
			sliderIframe = slider.iframe;
			innerDoc = sliderIframe.contentDocument || sliderIframe.contentWindow.document;

			url = new URL(window.location.href + slider.url);
			paySystemId = url.searchParams.get("ID");
			actionFile = url.searchParams.get("ACTION_FILE");
			psMode = url.searchParams.get("PS_MODE");

			if (paySystemId || actionFile)
			{
				this.reloadPaySystemItem(paySystemId, actionFile, psMode);
			}
		},

		reloadPaySystemItem: function(paySystemId, actionFile, psMode)
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
						psMode: psMode
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

				if (self.itemData !== JSON.stringify(response.data))
				{
					top.BX.addCustomEvent('SalescenterPaysystemPanelReload', BX.proxy(self.reloadPage, self));
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

			parentWindow.window.location.reload();
		}
	};
})();