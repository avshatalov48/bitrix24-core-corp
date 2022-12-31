import {Dom, Event, Loc, Tag, Text, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Tool} from '../../utility/tool';

type Params = {
	name: string,
	isCompleted: boolean,
	isImportant: boolean,
	pathToTask: string,
	sourceId: number,
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
		this.sourceId = params.sourceId;

		this.pathToTask = this.sourceId ? params.pathToTask.replace('#task_id#', this.sourceId) : null;

		if (!this.value)
		{
			throw new Error(Loc.getMessage('TASKS_SCRUM_TASK_ADD_NAME_ERROR'));
		}

		this.node = null;
	}

	render(): HTMLElement
	{
		let visualClasses = this.completed ? '--completed' : '';
		visualClasses += this.important ? ' --important' : '';

		let value = Text.encode(this.value);
		if (this.important)
		{
			const words = this.value.split(' ');
			const lastWord = words[words.length - 1];
			value = value.replace(new RegExp(Tool.escapeRegex(lastWord) + '$'), `<span>${lastWord}</span>`);
		}

		if (this.pathToTask)
		{
			this.node = Tag.render`
				<a
					href="${Text.encode(this.pathToTask)}"
					class="tasks-scrum__item--title ${visualClasses}"
				>
					${value}
				</a>
			`;

			Event.bind(this.node, 'click', () => {
				this.emit('urlClick');
			});
		}
		else
		{
			this.node = Tag.render`
				<div class="tasks-scrum__item--title ${visualClasses}">
					${value}
				</div>
			`;

			Event.bind(this.node, 'click', this.onClick.bind(this));
		}

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