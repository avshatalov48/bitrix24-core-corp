/**
 * @module background/notifications/open-helpdesk
 */
jn.define('background/notifications/open-helpdesk', (require, exports, module) => {
	const { BaseNotificationHandler } = require('background/notifications/base');
	const { AnalyticsEvent } = require('analytics');
	const { Type } = require('type');

	const NOTIFICATION_EVENT = 'PushNotifications::OpenHelpdesk';
	const SUBSCRIPTION_EVENT = 'PushNotifications::SubscribeToOpenHelpdesk';

	/**
	 * @class OpenHelpdeskNotification
	 */
	class OpenHelpdeskNotification extends BaseNotificationHandler
	{
		static bindOpenHelpdeskEvent()
		{
			BX.removeCustomEvent(NOTIFICATION_EVENT, OpenHelpdeskNotification.openArticle);
			BX.addCustomEvent(NOTIFICATION_EVENT, OpenHelpdeskNotification.openArticle);

			BX.postComponentEvent(SUBSCRIPTION_EVENT, []);
		}

		static getValidArticleIdFromMessage(message)
		{
			const articleId = message?.payload?.articleId;
			if (Type.isStringFilled(articleId))
			{
				return articleId;
			}

			return null;
		}

		static openArticle(message)
		{
			const articleId = OpenHelpdeskNotification.getValidArticleIdFromMessage(message);
			if (articleId)
			{
				helpdesk.openHelpArticle(articleId, 'helpdesk');
			}
		}

		getNotificationType()
		{
			return 'MOBILE_OPEN_ARTICLE';
		}

		getNotificationEventName()
		{
			return NOTIFICATION_EVENT;
		}

		getSubscriptionEventName()
		{
			return SUBSCRIPTION_EVENT;
		}

		getAnalytics(message)
		{
			const articleId = OpenHelpdeskNotification.getValidArticleIdFromMessage(message);
			if (articleId)
			{
				return new AnalyticsEvent()
					.setEvent(`push_mobile_helpdesk_${articleId}`)
					.setCategory('push')
					.setTool('mobile');
			}

			return null;
		}
	}

	module.exports = {
		OpenHelpdeskNotification,
	};
});
