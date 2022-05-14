import Activity from "./activity";

/** @memberof BX.Crm.Timeline.Actions */
export default class SchedulePostpone extends Activity
{
	constructor()
	{
		super();
		this._button = null;
		this._clickHandler = BX.delegate(this.onClick, this);
		this._isMenuShown = false;

		this._menu = false;
	}

	doLayout()
	{
		this._button = BX.create("DIV",
			{
				attrs:
					{
						className: this._isEnabled
							? "crm-entity-stream-planned-action-aside"
							: "crm-entity-stream-planned-action-aside-disabled"
					},
				text: this.getMessage("postpone")
			}
		);

		if(this._isEnabled)
		{
			BX.bind(this._button, "click", this._clickHandler)
		}

		this._container.appendChild(this._button);
	}

	openMenu()
	{
		if(this._isMenuShown)
		{
			return;
		}

		const handler = BX.delegate(this.onMenuItemClick, this);
		const menuItems =
			[
				{id: "hour_1", text: this.getMessage("forOneHour"), onclick: handler},
				{id: "hour_2", text: this.getMessage("forTwoHours"), onclick: handler},
				{id: "hour_3", text: this.getMessage("forThreeHours"), onclick: handler},
				{id: "day_1", text: this.getMessage("forOneDay"), onclick: handler},
				{id: "day_2", text: this.getMessage("forTwoDays"), onclick: handler},
				{id: "day_3", text: this.getMessage("forThreeDays"), onclick: handler}
			];

		BX.PopupMenu.show(
			this._id,
			this._button,
			menuItems,
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

		this._menu = BX.PopupMenu.currentItem;
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

	onClick()
	{
		if(!this._isEnabled)
		{
			return;
		}

		if(this._isMenuShown)
		{
			this.closeMenu();
		}
		else
		{
			this.openMenu();
		}
	}

	onMenuItemClick(e, item)
	{
		this.closeMenu();

		let offset = 0;
		if(item.id === "hour_1")
		{
			offset = 3600;
		}
		else if(item.id === "hour_2")
		{
			offset = 7200;
		}
		else if(item.id === "hour_3")
		{
			offset = 10800;
		}
		else if(item.id === "day_1")
		{
			offset = 86400;
		}
		else if(item.id === "day_2")
		{
			offset = 172800;
		}
		else if(item.id === "day_3")
		{
			offset = 259200;
		}

		if(offset > 0 && this._item)
		{
			this._item.postpone(offset);
		}
	}

	onMenuShow()
	{
		this._isMenuShown = true;
	}

	onMenuClose()
	{
		if(this._menu && this._menu.popupWindow)
		{
			this._menu.popupWindow.destroy();
		}
	}

	onMenuDestroy()
	{
		this._isMenuShown = false;
		this._menu = null;

		if(typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
		{
			delete(BX.PopupMenu.Data[this._id]);
		}
	}

	getMessage(name)
	{
		const m = SchedulePostpone.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	static create(id, settings)
	{
		const self = new SchedulePostpone();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
