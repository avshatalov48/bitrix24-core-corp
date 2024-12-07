/**
 * @module crm/navigator/meta
 */
jn.define('crm/navigator/meta', (require, exports, module) => {
	const NOTIFICATION_EVENTS = {
		CRM: 'PushNotifications::CrmTabsOpen',
	};

	const SUBSCRIPTION_EVENTS = {
		CRM: 'PushNotifications::SubscribeToCrmTabsOpen',
	};

	module.exports = {
		NOTIFICATION_EVENTS,
		SUBSCRIPTION_EVENTS,
	};
});
