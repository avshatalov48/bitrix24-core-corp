/**
 * @module crm/background/crm-notifications/crm-tab-open
 */
jn.define('crm/background/crm-notifications/crm-tab-open', (require, exports, module) => {
	const { BaseNotificationHandler } = require('background/notifications/base');
	const { AnalyticsEvent } = require('analytics');
	const { NOTIFICATION_EVENTS, SUBSCRIPTION_EVENTS } = require('crm/navigator/meta');

	/**
	 * @class CrmTabsOpenNotification
	 */
	class CrmTabsOpenNotification extends BaseNotificationHandler
	{
		getNotificationType()
		{
			return 'MOBILE_OPEN_CRM_TAB';
		}

		getNotificationEventName()
		{
			return NOTIFICATION_EVENTS.CRM;
		}

		getSubscriptionEventName()
		{
			return SUBSCRIPTION_EVENTS.CRM;
		}

		getAnalytics()
		{
			return new AnalyticsEvent()
				.setEvent('push_mobile_1-8d_crm')
				.setCategory('1-8d')
				.setTool('mobile');
		}
	}

	module.exports = {
		CrmTabsOpenNotification,
	};
});
