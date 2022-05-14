import Call from "./call";

/** @memberof BX.Crm.Timeline.Items.Scheduled */
export default class CallTracker extends Call
{
	constructor()
	{
		super();
	}

	getStatusNode()
	{
		const entityData = this.getAssociatedEntityData();
		const callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);

		if (!callInfo)
		{
			return false;
		}
		if (!BX.prop.getBoolean(callInfo, "HAS_STATUS", false))
		{
			return false;
		}

		const isSuccessfull = BX.prop.getBoolean(callInfo, "SUCCESSFUL", false);
		const statusText = BX.prop.getString(callInfo, "STATUS_TEXT", "");

		return BX.create("DIV",
			{
				attrs:
					{
						className: isSuccessfull
							? "crm-entity-stream-content-event-successful"
							: "crm-entity-stream-content-event-missing"
					},
				text: statusText
			}
		)
	}

	static create(id, settings)
	{
		const self = new CallTracker();
		self.initialize(id, settings);
		return self;
	}
}
