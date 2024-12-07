/**
 * @module navigator/more-tab/meta
 */
jn.define('navigator/more-tab/meta', (require, exports, module) => {
	const NOTIFICATION_EVENTS = {
		TASKS: 'PushNotifications::TasksTabsOpenFromMore',
		CRM: 'PushNotifications::CrmTabsOpenFromMore',
		INVITE: 'PushNotifications::OpenInvite',
	};

	const SUBSCRIPTION_EVENTS = {
		TASKS: 'PushNotifications::SubscribeToTasksTabsOpenFromMore',
		CRM: 'PushNotifications::SubscribeToCrmTabsOpenFromMore',
		INVITE: 'PushNotifications::SubscribeToOpenInvite',
	};

	module.exports = {
		NOTIFICATION_EVENTS,
		SUBSCRIPTION_EVENTS,
	};
});
