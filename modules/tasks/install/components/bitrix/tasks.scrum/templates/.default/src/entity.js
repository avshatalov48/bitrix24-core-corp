import {Type, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {ActionsHeader} from './actions.header';
import {Item} from './item';
import {Input} from './input';

type entityParams = {
	id: number,
	storyPoints?: string
};

export class Entity extends EventEmitter
{
	constructor(entityData: entityParams = {})
	{
		super(entityData);

		this.listItemsNode = null;
		this.storyPointsNode = null;

		this.id = (Type.isInteger(entityData.id) ? parseInt(entityData.id, 10) : 0);

		this.setStoryPoints(entityData.storyPoints ? entityData.storyPoints : '');
		this.items = new Map();

		this.actionsHeader = new ActionsHeader(this);
		this.input = new Input();
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

	getListItemsNode()
	{
		return this.listItemsNode;
	}

	getItems()
	{
		return this.items;
	}

	setItem(newItem: Item)
	{
		this.items.set(newItem.getItemId(), newItem);
		this.subscribeToItem(newItem);
		this.updateStoryPoints(newItem.getStoryPoints());
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
			this.updateStoryPoints(item.getStoryPoints(), false);
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

		this.actionsHeader.onAfterAppend();
		this.actionsHeader.subscribe('openAddEpicForm', () => this.emit('openAddEpicForm'));

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
	}

	subscribeToItem(item: Item)
	{
		item.onAfterAppend(this.listItemsNode);

		item.subscribe('updateItem', (baseEvent) => {
			this.emit('updateItem', baseEvent.getData());
		});

		item.subscribe('updateStoryPoints', (baseEvent) => {
			const data = baseEvent.getData();
			const newValue = (data.newValue ? String(data.newValue).trim() : '');
			const oldValue = (data.oldValue ? String(data.oldValue).trim() : '');
			if (!newValue)
			{
				this.updateStoryPoints(oldValue, false);
			}
			else if (newValue > oldValue)
			{
				this.updateStoryPoints(newValue - oldValue);
			}
			else
			{
				this.updateStoryPoints(oldValue - newValue, false);
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
			const item = baseEvent.getTarget();
			this.removeItem(item);
			item.removeYourself();
			this.emit('removeItem', item);
		});

		item.subscribe('changeTaskResponsible', (baseEvent) => {
			const item = baseEvent.getTarget();
			this.emit('changeTaskResponsible', item);
		});
	}

	updateStoryPoints(inputStoryPoints, increment = true)
	{
		inputStoryPoints = (inputStoryPoints ? parseFloat(inputStoryPoints) : '');
		const currentStoryPoints = (this.storyPoints ? parseFloat(this.storyPoints) : '');

		const storyPoints = (increment ?
			(currentStoryPoints + inputStoryPoints) :
			(currentStoryPoints - inputStoryPoints));

		this.setStoryPoints(storyPoints);
	}

	getItemByItemId(itemId)
	{
		return this.items.get(parseInt(itemId, 10));
	}

	getStoryPoints()
	{
		return this.storyPoints;
	}

	setStoryPoints(storyPoints)
	{
		this.storyPoints = (Type.isFloat(storyPoints) ? storyPoints.toFixed(1) : storyPoints);
		if (this.storyPoints === 0)
		{
			this.storyPoints = '';
		}
		if (this.storyPointsNode)
		{
			this.storyPointsNode.textContent = Text.encode(this.storyPoints);
		}
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
}