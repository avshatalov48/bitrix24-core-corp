import {Type, Dom} from 'main.core';
import {Loader} from 'main.loader';
import {BaseEvent} from 'main.core.events';

import {Filter} from '../service/filter';

import {PlanBuilder} from '../view/plan/plan.builder';

import {Entity} from '../entity/entity';
import {EntityStorage} from '../entity/entity.storage';
import {Sprint, SprintParams} from '../entity/sprint/sprint';

import {RequestSender} from './request.sender';

import {Item, ItemParams} from '../item/item';

type Params = {
	filter: Filter,
	requestSender: RequestSender,
	entityStorage: EntityStorage,
	planBuilder: PlanBuilder,
	pageSize: number
}

type ApplyFilterResponse = {
	data: {
		isExactSearchApplied: 'Y' | 'N',
		completedSprints: Array<SprintParams>,
		items: Array<ItemParams>
	}
}

export class FilterHandler
{
	constructor(params: Params)
	{
		this.filter = params.filter;
		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;
		this.planBuilder = params.planBuilder;
		this.pageSize = params.pageSize;

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

		this.requestSender.applyFilter({
			pageSize: this.pageSize
		}).then((response: ApplyFilterResponse) => {

			const filteredItemsData = response.data.items;

			this.entityStorage.getAllItems().forEach((item: Item) => {
				const entity = this.entityStorage.findEntityByEntityId(item.getEntityId());
				entity.removeItem(item);
				if (item.isShownSubTasks())
				{
					item.hideSubTasks();
				}
				item.removeYourself();
			});

			const completedSprints = new Map();

			filteredItemsData.forEach((itemParams: ItemParams) => {
				const item = Item.buildItem(itemParams);
				const entity = this.entityStorage.findEntityByEntityId(item.getEntityId());
				if (response.data.completedSprints.length)
				{
					response.data.completedSprints
						.forEach((sprintParams: SprintParams) => {
							if (item.getEntityId() === sprintParams.id)
							{
								if (completedSprints.has(item.getEntityId()))
								{
									completedSprints.get(item.getEntityId()).setItem(item);
								}
								else
								{
									const completedSprint = Sprint.buildSprint(sprintParams);
									completedSprint.setItem(item);
									completedSprint.setShortView('Y');

									completedSprints.set(completedSprint.getId(), completedSprint);
								}
							}
						})
					;
				}

				if (entity && !entity.isCompleted())
				{
					item.setShortView(entity.getShortView());

					entity.appendItemToList(item);
					entity.setItem(item);

					entity.setActiveLoadItems(false);
					entity.bindItemsLoader();
				}
			});

			const isExactSearchApplied = response.data.isExactSearchApplied === 'Y';

			this.entityStorage.getAllEntities()
				.forEach((entity: Entity) => {
					entity.setExactSearchApplied(response.data.isExactSearchApplied);
					if (!entity.isCompleted() && entity.isEmpty())
					{
						if (entity.getNumberTasks() > 0 && entity.isExactSearchApplied())
						{
							entity.showEmptySearchStub();
							entity.hideDropzone();
						}
						else
						{
							if (this.entityStorage.existsAtLeastOneItem())
							{
								entity.showDropzone();
							}
							entity.hideEmptySearchStub();
						}
					}
					if (!entity.isBacklog() && !entity.isCompleted())
					{
						entity.setPageNumberItems(Math.ceil((entity.getNumberItems() / entity.getPageSize())));
					}
				})
			;

			this.planBuilder.hideEmptySearchStub();

			if (completedSprints.size)
			{
				this.planBuilder.showFilteredCompletedSprints(completedSprints);
			}
			else
			{
				this.planBuilder.hideFilteredCompletedSprints();

				if (isExactSearchApplied)
				{
					this.planBuilder.showEmptySearchStub();
				}
			}

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