import Manager from "../manager";

/** @memberof BX.Crm.Timeline.Tools */
export default class MenuBar
{
	constructor()
	{
		this._id = "";
		this._ownerInfo = null;
		this._container = null;
		this._activityEditor = null;
		this._commentEditor = null;
		this._todoEditor = null;
		this._waitEditor = null;
		this._smsEditor = null;
		this._zoomEditor = null;
		this._readOnly = false;

		this._menu = null;
		this._manager = null;
	}

	static create(id, settings)
	{
		const self = new MenuBar();
		self.initialize(id, settings);
		return self;
	}

	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._settings = settings ? settings : {};

		this._ownerInfo = BX.prop.getObject(this._settings, "ownerInfo");
		if (!this._ownerInfo)
		{
			throw "MenuBar. A required parameter 'ownerInfo' is missing.";
		}

		this._activityEditor = BX.prop.get(this._settings, "activityEditor", null);

		this._commentEditor = BX.prop.get(this._settings, "commentEditor");
		this._todoEditor = BX.prop.get(this._settings, "todoEditor");
		this._waitEditor = BX.prop.get(this._settings, "waitEditor");
		this._smsEditor = BX.prop.get(this._settings, "smsEditor");
		this._zoomEditor = BX.prop.get(this._settings, "zoomEditor");
		this._restEditor = BX.prop.get(this._settings, "restEditor");
		this._manager = BX.prop.get(this._settings, "manager");

		if (!(this._manager instanceof Manager))
		{
			throw "BX.CrmTimeline. Manager instance is not found.";
		}

		this._readOnly = BX.prop.getBoolean(this._settings, "readOnly", false);
		this._menu = BX.Main.interfaceButtonsManager.getById(
			BX.prop.getString(this._settings, "menuId", (this._ownerInfo['ENTITY_TYPE_NAME'] + "_menu").toLowerCase())
		);

