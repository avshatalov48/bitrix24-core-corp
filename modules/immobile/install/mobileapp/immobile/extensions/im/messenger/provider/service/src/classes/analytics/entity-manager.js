/**
 * @module im/messenger/provider/service/classes/analytics/entity-manager
 */
jn.define('im/messenger/provider/service/classes/analytics/entity-manager', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	const { Analytics } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { AnalyticsHelper } = require('im/messenger/provider/service/classes/analytics/helper');

	/**
	 * @class EntityManager
	 */
	class EntityManager
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendClickToOpenCreateTask(dialogId)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);

			const analytics = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(dialog.type))
				.setEvent(Analytics.Event.clickCreateTask)
				.setSection(Analytics.Section.chatTextarea)
				.setP1(AnalyticsHelper.getP1ByChatType())
				.setP2(AnalyticsHelper.getP2ByUserType())
				.setP5(AnalyticsHelper.getFormattedChatId(dialog.chatId));

			const isCollab = DialogHelper.createByDialogId(dialogId)?.isCollab;
			if (isCollab)
			{
				analytics.setP4(AnalyticsHelper.getFormattedCollabIdByDialogId(dialog.dialogId));
			}

			analytics.send();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendClickToOpenCreateMeeting(dialogId)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);

			const analytics = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(dialog.type))
				.setEvent(Analytics.Event.clickCreateEvent)
				.setSection(Analytics.Section.chatTextarea)
				.setP1(AnalyticsHelper.getP1ByChatType())
				.setP2(AnalyticsHelper.getP2ByUserType())
				.setP5(AnalyticsHelper.getFormattedChatId(dialog.chatId));

			const isCollab = DialogHelper.createByDialogId(dialogId)?.isCollab;
			if (isCollab)
			{
				analytics.setP4(AnalyticsHelper.getFormattedCollabIdByDialogId(dialog.dialogId));
			}

			analytics.send();
		}
	}

	module.exports = { EntityManager };
});
