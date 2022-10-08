import {Type, Loc, Text} from 'main.core';

import {MessageBox, MessageBoxButtons} from 'ui.dialogs.messagebox';
import {UI} from 'ui.notification';

import {Dod} from 'tasks.scrum.dod';

import {RequestSender} from './request.sender';

type State = {
	taskId: number,
	action: 'complete' | 'renew',
	groupId?: number,
	parentTaskId?: number,
	performActionOnParentTask?: boolean
}

type Task = {
	name: string
}

export class TaskStatus
{
	static actions = {
		complete: 'complete',
		renew: 'renew',
		proceed: 'proceed',
		skip: 'skip'
	};

	constructor(state: State)
	{
		this.setState(state);

		this.requestSender = new RequestSender();
	}

	setState(state: State)
	{
		this.taskId = parseInt(state.taskId, 10);
		this.action = (
			state.action === TaskStatus.actions.complete
				? TaskStatus.actions.complete
				: TaskStatus.actions.renew
		);
		this.groupId = Type.isUndefined(state.groupId) ? 0 : parseInt(state.groupId, 10);
		this.parentTaskId = Type.isUndefined(state.parentTaskId) ? 0 : parseInt(state.parentTaskId, 10);
		this.performActionOnParentTask = (
			Type.isUndefined(state.performActionOnParentTask)
				? false
				: state.performActionOnParentTask
		);
	}

	updateState(): Promise
	{
		return this.requestSender.getData({
			taskId: this.taskId
		})
			.then((response) => {
				this.setState(
					{
						...{
							action: this.action,
							groupId: this.groupId,
							parentTaskId: this.parentTaskId,
							performActionOnParentTask: this.performActionOnParentTask
						},
						...response.data
					}
				);
			})
		;
	}

	update(): Promise
	{
		return this.requestSender.needUpdateTask({
			taskId: this.parentTaskId,
			action: this.action
		})
			.then((response) => response.data === true)
			.then((needUpdate: boolean) => {
				if (needUpdate)
				{
					return this.requestSender.getTasks({
						groupId: this.groupId,
						taskIds: [this.parentTaskId, this.taskId]
					})
						.then((response) => {
							const tasks = response.data;

							return this.showMessage(
								tasks[this.parentTaskId],
								tasks[this.taskId]
							);
						})
					;
				}
				else
				{
					return TaskStatus.actions.skip;
				}
			})
			.then((response) => {
				if (this.performActionOnParentTask)
				{
					switch (response)
					{
						case TaskStatus.actions.complete:
							return this.completeTask(this.parentTaskId)
								.then(() => {
									UI.Notification.Center.notify({
										content: Loc.getMessage('TST_PARENT_COMPLETE_NOTIFY')
									});

									return response;
								})
							;
						case TaskStatus.actions.renew:
							return this.renewTask(this.parentTaskId)
								.then(() => {
									UI.Notification.Center.notify({
										content: Loc.getMessage('TST_PARENT_RENEW_NOTIFY')
									});

									return response;
								})
							;
						case TaskStatus.actions.proceed:
							UI.Notification.Center.notify({
								content: Loc.getMessage('TST_PARENT_PROCEED_NOTIFY')
							});

							return response;
						case TaskStatus.actions.skip:
							return response;
					}
				}
				else
				{
					return response;
				}
			})
			.catch((response) => this.requestSender.showErrorAlert(response))
		;
	}

	isParentScrumTask(taskId?: number): Promise
	{
		taskId = Type.isUndefined(taskId) ? this.parentTaskId : taskId;
		if (!taskId)
		{
			return new Promise((resolve) => resolve(false));
		}

		return this.requestSender.isParentScrumTask({
			groupId: this.groupId,
			taskId: taskId
		})
			.then((response) => response.data === true)
		;
	}

	showMessage(parentTask: Task, task: Task): Promise
	{
		return new Promise((resolve, reject) => {
			const isCompleteAction = (this.action === TaskStatus.actions.complete);

			(new MessageBox({
				minWidth: 300,
				message: isCompleteAction
					? Loc.getMessage('TST_PARENT_COMPLETE_MESSAGE')
						.replace(/#name#/g, Text.encode(parentTask.name))
					: Loc.getMessage('TST_PARENT_RENEW_MESSAGE')
						.replace("#name#", Text.encode(parentTask.name))
						.replace("#sub-name#", Text.encode(task.name))
				,
				buttons: MessageBoxButtons.OK_CANCEL,
				okCaption: isCompleteAction
					? Loc.getMessage('TST_PARENT_COMPLETE_OK_CAPTION')
					: Loc.getMessage('TST_PARENT_RENEW_OK_CAPTION')
				,
				cancelCaption: isCompleteAction
					? Loc.getMessage('TST_PARENT_PROCEED_CAPTION')
					: Loc.getMessage('TST_PARENT_RENEW_CANCEL_CAPTION')
				,
				onOk: (messageBox) => {
					if (isCompleteAction)
					{
						this.showDod(this.parentTaskId)
							.then(() => {
								messageBox.close();

								resolve(TaskStatus.actions.complete);
							})
							.catch(() => {
								messageBox.getOkButton().setDisabled(false);
							})
						;
					}
					else
					{
						messageBox.close();

						resolve(TaskStatus.actions.renew);
					}
				},
				onCancel: (messageBox) => {
					messageBox.close();
					if (isCompleteAction)
					{
						this.proceedParentTask(this.parentTaskId)
							.then(() => {
								resolve(TaskStatus.actions.proceed);
							})
						;
					}
					else
					{
						resolve(TaskStatus.actions.skip);
					}
				},
			})).show();
		});
	}

	showDod(taskId: number): Promise
	{
		return new Promise((resolve, reject) => {
			const dod = new Dod({
				groupId: this.groupId,
				taskId: taskId
			})

			dod.subscribe('resolve', () => resolve());
			dod.subscribe('reject', () => reject());

			dod.isNecessary()
				.then((isNecessary) => {
					if (isNecessary)
					{
						dod.showList();
					}
					else
					{
						resolve();
					}
				})
			;
		});
	}

	completeTask(taskId: number): Promise
	{
		return this.requestSender.completeTask({
			groupId: this.groupId,
			taskId: taskId
		});
	}

	renewTask(taskId: number): Promise
	{
		return this.requestSender.renewTask({
			groupId: this.groupId,
			taskId: taskId
		});
	}

	proceedParentTask(taskId: number): Promise
	{
		return this.requestSender.proceedParentTask({
			groupId: this.groupId,
			taskId: taskId
		});
	}
}