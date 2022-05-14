import {Dom} from 'main.core';

import {PlanBuilder} from '../view/plan/plan.builder';

import {EntityStorage} from '../entity/entity.storage';

import {Sprint} from '../entity/sprint/sprint';
import {Item} from '../item/item';

type Params = {
	planBuilder: PlanBuilder,
	entityStorage: EntityStorage
}

export class Scroller
{
	constructor(params: Params)
	{
		this.planBuilder = params.planBuilder;
		this.entityStorage = params.entityStorage;
	}

	scrollToItem(item: Item)
	{
		if (this.isItemInViewport(item))
		{
			return;
		}

		const offset = 112;

		if (this.isBacklogItem(item))
		{
			const scrollContainer = this.entityStorage.getBacklog().getListItemsNode();

			const itemTopPosition = Dom.getRelativePosition(item.getNode(), scrollContainer).top;

			scrollContainer.scrollTo({
				top: scrollContainer.scrollTop + itemTopPosition - offset,
				behavior: 'smooth'
			});
		}
		else
		{
			const sprintsContainer = this.planBuilder.getSprintsContainer()

			const itemTopPosition = Dom.getRelativePosition(item.getNode(), sprintsContainer).top;

			sprintsContainer.scrollTo({
				top: sprintsContainer.scrollTop + itemTopPosition - offset,
				behavior: 'smooth'
			});
		}
	}

	scrollToSprint(sprint: Sprint)
	{
		window.scrollTo({ top: 240, behavior: 'smooth' });

		// todo dynamic focus to sprint node (loadItems)

		const offset = 80;

		const sprintsContainer = this.planBuilder.getSprintsContainer();
		const position = Dom.getRelativePosition(sprint.getNode(), sprintsContainer).top;

		sprintsContainer.scrollTo({
			top: sprintsContainer.scrollTop + position - offset,
			behavior: 'smooth'
		});
	}

	isItemInViewport(item: Item): boolean
	{
		const rect = item.getNode().getBoundingClientRect();

		return (
			rect.top >= 0
			&& rect.left >= 0
			&& rect.bottom <= (window.innerHeight || document.documentElement.clientHeight)
			&& rect.right <= (window.innerWidth || document.documentElement.clientWidth)
		);
	}

	isBacklogItem(item: Item): boolean
	{
		return this.entityStorage.getBacklog().hasItem(item);
	}
}