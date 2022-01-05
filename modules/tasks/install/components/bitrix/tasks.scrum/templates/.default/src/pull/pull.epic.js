import {EntityStorage} from '../entity/entity.storage';

import {BaseEvent} from 'main.core.events';

import {RequestSender} from '../utility/request.sender';

import {Epic} from '../epic/epic';

import type {EpicType} from '../item/task/epic';

type Params = {
	requestSender: RequestSender,
	entityStorage: EntityStorage,
	epic: Epic
}

export class PullEpic
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;
		this.epic = params.epic;

		this.listIdsToSkipAdding = new Set();
		this.listIdsToSkipUpdating = new Set();
		this.listIdsToSkipRemoving = new Set();
	}

	getModuleId()
	{
		return 'tasks';
	}

	getMap()
	{
		return {
			epicAdded: this.onEpicAdded.bind(this),
			epicUpdated: this.onEpicUpdated.bind(this),
			epicRemoved: this.onEpicRemoved.bind(this)
		};
	}

	onEpicAdded(epicData: EpicType)
	{
		this.epic.onAfterAdd((new BaseEvent()).setData(epicData));
	}

	onEpicUpdated(epicData: EpicType)
	{
		this.epic.onAfterEdit((new BaseEvent()).setData(epicData));
	}

	onEpicRemoved(epicData: EpicType)
	{
		this.epic.onAfterRemove((new BaseEvent()).setData(epicData));
	}
}