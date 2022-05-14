import {Dom, Event, Loc, Tag, Text, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';

export class Name extends EventEmitter
{
	constructor(name: string, completed: boolean)
	{
		super(name);

		this.setEventNamespace('BX.Tasks.Scrum.Item.Name');

		this.value = ((Type.isString(name) && name) ? name.trim() : '');
		this.completed = completed;

		if (!this.value)
		{
			throw new Error(Loc.getMessage('TASKS_SCRUM_TASK_ADD_NAME_ERROR'));
		}

		this.node = null;
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__item--title ${this.completed ? '--completed' : ''}">
				${Text.encode(this.value)}
			</div>
		`;

		Event.bind(this.node, 'click', this.onClick.bind(this));

		return this.node;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	getValue(): string
	{
		return this.value;
	}

	setCompleted(value: boolean)
	{
		this.completed = value;
	}

	strikeOut()
	{
		Dom.addClass(this.node, '--completed');
	}

	unStrikeOut()
	{
		Dom.removeClass(this.node, '--completed');
	}

	onClick()
	{
		this.emit('click');
	}
}