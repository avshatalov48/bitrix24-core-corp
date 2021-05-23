import {Tag} from 'main.core';
import {Entity} from './entity';
import {Item} from '../item/item';

export class ListItems
{
	constructor(entity: Entity)
	{
		this.entity = entity;

		this.node = null;
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum-items-list" data-entity-id="${this.entity.getId()}">
				${this.entity.isCompleted() ? '' : this.entity.getInput().render()}
				${[...this.entity.getItems().values()].map((item: Item) => {
					item.setEntityType(this.entity.getEntityType());
					return item.render();
				})}
			</div>
		`;
		return this.node;
	}

	getNode(): HTMLElement|null
	{
		return this.node;
	}

	setEntityId(entityId: number)
	{
		this.node.dataset.entityId = parseInt(entityId, 10);
	}
}