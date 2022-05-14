import Relation from "./relation"

/** @memberof BX.Crm.Timeline.Items */
export default class Link extends Relation
{
	constructor()
	{
		super();
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-link";
	}

	getMessage(name)
	{
		const m = Link.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	static create(id, settings)
	{
		const self = new Link();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
