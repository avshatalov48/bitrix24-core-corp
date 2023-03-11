import {Type} from 'main.core';
import {BaseEvent} from 'main.core.events';

import {EntityStorage} from '../entity/entity.storage';

import {Item, ItemParams} from '../item/item';
import {ItemMover, ItemsSortInfo} from '../item/item.mover';

import {EntityCounters} from '../counters/entity.counters';

import {RequestSender} from '../utility/request.sender';
import {TagSearcher} from '../utility/tag.searcher';

import {Entity} from '../entity/entity';

type Params = {
	requestSender: RequestSender,
	entityStorage: EntityStorage,
	entityCounters: EntityCounters,
	tagSearcher: TagSearcher,
	itemMover: ItemMover,
	currentUserId: number,
	groupId: number
}

type PushParams = {
	id: number,
	groupId: number,
	tmpId?: string
}

export class PullItem
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;
		this.entityCounters = params.entityCounters;
		this.tagSearcher = params.tagSearcher;
		this.itemMover = params.itemMover;
		this.currentUserId = params.currentUserId;
		this.groupId = params.groupId;

		this.listToAddAfterUpdate = new Set();

		this.listIdsToSkipAdding = new Set();
		this.listIdsToSkipUpdating = new Set();
		this.listIdsToSkipRemoving = new Set();
		this.listIdsToSkipSorting = new Set();

		this.itemMover.subscribe('calculateSort', this.onCalculateSort.bind(this));
	}

	getModuleId(): string
	{
		return 'tasks';
	}

	getMap(): Object
	{
		return {
			itemAdded: this.onItemAdded.bind(this),
			itemUpdated: this.onItemUpdated.bind(this),
			itemRemoved: this.onItemRemoved.bind(this),
			itemSortUpdated: this.onItemSortUpdated.bind(this),
			comment_add: this.onCommentAdd.bind(this)
		};
	}

	onItemAdded(params: PushParams)
	{
		if (this.groupId !== params.groupId)
		{
			return;
		}

		this.setDelayedAdd(params.id);

		this.externalAdd(params.id)
			.finally(() => this.cleanDelayedAdd(params.id))
			.then(() => {
				this.requestSender.getItemData({
					itemIds: [params.id]
				})
					.then((response) => {
						const itemData = response.data
							.find((itemData: ItemParams) => itemData.id === params.id)
						;
						const item = Item.buildItem(itemData);
						this.addItemToEntity(item);
					})
					.catch((response) => {})
				;
			})
			.catch(() => {})
		;
	}

	onItemUpdated(params: PushParams)
	{
		if (this.groupId !== params.groupId)
		{
			return;
		}

		this.requestSender.getItemData({
			itemIds: [params.id]
		})
			.then((response) => {
				const itemData = response.data
					.find((itemData: ItemParams) => itemData.id === params.id)
				;
				const item = Item.buildItem(itemData);

				if (this.isDelayedAdd(item.getId()))
				{
					this.cleanDelayedAdd(item.getId());

					if (this.needSkipAdd(params.tmpId))
					{
						this.cleanSkipAdd(params.tmpId);

						return;
					}

					this.addItemToEntity(item);

					return;
				}

				if (this.needSkipUpdate(item))
				{
					this.cleanSkipUpdate(item);

					return;
				}

				this.updateItem(item);
			})
			.catch((response) => {})
		;
	}

	onItemRemoved(params: PushParams)
	{
		if (this.groupId !== params.groupId)
		{
			return;
		}

		if (this.needSkipRemove(params.id))
		{
			this.cleanSkipRemove(params.id);

			return;
		}

		const item = this.entityStorage.findItemByItemId(params.id);
		if (item)
		{
			const entity = this.entityStorage.findEntityByItemId(item.getId());
			entity.removeItem(item);
			item.removeYourself();

			this.updateEntityCounters(entity);
		}
	}

	onItemSortUpdated(itemsSortInfo: ItemsSortInfo)
	{
		const itemsInfoToSort = new Map();

		Object.entries(itemsSortInfo).forEach(([itemId, info]) => {
			if (!this.needSkipSort(info.tmpId))
			{
				itemsInfoToSort.set(parseInt(itemId, 10), info);
			}
			this.cleanSkipRemove(info.tmpId);
		});

		if (itemsInfoToSort.size === 0)
		{
			return;
		}

		this.requestSender.getItemData({
			itemIds: [...itemsInfoToSort.keys()]
		})
			.then((response) => {
				const itemsToSort = new Map();
				const newItems = new Set();
				response.data
					.forEach((itemData: ItemParams) => {
						let item = this.entityStorage.findItemByItemId(itemData.id);
						if (!item)
						{
							item = Item.buildItem(itemData);
							newItems.add(item.getId());
						}
						itemsToSort.set(item.getId(), item);
					})
				;
				itemsToSort.forEach((item: Item) => {
					const itemInfoToSort = itemsInfoToSort.get(item.getId());
					if (item.isParentTask() && item.isShownSubTasks())
					{
						item.hideSubTasks();
					}
					item.setSort(itemInfoToSort.sort);
					if (newItems.has(item.getId()))
					{
						item.setPreviousSort(0);
					}
					const sourceEntity = this.entityStorage.findEntityByEntityId(item.getEntityId());
					if (sourceEntity)
					{
						const targetEntityId = (
							Type.isUndefined(itemInfoToSort.entityId)
								? item.getEntityId()
								: itemInfoToSort.entityId
						);
						let targetEntity = this.entityStorage.findEntityByEntityId(targetEntityId);
						if (!targetEntity || sourceEntity.getId() === targetEntity.getId())
						{
							targetEntity = sourceEntity;
						}
						this.itemMover.moveToPosition(sourceEntity, targetEntity, item);
						this.entityStorage.recalculateItemsSort();
					}
				});
			})
			.catch((response) => {})
		;
	}

	onCommentAdd(params)
	{
		const participants = Type.isArray(params.participants) ? params.participants : [];

		if (participants.includes(this.currentUserId.toString()))
		{
			const xmlId = params.entityXmlId.split('_');
			if (xmlId)
			{
				const entityType = xmlId[0];
				const taskId = xmlId[1];
				const item = this.entityStorage.findItemBySourceId(taskId);
				if (entityType === 'TASK' && item)
				{
					this.requestSender.getCurrentState({
						taskId: item.getSourceId()
					}).then((response) => {
						const tmpItem = Item.buildItem(response.data.itemData);
						this.updateItem(tmpItem, item);
					}).catch((response) => {
						this.requestSender.showErrorAlert(response);
					});
				}
			}
		}
	}

	addItemToEntity(item: Item)
	{
		if (item.isSubTask())
		{
			const parentItem = this.entityStorage.findItemBySourceId(item.getParentTaskId());

			if (parentItem && !parentItem.isDecompositionMode())
			{
				parentItem.hideSubTasks();
				parentItem.cleanSubTasks();
			}

			return;
		}

		this.requestSender.hasTaskInFilter({
			taskId: item.getSourceId()
		}).then((response) => {
			if (!response.data.has)
			{
				return;
			}

			const entity = this.entityStorage.findEntityByEntityId(item.getEntityId());
			if (!entity)
			{
				return;
			}

			const existingItem = this.entityStorage.findItemBySourceId(item.getSourceId());
			if (existingItem)
			{
				return;
			}

			this.itemMover.moveToPosition(entity, entity, item);
			this.entityStorage.recalculateItemsSort();
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	updateItem(tmpItem: Item, item?: Item)
	{
		if (!item)
		{
			item = this.entityStorage.findItemByItemId(tmpItem.getId());
		}

		if (item)
		{
			const isParentChangeAction = tmpItem.isSubTask() !== item.isSubTask();
			if (isParentChangeAction)
			{
				if (tmpItem.isSubTask())
				{
					const entity = this.entityStorage.findEntityByItemId(item.getId());
					entity.removeItem(item);
					item.removeYourself();
				}

				return;
			}

			const targetEntityId = tmpItem.getEntityId();
			const sourceEntityId = item.getEntityId();
			const targetEntity = this.entityStorage.findEntityByEntityId(targetEntityId);
			const sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);

			if (tmpItem.getEntityId() !== item.getEntityId())
			{
				if (targetEntity && sourceEntity)
				{
					this.itemMover.moveToPosition(sourceEntity, targetEntity, item);
					this.entityStorage.recalculateItemsSort();
				}
			}
			else
			{
				this.updateEntityCounters(targetEntity);
			}

			item.updateYourself(tmpItem);
		}
		else
		{
			if (tmpItem.isSubTask())
			{
				const targetEntityId = tmpItem.getEntityId();
				const targetEntity = this.entityStorage.findEntityByEntityId(targetEntityId);

				this.updateEntityCounters(targetEntity);
			}
			else
			{
				this.addItemToEntity(tmpItem);
			}
		}
	}

	updateEntityCounters(sourceEntity: Entity, endEntity?: Entity)
	{
		const entities = new Map();

		entities.set(sourceEntity.getId(), sourceEntity);
		if (endEntity)
		{
			entities.set(endEntity.getId(), endEntity);
		}

		this.entityCounters.updateCounters(entities);
	}

	onCalculateSort(baseEvent: BaseEvent)
	{
		const listSortInfo = baseEvent.getData();

		Object.entries(listSortInfo).forEach(([itemId, info]) => {
			if (Object.prototype.hasOwnProperty.call(info, 'tmpId'))
			{
				this.addTmpIdToSkipSorting(info.tmpId);
			}
		});
	}

	addTmpIdToSkipAdding(tmpId: string)
	{
		this.listIdsToSkipAdding.add(tmpId);
	}

	addIdToSkipUpdating(itemId: string)
	{
		this.listIdsToSkipUpdating.add(itemId);
	}

	addIdToSkipRemoving(itemId: number)
	{
		this.listIdsToSkipRemoving.add(itemId);
	}

	addTmpIdToSkipSorting(tmpId: string)
	{
		this.listIdsToSkipSorting.add(tmpId);
	}

	externalAdd(itemId: number): boolean
	{
		return new Promise((resolve, reject) => {
			setTimeout(() => (this.isDelayedAdd(itemId) ? resolve() : reject()), 3000);
		});
	}

	isDelayedAdd(itemId: number): boolean
	{
		return this.listToAddAfterUpdate.has(itemId);
	}

	setDelayedAdd(itemId: number)
	{
		this.listToAddAfterUpdate.add(itemId);
	}

	cleanDelayedAdd(itemId: number)
	{
		this.listToAddAfterUpdate.delete(itemId);
	}

	needSkipAdd(tmpId: string): boolean
	{
		return this.listIdsToSkipAdding.has(tmpId);
	}

	cleanSkipAdd(tmpId: string)
	{
		this.listIdsToSkipAdding.delete(tmpId);
	}

	needSkipUpdate(item: Item): boolean
	{
		return this.listIdsToSkipUpdating.has(item.getId());
	}

	cleanSkipUpdate(item: Item)
	{
		this.listIdsToSkipUpdating.delete(item.getId());
	}

	needSkipRemove(itemId: number): boolean
	{
		return this.listIdsToSkipRemoving.has(itemId);
	}

	cleanSkipRemove(itemId: number)
	{
		this.listIdsToSkipRemoving.delete(itemId);
	}

	needSkipSort(tmpId: string): boolean
	{
		return this.listIdsToSkipSorting.has(tmpId);
	}

	cleanSkipSort(tmpId: string)
	{
		this.listIdsToSkipSorting.delete(tmpId);
	}
}