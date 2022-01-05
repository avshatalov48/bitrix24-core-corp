import {BaseEvent} from 'main.core.events';

import {Filter} from '../service/filter';

import {EntityStorage} from '../entity/entity.storage';
import {Sprint} from '../entity/sprint/sprint';

import {RequestSender} from './request.sender';

import {Item, ItemParams} from '../item/item';

type Params = {
	filter: Filter,
	requestSender: RequestSender,
	entityStorage: EntityStorage
}

export class FilterHandler
{
	constructor(params: Params)
	{
		this.filter = params.filter;
		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;

		this.filter.subscribe('applyFilter', this.onApplyFilter.bind(this));
	}

	onApplyFilter(baseEvent: BaseEvent)
	{
		this.fadeOutAll();

		this.requestSender.applyFilter().then((response) => {

			const filteredItemsData = response.data;

			this.entityStorage.getAllItems().forEach((item: Item) => {
				const entity = this.entityStorage.findEntityByEntityId(item.getEntityId());
				if (!entity.isCompleted())
				{
					entity.removeItem(item);
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
		}).catch((response) => {

			this.fadeInAll();

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