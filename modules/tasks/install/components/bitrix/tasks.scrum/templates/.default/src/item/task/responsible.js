import {Tag, Type, Text, Event, Dom} from 'main.core';
import {EventEmitter} from 'main.core.events';

export type ResponsibleType = {
	id: number,
	name: string,
	pathToUser: string,
	photo?: {
		src: string
	}
}

export class Responsible  extends EventEmitter
{
	constructor(responsible: ResponsibleType)
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.Item.Responsible');

		this.responsible = (Type.isPlainObject(responsible) ? responsible : null);
	}

	render(): HTMLElement
	{
		const uiClasses = 'ui-icon ui-icon-common-user';

		const name = Text.encode(this.responsible.name);

		const src = this.responsible.photo ? Text.encode(this.responsible.photo.src) : null;
		const photoStyle = src ? `background-image: url('${encodeURI(src)}');` : '';

		this.node = Tag.render`
			<div class="tasks-scrum__item--responsible">
				<div class="tasks-scrum__item--responsible-photo ${uiClasses}" title="${name}">
					<i style="${photoStyle}"></i>
				</div>
				<span>${name}</span>
			</div>
		`;

		Event.bind(this.node.querySelector('div'), 'click', this.onClick.bind(this));
		Event.bind(this.node.querySelector('span'), 'click', this.onClick.bind(this));

		return this.node;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	getValue(): ResponsibleType
	{
		return this.responsible;
	}

	onClick()
	{
		this.emit('click');
	}
}