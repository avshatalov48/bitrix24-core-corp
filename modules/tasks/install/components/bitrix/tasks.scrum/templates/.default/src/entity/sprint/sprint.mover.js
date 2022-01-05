import {BaseEvent} from 'main.core.events';

import {PlanBuilder} from '../../view/plan/plan.builder';

import {EntityStorage} from '../entity.storage';
import {Sprint} from './sprint';

import {RequestSender} from '../../utility/request.sender';

type Params = {
	requestSender: RequestSender,
	planBuilder: PlanBuilder,
	entityStorage: EntityStorage
}

export class SprintMover
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.planBuilder = params.planBuilder;
		this.entityStorage = params.entityStorage;

		this.bindHandlers();
	}

	bindHandlers()
	{
		this.planBuilder.subscribe('setDraggable', this.onSetDraggable.bind(this));

		//this.planBuilder.subscribe('sprintMove', this.onSprintMove.bind(this))
	}

	onSetDraggable(baseEvent: BaseEvent)
	{
		// this.draggableSprints = new Draggable({
		// 	container: this.sprintsNode.querySelector('.tasks-scrum__sprints--planned'),
		// 	draggable: '.tasks-scrum-sprint',
		// 	dragElement: '.tasks-scrum-sprint-dragndrop',
		// 	type: Draggable.DROP_PREVIEW,
		// });
		// this.draggableSprints.subscribe('end', (baseEvent) => {
		// 	const dragEndEvent = baseEvent.getData();
		// 	this.emit('sprintMove', dragEndEvent);
		// });
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

		const sprints = this.entityStorage.getPlannedSprints();

		let sort = 1 + increment;
		sprints.forEach((sprint: Sprint) => {
			sprint.setSort(sort);
			listSortInfo[sprint.getId()] = {
				sort: sort
			};
			sort++;
		});

		return listSortInfo;
	}
}