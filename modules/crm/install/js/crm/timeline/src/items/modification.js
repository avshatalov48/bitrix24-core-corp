import History from "./history";

/** @memberof BX.Crm.Timeline.Items */
export default class Modification extends History
{
	constructor()
	{
		super();
	}

	getMessage(name)
	{
		const m = Modification.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	getTitle()
	{
		return this.getTextDataParam("TITLE");
	}

	prepareContent()
	{
		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-info"}});

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info" } })
		);

		const content = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		const header = this.prepareHeaderLayout();

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


		content.appendChild(header);
		content.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children:
						[
							BX.create("DIV",
								{
									attrs: { className: "crm-entity-stream-content-detail-info" },
									children: contentChildren
								})
						]
				})
		);

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
		const self = new Modification();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
