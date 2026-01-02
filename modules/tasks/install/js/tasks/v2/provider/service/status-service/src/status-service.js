import type { Store } from 'ui.vue3.vuex';

import { Core } from 'tasks.v2.core';
import { Model, TaskStatus } from 'tasks.v2.const';
import { apiClient } from 'tasks.v2.lib.api-client';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { TaskModel } from 'tasks.v2.model.tasks';

export const statusService = new class
{
	async start(id: number): Promise<void>
	{
		await this.#updateStatus(id, 'Task.Status.start', TaskStatus.InProgress);
	}

	async disapprove(id: number): Promise<void>
	{
		await this.#updateStatus(id, 'Task.Status.disapprove', TaskStatus.Pending);
	}

	async defer(id: number): Promise<void>
	{
		await this.#updateStatus(id, 'Task.Status.defer', TaskStatus.Deferred);
	}

	async approve(id: number): Promise<void>
	{
		await this.#updateStatus(id, 'Task.Status.approve', TaskStatus.Completed);
	}

	async pause(id: number): Promise<void>
	{
		await this.#updateStatus(id, 'Task.Status.pause', TaskStatus.Pending);
	}

	async complete(id: number): Promise<void>
	{
		const status = this.#getStoreTask(id).needsControl ? TaskStatus.SupposedlyCompleted : TaskStatus.Completed;

		await this.#updateStatus(id, 'Task.Status.complete', status);
	}

	async renew(id: number): Promise<void>
	{
		await this.#updateStatus(id, 'Task.Status.renew', TaskStatus.Pending);
	}

	async #updateStatus(id: number, action: string, status: string): Promise<void>
	{
		const taskBeforeUpdate = this.#getStoreTask(id);

		await this.#updateStoreTask(id, { status });

		if (!taskService.isRealId(id))
		{
			return;
		}

		try
		{
			const data = await apiClient.post(action, { task: { id } });

			await taskService.extractTask(data);
		}
		catch (error)
		{
			await this.#updateStoreTask(id, taskBeforeUpdate);

			console.error(`StatusService: ${action} error`, error);
		}
	}

	async #updateStoreTask(id: number, fields: TaskModel): Promise<void>
	{
		await this.$store.dispatch(`${Model.Tasks}/update`, { id, fields });
	}

	#getStoreTask(id: number): TaskModel
	{
		return { ...this.$store.getters[`${Model.Tasks}/getById`](id) };
	}

	get $store(): Store
	{
		return Core.getStore();
	}
}();
