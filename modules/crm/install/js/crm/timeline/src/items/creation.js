import HistoryItem from "./history";
import {Item as ItemType} from "../types";

/** @memberof BX.Crm.Timeline.Items */
export default class Creation extends HistoryItem
{
	static messages = {};

	constructor()
	{
		super();
	}

	static create(id, settings)
	{
		const self = new Creation();
		self.initialize(id, settings);
		return self;
	}

	doInitialize()
	{
		super.doInitialize();
		if (!(this._activityEditor instanceof BX.CrmActivityEditor))
		{
			throw "Creation. The field 'activityEditor' is not assigned.";
		}
	}

	getTitle()
	{
		const entityTypeId = this.getAssociatedEntityTypeId();
		const entityData = this.getAssociatedEntityData();
		if (entityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			const typeId = BX.prop.getInteger(entityData, "TYPE_ID");
			const title = this.getMessage(typeId === BX.CrmActivityType.task ? "task" : "activity");
			return title.replace(/#TITLE#/gi, this.cutOffText(BX.prop.getString(entityData, "SUBJECT")), 64);
		}

		if (entityTypeId === BX.CrmEntityType.enumeration.storeDocument)
		{
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

		const entityTypeName = BX.CrmEntityType.resolveName(this.getAssociatedEntityTypeId()).toLowerCase();
		let msg = this.getMessage(entityTypeName);
		const isMessageNotFound = (msg === entityTypeName);
		if (!BX.type.isNotEmptyString(msg) || isMessageNotFound)
		{
			msg = this.getTextDataParam("TITLE");
		}

		return msg;
	}

	getWrapperClassName()
	{
		return "crm-entity-stream-section-createEntity";
	}

	prepareContent()
	{
		const entityTypeId = this.getAssociatedEntityTypeId();

		if (
			entityTypeId === BX.CrmEntityType.enumeration.ordershipment
			|| entityTypeId === BX.CrmEntityType.enumeration.orderpayment
		)
		{
			const data = this.getData();
			data.TYPE_CATEGORY_ID = ItemType.modification;
			if (data.hasOwnProperty('ASSOCIATED_ENTITY'))
			{
				data.ASSOCIATED_ENTITY.HTML_TITLE = '';
			}

			const createOrderEntityItem = this._history.createOrderEntityItem(data);
			return createOrderEntityItem.prepareContent();
		}

		return super.prepareContent();
	}

	prepareContentDetails()
	{
		const entityTypeId = this.getAssociatedEntityTypeId();
		const entityId = this.getAssociatedEntityId();
		const entityData = this.getAssociatedEntityData();

		if (entityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			const link = BX.create("A",
				{
					attrs: {href: "#"},
					html: this.cutOffText(BX.prop.getString(entityData, "DESCRIPTION_RAW"), 128)
				}
			);
			BX.bind(link, "click", this._headerClickHandler);

			return [link];
		}

		const title = BX.prop.getString(entityData, "TITLE", "");
		let htmlTitle = BX.prop.getString(entityData, "HTML_TITLE", "");
		const showUrl = BX.prop.getString(entityData, "SHOW_URL", "");

		if (
			entityTypeId === BX.CrmEntityType.enumeration.deal
			&& BX.prop.getObject(entityData, "ORDER", null)
		)
		{
			const orderData = BX.prop.getObject(entityData, "ORDER", null);
			htmlTitle = this.getMessage('dealOrderTitle')
				.replace("#ORDER_ID#", orderData.ID)
				.replace("#DATE_TIME#", orderData.ORDER_DATE)
				.replace("#HREF#", orderData.SHOW_URL)
				.replace("#PRICE_WITH_CURRENCY#", orderData.SUM)
			;
		}

		if (title !== "" || htmlTitle !== "")
		{
			const nodes = [];
			if (showUrl === "" || (entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId()))
			{
				const spanAttrs = (htmlTitle !== "") ? {html: htmlTitle} : {text: title};
				nodes.push(BX.create("SPAN", spanAttrs));
			}
			else
			{
				let linkAttrs = {attrs: {href: showUrl}, text: title};
				if (htmlTitle !== "")
				{
					linkAttrs = {attrs: {href: showUrl}, html: htmlTitle};
				}
				nodes.push(BX.create("A", linkAttrs));
			}

			const legend = this.getTextDataParam("LEGEND");
			if (legend !== "")
			{
				nodes.push(BX.create("BR"));
				nodes.push(BX.create("SPAN", {text: legend}));
			}

			const baseEntityData = this.getObjectDataParam("BASE");
			const baseEntityInfo = BX.prop.getObject(baseEntityData, "ENTITY_INFO");
			if (baseEntityInfo)
			{
				nodes.push(BX.create("BR"));
				nodes.push(BX.create("SPAN", {text: BX.prop.getString(baseEntityData, "CAPTION") + ": "}));
				nodes.push(
					BX.create("A",
						{
							attrs: {href: BX.prop.getString(baseEntityInfo, "SHOW_URL", "#")},
							text: BX.prop.getString(baseEntityInfo, "TITLE", "")
						}
					)
				);
			}
			return nodes;
		}
		return [];
	}

	view()
	{
		const entityTypeId = this.getAssociatedEntityTypeId();
		if (entityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			const entityData = this.getAssociatedEntityData();
			const id = BX.prop.getInteger(entityData, "ID", 0);
			if (id > 0)
			{
				this._activityEditor.viewActivity(id);
			}
		}
	}

	getMessage(name)
	{
		const m = Creation.messages;

		return m.hasOwnProperty(name) ? m[name] : name;
	}
}
