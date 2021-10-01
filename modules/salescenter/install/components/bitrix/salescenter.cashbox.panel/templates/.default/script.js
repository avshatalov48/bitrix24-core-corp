;(function () {
	'use strict';

	BX.namespace('BX.SaleCenterCashbox');

	BX.SaleCenterCashbox = {
		init: function(config)
		{
			this.cashboxParams = config.cashboxParams;

			var cashbox = new BX.TileGrid.Grid(this.cashboxParams);
			cashbox.draw();
		},
	};

	/**
	 *
	 * @param options
	 * @extends {BX.TileGrid.Item}
	 * @constructor
	 */
	BX.SaleCenterCashbox.TileGrid = function(options)
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
		this.cashboxId = options.id;
		this.data = options.data || {};
	};

	BX.SaleCenterCashbox.TileGrid.prototype =
	{
		__proto__: BX.TileGrid.Item.prototype,
		constructor: BX.TileGrid.Item,

		getContent: function()
		{
			if(!this.layout.wrapper)
			{
				this.layout.wrapper = BX.create('div', {
					props: {
						className: 'salescenter-cashbox-item'
					},
					children: [
						BX.create('div', {
							props: {
								className: 'salescenter-cashbox-item-content'
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

			this.itemSelected ? this.setSelected() : null;

			return this.layout.wrapper;
		},

		getImage: function()
		{
			if(!this.layout.image)
			{
				this.layout.image = BX.create('div', {
					props: {
						className: 'salescenter-cashbox-item-image'
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
					className: 'salescenter-cashbox-item-status-selected'
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
			if(!this.itemSelected)
				return;

			BX.addClass(this.layout.wrapper, 'salescenter-cashbox-item-selected');

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
					className: 'salescenter-cashbox-item-status-selected'
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

			BX.removeClass(this.layout.wrapper, 'salescenter-cashbox-item-selected');

			if(this.image)
			{
				this.layout.image.style.backgroundImage = 'url(' + this.image + ')';
			}

			this.layout.wrapper.style.backgroundColor = '';

			var itemSelected = this.layout.wrapper.querySelector('.salescenter-cashbox-item-status-selected');
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
						className: 'salescenter-cashbox-item-title'
					},
					text: this.title
				});
			}

			return this.layout.title;
		},

		onClick: function()
		{
			if (this.data.type === 'cashbox')
			{
				var sliderOptions = {
					allowChangeHistory: false,
					width: 1050,
					events: {
						onCloseComplete: function (e)
						{
							this.reloadCashboxItem(this.data.handler, this.cashboxId);
						}.bind(this),
					}
				};

				if (
					this.data.hasOwnProperty('paySystem')
					&& this.data.paySystem
					&& this.data.menuItems.length === 1
				)
				{
					BX.SidePanel.Instance.open(this.data.menuItems[0].LINK, sliderOptions);
				}
				else if(!this.itemSelected && !this.data.showMenu)
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
				menuitemId = 'salescenter-item-menu-' + BX.util.getRandomString();

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

		reloadCashboxItem: function(handler, cashboxId)
		{
			var self = this;
			BX.ajax.runComponentAction(
				'bitrix:salescenter.cashbox.panel',
				'reloadCashboxItem',
				{
					mode: 'class',
					data: {
						handler: handler,
						cashboxId: cashboxId,
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
	};

})();