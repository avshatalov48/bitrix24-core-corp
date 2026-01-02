/**
 * @module im/messenger/provider/services/component-code/service
 */
jn.define('im/messenger/provider/services/component-code/service', (require, exports, module) => {
	const { NotifyManager } = require('notify-manager');

	const {
		ComponentCode,
		DialogType,
		UserRole,
		ErrorType,
	} = require('im/messenger/const');
	const { ChatService } = require('im/messenger/provider/services/chat');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Logger } = require('im/messenger/lib/logger');
	const { Feature } = require('im/messenger/lib/feature');
	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');

	/**
	 * @class ComponentCodeService
	 */
	class ComponentCodeService
	{
		#chatService;
		#core;
		#store;

		constructor()
		{
			this.#chatService = new ChatService();
			this.#core = serviceLocator.get('core');
			this.#store = serviceLocator.get('core').getStore();
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {ComponentCode} [fallbackCode=ComponentCode.imMessenger]
		 */
		async getCodeByDialogId(dialogId, fallbackCode = ComponentCode.imMessenger)
		{
			let dialog = this.#store.getters['dialoguesModel/getById'](dialogId);
			if (!dialog)
			{
				dialog = await this.#core.getRepository().dialog.getByDialogId(dialogId);
			}
			Logger.log(`${this.constructor.name}:getCodeByDialogId: local dialog by dialogId ${dialogId}`, dialog);

			// message from unknown dialog
			if (!dialog || this.#isInvalidDialog(dialog))
			{
				try
				{
					void NotifyManager.showLoadingIndicator();

					dialog = await this.#chatService.getByDialogId(dialogId);
					Logger.log(`${this.constructor.name}:getCodeByDialogId: server dialog by dialogId ${dialogId}`, dialog);
				}
				catch (error)
				{
					Logger.error(`${this.constructor.name}.getComponentCodeByDialogId: error`, error);

					throw error;
				}
				finally
				{
					NotifyManager.hideLoadingIndicatorWithoutFallback();
				}
			}

			return this.getCodeByDialog(dialog, fallbackCode);
		}

		getCodeByDialog(dialogModel, fallbackCode = ComponentCode.imMessenger)
		{
			const dialogHelper = DialogHelper.createByModel(dialogModel);
			if (!dialogHelper)
			{
				return fallbackCode;
			}

			if (dialogHelper.isOpenChannel && dialogHelper.isCurrentUserGuest)
			{
				return ComponentCode.imChannelMessenger;
			}

			return ComponentCode.imMessenger;
		}

		/**
		 * @param {DialoguesModelState} dialog
		 */
		#isInvalidDialog(dialog)
		{
			if (dialog.type !== DialogType.openChannel)
			{
				return false;
			}

			if (!dialog.role || dialog.role === UserRole.none)
			{
				return true;
			}

			return false;
		}

		/**
		 * @param {string} errorCode
		 */
		static showToastByErrorCode(errorCode)
		{
			if (errorCode === ErrorType.dialog.accessDenied)
			{
				Notification.showToast(ToastType.chatAccessDenied);
			}

			if (errorCode === ErrorType.dialog.chatNotFound)
			{
				Notification.showToast(ToastType.chatAccessDenied);
			}

			if (errorCode === ErrorType.networkError)
			{
				Notification.showOfflineToast();
			}
		}
	}

	module.exports = { ComponentCodeService };
});
