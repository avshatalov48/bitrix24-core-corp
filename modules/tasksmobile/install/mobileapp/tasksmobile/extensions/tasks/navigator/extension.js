/**
 * @module tasks/navigator
 */
jn.define('tasks/navigator', (require, exports, module) => {
	const { BaseNavigator } = require('navigator/base');
	const { NOTIFICATION_EVENTS, SUBSCRIPTION_EVENTS } = require('tasks/navigator/meta');

	/**
	 * @class TasksNavigator
	 */
	class TasksNavigator extends BaseNavigator
	{
		subscribeToPushNotifications()
		{
			this.subscribeToTasksNotification();
		}

		unsubscribeFromPushNotifications()
		{
			BX.removeCustomEvent(NOTIFICATION_EVENTS.TASKS, this.onTasksNotification.bind(this));
		}

		subscribeToTasksNotification()
		{
			BX.addCustomEvent(NOTIFICATION_EVENTS.TASKS, this.onTasksNotification.bind(this));
			this.onSubscribeToPushNotification(SUBSCRIPTION_EVENTS.TASKS);
		}

		onTasksNotification()
		{
			if (!this.isActiveTab())
			{
				this.makeTabActive();
			}
		}
	}

	module.exports = {
		TasksNavigator,
	};
});
