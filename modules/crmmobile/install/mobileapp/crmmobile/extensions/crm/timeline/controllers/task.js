/**
 * @module crm/timeline/controllers/task
 */
jn.define('crm/timeline/controllers/task', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { Type } = require('crm/type');
	const { dispatch } = require('statemanager/redux/store');

	const SupportedActions = {
		OPEN: 'Task:View',
		EDIT: 'Task:Edit',
		DELETE: 'Task:Delete',
		SEND_PING: 'Task:Ping',
		OPEN_RESULT: 'Task:ResultView',
		CHANGE_DEADLINE: 'Task:ChangeDeadline',
	};

	/**
	 * @class TimelineTaskController
	 */
	class TimelineTaskController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		onItemAction({ action, actionParams = {} })
		{
			switch (action)
			{
				case SupportedActions.OPEN:
				case SupportedActions.EDIT:
				case SupportedActions.OPEN_RESULT:
					void this.openTask({
						...actionParams,
						analyticsLabel: {
							c_section: 'crm',
							c_sub_section: Type.getTypeForAnalytics(this.entity.typeId),
						},
					});
					break;

				case SupportedActions.SEND_PING:
					void this.sendPing(actionParams);
					break;

				case SupportedActions.DELETE:
					void this.deleteTask(actionParams);
					break;

				case SupportedActions.CHANGE_DEADLINE:
					void this.changeDeadline(actionParams);
					break;

				default:
					break;
			}
		}

		async openTask({ taskId, analyticsLabel })
		{
			const { Entry } = await requireLazy('tasks:entry');
			if (Entry)
			{
				Entry.openTask({ taskId }, { analyticsLabel });
			}
		}

		async deleteTask({ taskId })
		{
			const { removeTask } = await requireLazy('tasks:task/remove');
			if (removeTask)
			{
				removeTask(taskId);
			}
		}

		async sendPing(data)
		{
			const { ping } = await requireLazy('tasks:statemanager/redux/slices/tasks');
			if (ping)
			{
				dispatch(
					ping({ taskId: data.taskId }),
				);
			}
		}

		async changeDeadline(data)
		{
			const { updateDeadline } = await requireLazy('tasks:statemanager/redux/slices/tasks');
			if (updateDeadline)
			{
				dispatch(
					updateDeadline({
						taskId: data.taskId,
						deadline: data.valueTs * 1000,
					}),
				);
			}
		}
	}

	module.exports = { TimelineTaskController };
});
