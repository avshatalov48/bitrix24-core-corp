import HistoryActivity from "./history-activity";

/** @memberof BX.Crm.Timeline.Items */
export default class WebForm extends HistoryActivity
{
	constructor()
	{
		super();
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
		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-crmForm"}});

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-crmForm" } })
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

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

		const header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		const detailWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-detail"}});
		contentWrapper.appendChild(detailWrapper);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-title" },
					children:
						[
							BX.create("A",
								{
									attrs: { href: "#" },
									events: { "click": this._headerClickHandler },
									text: this.getTitle()
								}
							)
						]
				}
			)
		);

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

		if (!this.isReadOnly())
			contentWrapper.appendChild(this.prepareFixedSwitcherLayout());

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
		const self = new WebForm();
		self.initialize(id, settings);
		return self;
	}
}
