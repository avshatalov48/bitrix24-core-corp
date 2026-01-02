import { Core } from 'tasks.v2.core';
import { Limit, Model } from 'tasks.v2.const';
import { apiClient } from 'tasks.v2.lib.api-client';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { TaskDto } from 'tasks.v2.provider.service.task-service';
import type { RelationMeta } from './types';

const limit = Limit.RelationList;

export class RelationService
{
	#meta: RelationMeta;
	#addPromises: Promise[] = [];

	constructor(meta: RelationMeta)
	{
		this.#meta = meta;
	}

	async list(taskId: number, withIds: boolean = false): Promise<void>
	{
		await Promise.all(this.#addPromises);

		const { tasks, ids } = await this.#requestTasks(taskId, withIds);

		if (withIds)
		{
			this.#updateStoreRelationTasks(taskId, ids);
		}

		tasks.forEach((taskDto: TaskDto) => {
			if (!taskService.hasStoreTask(taskDto.id))
			{
				taskService.extractTask({ ...taskDto, [this.#meta.relationToField]: taskId });
				void Core.getStore().dispatch(`${Model.Tasks}/addPartiallyLoaded`, taskDto.id);
			}
		});
	}

	async setParent(taskId: number, parentId: number): Promise<?string>
	{
		return this.add(parentId, [taskId]);
	}

	async add(taskId: number, taskIds: number[]): Promise<?string>
	{
		this.addStore(taskId, taskIds);

		if (!taskService.isRealId(taskId) || taskIds.length === 0)
		{
			return null;
		}

		try
		{
			const promise = apiClient.post(`${this.#meta.controller}.add`, { taskId, taskIds });
			this.#addPromises.push(promise);
			await promise;
			this.#addPromises = this.#addPromises.filter((it) => it !== promise);
		}
		catch (error)
		{
			const failedIds = Object.entries(error.data)
				.filter(([, success]) => !success)
				.map(([id]) => Number(id))
			;

			this.deleteStore(taskId, failedIds);

			console.error(`${this.#meta.controller}.add error`, error);

			return error.errors?.[0]?.message;
		}

		return null;
	}

	addStore(taskId: number, taskIds: number[]): void
	{
		const meta = this.#meta;
		const task = taskService.getStoreTask(taskId);
		this.#updateStoreRelationTasks(taskId, [...(task?.[meta.idsField] || []), ...taskIds]);
		taskIds.forEach((it) => taskService.updateStoreTask(it, { [meta.relationToField]: taskId }));
	}

	async delete(taskId: number, taskIds: number[]): Promise<void>
	{
		this.deleteStore(taskId, taskIds);

		if (!taskService.isRealId(taskId) || taskIds.length === 0)
		{
			return;
		}

		try
		{
			await apiClient.post(`${this.#meta.controller}.delete`, { taskId, taskIds });
		}
		catch (error)
		{
			this.addStore(taskId, taskIds);

			console.error(`${this.#meta.controller}.delete error`, error);
		}
	}

	deleteStore(taskId: number, taskIds: number[]): void
	{
		const meta = this.#meta;
		const task = taskService.getStoreTask(taskId);
		this.#updateStoreRelationTasks(taskId, task?.[meta.idsField].filter((it) => !taskIds.includes(it)));
		taskIds.forEach((it) => taskService.updateStoreTask(it, { [meta.relationToField]: 0 }));
	}

	areIdsLoaded(taskId: number): boolean
	{
		const meta = this.#meta;
		const task = taskService.getStoreTask(taskId);
		if (!task || !taskService.isRealId(taskId))
		{
			return false;
		}

		return !task[meta.containsField] || (task[meta.containsField] && task[meta.idsField].length > 0);
	}

	hasUnloadedIds(taskId: number): boolean
	{
		const ids = taskService.getStoreTask(taskId)[this.#meta.idsField];

		return this.#getVisibleIds(ids).some((it) => !taskService.hasStoreTask(it));
	}

	async #requestTasks(taskId: number, withIds: boolean = false): Promise<{ tasks: TaskDto[], ids?: number[] }>
	{
		if (!taskService.isRealId(taskId))
		{
			const ids = taskService.getStoreTask(taskId)[this.#meta.idsField];

			const { tasks } = await apiClient.post(`${this.#meta.controller}.listByIds`, {
				taskIds: this.#getVisibleIds(ids),
			});

			return { tasks, ids };
		}

		const { tasks, ids } = await apiClient.post(`${this.#meta.controller}.list`, {
			taskId,
			relationTaskSelect: ['id', 'title', 'creator', 'responsible', 'deadline', 'rights'],
			withIds,
			navigation: {
				size: limit,
			},
		});

		return { tasks, ids };
	}

	#getVisibleIds(ids: number[]): number[]
	{
		return [...ids].sort((a, b) => b - a).slice(0, limit);
	}

	#updateStoreRelationTasks(taskId: number, taskIds: number[]): void
	{
		const meta = this.#meta;
		const relationIds = [...new Set(taskIds)];
		const contains = relationIds.length > 0;
		void taskService.updateStoreTask(taskId, { [meta.idsField]: relationIds, [meta.containsField]: contains });
	}
}
