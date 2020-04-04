if(typeof(BX.InterfaceGridFilterNavigationBar) === "undefined")
{
	BX.InterfaceGridFilterNavigationBar = function()
	{
		this._id = "";
		this._settings = null;
		this._binding = null;
		this._items = null;
		this._activeItem = null;
	};
	BX.InterfaceGridFilterNavigationBar.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);

			this._binding = this.getSetting("binding", null);
			this._items = [];
			var items = this.getSetting("items", []);

			for(var i = 0; i < items.length; i++)
			{
				var itemSettings = items[i];
				var itemId = BX.type.isNotEmptyString(itemSettings["id"]) ? itemSettings["id"] : i;
				itemSettings["parent"] = this;
				var item = BX.InterfaceGridFilterNavigationBarItem.create(
					itemId,
					BX.CrmParamBag.create(itemSettings)
				);

				if(this._activeItem === null && item.isActive())
				{
					this._activeItem = item;
				}
				this._items.push(item);
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
		getBinding: function()
		{
			return this._binding;
		},
		processMenuItemClick: function(item)
		{
			if(!item.isActive())
			{
				item.openUrl();
			}
		}
	};
	BX.InterfaceGridFilterNavigationBar.create = function(id, settings)
	{
		var self = new BX.InterfaceGridFilterNavigationBar();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.InterfaceGridFilterNavigationBarItem) === "undefined")
{
	BX.InterfaceGridFilterNavigationBarItem = function()
	{
		this._id = "";
		this._settings = null;
		this._parent = null;
		this._element = null;
		this._name = "";
		this._isActive = false;
		this._elementClickHandler = BX.delegate(this.onElementClick, this);
	};
	BX.InterfaceGridFilterNavigationBarItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._name = this.getSetting("name", "");
			this._parent = this.getSetting("parent");
			if(!this._parent)
			{
				throw "InterfaceGridFilterNavigationBarItem: The parameter 'parent' is not found.";
			}

			this._element = BX(this.getSetting("elementId"));
			if(!BX.type.isElementNode(this._element))
			{
				throw "InterfaceGridFilterNavigationBarItem: The parameter 'element' is not found.";
			}
			BX.bind(this._element, "click", this._elementClickHandler);
			this._isActive = this.getSetting("active", false);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		getName: function()
		{
			return this._name;
		},
		getElement: function()
		{
			return this._element;
		},
		isActive: function()
		{
			return this._isActive;
		},
		openUrl: function()
		{
			var url = this.getSetting("url", "");
			if(url === "")
			{
				return;
			}

			var binding = this._parent.getBinding();
			if(binding)
			{
				var category = BX.type.isNotEmptyString(binding["category"]) ? binding["category"] : "";
				var name = BX.type.isNotEmptyString(binding["name"]) ? binding["name"] : "";
				var key = BX.type.isNotEmptyString(binding["key"]) ? binding["key"] : "";

				if(category !== "" && name !== "" && key !== "")
				{
					var date = new Date();
					var y = date.getFullYear().toString();
					var m = date.getMonth() + 1;
					m = m >= 10 ? m.toString() : "0" + m.toString();
					var d = date.getDate();
					d = d >= 10 ? d.toString() : "0" + d.toString();

					var value = this._id + ":" + y + m + d;
					BX.userOptions.save(category, name, key, value, false);
				}
			}
			setTimeout(function(){ window.location.href = url; }, 150);
		},
		onElementClick: function(e)
		{
			this._parent.processMenuItemClick(this);
		}
	};
	BX.InterfaceGridFilterNavigationBarItem.create = function(id, settings)
	{
		var self = new BX.InterfaceGridFilterNavigationBarItem();
		self.initialize(id, settings);
		return self;
	};
}