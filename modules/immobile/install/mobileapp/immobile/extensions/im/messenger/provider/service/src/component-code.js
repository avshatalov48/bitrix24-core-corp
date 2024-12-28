/**
 * @module im/messenger/provider/service/component-code
 */
jn.define('im/messenger/provider/service/component-code', (require, exports, module) => {
	const { ComponentCode, DialogType, UserRole } = require('im/messenger/const');
	const { ChatService } = require('im/messenger/provider/service/chat');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Logger } = require('im/messenger/lib/logger');

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
					dialog = await this.#chatService.getByDialogId(dialogId);
					Logger.log(`${this.constructor.name}:getCodeByDialogId: server dialog by dialogId ${dialogId}`, dialog);
				}
				catch (error)
				{
					Logger.error(`${this.constructor.name}.getComponentCodeByDialogId: error`, error);

					return fallbackCode;
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

			if (dialogHelper.isCopilot)
			{
				return ComponentCode.imCopilotMessenger;
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
	}

	module.exports = { ComponentCodeService };
});
