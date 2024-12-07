/**
 * @module tasks/background/tasks-notifications/task-tab-open
 */
jn.define('tasks/background/tasks-notifications/task-tab-open', (require, exports, module) => {
	const { BaseNotificationHandler } = require('background/notifications/base');
	const { AnalyticsEvent } = require('analytics');
	const { NOTIFICATION_EVENTS, SUBSCRIPTION_EVENTS } = require('tasks/navigator/meta');

	/**
	 * @class TasksTabsOpenNotification
	 */
	class TasksTabsOpenNotification extends BaseNotificationHandler
	{
		getNotificationType()
		{
			return 'MOBILE_OPEN_TASK_TAB';
		}

		getNotificationEventName()
		{
			return NOTIFICATION_EVENTS.TASKS;
		}

		getSubscriptionEventName()
		{
			return SUBSCRIPTION_EVENTS.TASKS;
		}

		getAnalytics()
		{
			return new AnalyticsEvent()
				.setEvent('push_mobile_1-8d_tasks')
				.setCategory('1-8d')
				.setTool('mobile');
		}
	}

	module.exports = {
		TasksTabsOpenNotification,
	};
});
