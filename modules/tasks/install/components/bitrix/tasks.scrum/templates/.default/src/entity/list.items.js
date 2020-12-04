import {Tag} from 'main.core';
import {Entity} from './entity';

export class ListItems
{
	constructor(entity: Entity)
	{
		this.entity = entity;

		this.element = null;
	}

	render(): HTMLElement
	{
		this.element = Tag.render`
			<div class="tasks-scrum-items-list" data-entity-id="${this.entity.getId()}">
				${this.entity.isCompleted() ? '' : this.entity.getInput().render()}
				${[...this.entity.getItems().values()].map((item) => item.render())}
			</div>
		`;
		return this.element;
	}

	getElement(): HTMLElement|null
	{
		return this.element;
	}
}