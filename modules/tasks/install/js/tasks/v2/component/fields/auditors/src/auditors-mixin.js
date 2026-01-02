import { Core } from 'tasks.v2.core';
import { Model } from 'tasks.v2.const';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { UserModel } from 'tasks.v2.model.users';

export const AuditorsMixin = {
	methods: {
		update(users: UserModel[]): void
		{
			this.insertUsers(users);
			this.updateTask(users.map(({ id }) => id));
		},
		insertUsers(users: UserModel[]): void
		{
			void Core.getStore().dispatch(`${Model.Users}/upsertMany`, users);
		},
		updateTask(auditorsIds: number[]): void
		{
			void taskService.update(
				this.taskId,
				{ auditorsIds },
			);
		},
	},
};
