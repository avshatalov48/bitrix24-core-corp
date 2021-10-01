;(function () {
	'use strict';

	BX.namespace('BX.Salescenter.CrmFormPanel');

	BX.Salescenter.CrmFormPanel = {
		grid: null,
		signedParameters: null,

		init: function(config)
		{
			this.gridParams = config.gridParams;
			this.signedParameters = config.signedParameters;

			this.grid = new BX.TileGrid.Grid(this.gridParams);
			this.grid.draw();
		},

		closeContextMenus: function()
		{
			this.grid.items.map(function(item)
			{
				if (item.contextMenu)
				{
					item.contextMenu.close();
				}
			});
		}
	};

	/**
	 *
	 * @param options
	 * @extends {BX.TileGrid.Item}
	 * @constructor
	 */
	BX.Salescenter.CrmFormPanel.TileGrid = function(options)
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
			wrapper: null,
			image: null,
			title: null,
		};
		this.data = options.data || {};
		this.contextMenu = null;
	};

	BX.Salescenter.CrmFormPanel.TileGrid.prototype =
	{
		__proto__: BX.TileGrid.Item.prototype,
		constructor: BX.TileGrid.Item,

		getContent: function()
		{
			if(!this.layout.wrapper)
			{
				this.layout.wrapper = BX.create('div', {
					props: {
						className: 'salescenter-crmform-panel-item'
					},
					children: [
						BX.create('div', {
							props: {
								className: 'salescenter-crmform-panel-item-content'
							},
							children: [
								this.getImage(),
								this.getTitle(),
								this.getStatus(),
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

			if (this.itemSelected)
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
						className: 'salescenter-crmform-panel-item-image'
					},
					style: {
						backgroundImage: this.image ? 'url(\'' + this.image + '\')' : null
					}
				});

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
					className: 'salescenter-crmform-panel-item-status-selected'
				}
			});

			return this.layout.itemSelected;
		},

		setSelected: function()
		{
			BX.addClass(this.layout.wrapper, 'salescenter-crmform-panel-item-selected');

			if(this.itemSelectedImage)
			{
				this.layout.image.style.backgroundImage = 'url(' + this.itemSelectedImage + ')';
			}

			if(this.itemSelectedColor)
			{
				this.layout.wrapper.style.backgroundColor = this.itemSelectedColor;
			}
		},

		getTitle: function()
		{
			if(!this.layout.title)
			{
				this.layout.title = BX.create('div', {
					props: {
						className: 'salescenter-crmform-panel-item-title'
					},
					text: this.title
				});

				if(this.itemSelected)
				{
					this.layout.title.style.color = '#fff';
				}
			}

			return this.layout.title;
		},

		onClick: function()
		{
			if (this.data.menu)
			{
				if (!this.contextMenu)
				{
					var opensInNewTab = this.data.menu.map(function(item)
					{
						if (item.link)
						{
							item.onclick = function (event, item)
							{
								item.getMenuWindow().close();
								window.open(item.link, '_blank');
							};
						}

						return item;
					});

					this.contextMenu = new BX.PopupMenuWindow({
						bindElement: this.layout.wrapper,
						items: opensInNewTab,
						offsetTop: 3
					});
				}

				this.contextMenu.show();
			}
		},
	};
})();
