/**
 * @module tasks/background/tasks-notifications/task-tab-open-from-more
 */
jn.define('tasks/background/tasks-notifications/task-tab-open-from-more', (require, exports, module) => {
	const { BaseNotificationHandler } = require('background/notifications/base');
	const { AnalyticsEvent } = require('analytics');
	const { NOTIFICATION_EVENTS, SUBSCRIPTION_EVENTS } = require('navigator/more-tab/meta');

	/**
	 * @class TasksTabsOpenFromMoreNotification
	 */
	class TasksTabsOpenFromMoreNotification extends BaseNotificationHandler
	{
		getNotificationType()
		{
			return 'MOBILE_OPEN_TASK_TAB_FROM_MORE';
		}

		getSubscriptionEventName()
		{
			return SUBSCRIPTION_EVENTS.TASKS;
		}

		getNotificationEventName()
		{
			return NOTIFICATION_EVENTS.TASKS;
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
		TasksTabsOpenFromMoreNotification,
	};
});
