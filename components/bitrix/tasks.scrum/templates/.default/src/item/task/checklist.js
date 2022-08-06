import {Event, Tag, Type} from 'main.core';
import {EventEmitter} from "main.core.events";

type Params = {
	complete: number,
	all: number
}

export class Checklist extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Item.Checklist');

		this.complete = (Type.isInteger(params.complete) ? parseInt(params.complete, 10) : 0);
		this.all = (Type.isInteger(params.all) ? parseInt(params.all, 10) : 0);

		this.value = `${this.complete}/${this.all}`;

		this.node = null;
	}

	render(): HTMLElement
	{
		const uiClasses = 'ui-label ui-label-sm ui-label-light';

		this.node = Tag.render`
			<div class="tasks-scrum__item--entity-tasks ${this.all ? '--visible' : ''} ${uiClasses}">
				${this.value}
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

	getComplete(): number
	{
		return this.complete;
	}

	getAll(): number
	{
		return this.all;
	}

	onClick()
	{
		this.emit('click');
	}
}