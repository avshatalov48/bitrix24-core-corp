/**
 * @module im/messenger/lib/messages-auto-delete
 */
jn.define('im/messenger/lib/messages-auto-delete', (require, exports, module) => {
	const { Type } = require('type');

	const { MessagesAutoDeleteDelay } = require('im/messenger/const');
	const { MessagesAutoDeleteContextMenu } = require('im/messenger/lib/ui/context-menu/messages-auto-delete');

	const { Feature } = require('im/messenger/lib/feature');
	const { getLogger } = require('im/messenger/lib/logger');
	const { ChatService } = require('im/messenger/provider/services/chat');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');
	const { ChatPermission, UserPermission } = require('im/messenger/lib/permission-manager');

	const logger = getLogger('dialog--action-button-manager');

	/**
	 * @class AutoDeleteMessages
	 */
	class AutoDeleteMessages
	{
		/**
		 * @param {DialogId} dialogId
		 * @param {{ toastParams?: ShowToastParams }} options
		 */
		constructor(dialogId, options = {})
		{
			this.dialogId = dialogId;
			this.store = serviceLocator.get('core').getStore();
			this.chatService = new ChatService();
			this.toastParams = options.toastParams ?? {};
		}

		/**
		 * @return {?DialoguesModelState}
		 */
		getDialog()
		{
			return this.store.getters['dialoguesModel/getById'](this.dialogId);
		}

		/**
		 * @param {number} delay
		 */
		updateMessagesAutoDeleteDelay({ delay })
		{
			if (Type.isNil(delay))
			{
				return;
			}
			const dialogModel = this.getDialog();
			if (!dialogModel)
			{
				return;
			}

			const { messagesAutoDeleteDelay: oldDelay } = dialogModel;

			this.#updateStore({ delay });
			this.chatService.updateMessagesAutoDeleteDelay({ delay, dialogId: this.dialogId })
				.then(() => {
					this.#showNotification({ newDelay: delay, oldDelay });
				})
				.catch((error) => {
					this.#updateStore({ delay: oldDelay });
					logger.error(`${this.constructor.name}.updateMessagesAutoDeleteDelay`, error);
				})
			;
		}

		/**
		 * @param {MessagesAutoDeleteContextMenu} params
		 * @returns {MessagesAutoDeleteContextMenu}
		 */
		createMessagesAutoDeleteContextMenu(params)
		{
			const dialogModel = this.getDialog();
			if (!dialogModel)
			{
				return false;
			}

			const { messagesAutoDeleteDelay, chatId } = dialogModel;
			const currentUserId = serviceLocator.get('core').getUserId();
			const hasPermission = ChatPermission.canChangeMessagesAutoDeleteDelay(this.dialogId)
				&& UserPermission.canChangeMessagesAutoDeleteDelay(currentUserId);

			return MessagesAutoDeleteContextMenu
				.createByFileId({
					chatId,
					selectedItem: messagesAutoDeleteDelay,
					disabled: !hasPermission,
					...params,
				});
		}

		openMessagesAutoDeleteMenu({ targetRef })
		{
			const dialogModel = this.getDialog();
			if (!Feature.isMessagesAutoDeleteEnabled && dialogModel?.messagesAutoDeleteDelay === MessagesAutoDeleteDelay.off)
			{
				Notification.showToast(ToastType.messagesAutoDeleteDisabled, null, this.toastParams);

				return;
			}

			const params = {
				ref: targetRef,
				onItemSelected: (delay) => {
					this.updateMessagesAutoDeleteDelay({ delay });
				},
			};
			this.createMessagesAutoDeleteContextMenu(params).open();
		}

		/**
		 * @param {Number} newDelay
		 * @param {Number} oldDelay
		 */
		#showNotification({ newDelay, oldDelay })
		{
			if (oldDelay === newDelay)
			{
				return;
			}

			if (!Feature.isMessagesAutoDeleteEnabled && newDelay !== MessagesAutoDeleteDelay.off)
			{
				Notification.showToast(ToastType.messagesAutoDeleteDisabled, null, this.toastParams);

				return;
			}

			if (newDelay === MessagesAutoDeleteDelay.off)
			{
				Notification.showToast(ToastType.autoDeleteNotActive, null, this.toastParams);

				return;
			}

			if (oldDelay === MessagesAutoDeleteDelay.off)
			{
				Notification.showToast(ToastType.autoDeleteActive, null, this.toastParams);
			}
		}

		/**
		 * @param {Number} delay
		 */
		#updateStore({ delay })
		{
			if (!Feature.isMessagesAutoDeleteEnabled)
			{
				this.store.dispatch('dialoguesModel/update', {
					dialogId: this.dialogId,
					fields: { messagesAutoDeleteDelay: MessagesAutoDeleteDelay.off },
				});

				return;
			}

			this.store.dispatch('dialoguesModel/update', {
				dialogId: this.dialogId,
				fields: {
					messagesAutoDeleteDelay: delay,
				},
			});
		}
	}

	module.exports = {
		AutoDeleteMessages,
	};
});
