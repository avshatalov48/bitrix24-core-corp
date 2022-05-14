import HistoryActivity from "./history-activity";

/** @memberof BX.Crm.Timeline.Items */
export default class Wait extends HistoryActivity
{
	constructor()
	{
		super();
	}

	getTitle()
	{
		return this.getMessage("wait");
	}

	prepareTitleLayout()
	{
		return BX.create("SPAN", {
			attrs:{ className: "crm-entity-stream-content-event-title"},
			children: [
				BX.create("A", {
					attrs: { href: "#" },
					events: { "click": this._headerClickHandler },
					text: this.getTitle()
				})
			]
		});


	}

	prepareTimeLayout()
	{
		return BX.create("SPAN",
			{
				attrs: { className: "crm-entity-stream-content-event-time" },
				text: this.formatTime(this.getCreatedTime())
			}
		);
	}

	prepareHeaderLayout()
	{
		const header = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-header"}});
		header.appendChild(this.prepareTitleLayout());
		header.appendChild(this.prepareTimeLayout());

		return header;
	}

	prepareContent()
	{
		const entityData = this.getAssociatedEntityData();
		let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
		if(description !== "")
		{
			description = BX.util.trim(description);
			description = BX.util.strip_tags(description);
			description = BX.util.nl2br(description);
		}

		const wrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-wait"}
			}
		);

		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-complete" }
				}
			)
		);

		const contentWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		const header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		const detailWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-detail"},
				html: description
			}
		);
		contentWrapper.appendChild(detailWrapper);

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		contentWrapper.appendChild(this._actionContainer);
		//endregion

		return wrapper;
	}

	prepareActions()
	{
	}

	showActions(show)
	{
		if(this._actionContainer)
		{
			this._actionContainer.style.display = show ? "" : "none";
		}
	}

	static create(id, settings)
	{
		const self = new Wait();
		self.initialize(id, settings);
		return self;
	}
}
