import Action from "../action";

/** @memberof BX.Crm.Timeline.Actions */
export default class Activity extends Action
{
	constructor()
	{
		super();
		this._activityEditor = null;
		this._entityData = null;
		this._item = null;
		this._isEnabled = true;
	}

	doInitialize()
	{
		this._entityData = this.getSetting("entityData");
		if(!BX.type.isPlainObject(this._entityData))
		{
			throw "BX.Crm.Timeline.Actions.Activity. A required parameter 'entityData' is missing.";
		}

		this._activityEditor = this.getSetting("activityEditor");
		if(!(this._activityEditor instanceof BX.CrmActivityEditor))
		{
			throw "BX.Crm.Timeline.Actions.Activity. A required parameter 'activityEditor' is missing.";
		}

		this._item = this.getSetting("item");
		this._isEnabled = this.getSetting("enabled", true);
	}

	getActivityId()
	{
		return BX.prop.getInteger(this._entityData, "ID", 0);
	}

	loadActivityCommunications(callback)
	{
		this._activityEditor.getActivityCommunications(
			this.getActivityId(),
			function(communications)
			{
				if(BX.type.isFunction(callback))
				{
					callback(communications);
				}
			},
			true
		);
	}

	getItemData()
	{
		return this._item ?  this._item.getData() : null;
	}
}
