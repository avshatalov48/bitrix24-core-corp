import Stream from "../stream";
import Email from "../item/scheduled/email";
import Call from "../item/scheduled/call";
import CallTracker from "../item/scheduled/call-tracker";
import Meeting from "../item/scheduled/meeting";
import Task from "../item/scheduled/task";
import StoreDocument from "../item/scheduled/store-document";
import WebForm from "../item/scheduled/webform";
import Wait from "../item/scheduled/wait";
import Request from "../item/scheduled/request";
import Rest from "../item/scheduled/rest";
import OpenLine from "../item/scheduled/openline";
import Zoom from "../item/scheduled/zoom";
import Item from "../animation/item";
import ItemNew from "../animation/item-new";
import HistoryActivity from "../item/history-activity";

/** @memberof BX.Crm.Timeline.Timelines */
export default class Schedule extends Stream
{
	constructor()
	{
		super();
		this._items = [];
		this._history = null;
		this._wrapper = null;
		this._anchor = null;
		this._stub = null;
		this._timeFormat = "";
	}

	doInitialize()
	{
		const datetimeFormat = BX.message("FORMAT_DATETIME").replace(/:SS/, "");
		const dateFormat = BX.message("FORMAT_DATE");
		const timeFormat = BX.util.trim(datetimeFormat.replace(dateFormat, ""));
		this._timeFormat = BX.date.convertBitrixFormat(timeFormat);

		if(!this.isStubMode())
		{
			let itemData = this.getSetting("itemData");
			if(!BX.type.isArray(itemData))
			{
				itemData = [];
			}

			let i, length, item;
			for(i = 0, length = itemData.length; i < length; i++)
			{
				item = this.createItem(itemData[i]);
				if(item)
				{
					this._items.push(item);
				}
			}
		}
	}

