/**
 * @module background/notifications/promotion
 */
jn.define('background/notifications/promotion', (require, exports, module) => {
	const { BaseNotificationHandler } = require('background/notifications/base');
	const { Color } = require('tokens');
	const { AnalyticsEvent } = require('analytics');

	const NOTIFICATION_EVENT = 'PushNotifications::OpenPromotion';
	const SUBSCRIPTION_EVENT = 'PushNotifications::SubscribeToOpenPromotion';

	/**
	 * @class OpenPromotionNotification
	 */
	class OpenPromotionNotification extends BaseNotificationHandler
	{
		getNotificationType()
		{
			return 'MOBILE_PROMOTION';
		}

		getNotificationEventName()
		{
			return NOTIFICATION_EVENT;
		}

		getSubscriptionEventName()
		{
			return SUBSCRIPTION_EVENT;
		}

		static bindPromotionEvent()
		{
			BX.removeCustomEvent(NOTIFICATION_EVENT, OpenPromotionNotification.openPromotion);
			BX.addCustomEvent(NOTIFICATION_EVENT, OpenPromotionNotification.openPromotion);

			BX.postComponentEvent(SUBSCRIPTION_EVENT, []);
		}

		static getUrlFromMessage(message)
		{
			return message?.payload?.url;
		}

		static getUrlSignFromMessage(message)
		{
			return message?.payload?.url_sign;
		}

		static getPromoNameFromMessage(message)
		{
			return message?.payload?.name;
		}

		static openPromotion(message)
		{
			const url = OpenPromotionNotification.getUrlFromMessage(message);
			const urlSign = OpenPromotionNotification.getUrlSignFromMessage(message);

			if (url && urlSign)
			{
				PageManager.openPage({
					url: `/mobile/promotion/index.php?URL=${encodeURIComponent(url)}&URL_SIGN=${urlSign}`,
					backgroundColor: Color.bgSecondary.toHex(),
					backdrop: {
						showOnTop: true,
						hideNavigationBar: true,
						onlyMediumPosition: false,
						adoptHeightByKeyboard: true,
						swipeAllowed: false,
					},
				});
			}
		}

		getAnalytics(message)
		{
			const promoName = OpenPromotionNotification.getPromoNameFromMessage(message);

			if (promoName)
			{
				return new AnalyticsEvent()
					.setEvent(`push_mobile_${promoName}`)
					.setCategory('promo')
					.setTool('mobile');
			}

			return null;
		}
	}

	module.exports = {
		OpenPromotionNotification,
	};
});
