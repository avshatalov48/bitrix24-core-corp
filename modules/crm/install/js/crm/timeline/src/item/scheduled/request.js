import Activity from "./activity";

/** @memberof BX.Crm.Timeline.Items.Scheduled */
export default class Request extends Activity
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
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-robot";
	}

	getTypeDescription()
	{
		return this.getMessage("activityRequest");
	}

	isEditable()
	{
		return false;
	}

	static create(id, settings)
	{
		const self = new Request();
		self.initialize(id, settings);
		return self;
	}
}
