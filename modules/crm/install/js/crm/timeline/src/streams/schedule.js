import Stream from "../stream";
import Activity from "../items/scheduled/activity";
import Email from "../items/scheduled/email";
import Call from "../items/scheduled/call";
import CallTracker from "../items/scheduled/call-tracker";
import Meeting from "../items/scheduled/meeting";
import Task from "../items/scheduled/task";
import StoreDocument from "../items/scheduled/store-document";
import WebForm from "../items/scheduled/webform";
import Wait from "../items/scheduled/wait";
import Request from "../items/scheduled/request";
import Rest from "../items/scheduled/rest";
import OpenLine from "../items/scheduled/openline";
import Zoom from "../items/scheduled/zoom";
import ItemNew from "../animations/item-new";
import {ConfigurableItem} from "crm.timeline.item";
import {StreamType} from 'crm.timeline.item';

/** @memberof BX.Crm.Timeline.Streams */
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

	getItems(): Array
	{
		return this._items;
	}

	setItems(items: Array): void
	{
		this._items = items;
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


	getItemByIndex(index)
	{
		return index < this._items.length ? this._items[index] : null;
	}

	createItem(data)
	{
		const entityTypeID = BX.prop.getInteger(data, "ASSOCIATED_ENTITY_TYPE_ID", 0);
		const entityID = BX.prop.getInteger(data, "ASSOCIATED_ENTITY_ID", 0);
		const entityData = BX.prop.getObject(data, "ASSOCIATED_ENTITY", {});
		let itemId = BX.CrmEntityType.resolveName(entityTypeID) + "_" + entityID.toString();

		if (data.hasOwnProperty('type'))
		{
			itemId = data.id;

			return ConfigurableItem.create(itemId, {
				timelineId: this.getId(),
				container: this.getWrapper(),
				itemClassName: this.getItemClassName(),
				isReadOnly: this.isReadOnly(),
				streamType: this.getStreamType(),
				data: data,
			})
		}

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
					return Activity.create(
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

	getWrapper()
	{
		return this._wrapper;
	}

	getItemClassName()
	{
		return 'crm-entity-stream-section crm-entity-stream-section-planned';
	}

	getStreamType(): number
	{
		return StreamType.scheduled;
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

	animateItemAdding(item): Promise
	{
		item.addWrapperClass('crm-entity-stream-section-updated', 1000);
		return Promise.resolve();
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
