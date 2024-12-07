/**
 * @module im/messenger/lib/ui/notification
 */
jn.define('im/messenger/lib/ui/notification', (require, exports, module) => {
	/* global InAppNotifier, include  */
	include('InAppNotifier');

	const { Loc } = require('loc');

	const { MessengerToast, ToastType } = require('im/messenger/lib/ui/notification/messenger-toast');

	/**
	 * @class Notify
	 */
	class Notification
	{
		static showComingSoon()
		{
			InAppNotifier.showNotification({
				title: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_COMING_SOON'),
				time: 1,
				backgroundColor: '#E6000000',
			});
		}

		/**
		 * @param {ToastType} toastType
		 * @param layoutWidget
		 */
		static showToast(toastType, layoutWidget = null)
		{
			MessengerToast.show(toastType, layoutWidget);
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
		static showOfflineToast(params, layoutWidget = null)
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
