import OrderCreation from "./order-creation";

/** @memberof BX.Crm.Timeline.Actions */
export default class StoreDocumentCreation extends OrderCreation
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
			throw "StoreDocumentCreation. The field 'activityEditor' is not assigned.";
		}
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-info";
	}

	getTitle()
	{
		const entityData = this.getAssociatedEntityData();
		const docType = BX.prop.getString(entityData, "DOC_TYPE");
		if (docType === 'A')
		{
			return this.getMessage('arrivalDocument');
		}
		if (docType === 'S')
		{
			return this.getMessage('storeAdjustmentDocument');
		}
		if (docType === 'M')
		{
			return this.getMessage('movingDocument');
		}
		if (docType === 'D')
		{
			return this.getMessage('deductDocument');
		}
		if (docType === 'W')
		{
			return this.getMessage('shipmentDocument');
		}

		return '';
	}

	getWrapperClassName()
	{
		return "crm-entity-stream-section-createStoreDocumentEntity";
	}

	prepareContentDetails()
	{
		const entityData = this.getAssociatedEntityData();
		const title = BX.prop.getString(entityData, "TITLE", "");
		const nodes = [];

		if(title === '')
		{
			return nodes;
		}

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

		nodes.push(BX.create("DIV", {
			attrs: { className: "crm-entity-stream-content-detail-description" },
			children: [titleNode],
		}));

		return nodes;
	}

	getMessage(name)
	{
		const m = StoreDocumentCreation.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	static create(id, settings)
	{
		const self = new StoreDocumentCreation();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
