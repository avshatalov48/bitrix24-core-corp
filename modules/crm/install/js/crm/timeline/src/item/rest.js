import HistoryActivity from "./history-activity";

/** @memberof BX.Crm.Timeline.Items */
export default class Rest extends HistoryActivity
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

	getTypeDescription()
	{
		const entityData = this.getAssociatedEntityData();
		if (entityData['APP_TYPE'] && entityData['APP_TYPE']['NAME'])
		{
			return entityData['APP_TYPE']['NAME'];
		}

		return Rest.superclass.getTypeDescription.apply(this);
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
		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-today crm-entity-stream-section-rest"}});

		const iconNode = BX.create("DIV", {attrs: {className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-rest"}});

		wrapper.appendChild(iconNode);

		if (entityData['APP_TYPE'] && entityData['APP_TYPE']['ICON_SRC'])
		{
			if (iconNode)
			{
				iconNode.style.backgroundImage = "url('" +  entityData['APP_TYPE']['ICON_SRC'] + "')";
				iconNode.style.backgroundPosition = "center center";
			}
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

	static create(id, settings)
	{
		const self = new Rest();
		self.initialize(id, settings);
		return self;
	}
}
