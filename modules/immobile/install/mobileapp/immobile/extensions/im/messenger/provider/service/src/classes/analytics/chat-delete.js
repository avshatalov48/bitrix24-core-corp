/**
 * @module im/messenger/provider/service/classes/analytics/chat-delete
 */
jn.define('im/messenger/provider/service/classes/analytics/chat-delete', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	const { Analytics, ComponentCode } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerParams } = require('im/messenger/lib/params');

	const { AnalyticsHelper } = require('im/messenger/provider/service/classes/analytics/helper');

	/**
	 * @class ChatDelete
	 */
	class ChatDelete
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		sendChatDeletePopupShown({ dialogId })
		{
			const chatData = this.store.getters['dialoguesModel/getById'](dialogId);

			const analyticsEvent = new AnalyticsEvent();

			analyticsEvent
				.setTool(Analytics.Tool.im)
				.setCategory(this.#getChatCategory())
				.setEvent(Analytics.Event.clickDelete)
				.setType(AnalyticsHelper.getTypeByChatType(chatData.type))
				.setSection(Analytics.Section.sidebar)
				.setSubSection(Analytics.SubSection.contextMenu)
				.setP1(AnalyticsHelper.getP1ByChatType(chatData.type))
				.setP5(AnalyticsHelper.getFormattedChatId(chatData.chatId))
			;

			analyticsEvent.send();
		}

		sendChatDeleteCanceled({ dialogId })
		{
			const chatData = this.store.getters['dialoguesModel/getById'](dialogId);

			const analyticsEvent = new AnalyticsEvent();

			analyticsEvent
				.setTool(Analytics.Tool.im)
				.setCategory(this.#getChatCategory())
				.setEvent(Analytics.Event.cancelDelete)
				.setType(AnalyticsHelper.getTypeByChatType(chatData.type))
				.setSection(Analytics.Section.popup)
				.setP1(AnalyticsHelper.getP1ByChatType(chatData.type))
				.setP5(AnalyticsHelper.getFormattedChatId(chatData.chatId))
			;

			analyticsEvent.send();
		}

		sendChatDeleteConfirmed({ dialogId })
		{
			const chatData = this.store.getters['dialoguesModel/getById'](dialogId);

			const analyticsEvent = new AnalyticsEvent();

			analyticsEvent
				.setTool(Analytics.Tool.im)
				.setCategory(this.#getChatCategory())
				.setEvent(Analytics.Event.delete)
				.setType(AnalyticsHelper.getTypeByChatType(chatData.type))
				.setSection(Analytics.Section.popup)
				.setP1(AnalyticsHelper.getP1ByChatType(chatData.type))
				.setP5(AnalyticsHelper.getFormattedChatId(chatData.chatId))
			;

			analyticsEvent.send();
		}

		sendToastShownChatDelete({ chatId, chatType, isChatOpened = false })
		{
			const analyticsEvent = new AnalyticsEvent();

			analyticsEvent
				.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.chatPopup)
				.setEvent(Analytics.Event.view)
				.setType(this.#getDeletingChatCategory())
				.setP1(AnalyticsHelper.getP1ByChatType(chatType))
				.setP5(AnalyticsHelper.getFormattedChatId(chatId))
			;

			if (isChatOpened)
			{
				analyticsEvent.setSection(Analytics.Section.activeChat);
			}

			analyticsEvent.send();
		}

		#getChatCategory()
		{
			switch (MessengerParams.getComponentCode())
			{
				case ComponentCode.imMessenger: return Analytics.Category.chat;
				case ComponentCode.imCopilotMessenger: return Analytics.Category.copilot;
				case ComponentCode.imChannelMessenger: return Analytics.Category.channel;
				default: return Analytics.Category.chat;
			}
		}

		#getDeletingChatCategory()
		{
			switch (MessengerParams.getComponentCode())
			{
				case ComponentCode.imMessenger: return 'deleted_chat';
				case ComponentCode.imCopilotMessenger: return 'deleted_copilot';
				case ComponentCode.imChannelMessenger: return 'deleted_channel';
				default: return 'deleted_chat';
			}
		}
	}

	module.exports = { ChatDelete };
});
