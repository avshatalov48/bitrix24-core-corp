import { Base } from './base';
import ConfigurableItem from '../configurable-item';
import type { ActionParams } from './base';
import { ajax, Loc } from 'main.core';
import { UI } from 'ui.notification';
import { MessageBox } from 'ui.dialogs.messagebox';

export class Task extends Base
{
	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (
			item.getType() === 'Activity:TasksTask'
			|| item.getType() === 'TasksTaskCreation'
			|| item.getType() === 'TasksTaskModification'
			|| item.getType() === 'Activity:TasksTaskComment'
		);
	}

	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData, animationCallbacks } = actionParams;
		if (!actionData)
		{
			return;
		}

		const taskId = actionData.taskId ?? null;
		if (!taskId)
		{
			return;
		}

		if (actionType !== 'jsEvent')
		{
			return;
		}

		switch (action)
		{
			case 'Task:Ping':
				this.ping(actionData);
				break;

			case 'Task:ChangeDeadline':
				this.changeDeadline(item, actionData);
				break;

			case 'Task:View':
				this.view(actionData);
				break;

			case 'Task:Edit':
				this.edit(actionData);
				break

			case 'Task:Delete':
				this.delete(actionData);
				break;

			case 'Task:ResultView':
				this.viewResult(actionData);
				break;
		}
	}

	ping(actionData): void
	{
		if (!actionData.taskId)
		{
			return;
		}

		ajax.runAction('tasks.task.ping', {
				data: {
					taskId: actionData.taskId,
				},
			},
		).then((response) => {
			if (response.status === 'success')
			{
				UI.Notification.Center.notify({
					content: Loc.getMessage('CRM_TIMELINE_ITEM_TASK_PING_SENT'),
					autoHideDelay: 3000,
				});
			}
		});
	}

	changeDeadline(item: ConfigurableItem, actionData): void
	{
		if (!actionData.taskId || !actionData.value)
		{
			return;
		}

		ajax.runAction('tasks.task.update', {
				data: {
					taskId: actionData.taskId,
					fields: {
						DEADLINE: (new Date(actionData.valueTs * 1000)).toISOString(),
					},
					params: {
						skipTimeZoneOffset: 'DEADLINE',
					}
				},
			},
		).catch(response => {
			const errors = response.errors ??  null;
			if (errors.length > 0)
			{
				UI.Notification.Center.notify({
					content: errors[0].message,
					autoHideDelay: 3000,
				});
				item.forceRefreshLayout();
			}
		});
	}

	view(actionData): void
	{
		if (!actionData.path)
		{
			return;
		}

		BX.SidePanel.Instance.open(actionData.path, {
			cacheable: false,
		})
	}

	edit(actionData): void
	{
		if (!actionData.path)
		{
			return;
		}

		BX.SidePanel.Instance.open(actionData.path, {
			cacheable: false,
		})
	}

	delete(actionData): void
	{
		if (!actionData.taskId)
		{
			return;
		}

		const messageBox = new MessageBox(
			{
				message: Loc.getMessage('CRM_TIMELINE_ITEM_TASK_CONFIRM_DELETE'),
				buttons: BX.UI.Dialogs.MessageBoxButtons.YES_NO,
				onYes: () => {
					ajax.runAction('tasks.task.delete', {
							data: {
								taskId: actionData.taskId,
							},
						},
					)
					.then(() => {
						messageBox.close();
					})
					.catch((error) => {
						UI.Notification.Center.notify({
							content: error.errors[0].message ?? 'Error',
							autoHideDelay: 3000,
						});
						messageBox.close();
					})
				},
				onNo: () => {
					messageBox.close();
				}
			}
		);

		messageBox.show();
	}

	viewResult(actionData): void
	{
		if (!actionData.taskId)
		{
			return;
		}

		if (!actionData.path)
		{
			return;
		}

		ajax.runAction('tasks.task.result.getLast', {
				data: {
					taskId: actionData.taskId,
				},
			},
		).then((response) => {
			if (response.status === 'success')
			{
				const resultId = response.data.result;
				BX.SidePanel.Instance.open(actionData.path + '?RID=' + resultId, {
					cacheable: false,
				})
			}
		});
	}
}