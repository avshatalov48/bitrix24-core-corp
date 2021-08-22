import {Type} from 'main.core';
import {BaseEvent} from 'main.core.events';

import {Item} from '../item/item';

import {RequestSender} from './request.sender';
import {DomBuilder} from './dom.builder';
import {EntityStorage} from './entity.storage';
import {EntityCounters} from './entity.counters';
import {TagSearcher} from './tag.searcher';
import {ItemMover} from './item.mover';
import {SubTasksManager} from './subtasks.manager';

import {Entity} from '../entity/entity';

import type {ItemParams} from '../item/item';
import type {ItemsSortInfo} from './item.mover';

type Params = {
	requestSender: RequestSender,
	domBuilder: DomBuilder,
	entityStorage: EntityStorage,
	entityCounters: EntityCounters,
	tagSearcher: TagSearcher,
	itemMover: ItemMover,
	subTasksCreator: SubTasksManager,
	currentUserId: number
}

type RemoveParams = {
	itemId: number
}

export class PullItem
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.domBuilder = params.domBuilder;
		this.entityStorage = params.entityStorage;
		this.entityCounters = params.entityCounters;
		this.tagSearcher = params.tagSearcher;
		this.itemMover = params.itemMover;
		this.subTasksCreator = params.subTasksCreator;
		this.currentUserId = params.currentUserId;

		this.listToAddAfterUpdate = new Map();

		this.listIdsToSkipAdding = new Set();
		this.listIdsToSkipUpdating = new Set();
		this.listIdsToSkipRemoving = new Set();
		this.listIdsToSkipSorting = new Set();

		this.itemMover.subscribe('calculateSort', this.onCalculateSort.bind(this))
	}

	getModuleId()
	{
		return 'tasks';
	}

	getMap()
	{
		return {
			itemAdded: this.onItemAdded.bind(this),
			itemUpdated: this.onItemUpdated.bind(this),
			itemRemoved: this.onItemRemoved.bind(this),
			itemSortUpdated: this.onItemSortUpdated.bind(this),
			comment_add: this.onCommentAdd.bind(this)
		};
	}

	onItemAdded(itemData: ItemParams)
	{
		const item = new Item(itemData);

		item.cleanTaskCounts();

		this.setDelayedAdd(item);

		this.externalAdd(item)
			.finally(() => this.cleanDelayedAdd(item))
			.then(() => this.addItemToEntity(item))
			.catch(() => {})
		;
	}

	onItemUpdated(itemData: ItemParams)
	{
		const item = new Item(itemData);

		item.cleanTaskCounts();

		if (this.isDelayedAdd(item))
		{
			this.cleanDelayedAdd(item);

			if (this.needSkipAdd(item))
			{
				this.cleanSkipAdd(item);

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
	}

	onItemRemoved(params: RemoveParams)
	{
		if (this.needSkipRemove(params.itemId))
		{
			this.cleanSkipRemove(params.itemId);

			return;
		}

		const item = this.entityStorage.findItemByItemId(params.itemId);
		if (item)
		{
			const entity = this.entityStorage.findEntityByItemId(item.getItemId());
			entity.removeItem(item);
			item.removeYourself();
		}
	}

	onItemSortUpdated(itemsSortInfo : ItemsSortInfo)
	{
		const itemsToSort = new Map();
		const itemsInfoToSort = new Map();
		Object.entries(itemsSortInfo).forEach(([itemId, info]) => {
			const item = this.entityStorage.findItemByItemId(itemId);
			if (item)
			{
				if (!this.needSkipSort(info.tmpId))
				{
					itemsToSort.set(item.getItemId(), item);
					itemsInfoToSort.set(item.getItemId(), info);
				}
				this.cleanSkipRemove(info.tmpId);
			}
		});

		itemsToSort.forEach((item: Item) => {
			const itemInfoToSort = itemsInfoToSort.get(item.getItemId());
			item.setSort(itemInfoToSort.sort);
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
						const tmpItem = new Item(response.data.itemData);
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

			const bindItemNode = entity.getListItemsNode().children[1];

			if (bindItemNode)
			{
				this.domBuilder.insertBefore(item.render(), bindItemNode);
			}
			else
			{
				this.domBuilder.append(item.render(), entity.getListItemsNode());
			}

			item.onAfterAppend(entity.getListItemsNode());
			entity.setItem(item);

			this.updateEntityCounters(entity);

			item.getTags().forEach((tag) => {
				this.tagSearcher.addTagToSearcher(tag);
			});
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	updateItem(tmpItem: Item, item?: Item)
	{
		if (!item)
		{
			item = this.entityStorage.findItemByItemId(tmpItem.getItemId());
		}
		if (item)
		{
			if (item.isParentTask())
			{
				if (this.subTasksCreator.isShown(item))
				{
					const entity = this.entityStorage.findEntityByEntityId(item.getEntityId());
					this.subTasksCreator.hideSubTaskItems(entity, item);
				}
				this.subTasksCreator.cleanSubTasks(item);
			}

			const isParentChangeAction = tmpItem.isSubTask() !== item.isSubTask();
			if (isParentChangeAction)
			{
				if (tmpItem.isSubTask())
				{
					const entity = this.entityStorage.findEntityByItemId(item.getItemId());
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
				const parentItem = this.entityStorage.findItemBySourceId(tmpItem.getParentTaskId());
				if (parentItem)
				{
					parentItem.updateSubTasksPoints(tmpItem.getSourceId(), tmpItem.getStoryPoints());
				}

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

	addTmpIdsToSkipAdding(tmpId: string)
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

	addTmpIdToSkipSorting(itemId: number)
	{
		this.listIdsToSkipSorting.add(itemId);
	}

	externalAdd(item: Item): boolean
	{
		return new Promise((resolve, reject) => {
			setTimeout(() => (this.isDelayedAdd(item) ? resolve() : reject()), 3000);
		});
	}

	isDelayedAdd(item: Item): boolean
	{
		return this.listToAddAfterUpdate.has(item.getItemId());
	}

	setDelayedAdd(item: Item)
	{
		this.listToAddAfterUpdate.set(item.getItemId(), item);
	}

	cleanDelayedAdd(item: Item)
	{
		this.listToAddAfterUpdate.delete(item.getItemId());
	}

	needSkipAdd(item: Item): boolean
	{
		return this.listIdsToSkipAdding.has(item.getTmpId());
	}

	cleanSkipAdd(item: Item)
	{
		this.listIdsToSkipAdding.delete(item.getTmpId());
	}

	needSkipUpdate(item: Item): boolean
	{
		return this.listIdsToSkipUpdating.has(item.getItemId());
	}

	cleanSkipUpdate(item: Item)
	{
		this.listIdsToSkipUpdating.delete(item.getItemId());
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