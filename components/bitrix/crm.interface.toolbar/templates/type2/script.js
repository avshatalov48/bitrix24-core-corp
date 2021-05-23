if(typeof(BX.InterfaceToolBar) === "undefined")
{
	BX.InterfaceToolBar = function()
	{
		this._id = "";
		this._settings = null;
		this._container = null;
		this._moreBtn = null;
		this._prefix = "";
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
				var btnClassName = this.getSetting("moreButtonClassName", "");
				if(BX.type.isNotEmptyString(btnClassName))
				{
					var moreBtn = this._moreBtn = BX.findChild(container, { "className": btnClassName }, true, false);
					if(moreBtn)
					{
						BX.bind(moreBtn, 'click', BX.delegate(this._onMoreButtonClick, this));
					}
				}
			}

			this._prefix = this.getSetting("prefix", "");
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		setButtonVisible: function(code, visible)
		{
			var button = this._getButtonByCode(code);
			if(BX.type.isDomNode(button))
			{
				button.style.display = visible ? "" : "none";
			}
		},
		_getButtonByCode: function(code)
		{
			return BX(this._prefix !== "" ? (this._prefix + code) : code);
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

			this._menuId = this._id.toLowerCase();
			var menuItems = [];
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];

				var isSeparator = typeof(item["SEPARATOR"]) !== "undefined" ? item["SEPARATOR"] : false;
				if(isSeparator)
				{
					menuItems.push({"delimiter": true });
					continue;
				}

				var hdlr = typeof(item["ONCLICK"]) !== "undefined" ? item["ONCLICK"] : "";
				if(BX.type.isNotEmptyString(hdlr))
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
						"text":  typeof(item["TEXT"]) !== "undefined" ? item["TEXT"] : "",
						"title":  typeof(item["TITLE"]) !== "undefined" ? item["TITLE"] : "",
						"href" : typeof(item["LINK"]) !== "undefined" ? item["LINK"] : "#",
						"onclick": hdlr
					}
				);
			}

			if(typeof(BX.PopupMenu.Data[this._menuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._menuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._menuId];
			}

			var btnPos = BX.pos(this._moreBtn);
			this._menu = BX.PopupMenu.show(
				this._menuId,
				this._moreBtn,
				menuItems,
				{
					"offsetTop": Math.round(btnPos.height / 8),
					"offsetLeft": Math.round(btnPos.width / 2),
					"angle": { "position": "top", "offset": 0 }
				}
			);

			return BX.PreventDefault(e);
		}
	};

	BX.InterfaceToolBar.items = {};
	BX.InterfaceToolBar.create = function(id, settings)
	{
		var self = new BX.InterfaceToolBar();
		self.initialize(id, settings);
		this.items[id] = self;

		return self;
	};
}