		BX.addCustomEvent(this._manager.getId() + "_menu", function (id) {
			this.setActiveItemById(id);
		}.bind(this));
		this._activeItem = this._menu.getActive();
	}

	reset()
	{
		let firstId = null;
		this._menu.getAllItems().forEach(function (item) {
			if (firstId === null)
			{
				const id = item.dataset.id;
				if (['comment', 'wait', 'sms', 'zoom', 'todo'].indexOf(id) >= 0 && this[("_" + id + "Editor")])
				{
					firstId = id;
				}
			}
		}.bind(this));
		this.setActiveItemById(firstId || 'todo');
	}

	getId()
	{
		return this._id;
	}

	getActiveItem()
	{
		return this._activeItem;
	}

	getTodoEditor()
	{
		return this._todoEditor;
	}

	setActiveItemById(id)
	{
		if (this.processItemSelection(id) === true)
		{
			const currentDiv = this._menu.getItemById(id);
			if (currentDiv && this._activeItem !== currentDiv)
			{
				const wasActiveInMoreMenu = this._menu.isActiveInMoreMenu();
				BX.addClass(currentDiv, this._menu.classes.itemActive);

				if (this._menu.getItemData)
				{
					const currentDivData = this._menu.getItemData(currentDiv);
					currentDivData['IS_ACTIVE'] = true;
					if (BX.type.isDomNode(this._activeItem))
					{
						BX.removeClass(this._activeItem, this._menu.classes.itemActive);
						const activeItemData = this._menu.getItemData(this._activeItem);
						activeItemData['IS_ACTIVE'] = false;
					}
				}
				else
				{
					// Old approach
					let isActiveData = {};
					try
					{
						isActiveData = JSON.parse(currentDiv.dataset.item);
					}
					catch (err)
					{
						isActiveData = {};
					}
					isActiveData.IS_ACTIVE = true;
					currentDiv.dataset.item = JSON.stringify(isActiveData);
					let wasActiveData = {};
					if (BX.type.isDomNode(this._activeItem))
					{
						BX.removeClass(this._activeItem, this._menu.classes.itemActive);
						try
						{
							wasActiveData = JSON.parse(this._activeItem.dataset.item);
						}
						catch (err)
						{
							wasActiveData = {};
						}
						wasActiveData.IS_ACTIVE = false;
						this._activeItem.dataset.item = JSON.stringify(wasActiveData);
					}
				}

				const isActiveInMoreMenu = this._menu.isActiveInMoreMenu();
				if (isActiveInMoreMenu || wasActiveInMoreMenu)
				{
					const submenu = this._menu["getSubmenu"] ? this._menu.getSubmenu() :
						BX.PopupMenu.getMenuById("main_buttons_popup_" +
							String(this._ownerInfo['ENTITY_TYPE_NAME']).toLowerCase() + "_menu");
					if (submenu)
					{
						submenu.getMenuItems().forEach(function (menuItem) {
							const container = menuItem.getContainer();
							if (isActiveInMoreMenu && container.title === currentDiv.title)
							{
								BX.addClass(container, this._menu.classes.itemActive);
							}
							else if (wasActiveInMoreMenu && container.title === this._activeItem.title)
							{
								BX.removeClass(container, this._menu.classes.itemActive);
							}

						}.bind(this));
					}

					if (isActiveInMoreMenu)
					{
						BX.addClass(this._menu.getMoreButton(), this._menu.classes.itemActive);
					}
					else if (wasActiveInMoreMenu)
					{
						BX.removeClass(this._menu.getMoreButton(), this._menu.classes.itemActive);
					}
				}
				this._activeItem = currentDiv;
			}
		}
		this._menu.closeSubmenu();
	}

	processItemSelection(menuId)
	{
		if (this._readOnly)
		{
			return false;
		}

		let planner = null;
		const action = menuId;
		if (action === "call")
		{
			planner = new BX.Crm.Activity.Planner();
			planner.showEdit(
				{
					"TYPE_ID": BX.CrmActivityType.call,
					"OWNER_TYPE_ID": this._ownerInfo['ENTITY_TYPE_ID'],
					"OWNER_ID": this._ownerInfo['ENTITY_ID']
				}
			);
		}
		if (action === "meeting")
		{
			planner = new BX.Crm.Activity.Planner();
			planner.showEdit(
				{
					"TYPE_ID": BX.CrmActivityType.meeting,
					"OWNER_TYPE_ID": this._ownerInfo['ENTITY_TYPE_ID'],
					"OWNER_ID": this._ownerInfo['ENTITY_ID']
				}
			);
		}
		else if (action === "email")
		{
			this._activityEditor.addEmail(
				{
					"ownerType": this._ownerInfo['ENTITY_TYPE_NAME'],
					"ownerID": this._ownerInfo['ENTITY_ID'],
					"ownerUrl": this._ownerInfo['SHOW_URL'],
					"ownerTitle": this._ownerInfo['TITLE'],
					"subject": ""
				}
			);
		}
		else if (action === "delivery")
		{
			this._activityEditor.addDelivery(
				{
					"ownerType": this._ownerInfo['ENTITY_TYPE_NAME'],
					"ownerID": this._ownerInfo['ENTITY_ID'],
					"orderList": this._ownerInfo['ORDER_LIST']
				}
			);
		}
		else if (action === "task")
		{
			this._activityEditor.addTask(
				{
					"ownerType": this._ownerInfo['ENTITY_TYPE_NAME'],
					"ownerID": this._ownerInfo['ENTITY_ID']
				}
			);
		}
		else if (['comment', 'wait', 'sms', 'zoom', 'todo'].indexOf(action) >= 0 && this[("_" + action + "Editor")])
		{
			if (this._commentEditor)
			{
				this._commentEditor.setVisible(action === "comment");
			}
			if (this._todoEditor)
			{
				this._todoEditor.setVisible(action === 'todo');
			}
			if (this._waitEditor)
			{
				this._waitEditor.setVisible(action === "wait");
			}
			if (this._smsEditor)
			{
				this._smsEditor.setVisible(action === "sms");
			}
			if (this._zoomEditor)
			{
				this._zoomEditor.setVisible(action === "zoom");
			}
			return true;
		}
		else if (action === "visit")
		{
			const visitParameters = this._manager.getSetting("visitParameters");
			visitParameters['OWNER_TYPE'] = this._ownerInfo['ENTITY_TYPE_NAME'];
			visitParameters['OWNER_ID'] = this._ownerInfo['ENTITY_ID'];
			BX.CrmActivityVisit.create(visitParameters).showEdit();
		}
		else if (action.match(/^activity_rest_/))
		{
			if (this._restEditor)
			{
				this._restEditor.action(action);
			}
		}
		return false;
	}

	getMenuItems()
	{
		return this._menu.getAllItems();
	}
}
