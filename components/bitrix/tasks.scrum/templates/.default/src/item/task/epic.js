import {Event, Tag, Type, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';

export type EpicType = {
	id: number,
	groupId: number,
	name: string,
	description: string,
	createdBy: number,
	modifiedBy: number,
	color: string
}

export class Epic extends EventEmitter
{
	constructor(epic: EpicType)
	{
		super(epic);

		this.setEventNamespace('BX.Tasks.Scrum.Item.Epic');

		if (Type.isUndefined(epic) || Type.isArray(epic) || Type.isNull(epic))
		{
			epic = {
				id: 0,
				groupId: 0,
				name: '',
				description: '',
				createdBy: 0,
				modifiedBy: 0,
				color: ''
			};
		}

		this.epic = epic;
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__item--epic ${this.epic.id ? '--visible' : ''}">
				<i
					class="tasks-scrum__item--epic-point"
					style="${`background-color: ${this.epic.color}`}"
				></i>
				<span>${Text.encode(this.epic.name)}</span>
			</div>
		`;

		Event.bind(this.node, 'click', this.onClick.bind(this));

		return this.node;
	}

	renderFullView()
	{
		const colorBorder = this.convertHexToRGBA(this.epic.color, 0.7);
		const colorBackground = this.convertHexToRGBA(this.epic.color, 0.3);

		const visibility = this.epic.id > 0 ? '--visible' : '';

		this.node = Tag.render`
			<div
				class="tasks-scrum__item--epic-full-view ${visibility}"
				style="background: ${colorBackground}; border-color: ${colorBorder};"
			>${Text.encode(this.epic.name)}</div>
		`;

		Event.bind(this.node, 'click', this.onClick.bind(this));

		return this.node;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	getValue(): EpicType
	{
		return this.epic;
	}

	getId(): number
	{
		return this.epic.id;
	}

	onClick()
	{
		this.emit('click');
	}

	convertHexToRGBA(hexCode, opacity)
	{
		let hex = hexCode.replace('#', '');

		if (hex.length === 3)
		{
			hex = `${hex[0]}${hex[0]}${hex[1]}${hex[1]}${hex[2]}${hex[2]}`;
		}

		const r = parseInt(hex.substring(0, 2), 16);
		const g = parseInt(hex.substring(2, 4), 16);
		const b = parseInt(hex.substring(4, 6), 16);

		return `rgba(${r},${g},${b},${opacity})`;
	}
}