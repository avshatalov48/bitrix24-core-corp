if(typeof(BX.CrmDealCategoryPanel) === "undefined")
{
	BX.CrmDealCategoryPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._nodes = null;
		this._button = null;
		this._menuId = "";
		this._menu = null;
	};
	BX.CrmDealCategoryPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			var containerId = this.getSetting("containerId", "");
			this._container = BX.type.isNotEmptyString(containerId) ? BX(containerId) : null;
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmDealCategoryPanel: Container is not found.";
			}
			this._nodes = this._container.getElementsByClassName("crm-deal-panel-tab-item");
			this._button = this._container.getElementsByClassName("crm-deal-panel-tab-item-show-more")[0];

			BX.bind(this._button, "click", this.onButtonClick.bind(this));
			BX.bind(window, "resize", this.adjust.bind(this));

			this._menuId = this._id;

			this.adjust();
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		adjust: function()
		{
			this.closeMenu();

			var enableMenu = false;
			var tail = null;
			for(var i = this._nodes.length - 1; i >= 0; i--)
			{
				var n = this._nodes[i];
				if(n.offsetTop === 0)
				{
					tail = n;
					break;
				}
				else if(!enableMenu)
				{
					enableMenu = true;
				}
			}

			if(!enableMenu)
			{
				this._button.style.display = "none";
			}
			else
			{
				this._button.style.display = "block";
				if(tail)
				{
					this._button.style.left = tail.offsetLeft + tail.offsetWidth + 20 + "px";
				}
			}
		},
		onButtonClick: function(e)
		{
			this.openMenu();
		},
		openMenu: function()
		{
			this.closeMenu();

			var menuItems = [];
			for (var i = 0, l = this._nodes.length; i < l; i++)
			{
				var n = this._nodes[i];
				if (n.offsetTop > 0)
				{
					menuItems.push({ text: n.innerHTML, className : n.className , href: n.getAttribute("href") });
				}
			}

			this._menu = BX.PopupMenu.create(
				this._menuId,
				this._button,
				menuItems,
				{ offsetTop: -20, offsetLeft: 13, angle: true, autoHide: true, closeByEsc: true }
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
	BX.CrmDealCategoryPanel.create = function(id, settings)
	{
		var self = new BX.CrmDealCategoryPanel();
		self.initialize(id, settings);
		return self;
	};
}