import {Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Item} from '../item/item';
import {Input} from '../utility/input';
import {StoryPoints} from '../utility/story.points';
import {GroupActionsButton} from './group.actions.button';
import {ListItems} from './list.items';

type entityParams = {
	id: number,
	views: {
		plan: {
			name: string,
			url: string,
			active: boolean
		},
		activeSprint: {
			name: string,
			url: string,
			active: boolean
		},
		completedSprint: {
			name: string,
			url: string,
			active: boolean
		}
	}
};

export class Entity extends EventEmitter
{
	constructor(entityData: entityParams = {})
	{
		super(entityData);

		this.id = (Type.isInteger(entityData.id) ? parseInt(entityData.id, 10) : 0);

		this.views = entityData.views;

		this.items = new Map();

		this.groupMode = false;
		this.groupModeItems = new Map();

		this.node = null;

		this.groupActionsButton = null;
		this.listItems = null;

		this.storyPoints = new StoryPoints();

		this.input = new Input();
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

	getNode(): HTMLElement|null
	{
		return this.node;
	}

	getId()
	{
		return this.id;
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
		return (this.listItems ? this.listItems.getElement() : null);
	}

	getItems(): Map
	{
		return this.items;
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

	hasInput(): Boolean
	{
		return true;
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

		this.updateStoryPoints();
	}

	subscribeToItem(item: Item)
	{
		if (!this.getListItemsNode())
		{
			return;
		}

		item.onAfterAppend(this.getListItemsNode());

		item.subscribe('updateItem', (baseEvent) => {
			this.emit('updateItem', baseEvent.getData());
		});

		item.subscribe('updateStoryPoints', () => this.updateStoryPoints());

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

	updateStoryPoints()
	{
		this.storyPoints.clearPoints();
		[...this.getItems().values()].map((item: Item) => {
			this.storyPoints.addPoints(item.getStoryPoints().getPoints());
		});
	}

	addTotalStoryPoints(item: Item) {}

	subtractTotalStoryPoints(item: Item) {}

	getItemByItemId(itemId)
	{
		return this.items.get(parseInt(itemId, 10));
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
}