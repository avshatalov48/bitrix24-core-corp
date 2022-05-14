import {Dom} from 'main.core';
import {Loader} from 'main.loader';
import {BaseEvent} from 'main.core.events';

import {Filter} from '../service/filter';

import {PlanBuilder} from '../view/plan/plan.builder';

import {EntityStorage} from '../entity/entity.storage';
import {Sprint} from '../entity/sprint/sprint';

import {RequestSender} from './request.sender';

import {Item, ItemParams} from '../item/item';

type Params = {
	filter: Filter,
	requestSender: RequestSender,
	entityStorage: EntityStorage,
	planBuilder: PlanBuilder
}

export class FilterHandler
{
	constructor(params: Params)
	{
		this.filter = params.filter;
		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;
		this.planBuilder = params.planBuilder;

		this.filter.subscribe('applyFilter', this.onApplyFilter.bind(this));
	}

	onApplyFilter(baseEvent: BaseEvent)
	{
		this.fadeOutAll();

		const containerPosition = Dom.getPosition(this.planBuilder.getScrumContainer());

		const loader = new Loader({
			target: this.planBuilder.getScrumContainer(),
			offset: {
				top: `${containerPosition.top / 2}px`
			}
		});

		loader.show();

		this.requestSender.applyFilter().then((response) => {

			const filteredItemsData = response.data;

			this.entityStorage.getAllItems().forEach((item: Item) => {
				const entity = this.entityStorage.findEntityByEntityId(item.getEntityId());
				if (!entity.isCompleted())
				{
					entity.removeItem(item);
					if (item.isShownSubTasks())
					{
						item.hideSubTasks();
					}
					item.removeYourself();
				}
			});

			filteredItemsData.forEach((itemParams: ItemParams) => {
				const item = Item.buildItem(itemParams);
				const entity = this.entityStorage.findEntityByEntityId(item.getEntityId());
				item.setShortView(entity.getShortView());
				if (!entity.isCompleted())
				{
					entity.appendItemToList(item);
					entity.setItem(item);
				}
			});

			this.fadeInAll();

			loader.hide();
		}).catch((response) => {

			this.fadeInAll();

			loader.hide();

			this.requestSender.showErrorAlert(response);
		});
	}

	fadeOutAll()
	{
		this.entityStorage.getBacklog().fadeOut();

		this.entityStorage.getSprints().forEach((sprint: Sprint) => {
			if (!sprint.isCompleted())
			{
				sprint.fadeOut();
			}
		});
	}

	fadeInAll()
	{
		this.entityStorage.getBacklog().fadeIn();

		this.entityStorage.getSprints().forEach((sprint: Sprint) => {
			if (!sprint.isCompleted())
			{
				sprint.fadeIn();
			}
		});
	}
}