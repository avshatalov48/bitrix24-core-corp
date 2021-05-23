if(typeof(BX.InterfaceToolBar) === "undefined")
{
	BX.InterfaceToolBar = function()
	{
		this._id = "";
		this._settings = null;
		this._container = null;
		this._menuButton = null;
		this._menuPopup = null;
		this._isMenuOpened = false;
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
				var btnClassName = this.getSetting("menuButtonClassName");
				if(!BX.type.isNotEmptyString(btnClassName))
				{
					btnClassName = this.getSetting("moreButtonClassName", "crm-setting-btn");
				}
				if(BX.type.isNotEmptyString(btnClassName))
				{
					this._menuButton = BX.findChild(container, { "className": btnClassName }, true, false);
					if(this._menuButton)
					{
						BX.bind(this._menuButton, 'click', BX.delegate(this.onMenuButtonClick, this));
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
		prepareMenuItem: function(item)
		{
			var hdlrRx1 = /return\s+false(\s*;)?\s*$/;
			var hdlrRx2 = /;\s*$/;

			var isSeparator = typeof(item["SEPARATOR"]) !== "undefined" ? item["SEPARATOR"] : false;
			if(isSeparator)
			{
				return { delimiter: true };
			}

			var link = typeof(item["LINK"]) !== "undefined" ? item["LINK"] : "";
			var hdlr = typeof(item["ONCLICK"]) !== "undefined" ? item["ONCLICK"] : "";

			if(link !== "")
			{
				var s = "window.top.location.href = \"" + link + "\";";
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

			var result =
				{
					text:  typeof(item["TEXT"]) !== "undefined" ? item["TEXT"] : "",
					className: "menu-popup-item-none"
				};

			if(hdlr !== "")
			{
				result["onclick"] = hdlr;
			}

			if(BX.type.isArray(item["MENU"]))
			{
				var subMenuItems = [];
				for(var i = 0, l = item["MENU"].length; i < l; i++)
				{
					subMenuItems.push(this.prepareMenuItem(item["MENU"][i]));
				}
				result["items"] = subMenuItems;
			}

			return result;
		},
		openMenu: function(e)
		{
			if(this._isMenuOpened)
			{
				this.closeMenu();
				return;
			}

			var items = this.getSetting('items', null);
			if(!BX.type.isArray(items))
			{
				return;
			}

			var menuItems = [];
			for(var i = 0; i < items.length; i++)
			{
				menuItems.push(this.prepareMenuItem(items[i]));
			}
			BX.onCustomEvent(window, "Crm.InterfaceToolbar.MenuBuild", [ this, { items: menuItems } ]);

			this._menuId = this._id.toLowerCase() + "_menu";
			BX.PopupMenu.show(
				this._menuId,
				this._menuButton,
				menuItems,
				{
					autoHide: true,
					closeByEsc: true,
					offsetTop: 0,
					offsetLeft: 0,
					events:
						{
							onPopupShow: BX.delegate(this.onPopupShow, this),
							onPopupClose: BX.delegate(this.onPopupClose, this),
							onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
						}
				}
			);
			this._menuPopup = BX.PopupMenu.currentItem;
		},
		closeMenu: function()
		{
			if(this._menuPopup)
			{
				if(this._menuPopup.popupWindow)
				{
					this._menuPopup.popupWindow.destroy();
				}
			}
		},
		onMenuButtonClick: function(e)
		{
			this.openMenu();
		},
		onPopupShow: function()
		{
			this._isMenuOpened = true;
		},
		onPopupClose: function()
		{
			this.closeMenu();
		},
		onPopupDestroy: function()
		{
			this._isMenuOpened = false;
			this._menuPopup = null;

			if(typeof(BX.PopupMenu.Data[this._menuId]) !== "undefined")
			{
				delete(BX.PopupMenu.Data[this._menuId]);
			}
		},
		onEditorConfigReset: function()
		{
			var editor = BX.Crm.EntityEditor.getDefault();
			if(editor)
			{
				editor.resetConfig();
			}
		}
	};

	BX.InterfaceToolBar.create = function(id, settings)
	{
		var self = new BX.InterfaceToolBar();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.InterfaceToolBarCommunicationButton) === "undefined")
{
	BX.InterfaceToolBarCommunicationButton = function()
	{
		this._id = "";
		this._settings = [];
		this._button = null;
		this._ownerInfo = null;
		this._isMenuOpened = false;
		this._menuPopup = null;
		this._menuId = "";
		this._data = null;
		this._isEnabled = false;
	};

	BX.InterfaceToolBarCommunicationButton.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._button = BX.prop.getElementNode(this._settings, "button");
			BX.bind(this._button, "click", BX.delegate(this.onButtonClick, this));

			this._ownerInfo = BX.prop.getObject(this._settings, "ownerInfo", {});
			this._data = BX.prop.getObject(this._settings, "data", {});

			this._isEnabled = this.hasData();

			BX.addCustomEvent(window, "onCrmEntityUpdate", BX.delegate(this.onCrmEntityUpdate, this));
		},
		getOwnerInfo: function()
		{
			return(
				{
					ownerID: this._ownerInfo["ENTITY_ID"],
					ownerType: this._ownerInfo["ENTITY_TYPE_NAME"],
					ownerUrl: this._ownerInfo["SHOW_URL"],
					ownerTitle: this._ownerInfo["TITLE"]
				}
			);
		},
		getOwnerTypeName: function()
		{
			return BX.prop.getString(this._ownerInfo, "ENTITY_TYPE_NAME", "");
		},
		getOwnerId: function()
		{
			return BX.prop.getInteger(this._ownerInfo, "ENTITY_ID", 0);
		},
		getMultifieldTypeName: function()
		{
			return "";
		},
		hasData: function()
		{
			return BX.type.isPlainObject(this._data) && Object.keys(this._data).length > 0;
		},
		isEnabled: function()
		{
			return this._isEnabled;
		},
		enable: function(enabled)
		{
			enabled = !!enabled;
			if(this._isEnabled === enabled)
			{
				return;
			}

			this._isEnabled = enabled;
			this.doEnable(this._isEnabled);
		},
		doEnable: function(enabled)
		{
		},
		onButtonClick: function(e)
		{
		},
		prepareMenuItem: function(item)
		{
		},
		openMenu: function()
		{
			if(this._isMenuOpened)
			{
				this.closeMenu();
				return;
			}

			var menuItems = [];
			for(var key in this._data)
			{
				if(!this._data.hasOwnProperty(key))
				{
					continue;
				}

				var items = this._data[key];
				for(var i = 0; i < items.length; i++)
				{
					menuItems.push(this.prepareMenuItem(key, items[i]));
				}
			}

			this._menuId = this._id.toLowerCase() + "_menu";

			BX.PopupMenu.show(
				this._menuId,
				this._button,
				menuItems,
				{
					"offsetTop": 0,
					"offsetLeft": 0,
					"events":
						{
							"onPopupShow": BX.delegate(this.onPopupShow, this),
							"onPopupClose": BX.delegate(this.onPopupClose, this),
							"onPopupDestroy": BX.delegate(this.onPopupDestroy, this)
						}
				}
			);
			this._menuPopup = BX.PopupMenu.currentItem;
		},
		closeMenu: function()
		{
			if(this._menuPopup)
			{
				if(this._menuPopup.popupWindow)
				{
					this._menuPopup.popupWindow.destroy();
				}
			}
		},
		onPopupShow: function()
		{
			this._isMenuOpened = true;
		},
		onPopupClose: function()
		{
			this.closeMenu();
		},
		onPopupDestroy: function()
		{
			this._isMenuOpened = false;
			this._menuPopup = null;

			if(typeof(BX.PopupMenu.Data[this._menuId]) !== "undefined")
			{
				delete(BX.PopupMenu.Data[this._menuId]);
			}
		},
		onCrmEntityUpdate: function(eventArgs)
		{
			var entityInfo = BX.prop.getObject(eventArgs, "entityInfo", {});
			if(this.getOwnerTypeName() !== BX.prop.getString(entityInfo, "typeName", "")
				|| this.getOwnerId() !== BX.prop.getInteger(entityInfo, "id", 0)
			)
			{
				return;
			}

			var entityData = BX.prop.getObject(eventArgs, "entityData", {});
			this._data = BX.prop.getObject(BX.prop.getObject(entityData, "MULTIFIELD_DATA", {}), this.getMultifieldTypeName(), {});

			this.enable(this.hasData());
			this.processDataChange();
		},
		processDataChange: function()
		{
		}
	};
}

if(typeof(BX.InterfaceToolBarPhoneButton) === "undefined")
{
	BX.InterfaceToolBarPhoneButton = function()
	{
		BX.InterfaceToolBarPhoneButton.superclass.constructor.apply(this);
		this._menuItems = null;
	};
	BX.extend(BX.InterfaceToolBarPhoneButton, BX.InterfaceToolBarCommunicationButton);
	BX.InterfaceToolBarPhoneButton.prototype.getMessage = function(name)
	{
		var m = BX.InterfaceToolBarPhoneButton.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.InterfaceToolBarPhoneButton.prototype.onButtonClick = function(e)
	{
		if(!this.isEnabled())
		{
			return;
		}

		var keys = Object.keys(this._data);
		if(keys.length === 1)
		{
			var firstKey = keys[0];
			var items = this._data[firstKey];
			if(items.length === 1)
			{
				var parts = firstKey.split("_");
				if(parts.length >= 2)
				{
					this.addCall(firstKey, items[0]);
					return;
				}
			}
		}

		this._menuItems = [];
		this.openMenu();
	};
	BX.InterfaceToolBarPhoneButton.prototype.prepareMenuItem = function(key, value)
	{
		var	phoneText;
		var phoneValue;

		if(BX.type.isPlainObject(value))
		{
			phoneText = BX.prop.getString(value, 'COMPLEX_NAME', '') + ': ' + BX.prop.getString(value, 'VALUE_FORMATTED', '');
			phoneValue = BX.prop.getString(value, 'VALUE', '');
		}
		else
		{
			phoneText = value;
			phoneValue = value;
		}

		var menuItem = BX.InterfaceToolBarPhoneMenuItem.create(
			{
				owner: this,
				entityKey: key,
				value: phoneValue,
				text: phoneText
			}
		);
		this._menuItems.push(menuItem);
		return menuItem.createMenuItem();
	};
	BX.InterfaceToolBarPhoneButton.prototype.addCall = function(entityKey, phone)
	{
		if(typeof(window.top['BXIM']) === 'undefined')
		{
			window.alert(this.getMessage("telephonyNotSupported"));
			return;
		}

		var parts = entityKey.split("_");
		if(parts.length < 2)
		{
			return;
		}

		var entityTypeId = BX.type.stringToInt(parts[0]);
		var entityId = BX.type.stringToInt(parts[1]);

		var ownerTypeId = BX.prop.getInteger(this._ownerInfo, "ENTITY_TYPE_ID", 0);
		var ownerId = BX.prop.getInteger(this._ownerInfo, "ENTITY_ID", 0);

		var phoneValue = BX.type.isPlainObject(phone) ? phone['VALUE'] : phone;

		var params =
			{
				"ENTITY_TYPE_NAME": BX.CrmEntityType.resolveName(entityTypeId),
				"ENTITY_ID": entityId,
				"AUTO_FOLD": true
			};
		if(ownerTypeId !== entityTypeId || ownerId !== entityId)
		{
			params["BINDINGS"] = [ { "OWNER_TYPE_NAME": BX.CrmEntityType.resolveName(ownerTypeId), "OWNER_ID": ownerId } ];
		}

		window.top['BXIM'].phoneTo(phoneValue, params);
	};
	BX.InterfaceToolBarPhoneButton.prototype.getMultifieldTypeName = function()
	{
		return "PHONE";
	};
	BX.InterfaceToolBarPhoneButton.prototype.doEnable = function(enabled)
	{
		if(enabled)
		{
			BX.removeClass(this._button, "crm-contact-menu-call-icon-not-available");
			BX.addClass(this._button, "crm-contact-menu-call-icon");
		}
		else
		{
			BX.removeClass(this._button, "crm-contact-menu-call-icon");
			BX.addClass(this._button, "crm-contact-menu-call-icon-not-available");
		}
	};
	if(typeof(BX.InterfaceToolBarPhoneButton.messages) === "undefined")
	{
		BX.InterfaceToolBarPhoneButton.messages = {};
	}
	BX.InterfaceToolBarPhoneButton.create = function(id, settings)
	{
		var self = new BX.InterfaceToolBarPhoneButton();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.InterfaceToolBarPhoneMenuItem) === "undefined")
{
	BX.InterfaceToolBarPhoneMenuItem = function()
	{
		this._settings = {};
		this._entityKey = "";
		this._value = "";
		this._text = "";
	};
	BX.InterfaceToolBarPhoneMenuItem.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._owner = BX.prop.get(this._settings, "owner");

			this._entityKey = BX.prop.getString(this._settings, "entityKey", "");
			this._value = BX.prop.getString(this._settings, "value", "");
			this._text = BX.prop.getString(this._settings, "text", "");
		},
		onSelect: function()
		{
			this._owner.addCall(this._entityKey, this._value);
		},
		createMenuItem: function()
		{
			return { text: this._text, onclick: BX.delegate(this.onSelect, this) };
		}
	};

	BX.InterfaceToolBarPhoneMenuItem.create = function(settings)
	{
		var self = new BX.InterfaceToolBarPhoneMenuItem();
		self.initialize(settings);
		return self;
	}
}

