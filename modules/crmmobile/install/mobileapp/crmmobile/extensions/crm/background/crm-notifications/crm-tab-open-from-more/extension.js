/**
 * @module crm/background/crm-notifications/crm-tab-open-from-more
 */
jn.define('crm/background/crm-notifications/crm-tab-open-from-more', (require, exports, module) => {
	const { BaseNotificationHandler } = require('background/notifications/base');
	const { AnalyticsEvent } = require('analytics');
	const { NOTIFICATION_EVENTS, SUBSCRIPTION_EVENTS } = require('navigator/more-tab/meta');

	/**
	 * @class CrmTabsOpenFromMoreNotification
	 */
	class CrmTabsOpenFromMoreNotification extends BaseNotificationHandler
	{
		getNotificationType()
		{
			return 'MOBILE_OPEN_CRM_TAB_FROM_MORE';
		}

		getSubscriptionEventName()
		{
			return SUBSCRIPTION_EVENTS.CRM;
		}

		getNotificationEventName()
		{
			return NOTIFICATION_EVENTS.CRM;
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
		CrmTabsOpenFromMoreNotification,
	};
});
