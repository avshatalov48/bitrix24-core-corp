import {Loc, Type, Text} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Menu} from 'main.popup';
import {MessageBox} from 'ui.dialogs.messagebox';

import {Entity} from '../entity/entity';
import {Sprint} from '../entity/sprint/sprint';
import {Item} from '../item/item';

import {RequestSender} from './request.sender';
import {DomBuilder} from './dom.builder';
import {EntityStorage} from './entity.storage';
import {SubTasksManager} from './subtasks.manager';

export type ItemsSortInfo = {
	[id: number]: {
		sort: number,
		entityId?: number,
		updatedItemId?: number
	}
}

type Params = {
	requestSender: RequestSender,
	domBuilder: DomBuilder,
	entityStorage: EntityStorage,
	subTasksCreator: SubTasksManager
}

export class ItemMover extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.requestSender = params.requestSender;
		this.domBuilder = params.domBuilder;
		this.entityStorage = params.entityStorage;
		this.subTasksCreator = params.subTasksCreator;

		this.bindHandlers();
	}

	bindHandlers()
	{
		this.domBuilder.subscribe('itemMoveStart', (baseEvent: BaseEvent) => {
			const dragEndEvent = baseEvent.getData();
			const itemNode = dragEndEvent.source;
			const itemId = itemNode.dataset.itemId;
			const item = this.entityStorage.findItemByItemId(itemId);
			const sourceContainer = dragEndEvent.sourceContainer;
			const sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
			const sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);
			this.hideSubTasks(sourceEntity, item);
			const actionsPanel = item.getCurrentActionsPanel();
			if (actionsPanel)
			{
				actionsPanel.destroy();
			}
		});
		this.domBuilder.subscribe('itemMoveEnd', (baseEvent: BaseEvent) => {
			const dragEndEvent = baseEvent.getData();
			const endContainer = dragEndEvent.endContainer;
			if (!endContainer)
			{
				return;
			}
			const sourceContainer = dragEndEvent.sourceContainer;
			const sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
			const endEntityId = parseInt(endContainer.dataset.entityId, 10);
			const sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);

			if (sourceEntityId === endEntityId)
			{
				this.onItemMove(dragEndEvent);
			}
			else
			{
				const message = Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE');
				this.onMoveConfirm(sourceEntity, message).then(() => {
					this.onItemMove(dragEndEvent);
				}).catch(() => {
					const itemNode = dragEndEvent.source;
					const itemId = itemNode.dataset.itemId;
					const item = this.entityStorage.findItemByItemId(itemId);
					const itemNodeAfterSourceItem = sourceEntity.getListItemsNode().children[item.getSort()];
					this.domBuilder.insertBefore(itemNode, itemNodeAfterSourceItem);
				});
			}
		});
	}

	moveItem(item: Item, button)
	{
		const entity = this.entityStorage.findEntityByItemId(item.getItemId());

		const listToMove = [];

		if (!entity.isFirstItem(item))
		{
			listToMove.push({
				text: Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_UP'),
				onclick: (event, menuItem) => {
					if (entity.isGroupMode())
					{
						const groupModeItems = entity.getGroupModeItems();
						const sortedItems = [...groupModeItems.values()].sort((first: Item, second: Item) => {
							if (first.getSort() < second.getSort()) return 1;
							if (first.getSort() > second.getSort()) return -1;
						});
						const sortedItemsIds = new Set();
						sortedItems.forEach((groupModeItem: Item) => {
							sortedItemsIds.add(groupModeItem.getItemId());
							this.hideSubTasks(entity, groupModeItem);
							this.moveItemToUp(groupModeItem, entity.getListItemsNode(), entity.hasInput(), false);
						});
						this.requestSender.updateItemSort({
							sortInfo: this.calculateSort(entity.getListItemsNode(), sortedItemsIds)
						}).catch((response) => {
							this.requestSender.showErrorAlert(response);
						});
						entity.deactivateGroupMode();
					}
					else
					{
						this.hideSubTasks(entity, item);
						this.moveItemToUp(item, entity.getListItemsNode(), entity.hasInput());
					}
					menuItem.getMenuWindow().close();
				}
			});
		}
		if (!entity.isLastItem(item))
		{
			listToMove.push({
				text: Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_DOWN'),
				onclick: (event, menuItem) => {
					if (entity.isGroupMode())
					{
						const groupModeItems = entity.getGroupModeItems();
						const sortedItems = [...groupModeItems.values()].sort((first: Item, second: Item) => {
							if (first.getSort() > second.getSort()) return 1;
							if (first.getSort() < second.getSort()) return -1;
						});
						const sortedItemsIds = new Set();
						sortedItems.forEach((groupModeItem: Item) => {
							sortedItemsIds.add(groupModeItem.getItemId());
							this.hideSubTasks(entity, groupModeItem);
							this.moveItemToDown(groupModeItem, entity.getListItemsNode(), false);
						});
						this.requestSender.updateItemSort({
							sortInfo: this.calculateSort(entity.getListItemsNode(), sortedItemsIds)
						}).catch((response) => {
							this.requestSender.showErrorAlert(response);
						});
						entity.deactivateGroupMode();
					}
					else
					{
						this.hideSubTasks(entity, item);
						this.moveItemToDown(item, entity.getListItemsNode());
					}
					menuItem.getMenuWindow().close();
				}
			});
		}

		this.showMoveItemMenu(item, button, listToMove);
	}

	moveItemToUp(item: Item, listItemsNode, entityWithInput = true, updateSort = true)
	{
		if (entityWithInput)
		{
			this.domBuilder.appendItemAfterItem(item.getItemNode(), listItemsNode.firstElementChild);
		}
		else
		{
			this.domBuilder.insertBefore(item.getItemNode(), listItemsNode.firstElementChild);
		}

		if (updateSort)
		{
			this.updateItemsSort(item, listItemsNode);
		}
	}

	moveItemToDown(item, listItemsNode, updateSort = true)
	{
		this.domBuilder.append(item.getItemNode(), listItemsNode);

		if (updateSort)
		{
			this.updateItemsSort(item, listItemsNode);
		}
	}

	updateItemsSort(item: Item, listItemsNode: HTMLElement)
	{
		this.requestSender.updateItem({
			itemId: item.getItemId(),
			sortInfo: {
				...this.calculateSort(listItemsNode, new Set([item.getItemId()]))
			}
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	onItemMove(dragEndEvent)
	{
		if (!dragEndEvent.endContainer)
		{
			return;
		}

		const sourceContainer = dragEndEvent.sourceContainer;
		const endContainer = dragEndEvent.endContainer;

		if (endContainer === this.domBuilder.getSprintCreatingDropZoneNode())
		{
			const createNewSprintAndMoveItem = () => {
				this.domBuilder.createSprint().then((sprint) => {
					const itemNode = dragEndEvent.source;
					const itemId = itemNode.dataset.itemId;
					const item = this.entityStorage.findItemByItemId(itemId);
					this.moveTo(this.entityStorage.getBacklog(), sprint, item);
				});
			};
			createNewSprintAndMoveItem();
			return;
		}

		const sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
		const endEntityId = parseInt(endContainer.dataset.entityId, 10);

		if (sourceEntityId === endEntityId)
		{
			const moveInCurrentContainer = () => {
				const itemNode = dragEndEvent.source;
				const itemId = parseInt(itemNode.dataset.itemId, 10);
				this.requestSender.updateItemSort({
					sortInfo: this.calculateSort(sourceContainer, new Set([itemId]))
				}).catch((response) => {
					this.requestSender.showErrorAlert(response);
				});
			};
			moveInCurrentContainer();
		}
		else
		{
			const moveInAnotherContainer = () => {
				const itemNode = dragEndEvent.source;
				const itemId = itemNode.dataset.itemId;
				const item = this.entityStorage.findItemByItemId(itemId);
				const sourceEntity = this.entityStorage.findEntityByEntityId(sourceEntityId);
				const endEntity = this.entityStorage.findEntityByEntityId(endEntityId);
				this.moveItemFromEntityToEntity(item, sourceEntity, endEntity);
				this.requestSender.updateItemSort({
					entityId: endEntity.getId(),
					itemId: item.getItemId(),
					itemType: item.getItemType(),
					sourceEntityId: sourceEntity.getId(),
					fromActiveSprint: ((sourceEntity.getEntityType() === 'sprint' && sourceEntity.isActive()) ? 'Y' : 'N'),
					toActiveSprint: ((endEntity.getEntityType() === 'sprint' && endEntity.isActive()) ? 'Y' : 'N'),
					sortInfo: this.calculateSort(endContainer, new Set([item.getItemId()]), true)
				}).catch((response) => {
					this.requestSender.showErrorAlert(response);
				});
			};
			moveInAnotherContainer();
		}
	}

	calculateSort(container, updatedItemsIds?: Set, moveToAnotherEntity = false): ItemsSortInfo
	{
		const listSortInfo = {};

		const items = [...container.querySelectorAll('[data-sort]')];
		let sort = 1;
		items.forEach((itemNode) => {
			const itemId = parseInt(itemNode.dataset.itemId, 10);
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
			}
			sort++;
		});

		this.emit('calculateSort', listSortInfo);

		return listSortInfo;
	}

	moveToAnotherEntity(entityFrom: Entity, item: Item, targetEntity: ?Entity, bindButton?: HTMLElement)
	{
		const isMoveToSprint = (Type.isNull(targetEntity));

		const sprints = (isMoveToSprint ? this.entityStorage.getSprintsAvailableForFilling(entityFrom) : null);

		if (entityFrom.isGroupMode())
		{
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
						this.domBuilder.createSprint().then((sprint: Sprint) => {
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
				this.onMoveConfirm(entityFrom, message).then(() => {
					this.moveToWithGroupMode(entityFrom, targetEntity, item, false, false);
				});
			}
		}
		else
		{
			if (isMoveToSprint)
			{
				if (sprints.size > 1)
				{
					this.showListSprintsToMove(entityFrom, item, bindButton);
				}
				else
				{
					const message = Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE');
					this.onMoveConfirm(entityFrom, message).then(() => {
						if (sprints.size === 0)
						{
							this.domBuilder.createSprint().then((sprint: Sprint) => {
								this.moveTo(entityFrom, sprint, item);
							});
						}
						else
						{
							sprints.forEach((sprint: Sprint) => {
								this.moveTo(entityFrom, sprint, item);
							});
						}
					});
				}
			}
			else
			{
				const message = Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE');
				this.onMoveConfirm(entityFrom, message).then(() => {
					this.moveTo(entityFrom, targetEntity, item, false);
				});
			}
		}
	}

	moveToWithGroupMode(entityFrom: Entity, entityTo: Entity, item: Item, after = true, update = true)
	{
		const groupModeItems = entityFrom.getGroupModeItems();
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
		const items = [];
		sortedItems.forEach((groupModeItem: Item) => {
			this.moveTo(entityFrom, entityTo, groupModeItem, after, update);
			sortedItemsIds.add(groupModeItem.getItemId());
			items.push({
				itemId: groupModeItem.getItemId(),
				itemType: groupModeItem.getItemType(),
				entityId: entityTo.getId(),
				sourceEntityId: entityFrom.getId(),
				fromActiveSprint: ((entityFrom.getEntityType() === 'sprint' && entityFrom.isActive()) ? 'Y' : 'N'),
				toActiveSprint: ((entityTo.getEntityType() === 'sprint' && entityTo.isActive()) ? 'Y' : 'N')
			});
		});
		this.requestSender.batchUpdateItem({
			items: items,
			sortInfo: {
				...this.calculateSort(entityTo.getListItemsNode(), sortedItemsIds, true),
				...this.calculateSort(entityFrom.getListItemsNode(), new Set(), true)
			}
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
		entityFrom.deactivateGroupMode();
	}

	hideSubTasks(entity: Entity, item: Item)
	{
		if (item && item.isParentTask())
		{
			if (this.subTasksCreator.isShown(item))
			{
				this.subTasksCreator.toggleSubTasks(entity, item);
			}
		}
	}

	moveTo(entityFrom: Entity, entityTo: Entity, item: Item, after = true, update = true)
	{
		this.hideSubTasks(entityFrom, item);

		const itemNode = item.getItemNode();
		const entityListNode = entityTo.getListItemsNode();
		if (after)
		{
			this.domBuilder.append(itemNode, entityListNode);
		}
		else
		{
			this.domBuilder.appendItemAfterItem(itemNode, entityListNode.firstElementChild);
		}

		this.moveItemFromEntityToEntity(item, entityFrom, entityTo);

		if (update)
		{
			this.onMoveItemUpdate(entityFrom, entityTo, item);
		}
	}

	moveToPosition(entityFrom: Entity, entityTo: Entity, item: Item)
	{
		this.hideSubTasks(entityFrom, item);

		const itemNode = item.getItemNode();
		const entityListNode = entityTo.getListItemsNode();
		const bindItemNode = entityListNode.children[item.getSort()];
		if (Type.isUndefined(bindItemNode))
		{
			this.domBuilder.append(itemNode, entityListNode);
		}
		else
		{
			const bindItemSort = parseInt(bindItemNode.dataset.sort, 10);
			const isMoveFromAnotherEntity = (entityFrom.getId() !== entityTo.getId())

			if (bindItemSort >= item.getPreviousSort())
			{
				if (isMoveFromAnotherEntity)
				{
					this.domBuilder.insertBefore(itemNode, bindItemNode);
				}
				else
				{
					this.domBuilder.appendItemAfterItem(itemNode, bindItemNode);
				}
			}
			else
			{
				this.domBuilder.insertBefore(itemNode, bindItemNode);
			}
		}

		this.moveItemFromEntityToEntity(item, entityFrom, entityTo);
	}

	onMoveItemUpdate(entityFrom: Entity, entityTo: Entity, item: Item)
	{
		this.requestSender.updateItem({
			itemId: item.getItemId(),
			itemType: item.getItemType(),
			entityId: entityTo.getId(),
			sourceEntityId: entityFrom.getId(),
			fromActiveSprint: ((entityFrom.getEntityType() === 'sprint' && entityFrom.isActive()) ? 'Y' : 'N'),
			toActiveSprint: ((entityTo.getEntityType() === 'sprint' && entityTo.isActive()) ? 'Y' : 'N'),
			sortInfo: {
				...this.calculateSort(entityTo.getListItemsNode(), new Set([item.getItemId()]), true),
				...this.calculateSort(entityFrom.getListItemsNode(), new Set(), true)
			}
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	moveItemFromEntityToEntity(item: Item, entityFrom: Entity, entityTo: Entity)
	{
		if (entityFrom.isActive())
		{
			entityFrom.subtractTotalStoryPoints(item);
		}

		if (entityTo.isActive())
		{
			entityTo.addTotalStoryPoints(item);
		}

		entityFrom.removeItem(item);
		item.setParentEntity(entityTo.getId(), entityTo.getEntityType());
		item.setDisableStatus(false);
		entityTo.setItem(item);
	}

	showListSprintsToMove(entityFrom: Entity, item: Item, button: HTMLElement)
	{
		const id = `item-sprint-action-${entityFrom.getEntityType() + entityFrom.getId() + item.itemId}`;

		if (this.moveToSprintMenu)
		{
			if (this.moveToSprintMenu.getPopupWindow().getId() === id)
			{
				this.moveToSprintMenu.getPopupWindow().setBindElement(button);
				this.moveToSprintMenu.show();
				return;
			}
			this.moveToSprintMenu.getPopupWindow().destroy();
		}

		this.moveToSprintMenu = new Menu({
			id: id,
			bindElement: button
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
						this.onMoveConfirm(entityFrom, message).then(() => {
							if (entityFrom.isGroupMode())
							{
								this.moveToWithGroupMode(entityFrom, sprint, item, true, false);
							}
							else
							{
								this.moveTo(entityFrom, sprint, item);
							}

						});
						menuItem.getMenuWindow().close();
					}
				});
			}
		});

		this.moveToSprintMenu.show();
	}

	isSameSprint(first: Sprint, second: Sprint): boolean
	{
		return (first.getEntityType() === 'sprint' && first.getId() === second.getId());
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

	showMoveItemMenu(item, button, listToMove)
	{
		const id = `item-move-${item.itemId}`;

		if (this.moveItemMenu)
		{
			this.moveItemMenu.getPopupWindow().destroy();
		}

		this.moveItemMenu = new Menu({
			id: id,
			bindElement: button
		});

		listToMove.forEach((item) => {
			this.moveItemMenu.addMenuItem(item);
		});

		this.moveItemMenu.show();
	}
}