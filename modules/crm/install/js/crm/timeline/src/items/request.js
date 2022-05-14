import HistoryActivity from "./history-activity";

/** @memberof BX.Crm.Timeline.Items */
export default class Request extends HistoryActivity
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
		const entityData = this.getAssociatedEntityData();

		let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
		if(description !== "")
		{
			//trim leading spaces
			description = description.replace(/^\s+/,'');
		}

		//var entityData = this.getAssociatedEntityData();
		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-today crm-entity-stream-section-robot"}});

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-robot" } })
		);
		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

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

		//Content
		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-description" },
					children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
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

	isEditable()
	{
		return false;
	}

	static create(id, settings)
	{
		const self = new Request();
		self.initialize(id, settings);
		return self;
	}
}
