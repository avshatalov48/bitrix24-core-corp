import ExternalNoticeModification from "./external-notice-modification";

/** @memberof BX.Crm.Timeline.Items */
export default class ExternalNoticeStatusModification extends ExternalNoticeModification
{
	constructor()
	{
		super();
	}

	prepareContentDetails()
	{
		const nodes = [];
		const contentChildren = [];

		if (BX.type.isNotEmptyString(this.getTextDataParam("START_NAME")))
		{
			contentChildren.push(
				BX.create("SPAN",
					{
						attrs: {className: "crm-entity-stream-content-detain-info-status"},
						text: this.getTextDataParam("START_NAME")
					})
			);
			contentChildren.push(
				BX.create("SPAN",{ attrs: { className: "crm-entity-stream-content-detail-info-separator-icon" } })
			);
		}

		if (BX.type.isNotEmptyString(this.getTextDataParam("FINISH_NAME")))
		{
			contentChildren.push(
				BX.create("SPAN",
					{
						attrs: { className: "crm-entity-stream-content-detain-info-status" },
						text: this.getTextDataParam("FINISH_NAME")
					})
			);
		}

		nodes.push(BX.create("DIV",	{
			attrs: { className: "crm-entity-stream-content-detail-info" },
			children: contentChildren
		}));

		return nodes;
	}

	static create(id, settings)
	{
		const self = new ExternalNoticeStatusModification();
		self.initialize(id, settings);
		return self;
	}
}
