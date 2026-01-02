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

	const TASKS_TABS = {
		TASKS: 'tasks.dashboard',
		PROJECT: 'tasks.project.list',
		FLOW: 'tasks.flow.list',
		SCRUM: 'tasks.scrum.list',
		EFFICIENCY: 'tasks.efficiency',
	};

	const TASKS_ROOT_COMPONENT_NAME = 'tasks.tabs';

	const TASKS_TABS_NAVIGATOR = {
		makeActive: 'TasksNavigator::makeActive',
		makeTabActive: 'TasksNavigator::makeTabActiveByTabId',
	};

	module.exports = {
		NOTIFICATION_EVENTS,
		SUBSCRIPTION_EVENTS,
		TASKS_TABS,
		TASKS_ROOT_COMPONENT_NAME,
		TASKS_TABS_NAVIGATOR,
	};
});
