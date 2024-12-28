/**
 * @module im/messenger/lib/helper/dialog
 */
jn.define('im/messenger/lib/helper/dialog', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogType, UserRole } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');

	const logger = LoggerManager.getInstance().getLogger('helpers--dialog');

	/**
	 * @class DialogHelper
	 */
	class DialogHelper
	{
		/** @type {DialoguesModelState} */
		dialogModel = null;
		/**
		 * @param chatId
		 * @return {boolean}
		 */
		static isChatId(chatId)
		{
			return Type.isNumber(Number(chatId));
		}

		/**
		 * @param dialogId
		 * @return {boolean}
		 */
		static isDialogId(dialogId)
		{
			return (
				dialogId.toString().startsWith('chat')
				&& Type.isNumber(Number(dialogId.slice(4)))
			);
		}

		/**
		 * @param {DialoguesModelState} dialogModel
		 * @return {DialogHelper|null}
		 */
		static createByModel(dialogModel)
		{
			if (!Type.isPlainObject(dialogModel))
			{
				logger.error('DialogHelper.getByModel error: dialogModel is not an object', dialogModel);

				return null;
			}

			return new DialogHelper(dialogModel);
		}

		/**
		 * @param {DialogId} dialogId
		 * @return {DialogHelper|null}
		 */
		static createByDialogId(dialogId)
		{
			if (!Type.isNumber(dialogId) && !Type.isStringFilled(dialogId))
			{
				logger.error('DialogHelper.getByDialogId error: dialogId is not a number or string filled', dialogId);

				return null;
			}

			const dialogModel = serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](dialogId);
			if (!dialogModel)
			{
				logger.warn('DialogHelper.getByDialogId: dialog not found', dialogId);

				return null;
			}

			return DialogHelper.createByModel(dialogModel);
		}

		/**
		 * @param {number} chatId
		 * @return {DialogHelper|null}
		 */
		static createByChatId(chatId)
		{
			if (!Type.isNumber(chatId))
			{
				logger.error('DialogHelper.getByChatId error: dialogId is not a number', chatId);

				return null;
			}

			const dialogModel = serviceLocator.get('core').getStore().getters['dialoguesModel/getByChatId'](chatId);
			if (!dialogModel)
			{
				logger.warn('DialogHelper.getByChatId: dialog not found', chatId);

				return null;
			}

			return DialogHelper.createByModel(dialogModel);
		}

		/**
		 * @param {DialoguesModelState} dialogModel
		 */
		constructor(dialogModel)
		{
			this.dialogModel = dialogModel;
		}

		get isDirect()
		{
			return this.constructor.isChatId(this.dialogModel.dialogId)
				&& [DialogType.user, DialogType.private].includes(this.dialogModel.type)
			;
		}

		get isChannel()
		{
			return [DialogType.generalChannel, DialogType.openChannel, DialogType.channel].includes(this.dialogModel.type);
		}

		get isCollab()
		{
			return this.dialogModel.type === DialogType.collab;
		}

		get isOpenChannel()
		{
			return this.dialogModel.type === DialogType.openChannel;
		}

		get isComment()
		{
			return this.dialogModel.type === DialogType.comment;
		}

		get isChannelOrComment()
		{
			return this.isComment || this.isChannel;
		}

		get isCopilot()
		{
			return this.dialogModel.type === DialogType.copilot;
		}

		get isOpenChat()
		{
			return this.dialogModel.type === DialogType.open;
		}

		get isGeneral()
		{
			return this.dialogModel.type === DialogType.general;
		}

		get isGeneralChannel()
		{
			return this.dialogModel.type === DialogType.generalChannel;
		}

		get isCurrentUserOwner()
		{
			return Number(this.dialogModel.owner) === serviceLocator.get('core').getUserId();
		}

		get isCurrentUserGuest()
		{
			return this.dialogModel.role === UserRole.guest;
		}

		get isCurrentUserParticipant()
		{
			return !([UserRole.none, UserRole.guest].includes(this.dialogModel.role));
		}

		get isLocalStorageSupported()
		{
			if (this.isComment)
			{
				return false;
			}

			if (this.isOpenChannel)
			{
				if (!this.dialogModel.role || this.dialogModel.role === UserRole.none)
				{
					return false;
				}

				return !this.isCurrentUserGuest;
			}

			return true;
		}

		get canBeDeleted()
		{
			return ChatPermission.isCanDeleteChat(this.dialogModel);
		}

		/**
		 * @return {boolean}
		 */
		get isHistoryLimitExceeded()
		{
			return !this.isChannelOrComment
				&& !MessengerParams.isFullChatHistoryAvailable()
				&& this.dialogModel?.tariffRestrictions?.isHistoryLimitExceeded;
		}
	}

	module.exports = {
		DialogHelper,
	};
});
