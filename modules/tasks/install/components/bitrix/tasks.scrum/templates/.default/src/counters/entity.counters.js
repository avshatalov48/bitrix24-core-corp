import {EntityStorage} from '../entity/entity.storage';

import {Backlog} from '../entity/backlog/backlog';
import {Sprint} from '../entity/sprint/sprint';

import {RequestSender} from '../utility/request.sender';

type Params = {
	requestSender: RequestSender,
	entityStorage: EntityStorage
}

export class EntityCounters
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;
	}

	updateCounters(entities: Map<number, Backlog | Sprint>)
	{
		const requestData = {
			entityIds: [...entities.keys()]
		};

		this.requestSender.getEntityCounters(requestData)
			.then((response) => {
				Object.keys(response.data).forEach((entityId: number) => {
					entityId = parseInt(entityId, 10);
					const entity = entities.get(entityId);
					const counters = response.data[entityId];
					entity.setStoryPoints(counters.storyPoints);
					entity.setNumberTasks(counters.numberTasks);
					if (entity.isActive())
					{
						entity.setCompletedStoryPoints(counters.completedStoryPoints);
						entity.setUncompletedStoryPoints(counters.uncompletedStoryPoints);
					}
				});
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}
}