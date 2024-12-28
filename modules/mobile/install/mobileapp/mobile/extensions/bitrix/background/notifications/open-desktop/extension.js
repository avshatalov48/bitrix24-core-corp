/**
 * @module background/notifications/open-desktop
 */
jn.define('background/notifications/open-desktop', (require, exports, module) => {
	const { BaseNotificationHandler } = require('background/notifications/base');
	const { Loc } = require('loc');
	const { AnalyticsEvent } = require('analytics');
	const { qrauth } = require('qrauth/utils');

	const NOTIFICATION_EVENT = 'PushNotifications::OpenDesktop';
	const SUBSCRIPTION_EVENT = 'PushNotifications::SubscribeToOpenDesktop';

	/**
	 * @class OpenDesktopNotification
	 */
	class OpenDesktopNotification extends BaseNotificationHandler
	{
		getNotificationType()
		{
			return 'MOBILE_OPEN_DESKTOP';
		}

		getNotificationEventName()
		{
			return NOTIFICATION_EVENT;
		}

		getSubscriptionEventName()
		{
			return SUBSCRIPTION_EVENT;
		}

		getAnalytics()
		{
			return new AnalyticsEvent()
				.setEvent('push_mobile_1-8d_web')
				.setCategory('1-8d')
				.setTool('mobile');
		}

		static bindOpenDesktopEvent()
		{
			BX.removeCustomEvent(NOTIFICATION_EVENT, OpenDesktopNotification.openDesktop);
			BX.addCustomEvent(NOTIFICATION_EVENT, OpenDesktopNotification.openDesktop);

			BX.postComponentEvent(SUBSCRIPTION_EVENT, []);
		}

		static openDesktop()
		{
			qrauth.open({
				title: Loc.getMessage('OPEN_DESKTOP_NOTIFICATION_QRAUTH_TITLE'),
				analyticsSection: 'marketing_push',
			});
		}
	}

	module.exports = {
		OpenDesktopNotification,
	};
});
