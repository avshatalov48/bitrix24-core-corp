import Activity from "./activity";

/** @memberof BX.Crm.Timeline.Items.Scheduled */
export default class Meeting extends Activity
{
	constructor()
	{
		super();
	}

	getWrapperClassName()
	{
		return "";
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-meeting";
	}

	prepareActions()
	{
	}

	getPrepositionText()
	{
		return this.getMessage("reciprocal");
	}

	getRemoveMessage()
	{
		const entityData = this.getAssociatedEntityData();
		let title = BX.prop.getString(entityData, "SUBJECT", "");
		title = BX.util.htmlspecialchars(title);
		return this.getMessage('meetingRemove').replace("#TITLE#", title);
	}

	getTypeDescription()
	{
		return this.getMessage("meeting");
	}

	static create(id, settings)
	{
		const self = new Meeting();
		self.initialize(id, settings);
		return self;
	}
}
