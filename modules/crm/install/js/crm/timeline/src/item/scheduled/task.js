import Activity from "./activity";

/** @memberof BX.Crm.Timeline.Items.Scheduled */
export default class Task extends Activity
{
	constructor()
	{
		super();
	}

	getWrapperClassName()
	{
		return "crm-entity-stream-section-planned-task";
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-task";
	}

	getTypeDescription()
	{
		return this.getMessage("task");
	}

	getPrepositionText(direction)
	{
		return this.getMessage("reciprocal");
	}

	getRemoveMessage()
	{
		const entityData = this.getAssociatedEntityData();
		let title = BX.prop.getString(entityData, "SUBJECT", "");
		title = BX.util.htmlspecialchars(title);
		return this.getMessage('taskRemove').replace("#TITLE#", title);
	}

	static create(id, settings)
	{
		const self = new Task();
		self.initialize(id, settings);
		return self;
	}
}
