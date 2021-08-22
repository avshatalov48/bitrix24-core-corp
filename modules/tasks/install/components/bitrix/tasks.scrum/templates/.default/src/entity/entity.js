import {Dom, Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Loader} from 'main.loader';

import {Item} from '../item/item';
import {GroupActionsButton} from './group.actions.button';
import {ListItems} from './list.items';

import {Input} from '../utility/input';
import {StoryPoints} from '../utility/story.points';

import type {Views} from '../view/view';

import '../css/entity.css';

type EntityParams = {
	id: number,
	views?: Views,
	numberTasks?: number,
	isExactSearchApplied: 'Y' | 'N',
	storyPoints?: string,
	pageNumberItems: number
};

export class Entity extends EventEmitter
{
	constructor(params: EntityParams)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Entity');

		this.setEntityParams(params);

		this.items = new Map();

		this.groupMode = false;
		this.groupModeItems = new Map();

		this.node = null;
		this.groupActionsButton = null;
		this.listItems = null;

		this.input = new Input();
	}

	setEntityParams(params: EntityParams)
	{
		this.setId(params.id);
		this.setViews(params.views);
		this.setNumberTasks(params.numberTasks);
		this.setStoryPoints(params.storyPoints);

		this.exactSearchApplied = (params.isExactSearchApplied === 'Y');

		this.pageNumberItems = parseInt(params.pageNumberItems, 10);
	}

	addGroupActionsButton(groupActionsButton: GroupActionsButton)
	{
		this.groupActionsButton = groupActionsButton;
		this.groupActionsButton.subscribe('activateGroupMode', this.onActivateGroupMode.bind(this));
		this.groupActionsButton.subscribe('deactivateGroupMode', this.onDeactivateGroupMode.bind(this));
	}

	addListItems(listItems: ListItems)
	{
		this.listItems = listItems;
	}

	getListItems(): ListItems|null
	{
		return this.listItems;
	}

	getNode(): HTMLElement|null
	{
		return this.node;
	}

	setId(id: number)
	{
		this.id = (Type.isInteger(id) ? parseInt(id, 10) : 0);

		if (this.listItems)
		{
			this.listItems.setEntityId(this.id);
		}
	}

	getId(): number
	{
		return this.id;
	}

	setViews(views)
	{
		this.views = (Type.isPlainObject(views) ? views : {});
	}

	getViews()
	{
		return this.views;
	}

	getEntityType()
	{
		return 'entity';
	}

	isActive()
	{
		return false;
	}

	isCompleted()
	{
		return false;
	}

	getListItemsNode(): HTMLElement|null
	{
		return (this.listItems ? this.listItems.getNode() : null);
	}

	isEmpty(): boolean
	{
		return (this.items.size === 0);
	}

	getItems(): Map
	{
		return this.items;
	}

	getPageNumberItems(): number
	{
		return this.pageNumberItems;
	}

	incrementPageNumberItems()
	{
		this.pageNumberItems++;
	}

	recalculateItemsSort()
	{
		const listItemsNode = this.getListItemsNode();
		if (!listItemsNode)
		{
			return;
		}

		let sort = 1;
		listItemsNode.querySelectorAll('.tasks-scrum-item').forEach((node: HTMLElement) => {
			const item = this.getItems().get(parseInt(node.dataset.itemId, 10));
			if (item)
			{
				item.setSort(sort);
				sort++;
			}
		});
	}

	getInput(): Input
	{
		return this.input;
	}

	setItem(newItem: Item)
	{
		this.items.set(newItem.getItemId(), newItem);
		this.subscribeToItem(newItem);
		[...this.items.values()].map((item) => {
			this.setItemMoveActivity(item);
		});
	}

	setItemMoveActivity(item: Item)
	{
		item.setMoveActivity(this.items.size > 2);
	}

	removeItem(item: Item)
	{
		if (this.items.has(item.getItemId()))
		{
			this.items.delete(item.getItemId());
			item.unsubscribeAll();
			[...this.items.values()].map((item) => {
				this.setItemMoveActivity(item);
			});
		}
	}

	isNodeCreated(): boolean
	{
		return (this.node !== null);
	}

	setNumberTasks(numberTasks: number)
	{
		this.numberTasks = (Type.isInteger(numberTasks) ? parseInt(numberTasks, 10) : 0);
	}

	getNumberTasks(): number
	{
		return (this.numberTasks ? this.numberTasks : this.getItems().size);
	}

	hasInput(): boolean
	{
		return true;
	}

	setExactSearchApplied(value: boolean)
	{
		this.exactSearchApplied = Boolean(value);
	}

	isExactSearchApplied(): boolean
	{
		return this.exactSearchApplied;
	}

	onAfterAppend()
	{
		[...this.items.values()].map((item) => {
			this.subscribeToItem(item);
			this.setItemMoveActivity(item);
		});

		if (!this.isCompleted())
		{
			this.input.onAfterAppend();
			this.input.subscribe('tagsSearchOpen', (baseEvent) => {
				this.emit('tagsSearchOpen', {
					inputObject: baseEvent.getTarget(),
					enteredHashTagName: baseEvent.getData()
				})
			});
			this.input.subscribe('tagsSearchClose', () => this.emit('tagsSearchClose'));
			this.input.subscribe('epicSearchOpen', (baseEvent) => {
				this.emit('epicSearchOpen', {
					inputObject: baseEvent.getTarget(),
					enteredHashEpicName: baseEvent.getData()
				})
			});
			this.input.subscribe('epicSearchClose', () => this.emit('epicSearchClose'));
			this.input.subscribe('createTaskItem', (baseEvent) => {
				this.emit('createTaskItem', {
					inputObject: baseEvent.getTarget(),
					value: baseEvent.getData()
				});
			});
		}

		this.updateStoryPointsNode();
	}

	subscribeToItem(item: Item)
	{
		if (!this.getListItemsNode())
		{
			return;
		}

		item.onAfterAppend(this.getListItemsNode());

		item.setEntityType(this.getEntityType());

		item.subscribe('updateItem', (baseEvent) => {
			this.emit('updateItem', baseEvent.getData());
		});

		item.subscribe('updateStoryPoints', () => {
			if (item.isSubTask())
			{
				const parentItem = this.getItemBySourceId(item.getParentTaskId());
				if (parentItem)
				{
					parentItem.updateSubTasksPoints(item.getSourceId(), item.getStoryPoints());
				}
			}
		});

		item.subscribe('showTask', (baseEvent) => this.emit('showTask', baseEvent.getTarget()));

		item.subscribe('move', (baseEvent) => {
			this.emit('moveItem', {
				item: baseEvent.getTarget(),
				button: baseEvent.getData()
			})
		});

		item.subscribe('moveToSprint', (baseEvent) => {
			this.emit('moveToSprint', {
				item: baseEvent.getTarget(),
				button: baseEvent.getData()
			})
		});

		item.subscribe('attachFilesToTask', (baseEvent) => {
			this.emit('attachFilesToTask', {
				item: baseEvent.getTarget(),
				attachedIds: baseEvent.getData()
			})
		});

		item.subscribe('dod', (baseEvent) => {
			this.emit('showDod', baseEvent.getTarget())
		});

		item.subscribe('showTagSearcher', (baseEvent) => {
			this.emit('showTagSearcher', {
				item: baseEvent.getTarget(),
				button: baseEvent.getData()
			})
		});
		item.subscribe('showEpicSearcher', (baseEvent) => {
			this.emit('showEpicSearcher', {
				item: baseEvent.getTarget(),
				button: baseEvent.getData()
			})
		});

		item.subscribe('startDecomposition', (baseEvent) => {
			this.emit('startDecomposition', baseEvent.getTarget())
		});

		item.subscribe('remove', (baseEvent) => {
			if (this.isGroupMode())
			{
				this.getGroupModeItems().forEach((groupModeItem: Item) => {
					this.removeItem(groupModeItem);
					groupModeItem.removeYourself();
				});
			}
			else
			{
				const item = baseEvent.getTarget();
				this.removeItem(item);
				item.removeYourself();
			}
			this.emit('removeItem', item);
		});

		item.subscribe('changeTaskResponsible', (baseEvent) => {
			const item = baseEvent.getTarget();
			this.emit('changeTaskResponsible', item);
		});

		item.subscribe('filterByEpic', (baseEvent) => {
			this.emit('filterByEpic', baseEvent.getData());
		});

		item.subscribe('filterByTag', (baseEvent) => {
			this.emit('filterByTag', baseEvent.getData());
		});

		item.subscribe('addItemToGroupMode', (baseEvent) => {
			this.addItemToGroupMode(baseEvent.getTarget());
		});

		item.subscribe('removeItemFromGroupMode', (baseEvent) => {
			this.removeItemFromGroupMode(baseEvent.getTarget());
		});
	}

	getItemByItemId(itemId: number|string): Item|undefined
	{
		return this.items.get((Type.isInteger(itemId) ? parseInt(itemId, 10) : itemId));
	}

	getItemBySourceId(sourceId: number): Item|undefined
	{
		return [...this.items.values()].find((item: Item) => item.getSourceId() === sourceId);
	}

	getItemsByParentTaskId(parentTaskId: number): Map<number, Item>
	{
		const items = new Map();

		[...this.items.values()].map((item: Item) => {
			if (item.getParentTaskId() === parentTaskId)
			{
				items.set(item.getItemId(), item);
			}
		});

		return items;
	}

	setStoryPoints(storyPoints)
	{
		this.storyPoints = new StoryPoints();

		this.storyPoints.addPoints(storyPoints);

		this.updateStoryPointsNode();
	}

	getStoryPoints(): StoryPoints
	{
		return this.storyPoints;
	}

	isFirstItem(item: Item): boolean
	{
		const listItemsNode = this.getListItemsNode();
		const itemNode = item.getItemNode();
		const firstElementChild = (
			this.hasInput() ? listItemsNode.firstElementChild.nextElementSibling : listItemsNode.firstElementChild
		)
		return firstElementChild.isEqualNode(itemNode);
	}

	isLastItem(item: Item): boolean
	{
		const listItemsNode = this.getListItemsNode();
		const itemNode = item.getItemNode();
		return listItemsNode.lastElementChild.isEqualNode(itemNode);
	}

	fadeOut()
	{
		this.getListItemsNode().classList.add('tasks-scrum-entity-items-faded');
	}

	fadeIn()
	{
		this.getListItemsNode().classList.remove('tasks-scrum-entity-items-faded');
	}

	deactivateGroupMode()
	{
		this.groupActionsButton.deactivateGroupMode();
	}

	onActivateGroupMode(baseEvent: BaseEvent)
	{
		this.groupMode = true;

		this.input.disable();

		this.emit('activateGroupMode');
	}

	onDeactivateGroupMode(baseEvent: BaseEvent)
	{
		this.groupMode = false;

		this.input.unDisable();

		this.groupModeItems.forEach((item: Item) => {
			item.deactivateGroupMode();
		});
		this.groupModeItems.clear();

		this.emit('deactivateGroupMode');
	}

	isGroupMode(): boolean
	{
		return this.groupMode;
	}

	addItemToGroupMode(item: Item)
	{
		this.groupModeItems.set(item.getItemId(), item);
	}

	getGroupModeItems(): Map
	{
		return this.groupModeItems;
	}

	removeItemFromGroupMode(item: Item)
	{
		this.groupModeItems.delete(item.getItemId());
	}

	bindItemsLoader(loader: HTMLElement)
	{
		this.setActiveLoadItems(false);

		if (typeof IntersectionObserver === `undefined`)
		{
			return;
		}

		const observer = new IntersectionObserver((entries) =>
			{
				if(entries[0].isIntersecting === true)
				{
					if (!this.isActiveLoadItems())
					{
						this.emit('loadItems');
					}
				}
			},
			{
				threshold: [0]
			}
		);

		observer.observe(loader);
	}

	setActiveLoadItems(value: boolean)
	{
		this.activeLoadItems = Boolean(value);
	}

	isActiveLoadItems(): boolean
	{
		return this.activeLoadItems;
	}

	showItemsLoader()
	{
		const listPosition = Dom.getPosition(this.itemsLoaderNode);

		const loader = new Loader({
			target: this.itemsLoaderNode,
			size: 60,
			mode: 'inline',
			color: 'rgba(82, 92, 105, 0.9)',
			offset: {
				left: `${(listPosition.width / 2 - 30)}px`
			}
		});

		loader.show();

		return loader;
	}

	updateStoryPointsNode() {}
}