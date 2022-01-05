import {Event, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';

type Params = {
	visible: boolean
}

export class Toggle extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Item.Toggle');

		this.visible = params.visible;

		this.shown = false;
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__item--btn-toggle-tasks ${this.visible ? '--visible' : ''}"></div>
		`;

		Event.bind(this.node, 'click', this.onClick.bind(this));

		return this.node;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	onClick()
	{
		if (this.isShown())
		{
			this.emit('hide');
		}
		else
		{
			this.emit('show');
		}
	}

	show()
	{
		this.shown = true;
	}

	hide()
	{
		this.shown = false;
	}

	isShown(): boolean
	{
		return this.shown;
	}
}