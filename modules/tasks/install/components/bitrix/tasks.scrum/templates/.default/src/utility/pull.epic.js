import {RequestSender} from './request.sender';
import {DomBuilder} from './dom.builder';
import {EntityStorage} from './entity.storage';
import {Epic} from './epic';

import type {EpicType} from '../item/item';

type Params = {
	requestSender: RequestSender,
	domBuilder: DomBuilder,
	entityStorage: EntityStorage,
	epic: Epic
}

export class PullEpic
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.domBuilder = params.domBuilder;
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
		this.epic.onAfterCreateEpic(epicData);
	}

	onEpicUpdated(epicData: EpicType)
	{
		this.epic.onAfterUpdateEpic(epicData);
	}

	onEpicRemoved(epicData: EpicType)
	{
		this.epic.onAfterRemoveEpic(epicData);
	}
}