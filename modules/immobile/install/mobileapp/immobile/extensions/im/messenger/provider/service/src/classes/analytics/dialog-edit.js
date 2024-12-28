/**
 * @module im/messenger/provider/service/classes/analytics/dialog-edit
 */
jn.define('im/messenger/provider/service/classes/analytics/dialog-edit', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Type } = require('type');

	const { Analytics } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper } = require('im/messenger/lib/helper');

	const { AnalyticsHelper} = require('im/messenger/provider/service/classes/analytics/helper');

	class DialogEdit
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendDialogEditHeaderMenuClick(dialogId)
		{
			if (Type.isNull(dialogId))
			{
				return;
			}

			const dialogModelState = this.store.getters['dialoguesModel/getById'](dialogId);
			const dialogHelper = DialogHelper.createByModel(dialogModelState);

			if (!dialogHelper)
			{
				return;
			}

			const analyticsEvent = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(dialogModelState.type))
				.setEvent(Analytics.Event.clickEdit)
				.setSection(Analytics.Section.sidebar)
				.setSubSection(Analytics.SubSection.contextMenu)
				.setP1(AnalyticsHelper.getP1ByChatType(dialogModelState.type))
				.setP5(AnalyticsHelper.getFormattedChatId(dialogModelState.chatId))
			;

			if (dialogHelper.isCollab)
			{
				analyticsEvent.setP4(AnalyticsHelper.getFormattedCollabIdByDialogId(dialogModelState.dialogId));
			}

			analyticsEvent.send();
		}

		/**
		 * @param {DialogId|DialoguesModelState} dialog
		 */
		sendDialogEditButtonDoneDialogInfoClick(dialog)
		{
			if (Type.isNull(dialog))
			{
				return;
			}
			let dialogModelState = dialog;
			if (Type.isStringFilled(dialog))
			{
				dialogModelState = this.store.getters['dialoguesModel/getById'](dialog);
			}

			const dialogHelper = DialogHelper.createByModel(dialogModelState);
			if (!dialogHelper)
			{
				return;
			}

			const analyticsEvent = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(dialogModelState.type))
				.setEvent(Analytics.Event.submitEdit)
				.setSection(Analytics.Section.editor)
				.setP1(AnalyticsHelper.getP1ByChatType(dialogModelState?.type))
				.setP2(AnalyticsHelper.getP2ByUserType())
				.setP5(AnalyticsHelper.getFormattedChatId(dialogModelState?.chatId))
			;

			if (dialogHelper.isCollab)
			{
				analyticsEvent.setP4(AnalyticsHelper.getFormattedCollabIdByDialogId(dialogModelState.dialogId));
			}

			analyticsEvent.send();
		}
	}

	module.exports = { DialogEdit };
});
