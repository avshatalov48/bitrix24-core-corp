/**
 * @module im/messenger/provider/service/classes/analytics/download-file
 */
jn.define('im/messenger/provider/service/classes/analytics/download-file', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	const { Analytics, DialogType } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class DownloadFile
	 */
	class DownloadFile
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
			this.userId = serviceLocator.get('core').getUserId();
		}

		/**
		 * @param {Object} params
		 * @param {FileType} params.fileType
		 * @param {DialogId} params.dialogId
		 * @return {AnalyticsEvent}
		 */
		sendDownloadToDisk(params)
		{
			this.#sendDownload(params).setEvent(Analytics.Event.saveToDisk).send();
		}

		/**
		 * @param {Object} params
		 * @param {FileType} params.fileType
		 * @param {DialogId} params.dialogId
		 * @return {AnalyticsEvent}
		 */
		sendDownloadToDevice(params)
		{
			this.#sendDownload(params).setEvent(Analytics.Event.downloadFile).send();
		}

		/**
		 * @param {Object} params
		 * @param {FileType} params.fileType
		 * @param {DialogId} params.dialogId
		 * @return {AnalyticsEvent}
		 */
		#sendDownload({ fileType, dialogId })
		{
			const dialogModel = this.store.getters['dialoguesModel/getById'](dialogId);
			const collabInfo = this.store.getters['dialoguesModel/collabModel/getByDialogId'](dialogId);
			const userModel = this.store.getters['usersModel/getById'](this.userId);

			const analytics = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(this.#getChatCategory(dialogId))
				.setType(Analytics.Type[fileType])
				.setSection(Analytics.Section.chatWindow)
				.setSubSection(Analytics.SubSection.contextMenu)
				.setP1(Analytics.P1[dialogModel.type])
				.setP2(Analytics.P2[userModel.type])
				.setP5(this.#getChatP5(dialogModel));

			if (collabInfo?.collabId > 0)
			{
				analytics.setP4(this.#getChatP4(collabInfo?.collabId));
			}

			return analytics;
		}

		/**
		 * @param {DialogId} dialogId
		 */
		#getChatCategory(dialogId)
		{
			const dialogHelper = DialogHelper.createByDialogId(dialogId);

			if (dialogHelper?.isChannel)
			{
				return DialogType.channel;
			}

			if (dialogHelper?.isCollab)
			{
				return DialogType.collab;
			}

			return DialogType.chat;
		}

		/**
		 * @param {number} collabId
		 */
		#getChatP4(collabId)
		{
			return `collabId_${collabId}`;
		}

		#getChatP5(chatData)
		{
			return `chatId_${chatData.chatId}`;
		}
	}

	module.exports = { DownloadFile };
});
