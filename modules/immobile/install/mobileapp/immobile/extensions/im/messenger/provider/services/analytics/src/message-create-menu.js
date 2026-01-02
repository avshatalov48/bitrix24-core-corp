/**
 * @module im/messenger/provider/services/analytics/message-create-menu
 */
jn.define('im/messenger/provider/services/analytics/message-create-menu', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Analytics } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const { AnalyticsHelper } = require('im/messenger/provider/services/analytics/helper');

	/**
	 * @class MessageCreateMenu
	 */
	class MessageCreateMenu
	{
		constructor()
		{
			/**
			 * @type MessengerCoreStore
			 */
			this.store = serviceLocator.get('core').getStore();
		}

		sendOpenCreateMenu(dialogId)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);

			try
			{
				const analytics = new AnalyticsEvent()
					.setTool(Analytics.Tool.im)
					.setCategory(AnalyticsHelper.getCategoryByChatType(dialog.type))
					.setEvent(Analytics.Event.clickAttach)
					.setSection(Analytics.Section.messageContextMenu)
					.setP1(AnalyticsHelper.getP1ByChatType(dialog.type))
					.setP2(AnalyticsHelper.getP2ByUserType())
					.setP5(AnalyticsHelper.getFormattedChatId(dialog.chatId));

				const isCollab = DialogHelper.createByDialogId(dialogId)?.isCollab;
				if (isCollab)
				{
					analytics.setP4(AnalyticsHelper.getFormattedCollabIdByDialogId(dialogId));
				}

				analytics.send();
			}
			catch (e)
			{
				console.error(`${this.constructor.name}.sendStartCreation.catch:`, e);
			}
		}
	}

	module.exports = { MessageCreateMenu };
});
