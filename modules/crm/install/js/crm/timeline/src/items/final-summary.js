import FinalSummaryDocuments from "./final-summary-documents";

/** @memberof BX.Crm.Timeline.Items */
export default class FinalSummary extends FinalSummaryDocuments
{
	constructor()
	{
		super();
	}

	prepareContent()
	{
		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-payment"}});

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: 'crm-entity-stream-section-icon ' + this.getIconClassName() } })
		);

		const content = BX.create("DIV", {attrs: {className: "crm-entity-stream-section-content"}});

		const contentItem = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});

		const header = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-header"},
				children: this.getHeaderChildren()
			});
		contentItem.appendChild(header);

		const data = this.getData();

		if (data.RESULT)
		{
			const summaryOptions = {
				'OWNER_ID': data.ASSOCIATED_ENTITY_ID,
				'OWNER_TYPE_ID': data.ASSOCIATED_ENTITY_TYPE_ID,
				'PARENT_CONTEXT': this,
				'CONTEXT': BX.CrmEntityType.resolveName(data.ASSOCIATED_ENTITY_TYPE_ID).toLowerCase(),
				'IS_WITH_ORDERS_MODE': true,
			};
			const timelineSummaryDocuments = new BX.Crm.TimelineSummaryDocuments(summaryOptions);

			const options = data.RESULT.TIMELINE_SUMMARY_OPTIONS;
			timelineSummaryDocuments.setOptions(options);
			const nodes = [
				timelineSummaryDocuments.render(),
			];

			contentItem.appendChild(
				BX.create("DIV",
					{
						attrs: {className: "crm-entity-stream-content-detail"},
						children: nodes
					})
			);

			content.appendChild(contentItem);
		}
		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			content.appendChild(authorNode);
		}
		//endregion

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-content" }, children: [ content ] })
		);

		return wrapper;
	}

	static create(id, settings)
	{
		const self = new FinalSummary();
		self.initialize(id, settings);
		return self;
	}
}