if(typeof(BX.InterfaceToolBarMessengerButton) === "undefined")
{
	BX.InterfaceToolBarMessengerButton = function()
	{
		BX.InterfaceToolBarMessengerButton.superclass.constructor.apply(this);
		this._menuItems = null;
	};
	BX.extend(BX.InterfaceToolBarMessengerButton, BX.InterfaceToolBarCommunicationButton);
	BX.InterfaceToolBarMessengerButton.prototype.getMessage = function(name)
	{
		var m = BX.InterfaceToolBarMessengerButton.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.InterfaceToolBarMessengerButton.prototype.onButtonClick = function(e)
	{
		var keys = Object.keys(this._data);
		if(keys.length === 1)
		{
			var firstKey = keys[0];
			var items = this._data[firstKey];
			if(items.length === 1)
			{
				var parts = firstKey.split("_");
				if(parts.length >= 2)
				{
					this.openChat(firstKey, items[0]);
					return;
				}
			}
		}

		this._menuItems = [];
		this.openMenu();
	};
	BX.InterfaceToolBarMessengerButton.prototype.prepareMenuItem = function(key, value)
	{
		var	messengerText;
		var messengerValue;

		if(BX.type.isPlainObject(value))
		{
			messengerValue = BX.prop.getString(value, "VALUE", "");
			var valueType = BX.prop.getString(value, "VALUE_TYPE", "");
			if(valueType === "OPENLINE")
			{
				//Open line does not have formatted value
				messengerText = BX.prop.getString(value, "COMPLEX_NAME", "");
			}
			else
			{
				messengerText = BX.prop.getString(value, "COMPLEX_NAME", "") + ": " + BX.prop.getString(value, "VALUE_FORMATTED", "");
			}
		}
		else
		{
			messengerText = value;
			messengerValue = value;
		}

		var menuItem = BX.InterfaceToolBarMessengerMenuItem.create(
			{
				owner: this,
				entityKey: key,
				value: messengerValue,
				text: messengerText
			}
		);
		this._menuItems.push(menuItem);
		return menuItem.createMenuItem();
	};
	BX.InterfaceToolBarMessengerButton.prototype.openChat = function(entityKey, messenger)
	{
		if(typeof(window.top["BXIM"]) === "undefined")
		{
			window.alert(this.getMessage("messagingNotSupported"));
			return;
		}
		var messengerValue = BX.type.isPlainObject(messenger) ? messenger["VALUE"] : messenger;
		window.top["BXIM"].openMessengerSlider(messengerValue, {RECENT: 'N', MENU: 'N'});
	};
	BX.InterfaceToolBarMessengerButton.prototype.getMultifieldTypeName = function()
	{
		return "IM";
	};
	BX.InterfaceToolBarMessengerButton.prototype.doEnable = function(enabled)
	{
		if(enabled)
		{
			BX.removeClass(this._button, "crm-contact-menu-im-icon-not-available");
			BX.addClass(this._button, "crm-contact-menu-im-icon");
		}
		else
		{
			BX.removeClass(this._button, "crm-contact-menu-im-icon");
			BX.addClass(this._button, "crm-contact-menu-im-icon-not-available");
		}
	};
	if(typeof(BX.InterfaceToolBarMessengerButton.messages) === "undefined")
	{
		BX.InterfaceToolBarMessengerButton.messages = {};
	}
	BX.InterfaceToolBarMessengerButton.create = function(id, settings)
	{
		var self = new BX.InterfaceToolBarMessengerButton();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.InterfaceToolBarMessengerMenuItem) === "undefined")
{
	BX.InterfaceToolBarMessengerMenuItem = function()
	{
		this._settings = {};
		this._entityKey = "";
		this._value = "";
		this._text = "";
	};
	BX.InterfaceToolBarMessengerMenuItem.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._owner = BX.prop.get(this._settings, "owner");

			this._entityKey = BX.prop.getString(this._settings, "entityKey", "");
			this._value = BX.prop.getString(this._settings, "value", "");
			this._text = BX.prop.getString(this._settings, "text", "");
		},
		onSelect: function()
		{
			this._owner.openChat(this._entityKey, this._value);
		},
		createMenuItem: function()
		{
			return { text: this._text, onclick: BX.delegate(this.onSelect, this) };
		}
	};

	BX.InterfaceToolBarMessengerMenuItem.create = function(settings)
	{
		var self = new BX.InterfaceToolBarMessengerMenuItem();
		self.initialize(settings);
		return self;
	}
}


