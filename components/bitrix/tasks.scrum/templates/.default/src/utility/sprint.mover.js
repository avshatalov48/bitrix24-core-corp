import {BaseEvent} from 'main.core.events';

import {RequestSender} from './request.sender';
import {DomBuilder} from './dom.builder';
import {EntityStorage} from './entity.storage';

type Params = {
	requestSender: RequestSender,
	domBuilder: DomBuilder,
	entityStorage: EntityStorage
}

export class SprintMover
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.domBuilder = params.domBuilder;
		this.entityStorage = params.entityStorage;

		this.bindHandlers();
	}

	bindHandlers()
	{
		this.domBuilder.subscribe('sprintMove', this.onSprintMove.bind(this))
	}

	onSprintMove(baseEvent: BaseEvent)
	{
		const dragEndEvent = baseEvent.getData();
		if (!dragEndEvent.endContainer)
		{
			return;
		}

		this.requestSender.updateSprintSort({
			sortInfo: this.calculateSprintSort()
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	calculateSprintSort(increment = 0)
	{
		const listSortInfo = {};

		const container = this.domBuilder.getSprintPlannedListNode();

		const sprints = [...container.querySelectorAll('[data-sprint-sort]')];
		let sort = 1 + increment;
		sprints.forEach((sprintNode) => {
			const sprintId = sprintNode.dataset.sprintId;
			const sprint = this.entityStorage.findEntityByEntityId(sprintId);
			if (sprint)
			{
				sprint.setSort(sort);
				listSortInfo[sprintId] = {
					sort: sort
				};
				sprintNode.dataset.sprintSort = sort;
				sort++;
			}
		});

		return listSortInfo;
	}
}