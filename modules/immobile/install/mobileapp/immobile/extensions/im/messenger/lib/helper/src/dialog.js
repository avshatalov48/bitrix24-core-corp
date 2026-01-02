/**
 * @module im/messenger/lib/helper/dialog
 */
jn.define('im/messenger/lib/helper/dialog', (require, exports, module) => {
	const { Type } = require('type');
	const { isNil } = require('utils/type');
	const { Feature } = require('im/messenger/lib/feature');
	const { DialogType, UserRole, UrlGetParameter } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getLogger } = require('im/messenger/lib/logger');
	const { MessagesAutoDeleteDelay, BotCode } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');

	const logger = getLogger('helpers--dialog');

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
			if (!Type.isStringFilled(dialogId) && !Type.isNumber(dialogId))
			{
				return false;
			}

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
				logger.warn('DialogHelper.getByDialogId error: dialogId is not a number or string filled', dialogId);

				return null;
			}

			const dialogModel = DialogHelper.getDialogModel(dialogId);
			if (!dialogModel)
			{
				logger.warn('DialogHelper.getByDialogId: dialog not found', dialogId);

				return null;
			}

			return DialogHelper.createByModel(dialogModel);
		}

		/**
		 * @param dialogId
		 * @returns {?DialoguesModelState}
		 */
		static getDialogModel(dialogId)
		{
			return serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](dialogId);
		}

		/**
		 * @param {number} chatId
		 * @return {DialogHelper|null}
		 */
		static createByChatId(chatId)
		{
			if (!Type.isNumber(chatId))
			{
				logger.warn('DialogHelper.getByChatId error: chatId is not a number', chatId);

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

		get dialogId()
		{
			return this.dialogModel.dialogId;
		}

		get chatId()
		{
			return this.dialogModel.chatId;
		}

		get isNotes()
		{
			return this.isDirect && Number(this.dialogId) === serviceLocator.get('core').getUserId();
		}

		get isDirect()
		{
			return this.constructor.isChatId(this.dialogModel.dialogId) && [
				DialogType.user,
				DialogType.private,
			].includes(this.dialogModel.type);
		}

		get isBot()
		{
			if (this.isDirect)
			{
				const store = serviceLocator.get('core').getStore();
				const userModelState = store.getters['usersModel/getById'](this.dialogId);

				return (userModelState?.bot === true);
			}

			return false;
		}

		/**
		 * @desc Return check is bot ai assistant
		 * @return {boolean}
		 */
		get isAiAssistant()
		{
			if (this.isDirect)
			{
				const store = serviceLocator.get('core').getStore();
				const userModelState = store.getters['usersModel/getById'](this.dialogId);

				return userModelState?.botData?.code === BotCode.aiAssistant;
			}

			return false;
		}

		get isChannel()
		{
			return [
				DialogType.generalChannel,
				DialogType.openChannel,
				DialogType.channel,
			].includes(this.dialogModel.type);
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

		get isExtranet()
		{
			return this.dialogModel.extranet === true;
		}

		get isAnnouncement()
		{
			return this.dialogModel.type === DialogType.announcement;
		}

		get isSupport24Notifier()
		{
			return this.dialogModel.type === DialogType.support24Notifier;
		}

		get isSupport24Question()
		{
			return this.dialogModel.type === DialogType.support24Question;
		}

		get isTaskComment()
		{
			return this.dialogModel.type === DialogType.tasksTask;
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
			const hasRecentConfig = this.dialogModel.recentConfig?.chatId > 0;
			const isIntoSomeRecentTab = Type.isArrayFilled(this.dialogModel.recentConfig?.sections);
			if (hasRecentConfig && !isIntoSomeRecentTab)
			{
				return false;
			}

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
			return ChatPermission.canDeleteChat(this.dialogModel);
		}

		get canClearHistory()
		{
			return false; // TODO Back not ready yet
		}

		get canCopyChatLink()
		{
			return !this.isCollab && !this.isCopilot && !this.isDirect;
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

		get isMessagesAutoDeleteDelayEnabled()
		{
			const delay = this.dialogModel.messagesAutoDeleteDelay;

			return !isNil(delay)
				&& MessagesAutoDeleteDelay.off !== delay
				&& Feature.isMessagesAutoDeleteNativeAvailable;
		}

		/**
		 * @returns {string}
		 */
		get chatLink()
		{
			const chatGetParameter = this.isCopilot
				? UrlGetParameter.openCopilotChat
				: UrlGetParameter.openChat;

			return `${serviceLocator.get('core').getHost()}/online/?${chatGetParameter}=${this.dialogId}`;
		}

		/**
		 * @return {boolean}
		 */
		get isMuted()
		{
			return this.dialogModel.muteList?.includes(MessengerParams.getUserId());
		}

		/**
		 * @return {boolean}
		 */
		get isPinned()
		{
			const store = serviceLocator.get('core').getStore();
			const recentItemState = store.getters['recentModel/getById'](this.dialogModel.dialogId);

			return recentItemState?.pinned;
		}
	}

	module.exports = {
		DialogHelper,
	};
});