	layout()
	{
		this._wrapper = BX.create("DIV", {});
		this._container.appendChild(this._wrapper);

		const label = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-planned-label"},
				text: this.getMessage("planned")
			}
		);

		const wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned-label";
		this._wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: wrapperClassName },
					children:
						[
							BX.create("DIV",
								{
									attrs: { className: "crm-entity-stream-section-content" },
									children: [ label ]
								}
							)
						]
				}
			)
		);

		if(this.isStubMode())
		{
			this.addStub();
		}
		else
		{
			const length = this._items.length;
			if(length === 0)
			{
				this.addStub();
			}
			else
			{
				for(let i = 0; i < length; i++)
				{
					const item = this._items[i];
					item.setContainer(this._wrapper);
					item.layout();
				}
			}
		}

		this.refreshLayout();
		this._manager.processSheduleLayoutChange();
	}

	refreshLayout()
	{
		BX.onCustomEvent('Schedule:onBeforeRefreshLayout', [this]);
		const length = this._items.length;
		if(length === 0)
		{
			this.addStub();
			if(this._history && this._history.hasContent())
			{
				BX.removeClass(this._stub, "crm-entity-stream-section-last");
			}
			else
			{
				BX.addClass(this._stub, "crm-entity-stream-section-last");
			}

			const stubIcon = this._stub.querySelector(".crm-entity-stream-section-icon");
			if(stubIcon)
			{
				if(this._manager.isStubCounterEnabled())
				{
					BX.addClass(stubIcon, "crm-entity-stream-section-counter");
				}
				else
				{
					BX.removeClass(stubIcon, "crm-entity-stream-section-counter");
				}
			}
			return;
		}

		let i, item;
		if(this._history && this._history.hasContent())
		{
			for(i = 0;  i < length; i++)
			{
				item = this._items[i];
				if(item.isTerminated())
				{
					item.markAsTerminated(false);
				}
			}
		}
		else
		{
			if(length > 1)
			{
				for(i = 0;  i < (length - 1); i++)
				{
					item = this._items[i];
					if(item.isTerminated())
					{
						item.markAsTerminated(false);
					}
				}
			}
			this._items[length - 1].markAsTerminated(true);
		}
	}

	formatDateTime(time)
	{
		const now = new Date();
		return BX.date.format(
			[
				[ "today", "today, " + this._timeFormat ],
				[ "tommorow", "tommorow, " + this._timeFormat ],
				[ "yesterday", "yesterday, " + this._timeFormat ],
				[ "" , (time.getFullYear() === now.getFullYear() ? "j F " : "j F Y ") + this._timeFormat ]
			],
			time,
			now
		);
	}

	checkItemForTermination(item)
	{
		if(this._history && this._history.getItemCount() > 0)
		{
			return false;
		}
		return this.getLastItem() === item;
	}

	getLastItem()
	{
		return this._items.length > 0 ? this._items[this._items.length - 1] : null;
	}

	calculateItemIndex(item)
	{
		let i, length;
		const time = item.getDeadline();
		if(time)
		{
			//Item has deadline
			for(i = 0, length = this._items.length; i < length; i++)
			{
				const curTime = this._items[i].getDeadline();
				if(!curTime || time <= curTime)
				{
					return i;
				}
			}
		}
		else
		{
			//Item has't deadline
			const sourceId = item.getSourceId();
			for(i = 0, length = this._items.length; i < length; i++)
			{
				if(this._items[i].getDeadline())
				{
					continue;
				}

				if(sourceId <= this._items[i].getSourceId())
				{
					return i;
				}
			}
		}
		return this._items.length;
	}

	getItemCount()
	{
		return this._items.length;
	}

	getItems()
	{
		return this._items;
	}

	getItemByAssociatedEntity($entityTypeId, entityId)
	{
		if(!BX.type.isNumber($entityTypeId))
		{
			$entityTypeId = parseInt($entityTypeId);
		}

		if(!BX.type.isNumber(entityId))
		{
			entityId = parseInt(entityId);
		}

		if(isNaN($entityTypeId) || $entityTypeId <= 0 || isNaN(entityId) || entityId <= 0)
		{
			return null;
		}

		for(let i = 0, length = this._items.length; i < length; i++)
		{
			const item = this._items[i];
			if(item.getAssociatedEntityTypeId() === $entityTypeId && item.getAssociatedEntityId() === entityId)
			{
				return item;
			}
		}
		return null;
	}

	getItemByData(itemData)
	{
		if(!BX.type.isPlainObject(itemData))
		{
			return null;
		}

		return this.getItemByAssociatedEntity(
			BX.prop.getInteger(itemData, "ASSOCIATED_ENTITY_TYPE_ID", 0),
			BX.prop.getInteger(itemData, "ASSOCIATED_ENTITY_ID", 0)
		);
	}

	getItemIndex(item)
	{
		for(let i = 0, l = this._items.length; i < l; i++)
		{
			if(this._items[i] === item)
			{
				return i;
			}
		}
		return -1;
	}

	getItemByIndex(index)
	{
		return index < this._items.length ? this._items[index] : null;
	}

	removeItemByIndex(index)
	{
		if(index < this._items.length)
		{
			this._items.splice(index, 1);
		}
	}

	createItem(data)
	{
		const entityTypeID = BX.prop.getInteger(data, "ASSOCIATED_ENTITY_TYPE_ID", 0);
		const entityID = BX.prop.getInteger(data, "ASSOCIATED_ENTITY_ID", 0);
		const entityData = BX.prop.getObject(data, "ASSOCIATED_ENTITY", {});
		const itemId = BX.CrmEntityType.resolveName(entityTypeID) + "_" + entityID.toString();

		if(entityTypeID === BX.CrmEntityType.enumeration.wait)
		{
			return Wait.create(
				itemId,
				{
					schedule: this,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else// if(entityTypeID === BX.CrmEntityType.enumeration.activity)
		{
			const typeId = BX.prop.getInteger(entityData, "TYPE_ID", 0);
			const providerId = BX.prop.getString(entityData, "PROVIDER_ID", "");

			if(typeId === BX.CrmActivityType.email)
			{
				return Email.create(
					itemId,
					{
						schedule: this,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if(typeId === BX.CrmActivityType.call)
			{
				return Call.create(
					itemId,
					{
						schedule: this,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if(typeId === BX.CrmActivityType.meeting)
			{
				return Meeting.create(
					itemId,
					{
						schedule: this,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if(typeId === BX.CrmActivityType.task)
			{
				return Task.create(
					itemId,
					{
						schedule: this,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if(typeId === BX.CrmActivityType.provider)
			{
				if(providerId === "CRM_WEBFORM")
				{
					return WebForm.create(
						itemId,
						{
							schedule: this,
							container: this._wrapper,
							activityEditor: this._activityEditor,
							data: data
						}
					);
				}
				else if(providerId === "CRM_REQUEST")
				{
					return Request.create(
						itemId,
						{
							schedule: this,
							container: this._wrapper,
							activityEditor: this._activityEditor,
							data: data
						}
					);
				}
				else if(providerId === "IMOPENLINES_SESSION")
				{
					return OpenLine.create(
						itemId,
						{
							schedule: this,
							container: this._wrapper,
							activityEditor: this._activityEditor,
							data: data
						}
					);
				}
				else if(providerId === "ZOOM")
				{
					return Zoom.create(
						itemId,
						{
							schedule: this,
							container: this._wrapper,
							activityEditor: this._activityEditor,
							data: data
						}
					);
				}
				else if(providerId === "REST_APP")
				{
					return Rest.create(
						itemId,
						{
							schedule: this,
							container: this._wrapper,
							activityEditor: this._activityEditor,
							data: data
						}
					);
				}
				else if(providerId === 'CRM_DELIVERY')
				{
					return HistoryActivity.create(
						itemId,
						{
							schedule: this,
							container: this._wrapper,
							activityEditor: this._activityEditor,
							data: data,
							vueComponent: BX.Crm.Timeline.DeliveryActivity,
						}
					);
				}
				else if(providerId === 'CRM_CALL_TRACKER')
				{
					return CallTracker.create(
						itemId,
						{
							schedule: this,
							container: this._wrapper,
							activityEditor: this._activityEditor,
							data: data,

						}
					);
				}
				else if(providerId === 'STORE_DOCUMENT')
				{
					return StoreDocument.create(
						itemId,
						{
							schedule: this,
							container: this._wrapper,
							activityEditor: this._activityEditor,
							data: data
						}
					);
				}
			}
		}

		return null;
	}

	addItem(item, index)
	{
		if(!BX.type.isNumber(index) || index < 0)
		{
			index = this.calculateItemIndex(item);
		}

		if(index < this._items.length)
		{
			this._items.splice(index, 0, item);
		}
		else
		{
			this._items.push(item);
		}

		this.removeStub();

		this.refreshLayout();
		this._manager.processSheduleLayoutChange();
	}

	getHistory()
	{
		return this._history;
	}

	setHistory(history)
	{
		this._history = history;
	}

	createAnchor(index)
	{
		this._anchor = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-shadow" } });
		if(index >= 0 && index < this._items.length)
		{
			this._wrapper.insertBefore(this._anchor, this._items[index].getWrapper());
		}
		else
		{
			this._wrapper.appendChild(this._anchor);
		}
		return this._anchor;
	}

	deleteItem(item)
	{
		const index = this.getItemIndex(item);
		if(index < 0)
		{
			return;
		}

		item.clearLayout();
		this.removeItemByIndex(index);

		this.refreshLayout();
		this._manager.processSheduleLayoutChange();
	}

	refreshItem(item)
	{
		const index = this.getItemIndex(item);
		if(index < 0)
		{
			return;
		}

		this.removeItemByIndex(index);

		const newItem = this.createItem(item.getData());
		const newIndex = this.calculateItemIndex(newItem);
		if(newIndex === index)
		{
			this.addItem(item, newIndex);
			item.refreshLayout();
			item.addWrapperClass("crm-entity-stream-section-updated", 1000);
			return;
		}

		const anchor = this.createAnchor(newIndex);
		this.addItem(newItem, newIndex);
		newItem.layout({ add: false });

		const animation = Item.create(
			"",
			{
				initialItem: item,
				finalItem: newItem,
				anchor: anchor
			}
		);
		animation.run();
	}

	transferItemToHistory(item, historyItemData)
	{
		const index = this.getItemIndex(item);
		if(index < 0)
		{
			return;
		}

		this.removeItemByIndex(index);

		this.refreshLayout();
		this._manager.processSheduleLayoutChange();

		const historyItem = this._history.createItem(historyItemData);
		this._history.addItem(historyItem, 0);
		historyItem.layout({ add: false });

		const animation = ItemNew.create(
			"",
			{
				initialItem: item,
				finalItem: historyItem,
				anchor: this._history.createAnchor(),
				events: {complete: BX.delegate(this.onTransferComplete, this)}
			}
		);
		animation.run();
	}

	onTransferComplete()
	{
		this._history.refreshLayout();

		if(this._items.length === 0)
		{
			this.addStub();
		}
	}

	onItemMarkedAsDone(item, params)
	{
	}

	addStub()
	{
		if(!this._stub)
		{
			const stubClassName = "crm-entity-stream-section crm-entity-stream-section-planned crm-entity-stream-section-notTask";
			let stubIconClassName = "crm-entity-stream-section-icon crm-entity-stream-section-icon-info";

			let stubMessage = this.getMessage("stub");

			const ownerTypeId = this._manager.getOwnerTypeId();
			if(ownerTypeId === BX.CrmEntityType.enumeration.lead)
			{
				stubMessage = this.getMessage("leadStub");
			}
			else if(ownerTypeId === BX.CrmEntityType.enumeration.deal)
			{
				stubMessage = this.getMessage("dealStub");
			}

			if(this._manager.isStubCounterEnabled())
			{
				stubIconClassName += " crm-entity-stream-section-counter";
			}

			this._stub = BX.create("DIV",
				{
					attrs: { className: stubClassName },
					children:
						[
							BX.create("DIV", { attrs: { className: stubIconClassName } }),
							BX.create("DIV",
								{
									attrs: { className: "crm-entity-stream-section-content" },
									children:
										[
											BX.create("DIV",
												{
													attrs: { className: "crm-entity-stream-content-event" },
													children:
														[
															BX.create("DIV",
																{
																	attrs: { className: "crm-entity-stream-content-detail" },
																	text: stubMessage
																}
															)
														]
												}
											)
										]
								}
							)
						]
				}
			);
			this._wrapper.appendChild(this._stub);
		}

		if(this._history && this._history.getItemCount() > 0)
		{
			BX.removeClass(this._stub, "crm-entity-stream-section-last");
		}
		else
		{
			BX.addClass(this._stub, "crm-entity-stream-section-last");
		}
	}

	removeStub()
	{
		if(this._stub)
		{
			this._stub = BX.remove(this._stub);
		}

	}

	getMessage(name)
	{
		const m = Schedule.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	static create(id, settings)
	{
		const self = new Schedule();
		self.initialize(id, settings);
		Schedule.items[self.getId()] = self;
		return self;
	}

	static items = {};
	static messages = {};
}
