if(typeof(BX.CrmDealCategoryTinyPanel) === "undefined")
{
	BX.CrmDealCategoryTinyPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._items = null;
		this._container = null;
		this._selectorButton = null;
		this._isCustomized = false;

		this._counterId = '';
		this._counterContainer = '';
		this._counterMap = null;

		this._enableCreation = false;
		this._createUrl = "";
		this._createLockScript = "";

		this._nodes = null;
		this._button = null;
		this._menuId = "";
		this._menu = null;
	};
	BX.CrmDealCategoryTinyPanel.prototype =
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
				throw "BX.CrmDealCategoryTinyPanel: Container is not found.";
			}

			var selectorButtonId = this.getSetting("selectorButtonId", "");
			this._selectorButton = BX.type.isNotEmptyString(selectorButtonId) ? BX(selectorButtonId) : null;
			if(this._selectorButton)
			{
				BX.bind(this._selectorButton, "click", BX.delegate(this.onSelectorClick, this));
			}

			this._isCustomized = this.getSetting("isCustomized", false);
			this._enableCreation = this.getSetting("enableCreation", false);
			this._createUrl = this.getSetting("createUrl", "");
			this._createLockScript = this.getSetting("createLockScript", "");

			this._menuId = this._id;

			this._counterId = this.getSetting("counterId", "");
			this._counterContainer = BX(this.getSetting("counterContainerId", ""));
			if(this._counterId !== "" && BX.type.isElementNode(this._counterContainer))
			{
				BX.addCustomEvent("onPullEvent-main", BX.delegate(this.onPullEvent, this));
			}

			this._counterMap = {};
			for (var i = 0, l = this._items.length; i < l; i++)
			{
				var item = this._items[i];
				var itemId = BX.prop.getNumber(item, "ID", 0);
				var itemCounterCode = BX.prop.getString(item, "COUNTER_CODE", "");
				if(itemCounterCode !== "")
				{
					this._counterMap[itemCounterCode] = itemId;
				}
			}
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
			var m = BX.CrmDealCategoryTinyPanel.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getItemById: function(id)
		{
			id = BX.convert.toNumber(id);
			for (var i = 0, l = this._items.length; i < l; i++)
			{
				var item = this._items[i];
				if(id === BX.prop.getNumber(item, "ID", -1))
				{
					return item;
				}
			}
			return null;
		},
		createNewItem: function()
		{
			if(this._enableCreation && this._createUrl !== "")
			{
				window.location.href = this._createUrl;
			}
			else if(this._createLockScript !== "")
			{
				eval(this._createLockScript);
			}
		},
		onSelectorClick: function(e)
		{
			if(this._isCustomized)
			{
				this.openMenu();
			}
			else
			{
				this.createNewItem();
			}
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

				var html = "<span class=\"main-buttons-item-text\">" + item["NAME"] + "</span>";

				var counter = BX.prop.getInteger(item, "COUNTER", 0);
				if(counter > 0)
				{
					html += "</span> <span class=\"main-buttons-item-counter\">" + counter + "</span>";
				}
				menuItems.push({ text: html, href: item["URL"] });
			}

			if(this._enableCreation)
			{
				menuItems.push({ delimiter: true });
				menuItems.push({ text: this.getMessage("create"), onclick: BX.delegate(this.onCreateButtonClick, this) });
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
		},
		onPullEvent: function(command, params)
		{
			if (command !== "user_counter")
			{
				return;
			}

			var data = BX.prop.getObject(params, BX.message("SITE_ID"), {});
			for (var code in data)
			{
				if(!data.hasOwnProperty(code))
				{
					continue;
				}

				var counterValue = BX.convert.toNumber(data[code]);
				if(counterValue < 0)
				{
					continue;
				}

				if(this._counterId === code)
				{
					this._counterContainer.innerHTML = counterValue;
				}

				var itemId = BX.prop.getNumber(this._counterMap, code, -1);
				if(itemId >= 0)
				{
					var item = this.getItemById(itemId);
					if(item)
					{
						item["COUNTER"] = counterValue;
					}
				}
			}
		}
	};

	if(typeof(BX.CrmDealCategoryTinyPanel.messages) === "undefined")
	{
		BX.CrmDealCategoryTinyPanel.messages = {};
	}

	BX.CrmDealCategoryTinyPanel.create = function(id, settings)
	{
		var self = new BX.CrmDealCategoryTinyPanel();
		self.initialize(id, settings);
		return self;
	};
}
