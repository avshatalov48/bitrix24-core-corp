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
		this.disabled = false;
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
		if (this.isDisabled())
		{
			return;
		}

		this.disable();

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

		this.unDisable();
	}

	hide()
	{
		this.shown = false;

		this.unDisable();
	}

	isShown(): boolean
	{
		return this.shown;
	}

	isDisabled(): boolean
	{
		return this.disabled;
	}

	disable()
	{
		this.disabled = true;
	}

	unDisable()
	{
		this.disabled = false;
	}
}