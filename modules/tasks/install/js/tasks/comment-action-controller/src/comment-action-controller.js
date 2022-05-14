import {ajax, Loc, Reflection, Runtime} from 'main.core';
import {rest} from 'rest.client';

class CommentActionController
{
	static workHours = null;
	static workSettings = null;
	static isAjaxRunning = false;

	static get possibleActions(): Object
	{
		return {
			deadlineChange: 'deadlineChange',
			taskApprove: 'taskApprove',
			taskDisapprove: 'taskDisapprove',
			taskComplete: 'taskComplete',
		};
	}

	static get accessActions(): Object
	{
		return {
			deadlineChange: 'CHANGE_DEADLINE',
			taskApprove: 'APPROVE',
			taskDisapprove: 'DISAPPROVE',
			taskComplete: 'COMPLETE',
		};
	}

	static get ajaxActions(): Object
	{
		return {
			deadlineChange: 'tasks.task.update',
			taskApprove: 'tasks.task.approve',
			taskDisapprove: 'tasks.task.disapprove',
			taskComplete: 'tasks.task.complete',
		};
	}

	static get actionNotificationMessages(): Object
	{
		const prefix = 'TASKS_COMMENT_ACTION_CONTROLLER_NOTIFICATION';

		return {
			deadlineChange: Loc.getMessage(`${prefix}_DEADLINE_CHANGE`),
			taskApprove: Loc.getMessage(`${prefix}_TASK_APPROVE`),
			taskDisapprove: Loc.getMessage(`${prefix}_TASK_DISAPPROVE`),
			taskComplete: Loc.getMessage(`${prefix}_TASK_COMPLETE`),
		};
	}

	static init(parameters: Object = {}): Promise
	{
		return new Promise((resolve) => {
			const promisesToResolve = [];

			if (!CommentActionController.workHours)
			{
				if (parameters.workHours)
				{
					CommentActionController.workHours = parameters.workHours;
				}
				else
				{
					promisesToResolve.push(CommentActionController.loadWorkHours());
				}
			}
			if (!CommentActionController.workSettings)
			{
				if (parameters.workSettings)
				{
					CommentActionController.workSettings = parameters.workSettings;
				}
				else
				{
					promisesToResolve.push(CommentActionController.loadWorkSettings());
				}
			}

			if (!promisesToResolve.length)
			{
				resolve();
			}

			Promise.all(promisesToResolve).then(() => resolve());
		});
	}

	static loadWorkHours(): Promise
	{
		return new Promise((resolve) => {
			rest.callMethod('calendar.settings.get').then((response) => {
				const {result} = response.answer;
				const [startHours, startMinutes] = String(result.work_time_start).split('.');
				const [endHours, endMinutes] = String(result.work_time_end).split('.');

				CommentActionController.workHours = {
					start: {
						hours: startHours,
						minutes: startMinutes,
					},
					end: {
						hours: endHours,
						minutes: endMinutes,
					},
				};
				resolve();
			});
		});
	}

	static loadWorkSettings(): Promise
	{
		return new Promise((resolve) => {
			ajax.runAction('tasks.userOption.getCalendarTimeVisibilityOption').then((response) => {
				CommentActionController.workSettings = {
					deadlineTimeVisibility: response.data.visibility || 'N',
				};
				resolve();
			});
		});
	}

	static isActionValid(action: string): boolean
	{
		return Object.keys(CommentActionController.possibleActions).includes(action);
	}

	static processLink(link: Object)
	{
		const [url, userId, taskId, action, deadline] = link.matches;

		if (!CommentActionController.isActionValid(action))
		{
			return;
		}

		if (action === CommentActionController.possibleActions.deadlineChange)
		{
			CommentActionController.init().then(() => {
				CommentActionController.showDeadlinePicker(link.anchor, taskId, deadline);
			});
			return;
		}

		CommentActionController.checkCanRun(action, taskId).then(
			(response) => {
				if (response)
				{
					CommentActionController.runAjaxAction(action, taskId);
				}
			},
			response => console.error(response)
		);
	}

