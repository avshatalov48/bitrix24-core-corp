import {Loc, Type, Text, Dom} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Menu} from 'main.popup';
import {MessageBox} from 'ui.dialogs.messagebox';
import {Draggable} from 'ui.draganddrop.draggable';

import {PlanBuilder} from '../view/plan/plan.builder';

import {EntityStorage} from '../entity/entity.storage';
import {Entity} from '../entity/entity';
import {Sprint} from '../entity/sprint/sprint';

import {Item} from './item';

import {EntityCounters} from '../counters/entity.counters';

import {RequestSender} from '../utility/request.sender';
import {Scroller} from "../utility/scroller";

export type ItemsSortInfo = {
	[id: number]: {
		sort: number,
		entityId?: number,
		tmpId?: string,
		updatedItemId?: number
	}
}

type Params = {
	requestSender: RequestSender,
	planBuilder: PlanBuilder,
	entityStorage: EntityStorage,
	entityCounters: EntityCounters
}

export class ItemMover extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.ItemMover');

		this.requestSender = params.requestSender;
		this.planBuilder = params.planBuilder;
		this.entityStorage = params.entityStorage;
		this.entityCounters = params.entityCounters;

		this.scroller = new Scroller({
			planBuilder: this.planBuilder,
			entityStorage: this.entityStorage
		});

		this.dragItems = new Set();

		this.lastOutContainer = null;

		this.bindHandlers();
	}

	bindHandlers()
	{
		this.planBuilder.subscribe('setDraggable', this.onSetDraggable.bind(this));
		this.planBuilder.subscribe('createSprintNode', (baseEvent: BaseEvent) => {
			const sprint = baseEvent.getData();
			this.draggableItems.addContainer(sprint.getListItemsNode());
			this.draggableItems.addDropzone(sprint.getDropzone());
		});
	}

	onSetDraggable(baseEvent: BaseEvent)
	{
		const backlog = this.entityStorage.getBacklog();

		const containers = [
			backlog.getListItemsNode()
		];
		const dropZones = [
			backlog.getDropzone(),
			this.planBuilder.getSprintDropzone()
		];

		this.entityStorage.getSprints().forEach((sprint) => {
			if (!sprint.isDisabled())
			{
				containers.push(sprint.getListItemsNode());

				if (sprint.getDropzone())
				{
					dropZones.push(sprint.getDropzone());
				}
			}
		});

		this.draggableItems = new Draggable({
			container: containers,
			dropzone: dropZones,
			draggable: '.tasks-scrum__item--drag',
			dragElement: '.tasks-scrum__item',
			type: Draggable.CLONE,
			delay: 200
		});

		this.draggableItems.subscribe('beforeStart', this.onBeforeDragStart.bind(this));
		this.draggableItems.subscribe('start', this.onDragStart.bind(this));
		this.draggableItems.subscribe('end', this.onDragEnd.bind(this));

		this.draggableItems.subscribe('drop', this.onDropEnd.bind(this));
		this.draggableItems.subscribe('dropzone:enter', this.onDropZoneEnter.bind(this));
		this.draggableItems.subscribe('dropzone:out', this.onDropZoneOut.bind(this));
	}

	onBeforeDragStart(baseEvent: BaseEvent)
	{
		const dragBeforeStartEvent = baseEvent.getData();

		if (!dragBeforeStartEvent.source)
		{
			return;
		}

		const itemId = parseInt(dragBeforeStartEvent.source.dataset.id, 10);

		const item = this.entityStorage.findItemByItemId(itemId);

		if (!item || item.isSubTask() || item.isDisabled())
		{
			baseEvent.preventDefault();
		}
		else
		{
			if (item.isShownSubTasks())
			{
				item.hideSubTasks();
			}

			const sourceContainer = dragBeforeStartEvent.sourceContainer;
			if (Type.isUndefined(sourceContainer))
			{
				baseEvent.preventDefault();

				return;
			}

			const sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
			const sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);
			if (!sourceEntity)
			{
				baseEvent.preventDefault();

				return;
			}

			this.dragItems.clear();

			sourceEntity.getGroupModeItems()
				.forEach((selectedItem: Item) => {
					if (selectedItem.getId() !== item.getId())
					{
						this.dragItems.add(selectedItem);
					}
				})
			;

			const isMultipleDrag = (this.dragItems.size > 0);
			if (isMultipleDrag)
			{
				this.addMultipleMode(item, this.dragItems);
			}

			this.entityStorage.getAllEntities()
				.forEach((entity: Entity) => {
					entity.deactivateGroupMode();
					entity.getItems()
						.forEach((entityItem: Item) => {
							if (entityItem.isShownSubTasks())
							{
								entityItem.hideSubTasks();
							}
						})
					;
				})
			;

			this.emit('dragStart');
		}
	}

	onDragStart(baseEvent: BaseEvent)
	{
		this.planBuilder.blockScrumContainerSelect();

		const dragStartEvent = baseEvent.getData();

		const sourceContainer = dragStartEvent.sourceContainer;
		const sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);

		const sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);

		this.entityStorage
			.getAllEntities()
			.forEach((entity: Entity) => {
				if (!entity.isCompleted() && sourceEntity.getId() !== entity.getId())
				{
					if (entity.isEmpty())
					{
						entity.showDropzone();
						entity.hideEmptySearchStub();
						entity.hideBlank();
					}
				}
			})
		;
	}

	onDragEnd(baseEvent: BaseEvent)
	{
		const dragEndEvent = baseEvent.getData();

		const itemNode = dragEndEvent.source;
		const itemId = parseInt(itemNode.dataset.id, 10);

		const item = this.entityStorage.findItemByItemId(itemId);

		if (!item)
		{
			return;
		}

		const isMultipleDrag = (this.dragItems.size > 0);
		if (isMultipleDrag)
		{
			this.removeMultipleMode(item, this.dragItems);
		}

		this.planBuilder.unblockScrumContainerSelect();

		const sourceContainer = dragEndEvent.sourceContainer;
		let endContainer = dragEndEvent.endContainer;
		if (!endContainer)
		{
			if (this.isDropToZone)
			{
				this.isDropToZone = false;
			}
			else
			{
				endContainer = this.lastOutContainer;
			}
		}

		this.lastOutContainer = null;

		if (!endContainer)
		{
			baseEvent.preventDefault();

			return;
		}

		const sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
		const endEntityId = parseInt(endContainer.dataset.entityId, 10);

		const sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);
		const endEntity = this.entityStorage.findEntityByEntityId(endEntityId);

		if (sourceEntity && endEntity)
		{
			this.onItemMove(item, sourceEntity, endEntity)
				.then(() => {
					const isMultipleDrag = (this.dragItems.size > 0);
					if (isMultipleDrag)
					{
						this.dragGroupItems(item, this.dragItems, sourceEntity, endEntity);
					}
				})
			;

			sourceEntity.adjustListItemsWidth();
			endEntity.adjustListItemsWidth();
		}

		if (sourceEntity)
		{
			this.entityStorage
				.getAllEntities()
				.forEach((entity: Entity) => {
					if (!entity.isCompleted() && sourceEntity.getId() !== entity.getId())
					{
						if (entity.isEmpty())
						{
							if (entity.getNumberTasks() > 0 && entity.isExactSearchApplied())
							{
								entity.showEmptySearchStub();
								entity.hideDropzone();
							}
							else
							{
								entity.showDropzone();
								entity.hideEmptySearchStub();
							}
						}
					}
				})
			;
		}

		this.planBuilder.adjustSprintListWidth();
	}

	onDropEnd(baseEvent: BaseEvent)
	{
		const dragDropEvent = baseEvent.getData();

		const dropzone = dragDropEvent.dropzone;

		const sourceContainer = dragDropEvent.sourceContainer;

		const sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
		const endEntityId = parseInt(dropzone.dataset.entityId, 10);

		const sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);
		const endEntity = this.entityStorage.findEntityByEntityId(endEntityId);

		const itemNode = dragDropEvent.source;
		const itemId = parseInt(itemNode.dataset.id, 10);

		const item = this.entityStorage.findItemByItemId(itemId);

		if (this.planBuilder.isSprintDropzone(dropzone))
		{
			this.planBuilder.createSprint()
				.then((sprint: Sprint) => {
					this.addSprintContainers(sprint);
					this.moveTo(sourceEntity, sprint, item);
					const isMultipleDrag = (this.dragItems.size > 0);
					if (isMultipleDrag)
					{
						this.dragGroupItems(item, this.dragItems, sourceEntity, sprint);
					}
				})
			;
		}
		else
		{
			if (sourceEntity && endEntity)
			{
				const itemNode = dragDropEvent.source;
				const itemId = parseInt(itemNode.dataset.id, 10);

				const item = this.entityStorage.findItemByItemId(itemId);
				if (item)
				{
					this.onItemMove(item, sourceEntity, endEntity, true)
						.then(() => {
							const isMultipleDrag = (this.dragItems.size > 0);
							if (isMultipleDrag)
							{
								this.dragGroupItems(item, this.dragItems, sourceEntity, endEntity);
							}

							sourceEntity.adjustListItemsWidth();
							endEntity.adjustListItemsWidth();

							this.planBuilder.adjustSprintListWidth();
						})
					;
				}
			}
		}

		this.entityStorage
			.getAllEntities()
			.forEach((entity: Entity) => {
				if (!entity.isCompleted() && sourceEntity.getId() !== entity.getId())
				{
					if (entity.isEmpty())
					{
						if (entity.getNumberTasks() > 0 && entity.isExactSearchApplied())
						{
							entity.showEmptySearchStub();
							entity.hideDropzone();
						}
						else
						{
							entity.showDropzone();
							entity.hideEmptySearchStub();
						}
					}
				}
			})
		;
	}

	onDropZoneEnter()
	{
		this.isDropToZone = true;
	}

	onDropZoneOut()
	{
		this.isDropToZone = false;
	}

	onItemMove(item: Item, sourceEntity: Entity, endEntity: Entity, insertDom: false): Promise
	{
		if (sourceEntity.getId() === endEntity.getId())
		{
			return this.moveInCurrentContainer(new Set([item.getId()]), sourceEntity);
		}
		else
		{
			const message = Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE');

			return this.onMoveConfirm(sourceEntity, message)
				.then(() => {
					if (insertDom)
					{
						Dom.insertBefore(item.getNode(), endEntity.getLoaderNode());
					}
					this.moveItemFromEntityToEntity(item, sourceEntity, endEntity);
					this.moveInAnotherContainer(new Set([item.getId()]), sourceEntity, endEntity);
				})
				.catch(() => {
					Dom.insertBefore(
						item.getNode(),
						sourceEntity.getListItemsNode().children[item.getSort() - 1]
					);
				})
			;
		}
	}

	onMoveItemUpdate(entityFrom: Entity, entityTo: Entity, item: Item)
	{
		this.requestSender.updateItemSort({
			entityId: entityTo.getId(),
			itemIds: [item.getId()],
			sortInfo: {
				...this.calculateSort(entityTo.getListItemsNode(), new Set([item.getId()]), true),
				...this.calculateSort(entityFrom.getListItemsNode(), new Set(), true)
			}
		})
			.then(() => {
				this.updateEntityCounters(entityFrom, entityTo);
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	onMoveConfirm(entity: Entity, message: string): Promise
	{
		return new Promise((resolve, reject) => {
			if (entity.isActive())
			{
				MessageBox.confirm(
					message,
					(messageBox) => {
						messageBox.close();
						resolve();
					},
					Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_MOVE'),
					(messageBox) => {
						messageBox.close();
						reject();
					},
				);
			}
			else
			{
				resolve();
			}
		});
	}

	dragGroupItems(dragItem: Item, dragItems: Set<Item>, entityFrom: Entity, entityTo: Entity)
	{
		const isMoveInCurrentContainer = (entityFrom.getId() === entityTo.getId());

		const sortedDragItems = [...dragItems.values()]
			.sort((first: Item, second: Item) => {
				if (first.getSort() < second.getSort()) return 1;
				if (first.getSort() > second.getSort()) return -1;
			})
		;

		const dragItemIds = new Set();
		sortedDragItems
			.forEach((groupedItem: Item) => {
				dragItemIds.add(groupedItem.getId());
				entityTo.appendNodeAfterItem(groupedItem.getNode(), dragItem.getNode());
				if (!isMoveInCurrentContainer)
				{
					this.moveItemFromEntityToEntity(groupedItem, entityFrom, entityTo);
				}
			})
		;

		if (isMoveInCurrentContainer)
		{
			this.moveInCurrentContainer(dragItemIds, entityFrom);
		}
		else
		{
			this.moveInAnotherContainer(dragItemIds, entityFrom, entityTo);
		}
	}

	addMultipleMode(item: Item, dragItems: Set<Item>)
	{

		Dom.addClass(item.getNode(), (dragItems.size > 1 ? '--multiple-drag-many' : '--multiple-drag'));

		dragItems
			.forEach((dragItem: Item) => {
				Dom.addClass(dragItem.getNode(), '--multiple-drag-shadow');
			})
		;
	}

	removeMultipleMode(item: Item, dragItems: Set<Item>)
	{

		Dom.removeClass(item.getNode(), '--multiple-drag');
		Dom.removeClass(item.getNode(), '--multiple-drag-many');

		dragItems
			.forEach((dragItem: Item) => {
				Dom.removeClass(dragItem.getNode(), '--multiple-drag-shadow');
			})
		;
	}

	addSprintContainers(sprint: Sprint)
	{
		if (!sprint.isDisabled())
		{
			this.draggableItems.addContainer(sprint.getListItemsNode());
			this.draggableItems.addDropzone(sprint.getDropzone());
		}
	}

	moveItem(item: Item, button)
	{
		const entity = this.entityStorage.findEntityByItemId(item.getId());

		const listToMove = [];

		if (!entity.isFirstItem(item))
		{
			listToMove.push({
				text: Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_UP'),
				onclick: (event, menuItem) => {
					const groupModeItems = entity.getGroupModeItems();

					const sortedItems = [...groupModeItems.values()].sort((first: Item, second: Item) => {
						if (first.getSort() < second.getSort()) return 1;
						if (first.getSort() > second.getSort()) return -1;
					});
					const sortedItemsIds = new Set();

					sortedItems.forEach((groupModeItem: Item) => {
						sortedItemsIds.add(groupModeItem.getId());
						if (groupModeItem.isParentTask() && groupModeItem.isShownSubTasks())
						{
							groupModeItem.hideSubTasks();
						}
						this.moveItemToUp(groupModeItem, entity.getListItemsNode(), false);
						groupModeItem.activateBlinking();
					});

					this.scroller.scrollToItem(sortedItems.values().next().value);

					this.requestSender.updateItemSort({
						sortInfo: this.calculateSort(entity.getListItemsNode(), sortedItemsIds)
					}).catch((response) => {
						this.requestSender.showErrorAlert(response);
					});

					entity.deactivateGroupMode();

					menuItem.getMenuWindow().close();
				}
			});
		}
		if (!entity.isLastItem(item))
		{
			listToMove.push({
				text: Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_DOWN'),
				onclick: (event, menuItem) => {
					const groupModeItems = entity.getGroupModeItems();

					const sortedItems = [...groupModeItems.values()].sort((first: Item, second: Item) => {
						if (first.getSort() > second.getSort()) return 1;
						if (first.getSort() < second.getSort()) return -1;
					});

					const sortedItemsIds = new Set();
					sortedItems.forEach((groupModeItem: Item) => {
						sortedItemsIds.add(groupModeItem.getId());
						if (groupModeItem.isParentTask() && groupModeItem.isShownSubTasks())
						{
							groupModeItem.hideSubTasks();
						}
						this.moveItemToDown(groupModeItem, entity.getListItemsNode(), false);
						groupModeItem.activateBlinking();
					});

					this.scroller.scrollToItem(sortedItems.values().next().value);

					this.requestSender.updateItemSort({
						sortInfo: this.calculateSort(entity.getListItemsNode(), sortedItemsIds)
					}).catch((response) => {
						this.requestSender.showErrorAlert(response);
					});

					entity.deactivateGroupMode();

					menuItem.getMenuWindow().close();
				}
			});
		}

		this.showMoveItemMenu(item, button, listToMove);
	}

	moveItemToUp(item: Item, listItemsNode, updateSort = true)
	{
		Dom.insertBefore(item.getNode(), listItemsNode.firstElementChild);

		if (updateSort)
		{
			this.updateItemsSort(item, listItemsNode);
		}
	}

	moveItemToDown(item, listItemsNode, updateSort = true)
	{
		Dom.insertBefore(item.getNode(), listItemsNode.lastElementChild);

		if (updateSort)
		{
			this.updateItemsSort(item, listItemsNode);
		}
	}

	updateItemsSort(item: Item, listItemsNode: HTMLElement)
	{
		this.requestSender.updateItemSort({
			itemIds: [item.getId()],
			sortInfo: {
				...this.calculateSort(listItemsNode, new Set([item.getId()]))
			}
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	moveInCurrentContainer(itemIds: Set<Item>, entity: Entity)
	{
		return this.requestSender.updateItemSort({
			sortInfo: this.calculateSort(entity.getListItemsNode(), itemIds)
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	moveInAnotherContainer(itemIds: Set<Item>, sourceEntity: Entity, endEntity: Entity)
	{
		this.requestSender.updateItemSort({
			entityId: endEntity.getId(),
			itemIds: Array.from(itemIds),
			sortInfo: this.calculateSort(endEntity.getListItemsNode(), itemIds, true)
		})
			.then(() => this.updateEntityCounters(sourceEntity, endEntity))
			.catch((response) => this.requestSender.showErrorAlert(response))
		;
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

	calculateSort(container, updatedItemsIds?: Set, moveToAnotherEntity = false): ItemsSortInfo
	{
		const listSortInfo = {};

		const items = [...container.querySelectorAll('[data-sort]')];
		let sort = 1;
		items.forEach((itemNode) => {
			const itemId = parseInt(itemNode.dataset.id, 10);
			const item = this.entityStorage.findItemByItemId(itemId);
			if (item && !item.isSubTask())
			{
				const tmpId = Text.getRandom();
				let isSortUpdated = (sort !== item.getSort());
				item.setSort(sort);
				listSortInfo[itemId] = {
					sort: sort
				};
				if (moveToAnotherEntity)
				{
					listSortInfo[itemId].entityId = container.dataset.entityId;
					isSortUpdated = true;
				}
				if (isSortUpdated && updatedItemsIds && updatedItemsIds.has(itemId))
				{
					listSortInfo[itemId].tmpId = tmpId;
					listSortInfo[itemId].updatedItemId = itemId;
				}
				itemNode.dataset.sort = sort;

				sort++;
			}
		});

		this.emit('calculateSort', listSortInfo);

		return listSortInfo;
	}

	resortItems(entity: Entity)
	{
		let sort = 1;
		[...entity.getListItemsNode().querySelectorAll('[data-sort]')]
			.forEach((itemNode: HTMLElement) => {
				const itemId = parseInt(itemNode.dataset.id, 10);
				const item = this.entityStorage.findItemByItemId(itemId);
				if (item && !item.isSubTask())
				{
					item.setSort(sort);
					sort++;
				}
			})
		;
	}

	moveToAnotherEntity(entityFrom: Entity, item: Item, targetEntity: ?Entity, bindButton?: HTMLElement)
	{
		const isMoveToSprint = (Type.isNull(targetEntity));

		const sprints = (isMoveToSprint ? this.entityStorage.getSprintsAvailableForFilling(entityFrom) : null);

		if (isMoveToSprint)
		{
			if (sprints.size > 1)
			{
				this.showListSprintsToMove(entityFrom, item, bindButton);
			}
			else
			{
				if (sprints.size === 0)
				{
					this.planBuilder.createSprint().then((sprint: Sprint) => {
						this.moveToWithGroupMode(entityFrom, sprint, item, true, false);
					});
				}
				else
				{
					sprints.forEach((sprint: Sprint) => {
						this.moveToWithGroupMode(entityFrom, sprint, item, true, false);
					});
				}
			}
		}
		else
		{
			const message = Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASKS_FROM_ACTIVE');

			this.onMoveConfirm(entityFrom, message)
				.then(() => {
					this.moveToWithGroupMode(entityFrom, targetEntity, item, false, false);
				})
				.catch(() => {})
			;
		}
	}

	moveToWithGroupMode(entityFrom: Entity, entityTo: Entity, item?: Item, after = true, update = true)
	{
		const groupModeItems = entityFrom.getGroupModeItems();

		if (item && !groupModeItems.has(item.getId()))
		{
			groupModeItems.set(item.getId(), item);
		}

		const sortedItems = [...groupModeItems.values()].sort((first: Item, second: Item) => {
			if (after)
			{
				if (first.getSort() > second.getSort()) return 1;
				if (first.getSort() < second.getSort()) return -1;
			}
			else
			{
				if (first.getSort() < second.getSort()) return 1;
				if (first.getSort() > second.getSort()) return -1;
			}
		});

		const sortedItemsIds = new Set();

		sortedItems.forEach((groupModeItem: Item) => {
			this.moveTo(entityFrom, entityTo, groupModeItem, after, update);
			sortedItemsIds.add(groupModeItem.getId());
			groupModeItem.activateBlinking();
		});

		this.scroller.scrollToItem(sortedItems.values().next().value);

		this.requestSender.updateItemSort({
			entityId: entityTo.getId(),
			itemIds: Array.from(sortedItemsIds),
			sortInfo: {
				...this.calculateSort(entityTo.getListItemsNode(), sortedItemsIds, true),
				...this.calculateSort(entityFrom.getListItemsNode(), new Set(), true)
			}
		})
			.then(() => {
				this.updateEntityCounters(entityFrom, entityTo);
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;

		entityFrom.deactivateGroupMode();
		entityTo.deactivateGroupMode();
	}

	moveTo(entityFrom: Entity, entityTo: Entity, item: Item, after = true, update = true)
	{
		const itemNode = item.getNode();
		const entityListNode = entityTo.getListItemsNode();

		if (item.isParentTask() && item.isShownSubTasks())
		{
			item.hideSubTasks();
		}

		if (after)
		{
			Dom.insertBefore(itemNode, entityListNode.lastElementChild);
		}
		else
		{
			Dom.insertBefore(itemNode, entityListNode.firstElementChild);
		}

		this.moveItemFromEntityToEntity(item, entityFrom, entityTo);

		if (update)
		{
			this.onMoveItemUpdate(entityFrom, entityTo, item);
		}
	}

	moveToPosition(entityFrom: Entity, entityTo: Entity, item: Item)
	{
		const isMoveFromAnotherEntity = (entityFrom.getId() !== entityTo.getId());

		const itemNode = item.getNode() ? item.getNode() : item.render();
		const itemSort = item.getSort();
		const itemPreviousSortSort = item.getPreviousSort();

		const entityListNode = entityTo.getListItemsNode();
		const bindItemNode = entityListNode.children[itemSort - 1];

		if (Dom.hasClass(bindItemNode, 'tasks-scrum__item'))
		{
			const bindItemSort = parseInt(bindItemNode.dataset.sort, 10);

			const bindItem = this.entityStorage.findItemByItemId(parseInt(bindItemNode.dataset.id, 10));
			if (bindItem.isParentTask() && bindItem.isShownSubTasks())
			{
				bindItem.hideSubTasks();
			}

			if (itemPreviousSortSort > 0 && bindItemSort >= itemPreviousSortSort)
			{
				if (isMoveFromAnotherEntity)
				{
					Dom.insertBefore(itemNode, bindItemNode);
				}
				else
				{
					this.planBuilder.appendItemAfterItem(itemNode, bindItemNode);
				}
			}
			else
			{
				Dom.insertBefore(itemNode, bindItemNode);
			}
		}
		else
		{
			if (entityTo.isEmpty())
			{
				Dom.insertBefore(itemNode, entityTo.getLoaderNode());
			}
			else
			{
				if (entityTo.isBacklog())
				{
					Dom.insertBefore(itemNode, entityTo.getFirstItemNode());
				}
				else
				{
					Dom.insertBefore(itemNode, entityTo.getLoaderNode());
				}
			}
		}

		this.moveItemFromEntityToEntity(item, entityFrom, entityTo);

		this.updateEntityCounters(entityFrom, entityTo);

		if (isMoveFromAnotherEntity)
		{
			this.resortItems(entityFrom);
		}
		this.resortItems(entityTo);
	}

	moveItemFromEntityToEntity(item: Item, entityFrom: Entity, entityTo: Entity)
	{
		entityFrom.removeItem(item);

		item.setParentEntity(entityTo.getId(), entityTo.getEntityType());

		entityTo.setItem(item);
	}

	showListSprintsToMove(entityFrom: Entity, item: Item, button: HTMLElement)
	{
		const id = `item-sprint-action-${entityFrom.getEntityType() + entityFrom.getId() + item.getId()}`;

		if (this.moveToSprintMenu)
		{
			this.moveToSprintMenu.getPopupWindow().close();

			return;
		}

		this.moveToSprintMenu = new Menu({
			id: id,
			bindElement: button,
			offsetTop: 12,
			offsetLeft: -32
		});

		this.entityStorage.getSprints().forEach((sprint) => {
			if (!sprint.isCompleted() && !this.isSameSprint(entityFrom, sprint))
			{
				this.moveToSprintMenu.addMenuItem({
					text: sprint.getName(),
					onclick: (event, menuItem) => {
						let message = Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE');
						if (entityFrom.isGroupMode())
						{
							message = Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASKS_FROM_ACTIVE');
						}
						this.onMoveConfirm(entityFrom, message)
							.then(() => {
								this.moveToWithGroupMode(entityFrom, sprint, item, true, false);
							})
							.catch(() => {})
						;
						menuItem.getMenuWindow().close();
					}
				});
			}
		});

		this.moveToSprintMenu.getPopupWindow().subscribe('onClose', () => {
			this.moveToSprintMenu.destroy();
			this.moveToSprintMenu = null;
			this.emit('moveToSprintMenuClose');
		});

		this.moveToSprintMenu.show();
	}

	isSameSprint(first: Sprint, second: Sprint): boolean
	{
		return (first.getEntityType() === 'sprint' && first.getId() === second.getId());
	}

	showMoveItemMenu(item, button, listToMove)
	{
		const id = `item-move-${item.getId()}`;

		if (this.moveItemMenu)
		{
			this.moveItemMenu.getPopupWindow().close();

			return;
		}

		this.moveItemMenu = new Menu({
			id: id,
			bindElement: button,
			offsetTop: 12,
			offsetLeft: -28
		});

		listToMove.forEach((item) => {
			this.moveItemMenu.addMenuItem(item);
		});

		this.moveItemMenu.getPopupWindow().subscribe('onClose', () => {
			this.moveItemMenu.destroy();
			this.moveItemMenu = null;
			this.emit('moveMenuClose');
		});

		this.moveItemMenu.show();
	}

	hasActionPanelDialog(): boolean
	{
		return (this.moveItemMenu || this.moveToSprintMenu)
	}

	closeActionPanelDialogs()
	{
		if (this.moveItemMenu)
		{
			this.moveItemMenu.getPopupWindow().close();
		}

		if (this.moveToSprintMenu)
		{
			this.moveToSprintMenu.getPopupWindow().close();
		}
	}
}
