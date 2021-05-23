if(typeof(BX.CrmEntityListSwitcher) === "undefined")
{
	BX.CrmEntityListSwitcher = function()
	{
		this._id = "";
		this._settings = {};
		this._items = null;
		this._container = null;
		this._selectorButton = null;

		this._menuId = "";
		this._menu = null;
	};
	BX.CrmEntityListSwitcher.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._items = this.getSetting("items", []);

			var containerId = this.getSetting("containerId", "");
			this._container = BX.type.isNotEmptyString(containerId) ? BX(containerId) : null;
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmEntityListSwitcher: Container is not found.";
			}

			var selectorButtonId = this.getSetting("selectorButtonId", "");
			this._selectorButton = BX.type.isNotEmptyString(selectorButtonId) ? BX(selectorButtonId) : null;

			if(this._selectorButton)
			{
				BX.bind(this._selectorButton, "click", BX.delegate(this.onSelectorClick, this));
			}
			this._createUrl = this.getSetting("createUrl", "");
			this._createLockScript = this.getSetting("createLockScript", "");

			this._menuId = this._id;
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var m = BX.CrmEntityListSwitcher.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		onSelectorClick: function(e)
		{
			this.openMenu();
		},
		onCreateButtonClick: function(e)
		{
			this.createNewItem();
		},
		openMenu: function()
		{
			this.closeMenu();

			var menuItems = [];
			for (var i = 0, l = this._items.length; i < l; i++)
			{
				var item = this._items[i];
				menuItems.push({ text: item["name"], href: item["url"] });
			}

			this._menu = BX.PopupMenu.create(
				this._menuId,
				this._selectorButton,
				menuItems,
				{ autoHide: true, closeByEsc: true }
			);

			this._menu.popupWindow.show();
		},
		closeMenu: function()
		{
			if(this._menu)
			{
				BX.PopupMenu.destroy(this._menuId);
				this._menu = null;
			}
		}
	};

	if(typeof(BX.CrmEntityListSwitcher.messages) === "undefined")
	{
		BX.CrmEntityListSwitcher.messages = {};
	}

	BX.CrmEntityListSwitcher.create = function(id, settings)
	{
		var self = new BX.CrmEntityListSwitcher();
		self.initialize(id, settings);
		return self;
	};
}
