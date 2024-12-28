/**
 * @module im/messenger/provider/service/classes/analytics/helper
 */
jn.define('im/messenger/provider/service/classes/analytics/helper', (require, exports, module) => {
	const { Analytics, DialogType } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

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
