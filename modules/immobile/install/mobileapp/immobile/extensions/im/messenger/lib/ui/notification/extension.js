/**
 * @module im/messenger/lib/ui/notification
 */
jn.define('im/messenger/lib/ui/notification', (require, exports, module) => {
	/* global InAppNotifier, include  */
	include('InAppNotifier');

	const { Loc } = require('im/messenger/loc');
	const { transparent } = require('utils/color');

	const { Theme } = require('im/lib/theme');
	const { MessengerToast, ToastType } = require('im/messenger/lib/ui/notification/messenger-toast');
	const { MessengerNotifier } = require('im/messenger/lib/ui/notification/messenger-notifier');

	/**
	 * @class Notification
	 */
	class Notification
	{
		static showComingSoon()
		{
			InAppNotifier.showNotification({
				title: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_COMING_SOON'),
				time: 1,
				backgroundColor: transparent(Theme.colors.baseBlackFixed, 0.8),
			});
		}

		/**
		 * @param {ShowNotifierParams} params
		 */
		static showNotifier(params)
		{
			MessengerNotifier.show(params);
		}

		/**
		 * @param {ToastType} toastType
		 * @param layoutWidget
		 * @param {ShowToastParams} params
		 */
		static showToast(toastType, layoutWidget = null, params = {})
		{
			MessengerToast.show(toastType, layoutWidget, params);
		}

		/**
		 * @param {ShowToastParams} params
		 * @param layoutWidget
		 */
		static showToastWithParams(params, layoutWidget = null)
		{
			MessengerToast.showWithParams(params, layoutWidget);
		}

		/**
		 * @param {ShowToastParams} params
		 * @param layoutWidget
		 */
		static showOfflineToast(params = {}, layoutWidget = null)
		{
			MessengerToast.showOfflineToast(params, layoutWidget);
		}

		/**
		 * @param {?ShowToastParams} params
		 * @param layoutWidget
		 */
		static showErrorToast(params = {}, layoutWidget = null)
		{
			MessengerToast.showErrorToast(params, layoutWidget);
		}
	}

	module.exports = { Notification, ToastType };
});
