import {Dom, Tag} from 'main.core';

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
			<div class="tasks-scrum__content-items" data-entity-id="${this.entity.getId()}">
				${[...this.entity.getItems().values()].map((item: Item) => {
					item.setEntityType(this.entity.getEntityType());
					return item.render();
				})}
				${this.renderLoader()}
			</div>
		`;

		return this.node;
	}

	renderLoader(): ?HTMLElement
	{
		return Tag.render`<div class="tasks-scrum-entity-items-loader"></div>`;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	getListNode(): ?HTMLElement
	{
		return this.node;
	}

	setEntityId(entityId: number)
	{
		this.node.dataset.entityId = parseInt(entityId, 10);
	}

	addScrollbar()
	{
		Dom.addClass(this.getNode(), '--scrollbar');
	}

	removeScrollbar()
	{
		Dom.removeClass(this.getNode(), '--scrollbar');
	}
}
