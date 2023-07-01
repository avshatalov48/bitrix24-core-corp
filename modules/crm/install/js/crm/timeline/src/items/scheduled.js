import CompatibleItem from "./compatible-item";
import History from "./history";
import {Item as ItemType} from "../types";

/** @memberof BX.Crm.Timeline.Items */
export default class Scheduled extends CompatibleItem
{
	constructor()
	{
		super();
		this._schedule = null;
		this._deadlineNode = null;

		this._headerClickHandler = BX.delegate(this.onHeaderClick, this);
		this._setAsDoneButtonHandler = BX.delegate(this.onSetAsDoneButtonClick, this);
	}

	doInitialize()
	{
		this._schedule = this.getSetting("schedule");
		if(!(this._activityEditor instanceof BX.CrmActivityEditor))
		{
			throw "Scheduled. The field 'activityEditor' is not assigned.";
		}

		if(this.hasPermissions() && !this.verifyPermissions())
		{
			this.loadPermissions();
		}
	}

	getTypeId()
	{
		return ItemType.undefined;
	}

	verifyPermissions()
	{
		const userId = BX.prop.getInteger(this.getPermissions(), "USER_ID", 0);
		return userId <= 0 || userId === this._schedule.getUserId();
	}

	loadPermissions()
	{
		BX.ajax(
			{
				url: this._schedule.getServiceUrl(),
				method: "POST",
				dataType: "json",
				data: { "ACTION": "GET_PERMISSIONS", "TYPE_ID": this.getTypeId(), "ID": this.getAssociatedEntityId() },
				onsuccess: this.onPermissionsLoad.bind(this)
			}
		);
	}

	onPermissionsLoad(result)
	{
		const permissions = BX.prop.getObject(result, "PERMISSIONS", null);
		if(!permissions)
		{
			return;
		}

		this.setPermissions(permissions);
		window.setTimeout(function(){ this.refreshLayout(); }.bind(this), 0);
	}

	getDeadline()
	{
		return null;
	}

	getLightTime()
	{
		return null;
	}

	hasDeadline()
	{
		return BX.type.isDate(this.getDeadline());
	}

	isCounterEnabled()
	{
		if (this.isDone())
		{
			return this._existedStreamItemDeadLine && History.isCounterEnabledByLightTime(this._existedStreamItemDeadLine);
		}

		const lightTime = this.getLightTime();

		return lightTime && History.isCounterEnabledByLightTime(lightTime);
	}

	isIncomingChannel()
	{
		return false;
	}

	getSourceId()
	{
		return BX.prop.getInteger(this.getAssociatedEntityData(), "ID", 0);
	}

	onSetAsDoneCompleted(data)
	{
		if(!BX.prop.getBoolean(data, "COMPLETED"))
		{
			return;
		}

		this.markAsDone(true);
		this._schedule.onItemMarkedAsDone(
			this,
			{ 'historyItemData': BX.prop.getObject(data, "HISTORY_ITEM") }
		);
	}

	onPosponeCompleted(data)
	{
	}

	refreshDeadline()
	{
		this._deadlineNode.innerHTML = this.formatDateTime(this.getDeadline());
	}

	formatDateTime(time)
	{
		return this._schedule.formatDateTime(time);
	}

	getWrapperClassName()
	{
		return "";
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon";
	}

	isReadOnly()
	{
		return this._schedule.isReadOnly();
	}

	isEditable()
	{
		return !this.isReadOnly();
	}

	canPostpone()
	{
		if(this.isReadOnly())
		{
			return false;
		}
		if (this.isIncomingChannel())
		{
			return false;
		}

		const perms = BX.prop.getObject(this.getAssociatedEntityData(), "PERMISSIONS", {});
		return BX.prop.getBoolean(perms, "POSTPONE", false);
	}

	isDone()
	{
		return BX.CrmActivityStatus.isFinal(
			BX.prop.getInteger(this.getAssociatedEntityData(), "STATUS", 0)
		);
	}

	canComplete()
	{
		if(this.isReadOnly())
		{
			return false;
		}

		const perms = BX.prop.getObject(this.getAssociatedEntityData(), "PERMISSIONS", {});
		return BX.prop.getBoolean(perms, "COMPLETE", false);
	}

	setAsDone(isDone)
	{
	}

	prepareContent(options)
	{
		return null;
	}

	prepareLayout(options)
	{
		this._wrapper = this.prepareContent();
		if(this._wrapper)
		{
			const enableAdd = BX.type.isPlainObject(options) ? BX.prop.getBoolean(options, "add", true) : true;
			if(enableAdd)
			{
				const anchor = BX.type.isPlainObject(options) && BX.type.isElementNode(options["anchor"]) ? options["anchor"] : null;
				if(anchor && anchor.nextSibling)
				{
					this._container.insertBefore(this._wrapper, anchor.nextSibling);
				}
				else
				{
					this._container.appendChild(this._wrapper);
				}
			}

			this.markAsTerminated(this._schedule.checkItemForTermination(this));
		}
	}

	onHeaderClick(e)
	{
		this.view();
		e.preventDefault ? e.preventDefault() : (e.returnValue = false);
	}

	onSetAsDoneButtonClick(e)
	{
		if(this.canComplete())
		{
			this.setAsDone(!this.isDone());
		}
	}

	onActivityCreate(activity, data)
	{
		this._schedule.getManager().onActivityCreated(activity, data);
	}

	static isDone(data)
	{
		const entityData = BX.prop.getObject(data, "ASSOCIATED_ENTITY", {});
		return BX.CrmActivityStatus.isFinal(BX.prop.getInteger(entityData, "STATUS", 0));
	}

	static create(id, settings)
	{
		const self = new Scheduled();
		self.initialize(id, settings);
		return self;
	}
}
