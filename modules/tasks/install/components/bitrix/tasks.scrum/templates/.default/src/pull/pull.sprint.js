import {PlanBuilder} from '../view/plan/plan.builder';

import {EntityStorage} from '../entity/entity.storage';
import {Sprint} from '../entity/sprint/sprint';

import {RequestSender} from '../utility/request.sender';

import type {SprintParams} from '../entity/sprint/sprint';

type Params = {
	requestSender: RequestSender,
	planBuilder: PlanBuilder,
	entityStorage: EntityStorage,
	groupId: number
}

type RemoveParams = {
	sprintId: number
}

export class PullSprint
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.planBuilder = params.planBuilder;
		this.entityStorage = params.entityStorage;
		this.groupId = params.groupId;

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
			sprintAdded: this.onSprintAdded.bind(this),
			sprintUpdated: this.onSprintUpdated.bind(this),
			sprintRemoved: this.onSprintRemoved.bind(this)
		};
	}

	onSprintAdded(params: SprintParams)
	{
		if (this.groupId !== params.groupId)
		{
			return;
		}

		const sprint = Sprint.buildSprint(params);

		if (this.needSkipAdd(sprint))
		{
			this.cleanSkipAdd(sprint);

			return;
		}

		this.planBuilder.createSprintNode(sprint);
	}

	onSprintUpdated(params: SprintParams)
	{
		const tmpSprint = Sprint.buildSprint(params);

		if (this.needSkipUpdate(tmpSprint))
		{
			this.cleanSkipUpdate(tmpSprint);

			return;
		}

		const sprint = this.entityStorage.findEntityByEntityId(tmpSprint.getId());
		if (sprint)
		{
			if (tmpSprint.getStatus() !== sprint.getStatus())
			{
				if (tmpSprint.getStatus() === 'active')
				{
					this.planBuilder.moveSprintToActiveListNode(sprint);
				}

				if (tmpSprint.getStatus() === 'completed')
				{
					this.planBuilder.moveSprintToCompletedListNode(sprint);
				}

				this.planBuilder.updatePlannedSprints(
					this.entityStorage.getPlannedSprints(),
					tmpSprint.getStatus() === 'active'
				);
			}

			sprint.updateYourself(tmpSprint);

			this.planBuilder.updateSprintContainers();
		}
	}

	onSprintRemoved(params: RemoveParams)
	{
		if (this.needSkipRemove(params.sprintId))
		{
			this.cleanSkipRemove(params.sprintId);

			return;
		}

		const sprint = this.entityStorage.findEntityByEntityId(params.sprintId);
		if (sprint)
		{
			sprint.removeYourself();
			this.entityStorage.removeSprint(sprint.getId());
		}
	}

	addTmpIdToSkipAdding(tmpId: string)
	{
		this.listIdsToSkipAdding.add(tmpId);
	}

	addIdToSkipUpdating(sprintId: number)
	{
		this.listIdsToSkipUpdating.add(sprintId);
	}

	addIdToSkipRemoving(sprintId: number)
	{
		this.listIdsToSkipRemoving.add(sprintId);
	}

	needSkipAdd(sprint: Sprint): boolean
	{
		return this.listIdsToSkipAdding.has(sprint.getTmpId());
	}

	cleanSkipAdd(sprint: Sprint)
	{
		this.listIdsToSkipAdding.delete(sprint.getTmpId());
	}

	needSkipUpdate(sprint: Sprint): boolean
	{
		return this.listIdsToSkipUpdating.has(sprint.getId());
	}

	cleanSkipUpdate(sprint: Sprint)
	{
		this.listIdsToSkipUpdating.delete(sprint.getId());
	}

	needSkipRemove(sprintId: number): boolean
	{
		return this.listIdsToSkipRemoving.has(sprintId);
	}

	cleanSkipRemove(sprintId: number)
	{
		this.listIdsToSkipRemoving.delete(sprintId);
	}
}