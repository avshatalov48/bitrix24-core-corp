import HistoryActivity from "./history-activity";

/** @memberof BX.Crm.Timeline.Items */
export default class Task extends HistoryActivity
{
	constructor()
	{
		super();
	}

	prepareHeaderLayout()
	{
		const header = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-header"}});
		header.appendChild(this.prepareTitleLayout());

		const markNode = this.prepareMarkLayout();
		if(markNode)
		{
			header.appendChild(markNode);
		}

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

		const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
		const communicationTitle = BX.prop.getString(communication, "TITLE", "");
		const communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");

		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-task"}});

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-task" } })
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

		const communicationWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-detail-contact-info"}});
		detailWrapper.appendChild(communicationWrapper);

		if(communicationTitle !== '')
		{
			communicationWrapper.appendChild(
				BX.create("SPAN",
					{ text: this.getMessage("reciprocal") + ": " }
				)
			);

			if(communicationShowUrl !== '')
			{
				communicationWrapper.appendChild(BX.create("A", { attrs: { href: communicationShowUrl }, text: communicationTitle }));
			}
			else
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: communicationTitle }));
			}
		}

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

	getRemoveMessage()
	{
		const title = BX.util.htmlspecialchars(this.getTitle());
		return this.getMessage('taskRemove').replace("#TITLE#", title);
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
		const self = new Task();
		self.initialize(id, settings);
		return self;
	}
}
