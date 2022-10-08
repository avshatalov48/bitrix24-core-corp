import Activity from "./activity";

/** @memberof BX.Crm.Timeline.Actions */
export class Call extends Activity
{
	constructor()
	{
		super();
		this._clickHandler = BX.delegate(this.onClick, this);
		this._menu = null;
		this._isMenuShown = false;
		this._menuItems = null;
	}

	getButton()
	{
		return null;
	}

	onClick(e)
	{
		if(typeof(window.top['BXIM']) === 'undefined')
		{
			window.alert(this.getMessage("telephonyNotSupported"));
			return;
		}

		let phone = "";
		const itemData = this.getItemData();
		const phones = BX.prop.getArray(itemData, "PHONE", []);

		if(phones.length === 1)
		{
			this.addCall(phones[0]['VALUE']);
		}
		else if(phones.length > 1)
		{
			this.showMenu();
		}
		else
		{
			const communication = BX.prop.getObject(this._entityData, "COMMUNICATION", null);
			if(communication)
			{
				if(BX.prop.getString(communication, "TYPE") === "PHONE")
				{
					phone = BX.prop.getString(communication, "VALUE");
					if(phone)
					{
						this.addCall(phone);
					}
				}
			}
		}

		return BX.PreventDefault(e);
	}

	showMenu()
	{
		if(this._isMenuShown)
		{
			return;
		}

		this.prepareMenuItems();

		if(!this._menuItems || this._menuItems.length === 0)
		{
			return;
		}


		this._menu = new BX.PopupMenuWindow(
			this._id,
			this._container,
			this._menuItems,
			{
				offsetTop: 0,
				offsetLeft: 16,
				events:
					{
						onPopupShow: BX.delegate(this.onMenuShow, this),
						onPopupClose: BX.delegate(this.onMenuClose, this),
						onPopupDestroy: BX.delegate(this.onMenuDestroy, this)
					}
			}
		);

		this._menu.popupWindow.show();
	}

	closeMenu()
	{
		if(!this._isMenuShown)
		{
			return;
		}

		if(this._menu)
		{
			this._menu.close();
		}
	}

	prepareMenuItems()
	{
		if(this._menuItems)
		{
			return;
		}

		const itemData = this.getItemData();
		const phones = BX.prop.getArray(itemData, "PHONE", []);
		const handler = BX.delegate(this.onMenuItemClick, this);
		this._menuItems = [];

		if(phones.length === 0)
		{
			return;
		}

		let i = 0;
		const l = phones.length;
		for(; i < l; i++)
		{
			const value = BX.prop.getString(phones[i], "VALUE");
			const formattedValue = BX.prop.getString(phones[i], "VALUE_FORMATTED");
			const complexName = BX.prop.getString(phones[i], "COMPLEX_NAME");
			const itemText = (complexName ? complexName + ': ' : '') + (formattedValue ? formattedValue : value);

			if(value !== "")
			{
				this._menuItems.push({ id: value, text:  itemText, onclick: handler});
			}
		}
	}

	onMenuItemClick(e, item)
	{
		this.closeMenu();
		this.addCall(item.id);
	}

	onMenuShow()
	{
		this._isMenuShown = true;
	}

	onMenuClose()
	{
		this._isMenuShown = false;
		this._menu.popupWindow.destroy();
	}

	onMenuDestroy()
	{
		this._menu = null;
	}

	addCall(phone)
	{
		const communication = BX.prop.getObject(this._entityData, "COMMUNICATION", null);
		let entityTypeId = parseInt(BX.prop.getString(communication, "ENTITY_TYPE_ID", "0"));
		if(isNaN(entityTypeId))
		{
			entityTypeId = 0;
		}

		let entityId = parseInt(BX.prop.getString(communication, "ENTITY_ID", "0"));
		if(isNaN(entityId))
		{
			entityId = 0;
		}

		let ownerTypeId = 0;
		let ownerId = 0;

		const ownerInfo = BX.prop.getObject(this._settings, "ownerInfo");
		if(ownerInfo)
		{
			ownerTypeId = BX.prop.getInteger(ownerInfo, "ENTITY_TYPE_ID", 0);
			ownerId = BX.prop.getInteger(ownerInfo, "ENTITY_ID", 0);
		}

		if(ownerTypeId <= 0 || ownerId <= 0)
		{
			ownerTypeId = BX.prop.getInteger(this._entityData, "OWNER_TYPE_ID", 0);
			ownerId = BX.prop.getInteger(this._entityData, "OWNER_ID", "0");
		}

		if(ownerTypeId <= 0 || ownerId <= 0)
		{
			ownerTypeId = entityTypeId;
			ownerId = entityId;
		}

		let activityId = parseInt(BX.prop.getString(this._entityData, "ID", "0"));
		if(isNaN(activityId))
		{
			activityId = 0;
		}

		const params =
			{
				"ENTITY_TYPE_NAME": BX.CrmEntityType.resolveName(entityTypeId),
				"ENTITY_ID": entityId,
				"AUTO_FOLD": true
			};
		if(ownerTypeId !== entityTypeId || ownerId !== entityId)
		{
			params["BINDINGS"] = [ { "OWNER_TYPE_NAME": BX.CrmEntityType.resolveName(ownerTypeId), "OWNER_ID": ownerId } ];
		}

		if(activityId > 0)
		{
			params["SRC_ACTIVITY_ID"] = activityId;
		}

		window.top['BXIM'].phoneTo(phone, params);

	}

	getMessage(name)
	{
		const m = Call.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	static messages = {};
}

/** @memberof BX.Crm.Timeline.Actions */
export class HistoryCall extends Call
{
	constructor()
	{
		super();
		this._button = null;
	}

	getButton()
	{
		return this._button;
	}

	doLayout()
	{
		this._button = BX.create("A",
			{
				attrs: { className: "crm-entity-stream-content-action-reply-btn" },
				events: { "click": this._clickHandler }
			}
		);
		this._container.appendChild(this._button);
	}

	static create(id, settings)
	{
		const self = new HistoryCall();
		self.initialize(id, settings);
		return self;
	}
}

export class ScheduleCall extends Call
{
	constructor()
	{
		super();
	}

	doLayout()
	{
		this._container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-action-reply-btn" },
					events: { "click": this._clickHandler }
				}
			)
		);
	}

	static create(id, settings)
	{
		const self = new ScheduleCall();
		self.initialize(id, settings);
		return self;
	}
}
