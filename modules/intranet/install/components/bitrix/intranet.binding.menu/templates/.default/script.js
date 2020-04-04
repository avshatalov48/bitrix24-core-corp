;(function () {

	'use strict';

	BX.namespace('BX.Intranet.Binding.Menu');

	BX.Intranet.Binding.Menu = function(id, items, itemsAdditional)
	{
		this.id = id || 'intranet_binding_menu';
		this.idAdditional = this.id + '_additional';
		this.items = items;
		this.itemsAdditional = itemsAdditional;
		this.menu = null;
		this.menuAdditional = null;
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
			}
			if (BX(this.idAdditional))
			{
				BX.bind(BX(this.idAdditional), 'click', BX.delegate(this.clickMenuAdditionalButton, this));
			}
		},

		/**
		 * Handler on main menu click.
		 */
		clickMenuButton: function()
		{
			if (!this.menu)
			{
				this.menu = new BX.PopupMenuWindow(
					this.id,
					BX(this.id),
					this.items,
					{
						autoHide: true,
						angle: true,
						offsetLeft: 50
					}
				);
			}

			if (this.menu)
			{
				this.menu.show();
			}
		},

		/**
		 * Handler on additional menu click.
		 */
		clickMenuAdditionalButton: function()
		{
			if (!this.menuAdditional)
			{
				this.menuAdditional = new BX.PopupMenuWindow(
					this.idAdditional,
					BX(this.idAdditional),
					this.itemsAdditional,
					{
						autoHide: true,
						angle: true,
						offsetLeft: 50
					}
				);
			}

			if (this.menuAdditional)
			{
				this.menuAdditional.show();
			}
		}
	};


})();
