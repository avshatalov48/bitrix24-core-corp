/**
 * @module im/messenger/provider/service/classes/analytics/chat-delete
 */
jn.define('im/messenger/provider/service/classes/analytics/chat-delete', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Type } = require('type');

	const { Analytics, ComponentCode } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerParams } = require('im/messenger/lib/params');

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
				.setType(this.#getChatType(chatData))
				.setSection(Analytics.Section.sidebar)
				.setSubSection(Analytics.SubSection.contextMenu)
				.setP1(this.#getChatP1(chatData))
				.setP5(this.#getChatP5(chatData))
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
				.setType(this.#getChatType(chatData))
				.setSection(Analytics.Section.popup)
				.setP1(this.#getChatP1(chatData))
				.setP5(this.#getChatP5(chatData))
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
				.setType(this.#getChatType(chatData))
				.setSection(Analytics.Section.popup)
				.setP1(this.#getChatP1(chatData))
				.setP5(this.#getChatP5(chatData))
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
				.setP1(this.#getChatP1({ type: chatType })) // hack: chat is already deleted
				.setP5(this.#getChatP5({ chatId }))
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

		#getChatType(chatData)
		{
			return Analytics.Type[chatData.type] ?? Analytics.Type.custom;
		}

		#getChatP1(chatData)
		{
			return `chatType_${chatData.type}`;
		}

		#getChatP5(chatData)
		{
			return `chatId_${chatData.chatId}`;
		}
	}

	module.exports = { ChatDelete };
});
