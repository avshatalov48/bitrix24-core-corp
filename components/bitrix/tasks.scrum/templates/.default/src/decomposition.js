import {Tag, Dom, Loc, Event} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Input} from './input';

import './css/decomposition.css';

export class Decomposition extends EventEmitter
{
	constructor()
	{
		super();

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

		Dom.insertAfter(this.input.render(), item.getItemNode());
		this.input.setNode();

		const inputNode = this.input.getInputNode();
		Event.bind(inputNode, 'input', this.input.onTagSearch.bind(this.input));
		Event.bind(inputNode, 'keydown', this.onCreateItem.bind(this));
		inputNode.focus();

		const button = this.createButton();
		Dom.insertAfter(button, this.input.getNode());

		Event.bind(button.querySelector('button'), 'click', () => {
			this.deactivateDecompositionMode();
			this.input.removeYourself()
			Dom.remove(button);
		});
	}

	addDecomposedItem(item: Item)
	{
		item.activateDecompositionMode();

		this.items.add(item);
	}

	getDecomposedItems(): Set
	{
		return this.items;
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
					this.emit('createItem', inputNode.value);
					inputNode.value = '';
					inputNode.focus();
				}
			}
		}
	}
}