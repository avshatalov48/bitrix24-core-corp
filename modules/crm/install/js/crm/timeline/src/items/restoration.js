import History from "./history";

/** @memberof BX.Crm.Timeline.Items */
export default class Restoration extends History
{
	constructor()
	{
		super();
	}

	getTitle()
	{
		return this.getTextDataParam("TITLE");
	}

	getWrapperClassName()
	{
		return "crm-entity-stream-section-restoreEntity";
	}

	prepareContentDetails()
	{
		const entityData = this.getAssociatedEntityData();
		const title = BX.prop.getString(entityData, "TITLE");
		return title !== "" ?  [ BX.create("SPAN", { text: title }) ] : [];
	}

	getMessage(name)
	{
		const m = Restoration.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	static create(id, settings)
	{
		const self = new Restoration();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
