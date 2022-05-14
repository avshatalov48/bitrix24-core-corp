import OrderModification from "./order-modification";

/** @memberof BX.Crm.Timeline.Items */
export default class ExternalNoticeModification extends OrderModification
{
	constructor()
	{
		super();
	}

	getIconClassName()
	{
		return 'crm-entity-stream-section-icon-restApp';
	}

	static create(id, settings)
	{
		const self = new ExternalNoticeModification();
		self.initialize(id, settings);
		return self;
	}
}
