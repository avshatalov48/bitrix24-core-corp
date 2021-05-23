import {Tag, Dom, Loc, Event, Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {Entity} from '../../entity/entity';
import {Responsible, Item} from '../item';

import {ItemStyleDesigner} from '../../utility/item.style.designer';
import {SubTasksManager} from '../../utility/subtasks.manager';
import {Input} from '../../utility/input';

import '../../css/decomposition.css';

type Params = {
	entity: Entity,
	itemStyleDesigner: ItemStyleDesigner,
	subTasksCreator: SubTasksManager
}

export class Decomposition extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.entity = params.entity;
		this.itemStyleDesigner = params.itemStyleDesigner;
		this.subTasksCreator = params.subTasksCreator;

		this.setEventNamespace('BX.Tasks.Scrum.Decomposition');

		this.items = new Set();

		this.input = new Input();
		this.input.setPlaceholder(Loc.getMessage('TASKS_SCRUM_TASK_ADD_DECOMPOSITION_INPUT_PLACEHOLDER'));
		this.input.subscribe('tagsSearchOpen', (baseEvent) => {
			this.emit('tagsSearchOpen', {
				inputObject: baseEvent.getTarget(),
				enteredHashTagName: baseEvent.getData()
			})
		});
		this.input.subscribe('tagsSearchClose', () => this.emit('tagsSearchClose'));
	}

	decomposeItem(item: Item)
	{
		this.addDecomposedItem(item);

		if (this.isBacklogDecomposition())
		{
			this.onDecomposeItem(item, item.getItemNode());
		}
		else
		{
			if (!this.subTasksCreator.isShown(item))
			{
				this.subTasksCreator.toggleSubTasks(this.entity, item).then(() => {
					const lastSubTask = this.getSubTasks(item)[0];
					const targetItemNode = (lastSubTask ? lastSubTask.getItemNode() : item.getItemNode());
					this.onDecomposeItem(item, targetItemNode);
				});
			}
			else
			{
				const lastSubTask = this.getSubTasks(item)[0];
				this.onDecomposeItem(item, lastSubTask.getItemNode());
			}
		}
	}

	onDecomposeItem(item: Item, targetItemNode)
	{
		Dom.insertAfter(this.input.render(), targetItemNode);
		this.input.setNode();

		const inputNode = this.input.getInputNode();
		Event.bind(inputNode, 'input', this.input.onTagSearch.bind(this.input));
		Event.bind(inputNode, 'keydown', this.onCreateItem.bind(this));
		inputNode.focus();

		const button = this.createButton();
		Dom.insertAfter(button, this.input.getNode());

		Event.bind(button.querySelector('button'), 'click', () => {
			if (this.isBacklogDecomposition() && this.firstDecomposition() && !item.isLinkedTask())
			{
				item.setBorderColor();
			}
			if (!this.isBacklogDecomposition())
			{
				if (this.subTasksCreator.isShown(item))
				{
					this.subTasksCreator.hideSubTaskItems(this.entity, item);
				}
				this.subTasksCreator.cleanSubTasks(item);
			}
			this.deactivateDecompositionMode();
			this.input.removeYourself()
			Dom.remove(button);
		});

		this.setResponsible(item.getResponsible());
	}

	getSubTasks(parentItem: Item): Array
	{
		return Array.from(this.subTasksCreator.getSubTasks(parentItem).values());
	}

	getLastDecomposedItemNode(parentItem: Item): HTMLElement
	{
		if (this.isBacklogDecomposition())
		{
			const decomposedItems = this.getDecomposedItems();
			const lastDecomposedItem = Array.from(decomposedItems).pop();

			return lastDecomposedItem.getItemNode();
		}
		else
		{
			const subTasks = this.getSubTasks(parentItem);
			if (subTasks.length)
			{
				const lastSubTask = (this.firstDecomposition() ? subTasks[0] : subTasks.pop());

				return lastSubTask.getItemNode();
			}
			else
			{
				return parentItem.getItemNode();
			}
		}
	}

	isBacklogDecomposition(): boolean
	{
		return this.entity.getEntityType() === 'backlog';
	}

	addDecomposedItem(item: Item)
	{
		this.activateDecompositionMode(item);

		item.subscribe('changeTaskResponsible', this.saveSelectedResponsible.bind(this));

		this.items.add(item);
	}

	getDecomposedItems(): Set
	{
		return this.items;
	}

	activateDecompositionMode(item: Item)
	{
		if (this.isBacklogDecomposition())
		{
			this.itemStyleDesigner.getRandomColorForItemBorder().then((randomColor: string) => {
				item.activateDecompositionMode(randomColor);
				this.setBorderColor(item.getBorderColor());
			});
		}
		else
		{
			item.activateDecompositionMode();
		}
	}

	deactivateDecompositionMode()
	{
		this.items.forEach((item) => {
			item.deactivateDecompositionMode();
		});
		this.items.clear();
	}

	createButton(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-decomposition-structure">
				<button class="ui-btn ui-btn-sm ui-btn-primary">
					${Loc.getMessage('TASKS_SCRUM_DECOMPOSITION_BUTTON')}
				</button>
			</div>
		`;
	}

	onCreateItem(event)
	{
		if (event.isComposing || event.keyCode === 13)
		{
			if (!this.input.isTagsSearchMode())
			{
				const inputNode = event.target;
				if (inputNode.value)
				{
					const parentItem = this.getParentItem();
					if (this.isBacklogDecomposition())
					{
						if (this.firstDecomposition() && !parentItem.isLinkedTask())
						{
							this.emit('updateParentItem', {
								itemId: parentItem.getItemId(),
								entityId: parentItem.getEntityId(),
								itemType: parentItem.getItemType(),
								info: parentItem.getInfo()
							});
						}
					}
					this.emit('createItem', inputNode.value);
					inputNode.value = '';
					inputNode.focus();
				}
			}
		}
	}

	firstDecomposition(): boolean
	{
		return (this.items.size === 1);
	}

	getParentItem(): Item
	{
		const iterator = this.items.values();
		return iterator.next().value;
	}

	saveSelectedResponsible(baseEvent: BaseEvent)
	{
		const item = baseEvent.getTarget();
		this.setResponsible(item.getResponsible());
	}

	getResponsible(): Responsible
	{
		return this.responsible;
	}

	setResponsible(responsible: Responsible)
	{
		this.responsible = responsible;
	}

	setBorderColor(color: string)
	{
		this.borderColor = (Type.isString(color) ? color : '');
	}

	getBorderColor(): string
	{
		return this.borderColor;
	}
}