import Modification from "./modification";

/** @memberof BX.Crm.Timeline.Items */
export default class StoreDocumentModification extends Modification
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
			throw "StoreDocumentModification. The field 'activityEditor' is not assigned.";
		}
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-info";
	}

	getTitle()
	{
		const error = this.getTextDataParam("ERROR");
		if (error === 'CONDUCT')
		{
			return this.getMessage('conductError');
		}
		const entityData = this.getAssociatedEntityData();
		const field = this.getTextDataParam("FIELD");
		const docType = BX.prop.getString(entityData, "DOC_TYPE");
		if (docType === 'A')
		{
			if (field === 'STATUS')
			{
				return this.getMessage('arrivalDocument');
			}
			else
			{
				return this.getMessage('arrivalModification');
			}
		}
		if (docType === 'S')
		{
			if (field === 'STATUS')
			{
				return this.getMessage('storeAdjustmentDocument');
			}
			else
			{
				return this.getMessage('storeAdjustmentModification');
			}
		}
		if (docType === 'M')
		{
			if (field === 'STATUS')
			{
				return this.getMessage('movingDocument');
			}
			else
			{
				return this.getMessage('movingModification');
			}
		}
		if (docType === 'D')
		{
			if (field === 'STATUS')
			{
				return this.getMessage('deductDocument');
			}
			else
			{
				return this.getMessage('deductModification');
			}
		}
		if (docType === 'W')
		{
			if (field === 'STATUS')
			{
				return this.getMessage('shipmentDocument');
			}
			else
			{
				return this.getMessage('shipmentModification');
			}
		}

		return '';
	}

	getStatusInfo()
	{
		const statusInfo = {};
		const statusName = this.getTextDataParam('STATUS_TITLE');
		const classCode = this.getTextDataParam('STATUS_CLASS');
		{
			statusInfo.message = statusName;
			statusInfo.className = "crm-entity-stream-content-event-" + classCode;
		}

		return statusInfo;
	}

	getHeaderChildren()
	{
		const children = [
			BX.create("DIV",
				{
					attrs: {className: "crm-entity-stream-content-event-title"},
					events: {click: this._headerClickHandler},
					text: this.getTitle(),
				}
			)
		];
		const statusInfo = this.getStatusInfo();
		if (BX.type.isNotEmptyObject(statusInfo))
		{
			children.push(
				BX.create("SPAN",
					{
						attrs: { className: statusInfo.className },
						text: statusInfo.message
					}
				));
		}
		children.push(
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: this.formatTime(this.getCreatedTime())
				}
			));
		return children;
	}

	prepareContent()
	{
		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history"}});

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info" } })
		);

		const content = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		const header = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-header"},
				children: this.getHeaderChildren()
			}
		);

		const entityData = this.getAssociatedEntityData();
		const title = BX.prop.getString(entityData, "TITLE", "");
		const error = this.getTextDataParam("ERROR");
		const contentChildren = [];

		if(error)
		{
			const errorMessage = this.getTextDataParam("ERROR_MESSAGE");
			contentChildren.push(BX.create("DIV", {
				attrs: { className: "crm-entity-stream-content-detail-description" },
				children: errorMessage
			}));
		}
		else if(title !== "")
		{
			const titleNode = BX.create('span', {text: title});
			const nodeText = title;

			const titleTemplate = BX.prop.getString(this._data, 'TITLE_TEMPLATE', '');
			if (titleTemplate)
			{
				const docType = BX.prop.getString(entityData, "DOC_TYPE");
				if (docType === 'W')
				{
					if (this.getOwnerTypeId() === BX.CrmEntityType.enumeration.deal)
					{
						const documentDetailUrl = BX.prop.getString(this._data, 'DETAIL_LINK', '');
						const documentLinkTag = '<a href="' + documentDetailUrl + '">' + title + '</a>';
						titleNode.innerHTML = titleTemplate.replace('#TITLE#', documentLinkTag);
					}
					else
					{
						titleNode.innerHTML = titleTemplate.replace('#TITLE#', title);
					}
				}
				else
				{
					titleNode.innerHTML = titleTemplate.replace('#TITLE#', title);
				}
			}

			contentChildren.push(BX.create("DIV", {
				attrs: { className: "crm-entity-stream-content-detail-description" },
				children: [titleNode],
			}));
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

	getMessage(name)
	{
		const m = StoreDocumentModification.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	static create(id, settings)
	{
		const self = new StoreDocumentModification();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
