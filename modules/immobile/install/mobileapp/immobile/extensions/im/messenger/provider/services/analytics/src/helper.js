/**
 * @module im/messenger/provider/services/analytics/helper
 */
jn.define('im/messenger/provider/services/analytics/helper', (require, exports, module) => {
	const { Analytics, DialogType, ComponentCode } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ObjectUtils } = require('im/messenger/lib/utils');

	const CUSTOM_CHAT_TYPE = 'custom';

	/**
	 * @class AnalyticsHelper
	 */
	class AnalyticsHelper
	{
		/**
		 * @param {DialogId} dialogId
		 */
		getFormattedCollabIdByDialogId(dialogId)
		{
			const collabId = serviceLocator.get('core').getStore()
				.getters['dialoguesModel/collabModel/getCollabIdByDialogId'](dialogId)
			;

			return `collabId_${collabId}`;
		}

		/**
		 * @param {number} chatId
		 * @returns {string}
		 */
		getFormattedChatId(chatId)
		{
			return `chatId_${chatId}`;
		}

		/**
		 * @param {number} parentChatId
		 * @returns {string}
		 */
		getFormattedParentChatId(parentChatId)
		{
			return `parentChatId_${parentChatId}`;
		}

		getTypeByChatType(type)
		{
			return this.#prepareChatType(type);
		}

		/**
		 * @param {role} role
		 * @returns {string}
		 */
		getCopilotRole(role)
		{
			return `role_${ObjectUtils.stringToCamelCase(role)}`;
		}

		/**
		 * @see DialogType
		 * @param {string} type
		 * @returns {string}
		 */
		getCategoryByChatType(type)
		{
			switch (type)
			{
				case DialogType.channel:
				case DialogType.openChannel:
				case DialogType.comment:
				case DialogType.generalChannel:
					return Analytics.Category.channel;
				case DialogType.copilot:
					return Analytics.Category.copilot;
				case DialogType.videoconf:
					return Analytics.Category.videoconf;
				case DialogType.collab:
					return Analytics.Category.collab;
				default:
					return Analytics.Category.chat;
			}
		}

		getSectionCode()
		{
			switch (MessengerParams.getComponentCode())
			{
				case ComponentCode.imChannelMessenger:
					return Analytics.Section.channelTab;
				case ComponentCode.imCopilotMessenger:
					return Analytics.Section.copilotTab;
				case ComponentCode.imCollabMessenger:
					return Analytics.Section.collabTab;
				default:
					return Analytics.Section.chatTab;
			}
		}

		/**
		 * @param {boolean} dialog
		 * @returns {string}
		 */
		getChatSubSectionCode(dialog)
		{
			return dialog ? Analytics.SubSection.chat : Analytics.SubSection.chatList;
		}

		getP1ByChatType(type)
		{
			return `chatType_${this.#prepareChatType(type)}`;
		}

		getP2ByUserType()
		{
			const userInfo = MessengerParams.getUserInfo();

			return Analytics.P2[userInfo.type] ?? Analytics.P2.user;
		}

		#prepareChatType(type)
		{
			if ([DialogType.private, DialogType.user].includes(type))
			{
				return DialogType.user;
			}

			const isInternalChatType = Boolean(DialogType[type]);

			if (isInternalChatType)
			{
				return type;
			}

			return CUSTOM_CHAT_TYPE;
		}
	}

	module.exports = {
		AnalyticsHelper: new AnalyticsHelper(),
		AnalyticsHelperClass: AnalyticsHelper,
	};
});
