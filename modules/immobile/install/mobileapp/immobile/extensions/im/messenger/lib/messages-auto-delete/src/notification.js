/**
 * @module im/messenger/lib/message-auto-delete/src/notification
 */
jn.define('im/messenger/lib/message-auto-delete/src/notification', (require, exports, module) => {
	const { isNil } = require('utils/type');
	const { debounce } = require('utils/function');
	const { MessagesAutoDeleteDelay } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');

	/**
	 * @class MessagesAutoDeleteNotification
	 */
	class MessagesAutoDeleteNotification
	{
		constructor({ dialogId })
		{
			this.core = serviceLocator.get('core');
			this.currentMessagesAutoDeleteDelay = this.getDialogModel(dialogId)?.messagesAutoDeleteDelay
				|| MessagesAutoDeleteDelay.off;
			this.storeManager = this.core.getStoreManager();
			this.showToast = debounce(this.showToast, 500, this);
		}

		subscribe()
		{
			this.storeManager.on('dialoguesModel/update', this.showToast);
		}

		unsubscribe()
		{
			this.storeManager.off('dialoguesModel/update', this.showToast);
		}

		showToast(event)
		{
			const { messagesAutoDeleteDelay } = event?.payload?.data?.fields || {};
			if (
				isNil(messagesAutoDeleteDelay)
				|| isNil(this.currentMessagesAutoDeleteDelay)
				|| this.currentMessagesAutoDeleteDelay === messagesAutoDeleteDelay
			)
			{
				return;
			}

			if (messagesAutoDeleteDelay === MessagesAutoDeleteDelay.off)
			{
				MessagesAutoDeleteNotification.showDisabledToast();
			}
			else if (
				this.currentMessagesAutoDeleteDelay === MessagesAutoDeleteDelay.off
				&& messagesAutoDeleteDelay !== MessagesAutoDeleteDelay.off
			)
			{
				MessagesAutoDeleteNotification.showEnabledToast();
			}

			this.currentMessagesAutoDeleteDelay = messagesAutoDeleteDelay;
		}

		getDialogModel(dialogId)
		{
			return this.core.getStore().getters['dialoguesModel/getById'](dialogId);
		}

		static showEnabledToast()
		{
			Notification.showToast(ToastType.autoDeleteEnabled);
		}

		static showDisabledToast()
		{
			Notification.showToast(ToastType.autoDeleteDisabled);
		}
	}

	module.exports = {
		MessagesAutoDeleteNotification,
	};
});
