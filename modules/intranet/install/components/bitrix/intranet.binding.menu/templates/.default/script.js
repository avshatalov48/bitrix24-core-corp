;(function () {

	'use strict';

	BX.namespace('BX.Intranet.Binding.Menu');

	BX.Intranet.Binding.Menu = function(id, items, params)
	{
		params = params || {};
		this.id = id || 'intranet_binding_menu';
		this.idTop = this.id + '_top';
		this.items = items;
		this.menu = null;
		this.menuShowed = false;
		this.sections = params.sections || {};
		this.frequencyItem = params.frequencyItem || {};
		this.ajaxPath = params.ajaxPath || '';
		this.bindingId = params.bindingId || '';
	};

	BX.Intranet.Binding.Menu.prototype =
	{
		/**
		 * Bind on button click.
		 */
		binding: function()
		{
			if (BX(this.id))
			{
				BX.bind(BX(this.id), 'click', BX.delegate(this.clickMenuButton, this));
				if (BX(this.idTop) && BX(this.idTop).getAttribute('href') === '#')
				{
					BX.bind(BX(this.idTop), 'click', BX.delegate(this.clickMenuButton, this));
				}
			}
		},

		/**
		 * Event on item click.
		 * @param {String} id Item id.
		 * @param {String} onclick String action for onclick argument.
		 */
		onItemClick: function(id, onclick)
		{
			this.menu.close();
			if (BX.type.isString(onclick))
			{
				eval(onclick);
			}
			BX.ajax({
				url: this.ajaxPath,
				method: 'POST',
				data: {
					action: 'openingLog',
					payload: {
						bindingId: this.bindingId,
						menuItemId: id
					},
					sessid: BX.message('bitrix_sessid')
				},
				dataType: 'json',
				onsuccess: function(data)
				{
				}.bind(this)
			});
		},

		/**
		 * Groups menu items with section codes.
		 * @param {[]} items Menu items array.
		 * @return {[]}
		 */
		buildMenu: function(items)
		{
			var newItems = [];
			for (var i = 0, c = items.length; i < c; i++)
			{
				items[i]['text'] = items[i]['text'];
				if (typeof items[i]['items'] !== 'undefined')
				{
					items[i]['items'] = this.buildMenu(items[i]['items']);
				}
				else if (!items[i]['system'])
				{
					items[i]['onclick'] = this.onItemClick.bind(
						this, items[i]['id'],
						items[i]['onclick']
					);
				}
				newItems.push(items[i]);
			}
			return newItems;
		},

		/**
		 * Handler on menu click.
		 */
		clickMenuButton: function(event)
		{
			if (!this.menu)
			{
				this.menu = new BX.PopupMenuWindow(
					this.id,
					(event.target === BX(this.idTop))
					? BX(this.idTop)
					: BX(this.id),
					this.buildMenu(this.items),
					{
						autoHide: true,
						events: {
							onClose: function()
							{
								this.menuShowed = false;
								if (event && event.target)
								{
									event.target.blur();
								}
							}.bind(this)
						}
					}
				);
			}

			if (this.menu)
			{
				if (this.menuShowed)
				{
					this.menu.close();
				}
				else
				{
					this.menuShowed = true;
					this.menu.show();
				}
			}

			BX.PreventDefault();
		}
	};


})();
