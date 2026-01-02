/**
 * @module im/messenger/provider/services/analytics/chat-open
 */
jn.define('im/messenger/provider/services/analytics/chat-open', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const {
		Analytics,
		UserRole,
		ComponentCode,
		OpenDialogContextType,
		DialogType,
	} = require('im/messenger/const');

	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { AnalyticsHelper } = require('im/messenger/provider/services/analytics/helper');
	const { ChatDataProvider } = require('im/messenger/provider/data');

	/**
	 * @class ChatOpen
	 */
	class ChatOpen
	{
		constructor()
		{
			/**
			 * @type MessengerCoreStore
			 */
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {string} context
		 */
		async sendChatOpen({ dialogId, context })
		{
			try
			{
				const chatProvider = new ChatDataProvider();

				const chatDataResult = await chatProvider.get({ dialogId });
				if (!chatDataResult.hasData())
				{
					return;
				}

				const chatData = chatDataResult.getData();

				const chatHelper = DialogHelper.createByModel(chatData);

				const category = chatHelper?.isChannel
					? Analytics.Category.channel
					: (Analytics.Category[chatData.type] || Analytics.Category.chat)
				;

				const isNotes = chatHelper?.isNotes;
				const type = isNotes
					? Analytics.Type.notes
					: Analytics.Type[chatData?.type] || Analytics.Type.custom
				;

				let section = Analytics.Section.chatTab;
				switch (MessengerParams.getComponentCode())
				{
					case ComponentCode.imChannelMessenger:
					{
						section = Analytics.Section.channelTab;
						break;
					}

					case ComponentCode.imCopilotMessenger:
					{
						section = Analytics.Section.copilotTab;
						break;
					}

					default:
					{
						section = Analytics.Section.chatTab;
					}
				}

				const element = context === OpenDialogContextType.push ? Analytics.Element.push : null;

				const analytics = new AnalyticsEvent()
					.setTool(Analytics.Tool.im)
					.setCategory(category)
					.setEvent(Analytics.Event.openExisting)
					.setType(type)
					.setSection(section)
					.setElement(element)
					.setP2(AnalyticsHelper.getP2ByUserType())
				;

				if (!isNotes)
				{
					analytics.setP5(AnalyticsHelper.getFormattedChatId(chatData.chatId));
				}

				if (chatHelper.isCollab)
				{
					analytics.setP4(AnalyticsHelper.getFormattedCollabIdByDialogId(chatData.dialogId));
				}

				if (chatHelper.isComment)
				{
					const parentChatDataResult = await chatProvider.get({ chatId: chatData.parentChatId });
					const parentChatData = parentChatDataResult.getData();

					const p1 = parentChatData?.type === DialogType.channel
						? Analytics.P1.channel : Analytics.P1[parentChatData?.type];

					analytics.setType(Analytics.Type.comment);
					analytics.setCategory(Analytics.Category.channel);
					analytics.setP1(p1);
					analytics.setP4(AnalyticsHelper.getFormattedParentChatId(chatData.parentChatId));
				}

				if (chatHelper.isCopilot)
				{
					const copilotMainRole = this.store.getters['dialoguesModel/copilotModel/getMainRoleByDialogId'](dialogId);
					if (copilotMainRole)
					{
						analytics.setP4(AnalyticsHelper.getCopilotRole(copilotMainRole.code));
					}
				}
				else
				{
					const p3 = (chatData.role === UserRole.guest || chatData.role === UserRole.none)
						? Analytics.P3.isMemberN
						: Analytics.P3.isMemberY
					;

					analytics.setP3(p3);
				}

				analytics.send();
			}
			catch (e)
			{
				console.error(`${this.constructor.name}.sendChatOpened.catch:`, e);
			}
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {string} context
		 */
		sendOpenCopilotDialog({ dialogId, context })
		{
			try
			{
				const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
				if (!dialog)
				{
					return;
				}

				const userCounter = dialog.userCounter;
				const p3type = userCounter > 2 ? Analytics.CopilotChatType.multiuser : Analytics.CopilotChatType.private;

				const element = context === OpenDialogContextType.push ? Analytics.Element.push : null;

				const analytics = new AnalyticsEvent()
					.setTool(Analytics.Tool.ai)
					.setCategory(Analytics.Category.chatOperations)
					.setEvent(Analytics.Event.openChat)
					.setType(Analytics.Type.ai)
					.setSection(Analytics.Section.copilotTab)
					.setElement(element)
					.setP3(p3type)
					.setP5(`chatId_${dialog.chatId}`);

				const copilotMainRole = this.store.getters['dialoguesModel/copilotModel/getMainRoleByDialogId'](dialogId);
				if (copilotMainRole)
				{
					analytics.setP4(AnalyticsHelper.getCopilotRole(copilotMainRole.code));
				}

				analytics.send();
			}
			catch (e)
			{
				console.error(`${this.constructor.name}.sendOpenCopilotDialog.catch:`, e);
			}
		}
	}

	module.exports = { ChatOpen };
});
