import Activity from "./activity";

/** @memberof BX.Crm.Timeline.Items.Scheduled */
export default class WebForm extends Activity
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
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-crmForm";
	}

	prepareActions()
	{
	}

	getPrepositionText()
	{
		return this.getMessage("from");
	}

	getTypeDescription()
	{
		return this.getMessage("webform");
	}

	static create(id, settings)
	{
		const self = new WebForm();
		self.initialize(id, settings);
		return self;
	}
}
