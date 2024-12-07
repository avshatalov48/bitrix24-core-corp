/**
 * @module tasks/navigator/meta
 */
jn.define('tasks/navigator/meta', (require, exports, module) => {
	const NOTIFICATION_EVENTS = {
		TASKS: 'PushNotifications::TasksTabsOpen',
	};

	const SUBSCRIPTION_EVENTS = {
		TASKS: 'PushNotifications::SubscribeToTasksTabsOpen',
	};

	module.exports = {
		NOTIFICATION_EVENTS,
		SUBSCRIPTION_EVENTS,
	};
});
