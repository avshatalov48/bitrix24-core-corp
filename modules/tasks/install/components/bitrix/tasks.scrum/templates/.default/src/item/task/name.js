import {Dom, Event, Loc, Tag, Text, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Tool} from '../../utility/tool';

type Params = {
	name: string,
	isCompleted: boolean,
	isImportant: boolean,
}

export class Name extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Item.Name');

		this.value = ((Type.isString(params.name) && params.name) ? params.name.trim() : '');
		this.important = params.isImportant;
		this.completed = params.isCompleted;

		if (!this.value)
		{
			throw new Error(Loc.getMessage('TASKS_SCRUM_TASK_ADD_NAME_ERROR'));
		}

		this.node = null;
	}

	render(): HTMLElement
	{
		let visualClasses = this.completed ? '--completed' : '';
		visualClasses += this.important ? '--important' : '';

		let value = Text.encode(this.value);
		if (this.important)
		{
			const words = this.value.split(' ');
			const lastWord = words[words.length - 1];
			value = value.replace(new RegExp(Tool.escapeRegex(lastWord) + '$'), `<span>${lastWord}</span>`);
		}

		this.node = Tag.render`
			<div class="tasks-scrum__item--title ${visualClasses}">
				${value}
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