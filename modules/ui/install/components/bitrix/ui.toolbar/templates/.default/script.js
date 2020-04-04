;(function () {
	'use strict';

	BX.namespace('BX.UI');

	BX.UI.Toolbar = function(options, target)
	{
		options = BX.type.isPlainObject(options) ? options : {};

		this.titleMinWidth = BX.type.isNumber(options.titleMinWidth) ? options.titleMinWidth : 158;
		this.titleMaxWidth = BX.type.isNumber(options.titleMaxWidth) ? options.titleMaxWidth : '';

		this.filterMinWidth = BX.type.isNumber(options.filterMinWidth) ? options.filterMinWidth : 300;
		this.filterMaxWidth = BX.type.isNumber(options.filterMaxWidth) ? options.filterMaxWidth : 748;

		this.toolbarContainer = target;
		// this.toolbarContainer = document.getElementById('uiToolbarContainer');
		this.titleContainer = this.toolbarContainer.querySelector('.ui-toolbar-title-box');
		this.filterContainer = this.toolbarContainer.querySelector('.ui-toolbar-filter-box');
		this.buttonContainer = this.toolbarContainer.querySelector('.ui-toolbar-btn-box');

		if (!this.filterContainer)
		{
			this.filterMinWidth = 0;
			this.filterMaxWidth = 0;
		}

		if(!this.buttonContainer)
		{
			return;
		}

		this.items = this.toolbarContainer.querySelectorAll('.ui-btn, .ui-btn-split');
		this.buttonIds = options.buttonIds || [];

		this.windowWidth = document.body.offsetWidth;

		this.setItemsOriginalWidth();
		this.reduceItemsWidth();

		window.addEventListener('resize', function() {
			if (this.isWindowIncreased())
			{
				this.increaseItemsWidth();
			}
			else
			{
				this.reduceItemsWidth();
			}

		}.bind(this));
	};

	BX.UI.Toolbar.prototype =
	{
		getButtons: function()
		{
			return this.buttonIds.map(function(id){
				return BX.UI.ButtonManager.getByUniqid(id);
			})
		},

		isWindowIncreased: function()
		{
			var previousWindowWidth = this.windowWidth;
			var currentWindowWidth = document.body.offsetWidth;
			this.windowWidth = currentWindowWidth;

			return currentWindowWidth > previousWindowWidth;
		},

		getContainerSize: function()
		{
			return this.toolbarContainer.offsetWidth;
		},

		getInnerTotalWidth: function()
		{
			return (
				(this.titleContainer ? this.titleContainer.offsetWidth : 0)+
				(this.filterContainer ? this.filterContainer.offsetWidth : 0) +
				this.buttonContainer.offsetWidth
			);
		},

		setItemsOriginalWidth: function()
		{
			for (var i = 0; i < this.items.length; i++)
			{
				this.items[i].originalWidth = this.items[i].offsetWidth;
			}
		},

		reduceItemsWidth: function()
		{
			var userAgent = navigator.userAgent.toLowerCase();

			if (userAgent.indexOf('safari') !== -1 || userAgent.indexOf('firefox') !== -1 || userAgent.indexOf('MSIE') !== -1)
			{
				if (userAgent.indexOf('chrome') > -1)
				{
					if (this.getInnerTotalWidth() <= this.getContainerSize())
					{
						return;
					}
				}
				else
				{
					if (this.getInnerTotalWidth() <= this.getContainerSize() + 1)
					{
						return;
					}
				}
			}

			for (var i = this.items.length - 1; i >= 0; i--)
			{
				var item = this.items[i];

				if (userAgent.indexOf('safari') !== -1)
				{
					if (userAgent.indexOf('chrome') > -1)
					{
						if (this.getInnerTotalWidth() <= this.getContainerSize())
						{
							break;
						}
					}
					else
					{
						if (this.getInnerTotalWidth() <= this.getContainerSize() + 1)
						{
							break;
						}
					}
				}

				if (!item.classList.contains('ui-toolbar-btn-minimize'))
				{
					item.classList.add('ui-toolbar-btn-minimize', 'ui-btn-empty');

					if (item.classList.contains('ui-btn-primary') && !item.classList.contains('ui-btn-dropdown'))
					{
						item.classList.add('ui-btn-icon-add');
					}

					if (item.classList.contains('ui-toolbar-btn-count'))
					{
						item.classList.add('ui-btn-icon-list');
					}

					if (item.classList.contains('ui-toolbar-btn-dropdown'))
					{
						item.classList.add('ui-btn-icon-page');
					}
				}
			}
		},

		increaseItemsWidth: function()
		{
			for (var i = 0; i < this.items.length; i++)
			{
				var item = this.items[i];
				var itemsWidth = this.calculateItemsWidth();

				if (!item.classList.contains('ui-toolbar-btn-minimize'))
				{
					continue;
				}

				var newInnerWidth = (
					this.titleMinWidth + this.filterMinWidth + itemsWidth + (item.originalWidth - item.offsetWidth)
				);

				var containerWidth = this.getContainerSize();

				if (newInnerWidth > containerWidth)
				{
					break;
				}

				item.classList.remove('ui-toolbar-btn-minimize', 'ui-btn-empty');

				if (item.classList.contains('ui-btn-primary') && !item.classList.contains('ui-btn-dropdown'))
				{
					item.classList.remove('ui-btn-icon-add');
				}

				if (item.classList.contains('ui-toolbar-btn-count'))
				{
					item.classList.remove('ui-btn-icon-list', 'ui-btn-empty');
				}

				if (item.classList.contains('ui-toolbar-btn-dropdown'))
				{
					item.classList.remove('ui-btn-icon-page', 'ui-btn-empty');
				}
			}
		},

		calculateItemsWidth: function()
		{
			var itemTotalWidth = 0;

			for (var i = this.items.length - 1; i >= 0; i--)
			{
				var itemStyle = window.getComputedStyle(this.items[i]);
				var itemWidth = this.items[i].offsetWidth;
				var itemMarginLeft = parseInt(itemStyle.marginLeft, 10);

				itemTotalWidth += itemWidth + itemMarginLeft;
			}

			return itemTotalWidth;
		}
	};
})();
