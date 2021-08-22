import {Dom} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {Filter} from '../service/filter';

import {RequestSender} from './request.sender';
import {EntityStorage} from './entity.storage';
import {SubTasksManager} from './subtasks.manager';

import {Sprint} from '../entity/sprint/sprint';
import {Item} from '../item/item';

type Params = {
	filter: Filter,
	requestSender: RequestSender,
	entityStorage: EntityStorage,
	subTasksCreator: SubTasksManager
}

export class FilterHandler extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.filter = params.filter;
		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;
		this.subTasksCreator = params.subTasksCreator;

		this.filter.subscribe('applyFilter', this.onApplyFilter.bind(this));
	}

	onApplyFilter(baseEvent: BaseEvent)
	{
		this.fadeOutAll();

		const filterInfo = baseEvent.getData();

		this.updateExactSearchStatusToEntities();

		this.requestSender.applyFilter().then((response) => {
			filterInfo.promise.fulfill();

			const filteredItemsData = response.data;

			this.entityStorage.getAllItems().forEach((item: Item) => {
				const entity = this.entityStorage.findEntityByEntityId(item.getEntityId());
				if (item.isParentTask())
				{
					this.subTasksCreator.cleanVisibility(item);
					this.subTasksCreator.cleanSubTasks(item);
				}
				entity.removeItem(item);
				item.removeYourself();
			});

			filteredItemsData.forEach((itemData) => {
				const item = Item.buildItem(itemData);
				const entity = this.entityStorage.findEntityByEntityId(item.getEntityId());
				Dom.append(item.render(), entity.getListItemsNode());
				entity.setItem(item);
				item.onAfterAppend(entity.getListItemsNode());
				if (item.isParentTask())
				{
					item.downSubTasksTick();
				}
			});

			this.updateVisibilityToEntities();

			this.fadeInAll();
		}).catch((response) => {
			filterInfo.promise.reject();

			this.fadeInAll();

			this.requestSender.showErrorAlert(response);
		});
	}

	updateExactSearchStatusToEntities()
	{
		this.entityStorage.getSprints().forEach((sprint: Sprint) => {
			sprint.setExactSearchApplied(this.filter.isSearchFieldApplied());
		});
	}

	updateVisibilityToEntities()
	{
		this.entityStorage.getSprints().forEach((sprint: Sprint) => {
			sprint.updateVisibility();
		});
	}

	fadeOutAll()
	{
		this.entityStorage.getBacklog().fadeOut();

		this.entityStorage.getSprints().forEach((sprint: Sprint) => {
			sprint.fadeOut();
		});
	}

	fadeInAll()
	{
		this.entityStorage.getBacklog().fadeIn();

		this.entityStorage.getSprints().forEach((sprint: Sprint) => {
			sprint.fadeIn();
		});
	}
}