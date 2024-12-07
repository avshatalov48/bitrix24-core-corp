/**
 * @module background/notifications/open-invite
 */
jn.define('background/notifications/open-invite', (require, exports, module) => {
	const { BaseNotificationHandler } = require('background/notifications/base');
	const { AnalyticsEvent } = require('analytics');
	const { NOTIFICATION_EVENTS, SUBSCRIPTION_EVENTS } = require('navigator/more-tab/meta');

	/**
	 * @class OpenInviteNotification
	 */
	class OpenInviteNotification extends BaseNotificationHandler
	{
		getNotificationType()
		{
			return 'MOBILE_OPEN_INVITE';
		}

		getNotificationEventName()
		{
			return NOTIFICATION_EVENTS.INVITE;
		}

		getSubscriptionEventName()
		{
			return SUBSCRIPTION_EVENTS.INVITE;
		}

		getAnalytics()
		{
			return new AnalyticsEvent()
				.setEvent('push_mobile_1-8d_invite')
				.setCategory('1-8d')
				.setTool('mobile');
		}
	}

	module.exports = {
		OpenInviteNotification,
	};
});
