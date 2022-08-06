import {Event, Tag, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';

export class Files extends EventEmitter
{
	constructor(count: number)
	{
		super(count);

		this.setEventNamespace('BX.Tasks.Scrum.Item.Files');

		this.value = (Type.isInteger(count) ? parseInt(count, 10) : 0);

		this.node = null;
	}

	render(): HTMLElement
	{
		const uiClasses = 'ui-label ui-label-sm ui-label-light';

		this.node = Tag.render`
			<div class="tasks-scrum__item--attachment-counter ${this.value ? '--visible' : ''} ${uiClasses}">
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

	onClick()
	{
		this.emit('click');
	}
}