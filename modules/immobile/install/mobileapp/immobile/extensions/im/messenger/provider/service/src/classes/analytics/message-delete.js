/**
 * @module im/messenger/provider/service/classes/analytics/message-delete
 */
jn.define('im/messenger/provider/service/classes/analytics/message-delete', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Type } = require('type');

	const { Analytics, OpenDialogContextType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessageHelper } = require('im/messenger/lib/helper');

	const { AnalyticsHelper } = require('im/messenger/provider/service/classes/analytics/helper');

	class MessageDelete
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		sendMessageDeleteActionClicked({ messageId, dialogId })
		{
			const messageHelper = MessageHelper.createById(messageId);
			if (Type.isNull(messageHelper))
			{
				return;
			}

			const chatData = this.store.getters['dialoguesModel/getById'](dialogId);

			const analyticsEvent = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.message)
				.setEvent(Analytics.Event.clickDelete)
				.setType(messageHelper.getComponentId())
				.setSubSection(Analytics.SubSection.contextMenu)
				.setP1(AnalyticsHelper.getP1ByChatType(chatData?.type))
				.setP5(AnalyticsHelper.getFormattedChatId(chatData?.chatId))
			;

			analyticsEvent.send();
		}

		sendMessageDeletingCanceled({ messageId, dialogId })
		{
			const messageHelper = MessageHelper.createById(messageId);
			if (Type.isNull(messageHelper))
			{
				return;
			}

			const chatData = this.store.getters['dialoguesModel/getById'](dialogId);

			const analyticsEvent = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.message)
				.setEvent(Analytics.Event.cancelDelete)
				.setType(messageHelper.getComponentId())
				.setSection(Analytics.Section.popup)
				.setSubSection(Analytics.SubSection.contextMenu)
				.setP1(AnalyticsHelper.getP1ByChatType(chatData?.type))
				.setP5(AnalyticsHelper.getFormattedChatId(chatData?.chatId))
			;

			analyticsEvent.send();
		}

		sendToastShownMessageNotFound({ dialogId, context })
		{
			const chatData = this.store.getters['dialoguesModel/getById'](dialogId);

			const analyticsEvent = new AnalyticsEvent();

			analyticsEvent.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.chatPopup)
				.setEvent(Analytics.Event.view)
				.setType('deleted_message')
				.setP1(AnalyticsHelper.getP1ByChatType(chatData?.type))
				.setP5(AnalyticsHelper.getFormattedChatId(chatData?.chatId))
			;

			switch (context)
			{
				case OpenDialogContextType.link: {
					analyticsEvent.setSection(Analytics.Section.link);
					break;
				}
				case OpenDialogContextType.mention:
				case OpenDialogContextType.forward: {
					analyticsEvent.setSection(Analytics.Section.mention);
					break;
				}

				default: { /* empty */ }
			}

			analyticsEvent.send();
		}

		sendToastShownChannelPublicationNotFound({ chatId, parentChatId })
		{
			const analyticsEvent = new AnalyticsEvent();

			const parentChatData = this.store.getters['dialoguesModel/getByChatId'](parentChatId);

			analyticsEvent.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.chatPopup)
				.setEvent(Analytics.Event.view)
				.setType('deleted_message')
				.setSection(Analytics.Section.comments)
				.setP1(AnalyticsHelper.getP1ByChatType(parentChatData.type))
				.setP4(AnalyticsHelper.getFormattedParentChatId(parentChatId))
				.setP5(AnalyticsHelper.getFormattedChatId(chatId))
			;

			analyticsEvent.send();
		}
	}

	module.exports = { MessageDelete };
});
