/**
 * @module tasks/navigator
 */
jn.define('tasks/navigator', (require, exports, module) => {
	const { BaseNavigator } = require('navigator/base');
	const {
		NOTIFICATION_EVENTS,
		SUBSCRIPTION_EVENTS,
		TASKS_TABS_NAVIGATOR,
		TASKS_ROOT_COMPONENT_NAME,
	} = require('tasks/navigator/meta');
	const { EntityReady } = require('entity-ready');

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
			BX.removeCustomEvent(NOTIFICATION_EVENTS.TASKS, this.setActiveTab.bind(this));
		}

		subscribeToTasksNotification()
		{
			BX.addCustomEvent(NOTIFICATION_EVENTS.TASKS, this.setActiveTab.bind(this));
			this.onSubscribeToPushNotification(SUBSCRIPTION_EVENTS.TASKS);
		}

		subscribeOnMakeTabActive()
		{
			BX.addCustomEvent(TASKS_TABS_NAVIGATOR.makeActive, this.setTasksTabsActive.bind(this));
		}

		setActiveTab()
		{
			if (!this.isActiveTab())
			{
				this.makeTabActive();
			}
		}

		async setTasksTabsActive(tabId)
		{
			this.setActiveTab();
			await EntityReady.wait(TASKS_ROOT_COMPONENT_NAME);
			BX.postComponentEvent(TASKS_TABS_NAVIGATOR.makeTabActive, [tabId]);
		}
	}

	module.exports = {
		TasksNavigator,
	};
});
