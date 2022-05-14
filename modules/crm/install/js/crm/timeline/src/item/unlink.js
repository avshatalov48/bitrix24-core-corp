import Relation from "./relation";

/** @memberof BX.Crm.Timeline.Items */
export default class Unlink extends Relation
{
	constructor()
	{
		super();
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-unlink";
	}

	getMessage(name)
	{
		const m = Unlink.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	static create(id, settings)
	{
		const self = new Unlink();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