	static showDeadlinePicker(target: HTMLElement, taskId: Integer, deadline: any)
	{
		const now = new Date();
		const today = new Date(
			Date.UTC(
				now.getFullYear(),
				now.getMonth(),
				now.getDate(),
				CommentActionController.workHours.end.hours,
				CommentActionController.workHours.end.minutes
			)
		);
		const value = (
			deadline ? new Date((Number(deadline) - (new Date()).getTimezoneOffset() * 60) * 1000) : today
		);

		const calendar = Reflection.getClass('BX.calendar');
		calendar({
			node: target,
			value,
			field: '',
			form: '',
			bTime: true,
			currentTime: Math.round((new Date()) / 1000) - (new Date()).getTimezoneOffset() * 60,
			bHideTimebar: true,
			bCompatibility: true,
			bCategoryTimeVisibilityOption: 'tasks.bx.calendar.deadline',
			bTimeVisibility: (
				CommentActionController.workSettings ?
					(CommentActionController.workSettings.deadlineTimeVisibility === 'Y')
					: false
			),
			callback_after: value => CommentActionController.onDeadlinePicked(value, taskId),
		});
	}

	static onDeadlinePicked(value: Date, taskId: Integer)
	{
		const action = CommentActionController.possibleActions.deadlineChange;

		CommentActionController.checkCanRun(action, taskId).then(
			(response) => {
				if (response)
				{
					CommentActionController.runAjaxAction(action, taskId, {
						fields: {
							DEADLINE: value.toISOString(),
						},
					});
				}
			},
			response => console.error(response)
		);
	}

	static checkCanRun(action: string, taskId: Integer): Promise
	{
		return new Promise((resolve, reject) => {
			if (CommentActionController.isAjaxRunning)
			{
				resolve(false);
			}

			CommentActionController.isAjaxRunning = true;

			ajax.runAction('tasks.task.getAccess', {
				data: {
					taskId,
				}
			}).then(
				(response) => {
					CommentActionController.isAjaxRunning = false;

					const {allowedActions} = response.data;
					const userId = Object.keys(allowedActions)[0];
					const accessAction = CommentActionController.accessActions[action];

					resolve(
						allowedActions
						&& allowedActions[userId]
						&& allowedActions[userId][accessAction]
					);
				},
				response => reject(response)
			);
		});
	}

	static runAjaxAction(action: string, taskId: Integer, data: Object = {})
	{
		if (CommentActionController.isAjaxRunning)
		{
			return;
		}

		CommentActionController.isAjaxRunning = true;
		if (action !== 'taskComplete')
		{
			CommentActionController.showNotification(action);
		}

		const defaultData = {
			taskId,
		};

		data = {...data, ...defaultData};
		if (!data.params)
		{
			data.params = {};
		}
		data.params.PLATFORM = 'web';

		ajax.runAction(CommentActionController.ajaxActions[action], {
			data: data,
		}).then(
			() => {
				if (action === 'taskComplete')
				{
					CommentActionController.showNotification(action);
				}
				CommentActionController.isAjaxRunning = false;
			},
			(response) => {
				if (
					response
					&& response.errors
				)
				{
					const errorMsg = {
						MESSAGE: response.errors[0].message,
						DATA: {
							ui: 'notification'
						}
					}
					const Tasks = Reflection.getClass('BX.Tasks');
					Tasks.alert([errorMsg]);
				}
				CommentActionController.isAjaxRunning = false;
			}
		);
	}

	static showNotification(action: string)
	{
		Runtime.loadExtension('ui.notification').then(() => {
			const notificationCenter = Reflection.getClass('BX.UI.Notification.Center');
			notificationCenter.notify({
				content: CommentActionController.actionNotificationMessages[action],
			});
		});
	}
}

export {CommentActionController};