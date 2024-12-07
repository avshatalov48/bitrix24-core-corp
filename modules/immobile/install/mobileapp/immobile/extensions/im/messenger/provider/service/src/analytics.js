/**
 * @module im/messenger/provider/service/analytics
 */
jn.define('im/messenger/provider/service/analytics', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	const {
		Analytics,
		ComponentCode,
		DialogType,
		UserRole,
		OpenDialogContextType,
	} = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');

	const { MessageDelete } = require('im/messenger/provider/service/classes/analytics/message-delete');
	const { ChatDelete } = require('im/messenger/provider/service/classes/analytics/chat-delete');
	const { ChatDataProvider } = require('im/messenger/provider/data');

	/** @type {AnalyticsService} */
	let instance = null;

	/**
	 * @class AnalyticsService
	 */
	class AnalyticsService
	{
		#messageDelete = new MessageDelete();
		#chatDelete = new ChatDelete();

		static getInstance()
		{
			instance ??= new this();

			return instance;
		}

		sendMessageDeleteActionClicked({ messageId, dialogId })
		{
			return this.#messageDelete.sendMessageDeleteActionClicked({ messageId, dialogId });
		}

		sendMessageDeletingCanceled({ messageId, dialogId })
		{
			return this.#messageDelete.sendMessageDeletingCanceled({ messageId, dialogId });
		}

		sendToastShownMessageNotFound({ dialogId, context })
		{
			return this.#messageDelete.sendToastShownMessageNotFound({ dialogId, context });
		}

		sendToastShownChannelPublicationNotFound({ chatId, parentChatId })
		{
			return this.#messageDelete.sendToastShownChannelPublicationNotFound({ chatId, parentChatId });
		}

		sendChatDeletePopupShown({ dialogId })
		{
			return this.#chatDelete.sendChatDeletePopupShown({ dialogId });
		}

		sendChatDeleteCanceled({ dialogId })
		{
			return this.#chatDelete.sendChatDeleteCanceled({ dialogId });
		}

		sendChatDeleteConfirmed({ dialogId })
		{
			return this.#chatDelete.sendChatDeleteConfirmed({ dialogId });
		}

		sendToastShownChatDelete({ chatId, chatType, isChatOpened = false })
		{
			return this.#chatDelete.sendToastShownChatDelete({
				chatId,
				chatType,
				isChatOpened,
			});
		}

		async sendChatOpened({ dialogId, context })
		{
			const chatProvider = new ChatDataProvider();

			const chatDataResult = await chatProvider.get({ dialogId });

			if (!chatDataResult.hasData())
			{
				return;
			}
			const chatData = chatDataResult.getData();

			const chatHelper = DialogHelper.createByModel(chatData);

			const category = chatHelper.isChannel
				? Analytics.Category.channel
				: (Analytics.Category[chatData.type] || Analytics.Category.chat)
			;

			const type = Analytics.Type[chatData?.type] ?? Analytics.Type.custom;

			const p3 = (chatData.role === UserRole.guest || chatData.role === UserRole.none)
				? Analytics.P3.isMemberN
				: Analytics.P3.isMemberY
			;

			let section = Analytics.Section.chatTab;
			switch (MessengerParams.getComponentCode())
			{
				case ComponentCode.imChannelMessenger: section = Analytics.Section.channelTab;
					break;
				case ComponentCode.imCopilotMessenger: section = Analytics.Section.copilotTab;
					break;
				default: section = Analytics.Section.chatTab;
			}

			const element = context === OpenDialogContextType.push
				? Analytics.Element.push
				: null
			;

			const analytics = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(category)
				.setEvent(Analytics.Event.openExisting)
				.setType(type)
				.setSection(section)
				.setElement(element)
				.setP3(p3)
				.setP5(`chatId_${chatData?.chatId}`);

			if (chatHelper.isComment)
			{
				const parentChatDataResult = await chatProvider.get({ chatId: chatData.parentChatId });
				const parentChatData = parentChatDataResult.getData();

				const p1 = parentChatData?.type === DialogType.channel
					? Analytics.P1.closedChannel : Analytics.P1[parentChatData?.type];

				analytics.setType(Analytics.Type.comment);
				analytics.setCategory(Analytics.Category.channel);
				analytics.setP1(p1);
				analytics.setP4(`parentChatId_${chatData?.parentChatId}`);
			}

			analytics.send();
		}
	}

	module.exports = { AnalyticsService };
});
