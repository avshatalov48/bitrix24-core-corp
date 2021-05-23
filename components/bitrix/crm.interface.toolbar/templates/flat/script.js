if(typeof(BX.InterfaceToolBar) === "undefined")
{
	BX.InterfaceToolBar = function()
	{
		this._id = "";
		this._settings = null;
		this._container = null;
		this._moreBtn = null;
	};

	BX.InterfaceToolBar.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			var container = this._container = BX(this.getSetting("containerId", ""));
			if(container)
			{
				var btnClassName = this.getSetting("moreButtonClassName", "crm-setting-btn");
				if(BX.type.isNotEmptyString(btnClassName))
				{
					var moreBtn = this._moreBtn = BX.findChild(container, { "className": btnClassName }, true, false).parentNode;
					if(moreBtn)
					{
						BX.bind(moreBtn, 'click', BX.delegate(this._onMoreButtonClick, this));
					}
				}
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		_onMenuClose: function()
		{
			var eventArgs = { menu: this._menu };
			BX.onCustomEvent(window, "CrmInterfaceToolbarMenuClose", [ this, eventArgs]);
		},
		_onMoreButtonClick: function(e)
		{
			var items = this.getSetting('items', null);
			if(!BX.type.isArray(items))
			{
				return;
			}

			var hdlrRx1 = /return\s+false(\s*;)?\s*$/;
			var hdlrRx2 = /;\s*$/;
			var menuItems = [];
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];

				var isSeparator = typeof(item["SEPARATOR"]) !== "undefined" ? item["SEPARATOR"] : false;
				if(isSeparator)
				{
					menuItems.push({ "SEPARATOR": true });
					continue;
				}

				var link = typeof(item["LINK"]) !== "undefined" ? item["LINK"] : "";
				var hdlr = typeof(item["ONCLICK"]) !== "undefined" ? item["ONCLICK"] : "";

				if(link !== "")
				{
					var s = "window.location.href = \"" + link + "\";";
					hdlr = hdlr !== "" ? (s + " " + hdlr) : s;
				}

				if(hdlr !== "")
				{
					if(!hdlrRx1.test(hdlr))
					{
						if(!hdlrRx2.test(hdlr))
						{
							hdlr += ";";
						}
						hdlr += " return false;";
					}
				}

				menuItems.push(
					{
						"TEXT":  typeof(item["TEXT"]) !== "undefined" ? item["TEXT"] : "",
						"TITLE":  typeof(item["TITLE"]) !== "undefined" ? item["TITLE"] : "",
						"ICONCLASS": item["ICON"] ? item["ICON"] : null,
						"ONCLICK": hdlr
					}
				);
			}

			this._menuId = this._id.toLowerCase() + "_menu";
			this._menu = new PopupMenu(this._menuId, 1010);

			/*var eventArgs = { menu: this._menu, items: [] };
			BX.onCustomEvent(window, "CrmInterfaceToolbarMenuShow", [ this, eventArgs]);

			if(eventArgs.items.length > 0)
			{
				menuItems.push({ "SEPARATOR": true });
				for(var j = 0; j < eventArgs.items.length; j++)
				{
					menuItems.push(eventArgs.items[j]);
				}
			}*/

			this._menu.ShowMenu(this._moreBtn, menuItems, false, false, BX.delegate(this._onMenuClose, this));
		}
	};

	BX.InterfaceToolBar.create = function(id, settings)
	{
		var self = new BX.InterfaceToolBar();
		self.initialize(id, settings);
		return self;
	};
}
