import History from "./history";

/** @memberof BX.Crm.Timeline.Actions */
export default class OrderCreation extends History
{
	constructor()
	{
		super();
	}

	doInitialize()
	{
		super.doInitialize();
		if(!(this._activityEditor instanceof BX.CrmActivityEditor))
		{
			throw "OrderCreation. The field 'activityEditor' is not assigned.";
		}
	}

	getTitle()
	{
		let msg = this.getMessage(BX.CrmEntityType.resolveName(this.getAssociatedEntityTypeId()).toLowerCase());
		if(!BX.type.isNotEmptyString(msg))
		{
			msg = this.getTextDataParam("TITLE");
		}

		return msg;
	}

	getWrapperClassName()
	{
		return "crm-entity-stream-section-createOrderEntity";
	}

	getHeaderChildren()
	{
		let statusMessage = '';
		let statusClass = '';
		const fields = this.getObjectDataParam('FIELDS');

		if (BX.prop.get(fields, 'DONE') === 'Y')
		{
			statusMessage = this.getMessage("done");
			statusClass = "crm-entity-stream-content-event-done";
		}
		else if (BX.prop.get(fields, 'CANCELED') === 'Y')
		{
			statusMessage = this.getMessage("canceled");
			statusClass = "crm-entity-stream-content-event-canceled";
		}
		else
		{
			if (BX.prop.get(fields, 'PAID') === 'Y')
			{
				statusMessage = this.getMessage("paid");
				statusClass = "crm-entity-stream-content-event-paid";
			}
			else if (BX.prop.get(fields, 'PAID') === 'N')
			{
				statusMessage = this.getMessage("unpaid");
				statusClass = "crm-entity-stream-content-event-not-paid";
			}
		}

		return [
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-event-title" },
					events: { click: this._headerClickHandler },
					text: this.getTitle(),
				}
			),
			BX.create("SPAN",
				{
					attrs: { className: statusClass },
					text: statusMessage
				}
			),
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: this.formatTime(this.getCreatedTime())
				}
			)
		];
	}

	prepareContentDetails()
	{
		const entityData = this.getAssociatedEntityData();
		const entityTypeId = this.getAssociatedEntityTypeId();
		const entityId = this.getAssociatedEntityId();
		let title = BX.util.htmlspecialchars(BX.prop.getString(entityData, "TITLE", ""));
		const showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
		const legend = BX.prop.getString(entityData, "LEGEND", "");
		if(legend !== "")
		{
			title += " " + legend;
		}
		const nodes = [];

		if(title !== "")
		{
			if(showUrl === "" || (entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId()))
			{
				nodes.push(BX.create("DIV", {
					attrs: { className: "crm-entity-stream-content-detail-description" },
					html: title
				}));
			}
			else
			{
				nodes.push(BX.create("DIV", {
					attrs: { className: "crm-entity-stream-content-detail-description" },
					html: title
				}));
				nodes.push(BX.create("A", { attrs: { href: showUrl }, text: this.getMessage('urlOrderLink') }));
			}
		}
		return nodes;
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-store";
	}

	prepareContent()
	{
		const wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history";
		const wrapper = BX.create("DIV", {attrs: {className: wrapperClassName}});
		wrapper.appendChild(BX.create("DIV", { attrs: { className: this.getIconClassName() } }));

		const contentWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		wrapper.appendChild(
			BX.create("DIV",
				{ attrs: { className: "crm-entity-stream-section-content" }, children: [ contentWrapper ] }
			)
		);

		const header = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-header"},
				children: this.getHeaderChildren()
			}
		);
		contentWrapper.appendChild(header);

		contentWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children: this.prepareContentDetails()
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

		return wrapper;
	}

	getMessage(name)
	{
		const m = OrderCreation.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	static create(id, settings)
	{
		const self = new OrderCreation();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
