import Activity from "./activity";

/** @memberof BX.Crm.Timeline.Items.Scheduled */
export default class Call extends Activity
{
	constructor()
	{
		super();
	}

	getWrapperClassName()
	{
		return 'crm-entity-stream-section-call';
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-call";
	}

	prepareActions()
	{
		if(this.isReadOnly())
		{
			return;
		}

		this._actions.push(
			BX.CrmScheduleCallAction.create(
				"call",
				{
					item: this,
					container: this._actionContainer,
					entityData: this.getAssociatedEntityData(),
					activityEditor: this._activityEditor,
					ownerInfo: this._schedule.getOwnerInfo()
				}
			)
		);
	}

	getTypeDescription(direction)
	{
		const entityData = this.getAssociatedEntityData();
		const callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);
		const callTypeText = callInfo !== null ? BX.prop.getString(callInfo, "CALL_TYPE_TEXT", "") : "";
		if(callTypeText !== "")
		{
			return callTypeText;
		}

		return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall");
	}

	getRemoveMessage()
	{
		const entityData = this.getAssociatedEntityData();
		const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
		let title = BX.prop.getString(entityData, "SUBJECT", "");
		const messageName = (direction === BX.CrmActivityDirection.incoming) ? 'incomingCallRemove' : 'outgoingCallRemove';
		title = BX.util.htmlspecialchars(title);
		return this.getMessage(messageName).replace("#TITLE#", title);
	}

	static create(id, settings)
	{
		const self = new Call();
		self.initialize(id, settings);
		return self;
	}
}
