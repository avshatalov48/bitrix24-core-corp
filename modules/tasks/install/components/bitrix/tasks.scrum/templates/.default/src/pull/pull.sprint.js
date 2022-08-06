import {Type} from 'main.core';

import {PlanBuilder} from '../view/plan/plan.builder';

import {EntityStorage} from '../entity/entity.storage';
import {Sprint} from '../entity/sprint/sprint';
import {Item} from '../item/item';

import {RequestSender} from '../utility/request.sender';

type Params = {
	requestSender: RequestSender,
	planBuilder: PlanBuilder,
	entityStorage: EntityStorage,
	groupId: number,
	canStartSprint: boolean,
	canCompleteSprint: boolean
}

type PushParams = {
	id: number,
	groupId: number,
	tmpId?: string
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

	getModuleId(): string
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

	onSprintAdded(params: PushParams)
	{
		if (this.groupId !== params.groupId)
		{
			return;
		}

		if (this.needSkipAdd(params.tmpId))
		{
			this.cleanSkipAdd(params.tmpId);

			return;
		}

		this.requestSender.getSprintData({
			sprintId: params.id
		})
			.then((response) => {
				response.data.items = [];
				const sprint = Sprint.buildSprint(response.data);
				this.planBuilder.createSprintNode(sprint);
			})
			.catch((response) => {})
		;
	}

	onSprintUpdated(params: PushParams)
	{
		if (this.groupId !== params.groupId)
		{
			return;
		}

		if (this.needSkipUpdate(params.id))
		{
			this.cleanSkipUpdate(params.id);

			return;
		}

		this.requestSender.getSprintData({
			sprintId: params.id
		})
			.then((response) => {
				response.data.items = [];
				const tmpSprint = Sprint.buildSprint(response.data);
				const sprint: Sprint = this.entityStorage.findEntityByEntityId(tmpSprint.getId());
				if (sprint)
				{
					const currentStatus = sprint.getStatus();

					sprint.updateYourself(tmpSprint);

					if (tmpSprint.getStatus() !== currentStatus)
					{
						if (tmpSprint.getStatus() === 'active')
						{
							this.planBuilder.moveSprintToActiveListNode(sprint);
						}

						if (tmpSprint.getStatus() === 'completed')
						{
							sprint.getItems()
								.forEach((item: Item) => {
									if (item.isShownSubTasks())
									{
										item.hideSubTasks();
									}
									sprint.removeItem(item);
									item.removeYourself();
								})
							;
							sprint.setBlank(sprint);
							sprint.hideContent();

							this.planBuilder.moveSprintToCompletedListNode(sprint);
						}
					}

					this.planBuilder.updatePlannedSprints(
						this.entityStorage.getPlannedSprints(),
						(!Type.isUndefined(this.entityStorage.getActiveSprint()))
					);

					this.planBuilder.updateSprintContainers();
				}
			})
			.catch((response) => {})
		;
	}

	onSprintRemoved(params: PushParams)
	{
		if (this.groupId !== params.groupId)
		{
			return;
		}

		if (this.needSkipRemove(params.id))
		{
			this.cleanSkipRemove(params.id);

			return;
		}

		const sprint = this.entityStorage.findEntityByEntityId(params.id);
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

	needSkipAdd(tmpId: string): boolean
	{
		return this.listIdsToSkipAdding.has(tmpId);
	}

	cleanSkipAdd(tmpId: string)
	{
		this.listIdsToSkipAdding.delete(tmpId);
	}

	needSkipUpdate(sprintId: number): boolean
	{
		return this.listIdsToSkipUpdating.has(sprintId);
	}

	cleanSkipUpdate(sprintId: number)
	{
		this.listIdsToSkipUpdating.delete(sprintId);
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