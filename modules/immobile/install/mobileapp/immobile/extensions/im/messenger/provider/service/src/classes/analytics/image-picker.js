/**
 * @module im/messenger/provider/service/classes/analytics/image-picker
 */
jn.define('im/messenger/provider/service/classes/analytics/image-picker', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	const { Analytics } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { AnalyticsHelper } = require('im/messenger/provider/service/classes/analytics/helper');

	/**
	 * @class ImagePicker
	 */
	class ImagePicker
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendShowImagePicker(dialogId)
		{
			const dialog = serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](dialogId);

			const analytics = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(dialog.type))
				.setEvent(Analytics.Event.clickAttach)
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

	module.exports = { ImagePicker };
});
