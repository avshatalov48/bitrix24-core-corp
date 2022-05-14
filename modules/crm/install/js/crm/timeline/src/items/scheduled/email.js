import Activity from "./activity";

/** @memberof BX.Crm.Timeline.Items.Scheduled */
export default class Email extends Activity
{
	constructor()
	{
		super();
	}

	getWrapperClassName()
	{
		return "crm-entity-stream-section-email";
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-email";
	}

	prepareActions()
	{
		if(this.isReadOnly())
		{
			return;
		}

		this._actions.push(
			BX.CrmScheduleEmailAction.create(
				"email",
				{
					item: this,
					container: this._actionContainer,
					entityData: this.getAssociatedEntityData(),
					activityEditor: this._activityEditor
				}
			)
		);
	}

	getTypeDescription(direction)
	{
		return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingEmail" : "outgoingEmail");
	}

	getRemoveMessage()
	{
		const entityData = this.getAssociatedEntityData();
		let title = BX.prop.getString(entityData, "SUBJECT", "");
		title = BX.util.htmlspecialchars(title);
		return this.getMessage('emailRemove').replace("#TITLE#", title);
	}

	isEditable()
	{
		return false;
	}

	static create(id, settings)
	{
		const self = new Email();
		self.initialize(id, settings);
		return self;
	}
}
