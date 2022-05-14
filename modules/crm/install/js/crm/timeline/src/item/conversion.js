import History from "./history";

/** @memberof BX.Crm.Timeline.Actions */
export default class Conversion extends History
{
	constructor()
	{
		super();
	}

	getMessage(name)
	{
		const m = Conversion.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	getTitle()
	{
		return this.getTextDataParam("TITLE");
	}

	prepareContent()
	{
		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-convert crm-entity-stream-section-history"}});

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-convert" } })
		);

		const content = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		const header = this.prepareHeaderLayout();

		content.appendChild(header);

		const entityNodes = [];
		const entityInfos = this.getArrayDataParam("ENTITIES");
		let i = 0;
		const length = entityInfos.length;
		for(; i < length; i++)
		{
			const entityInfo = entityInfos[i];

			let entityNode;
			if(BX.prop.getString(entityInfo, 'SHOW_URL', "") === "")
			{
				entityNode = BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-content-detail-convert" },
						children:
							[
								BX.create("DIV",
									{
										attrs: { className: "crm-entity-stream-content-detain-convert-status" },
										children:
											[
												BX.create("SPAN",
													{
														attrs: { className: "crm-entity-stream-content-detail-status-text" },
														text: BX.CrmEntityType.getNotFoundMessage(entityInfo['ENTITY_TYPE_ID'])
													}
												)
											]
									}
								)
							]
					}
				);
			}
			else
			{
				entityNode = BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-content-detail-convert" },
						children:
							[
								BX.create("DIV",
									{
										attrs: { className: "crm-entity-stream-content-detain-convert-status" },
										children:
											[
												BX.create("SPAN",
													{
														attrs: { className: "crm-entity-stream-content-detail-status-text" },
														text: entityInfo['ENTITY_TYPE_CAPTION']
													}
												)
											]
									}
								),
								BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-convert-separator-icon" } }),
								BX.create("DIV",
									{
										attrs: { className: "crm-entity-stream-content-detain-convert-status" },
										children:
											[
												BX.create("A",
													{
														attrs:
															{
																className: "crm-entity-stream-content-detail-target",
																href: entityInfo['SHOW_URL']
															},
														text: entityInfo['TITLE']
													}
												)
											]
									}
								)
							]
					}
				);
			}
			entityNodes.push(entityNode);
		}

		content.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children: entityNodes
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
		const self = new Conversion();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
