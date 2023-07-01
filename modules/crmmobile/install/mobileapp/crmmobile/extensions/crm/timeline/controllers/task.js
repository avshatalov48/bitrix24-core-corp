/**
 * @module crm/timeline/controllers/task
 */
jn.define('crm/timeline/controllers/task', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');

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
					this.openTask(actionParams);
					break;

				case SupportedActions.SEND_PING:
					this.sendPing(actionParams);
					break;

				case SupportedActions.DELETE:
					this.deleteTask(actionParams);
					break;

				case SupportedActions.CHANGE_DEADLINE:
					this.changeDeadline(actionParams);
					break;
			}
		}

		openTask(data)
		{
			const eventData = [
				{
					taskId: data.taskId,
					taskInfo: {
						title: data.taskTitle,
					},
				},
			];

			BX.postComponentEvent('taskbackground::task::open', eventData, 'background');
		}

		sendPing(data)
		{
			const task = new Task({ id: env.userId });
			task.updateData({ id: data.taskId });
			task.ping();
		}

		deleteTask(data)
		{
			const task = new Task({ id: env.userId });
			task.updateData({ id: data.taskId });
			task.remove();
		}

		changeDeadline(data)
		{
			const task = new Task({ id: env.userId });
			task.updateData({ id: data.taskId });
			task.deadline = data.valueTs * 1000;
			task.saveDeadline();
		}
	}

	module.exports = { TimelineTaskController };
});