if(typeof(BX.InterfaceToolBarEmailButton) === "undefined")
{
	BX.InterfaceToolBarEmailButton = function()
	{
		BX.InterfaceToolBarEmailButton.superclass.constructor.apply(this);
	};
	BX.extend(BX.InterfaceToolBarEmailButton, BX.InterfaceToolBarCommunicationButton);
	BX.InterfaceToolBarEmailButton.prototype.onButtonClick = function(e)
	{
		if(this.isEnabled())
		{
			BX.CrmActivityEditor.addEmail(this.getOwnerInfo());
		}
	};
	BX.InterfaceToolBarEmailButton.prototype.getMultifieldTypeName = function()
	{
		return "EMAIL";
	};
	BX.InterfaceToolBarEmailButton.prototype.doEnable = function(enabled)
	{
		if(enabled)
		{
			BX.removeClass(this._button, "crm-contact-menu-mail-icon-not-available");
			BX.addClass(this._button, "crm-contact-menu-mail-icon");
		}
		else
		{
			BX.removeClass(this._button, "crm-contact-menu-mail-icon");
			BX.addClass(this._button, "crm-contact-menu-mail-icon-not-available");
		}
	};
	BX.InterfaceToolBarEmailButton.create = function(id, settings)
	{
		var self = new BX.InterfaceToolBarEmailButton();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.InterfaceToolBarRestAppButton) === "undefined")
{
	BX.InterfaceToolBarRestAppButton = function()
	{
		this._id = "";
		this._settings = [];
		this._button = null;
		this._ownerInfo = null;
		this._isMenuOpened = false;
		this._menuPopup = null;
		this._menuId = "";
		this._items = null;
	};

	BX.InterfaceToolBarRestAppButton.prototype =
	{
		initialize: function (id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._button = BX.prop.getElementNode(this._settings, "button");
			BX.bind(this._button, "click", BX.delegate(this.onButtonClick, this));

			this._ownerInfo = BX.prop.getObject(this._settings, "ownerInfo", {});
			var data = BX.prop.getObject(this._settings, "data", {});

			this._items = [];
			var placementCode = BX.prop.getString(data, "PLACEMENT", "");
			var infos = BX.prop.getArray(data, "APP_INFOS", []);
			for(var i = 0, length = infos.length; i < length; i++)
			{
				var item = BX.InterfaceToolBarRestAppMenuItem.create(
					{
						owner: this,
						placementCode: placementCode,
						info: infos[i]
					}
				);
				this._items.push(item);
			}
		},
		getOwnerInfo: function()
		{
			return this._ownerInfo;
		},
		onButtonClick: function(e)
		{
			this.openMenu();
		},
		openMenu: function()
		{
			if(this._isMenuOpened)
			{
				this.closeMenu();
				return;
			}

			var menuItems = [];
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				menuItems.push(this._items[i].createMenuItem());
			}

			this._menuId = this._id.toLowerCase() + "_menu";

			BX.PopupMenu.show(
				this._menuId,
				this._button,
				menuItems,
				{
					"offsetTop": 0,
					"offsetLeft": 0,
					"events":
						{
							"onPopupShow": BX.delegate(this.onPopupShow, this),
							"onPopupClose": BX.delegate(this.onPopupClose, this),
							"onPopupDestroy": BX.delegate(this.onPopupDestroy, this)
						}
				}
			);
			this._menuPopup = BX.PopupMenu.currentItem;
		},
		closeMenu: function()
		{
			if(this._menuPopup)
			{
				if(this._menuPopup.popupWindow)
				{
					this._menuPopup.popupWindow.destroy();
				}
			}
		},
		onPopupShow: function()
		{
			this._isMenuOpened = true;
		},
		onPopupClose: function()
		{
			this.closeMenu();
		},
		onPopupDestroy: function()
		{
			this._isMenuOpened = false;
			this._menuPopup = null;

			if(typeof(BX.PopupMenu.Data[this._menuId]) !== "undefined")
			{
				delete(BX.PopupMenu.Data[this._menuId]);
			}
		}
	};
	BX.InterfaceToolBarRestAppButton.create = function(id, settings)
	{
		var self = new BX.InterfaceToolBarRestAppButton();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.InterfaceToolBarRestAppMenuItem) === "undefined")
{
	BX.InterfaceToolBarRestAppMenuItem = function()
	{
		this._settings = {};
		this._placementCode = "";
		this._appInfo = {};
	};
	BX.InterfaceToolBarRestAppMenuItem.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._owner = BX.prop.get(this._settings, "owner");

			this._placementCode = BX.prop.getString(this._settings, "placementCode", "");
			this._appInfo = BX.prop.getObject(this._settings, "info", {});
		},
		onSelect: function()
		{
			BX.rest.AppLayout.openApplication(
				BX.prop.getInteger(this._appInfo, "APP_ID"),
				{ ID: BX.prop.getInteger(this._owner.getOwnerInfo(), "ENTITY_ID") },
				{
					PLACEMENT: this._placementCode,
					PLACEMENT_ID:  BX.prop.getInteger(this._appInfo, "ID")
				}
			);
		},
		createMenuItem: function()
		{
			return { text: BX.prop.getString(this._appInfo, "TITLE"), onclick: BX.delegate(this.onSelect, this) };
		}
	};

	BX.InterfaceToolBarRestAppMenuItem.create = function(settings)
	{
		var self = new BX.InterfaceToolBarRestAppMenuItem();
		self.initialize(settings);
		return self;
	}
}