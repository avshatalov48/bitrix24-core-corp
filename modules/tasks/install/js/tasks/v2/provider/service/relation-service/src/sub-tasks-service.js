import { Loc } from 'main.core';
import { taskService } from 'tasks.v2.provider.service.task-service';
import { RelationService } from './relation-service';

export class SubTasksService extends RelationService
{
	async setParent(taskId: number, parentId: number): Promise<?string>
	{
		if (parentId === taskId)
		{
			return Loc.getMessage('TASKS_V2_RELATION_PARENT_CANNOT_BE_PARENT');
		}

		if (taskService.getStoreTask(taskId)?.subTaskIds.includes(parentId))
		{
			return Loc.getMessage('TASKS_V2_RELATION_SUB_TASK_CANNOT_BE_PARENT');
		}

		if (!taskService.isRealId(taskId))
		{
			return this.addStore(parentId, [taskId]);
		}

		const currentParentId = taskService.getStoreTask(taskId).parentId;

		const error = await this.add(parentId, [taskId]);

		if (error)
		{
			this.addStore(currentParentId, [taskId]);
		}

		return error;
	}

	async add(taskId: number, taskIds: number[]): Promise<?string>
	{
		let error = null;

		if (taskIds.includes(taskId))
		{
			error ??= Loc.getMessage('TASKS_V2_RELATION_SELF_CANNOT_BE_SUB_TASK');
		}

		const parentId = taskService.getStoreTask(taskId)?.parentId;
		if (taskIds.includes(parentId))
		{
			error ??= Loc.getMessage('TASKS_V2_RELATION_PARENT_CANNOT_BE_SUB_TASK');
		}

		error ??= super.add(taskId, taskIds.filter((id: number) => id !== taskId && id !== parentId));

		return error;
	}

	addStore(id: number, ids: number[]): void
	{
		ids.forEach((it) => this.deleteStore(taskService.getStoreTask(it)?.parentId, [it]));

		super.addStore(id, ids);
	}
}
