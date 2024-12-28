/**
 * @module im/messenger/provider/service/classes/analytics/file-sending
 */
jn.define('im/messenger/provider/service/classes/analytics/file-sending', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Analytics, ComponentCode, FileType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogHelper } = require('im/messenger/lib/helper');

	/**
	 * @class FileSending
	 */
	class FileSending
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} filesCount
		 */
		sendToastShownGalleryLimitExceeded({ dialogId, filesCount })
		{
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			const helper = DialogHelper.createByModel(dialog);
			const category = this.#getChatCategory(helper);
			const section = this.#getChatSection();
			try
			{
				const analytics = new AnalyticsEvent()
					.setTool(Analytics.Tool.im)
					.setCategory(category)
					.setEvent(Analytics.Event.galleryLimitException)
					.setSection(section)
					.setP1(`chatType_${dialog?.type}`)
					.setP3(`filesCount_${filesCount}`)
					.setP5(`chatId_${dialog.chatId}`);

				const isChannelOrComment = helper?.isChannelOrComment;
				if (isChannelOrComment)
				{
					analytics.setP4(`parentChatId_${dialog.parentChatId}`);
				}

				const isCollabType = helper?.isCollab;
				if (isCollabType)
				{
					const collabId = this.store.getters['dialoguesModel/collabModel/getCollabIdByDialogId'](dialogId);
					const userModel = this.store.getters['usersModel/getById'](dialogId);
					const userType = userModel?.type === 'user' ? 'user_intranet' : `user_${userModel?.type}`;
					analytics.setP2(userType);
					analytics.setP4(`collabId_${collabId}`);
				}

				analytics.send();
			}
			catch (error)
			{
				console.error(`${this.constructor.name}.sendToastShownGalleryLimitExceeded.catch:`, error);
			}
		}

		/**
		 * @param {string} temporaryMessageId
		 * @param {string} fileId
		 */
		async sendFileUploadCancel({ temporaryMessageId, fileId })
		{
			const messageModel = this.store.getters['messagesModel/getById'](temporaryMessageId);
			const dialogModel = this.store.getters['dialoguesModel/getByChatId'](messageModel?.chatId);
			const helper = DialogHelper.createByModel(dialogModel);
			const category = this.#getChatCategory(helper);
			const section = this.#getChatSection();

			try
			{
				const analytics = new AnalyticsEvent()
					.setTool(Analytics.Tool.im)
					.setCategory(category)
					.setEvent(Analytics.Event.cancelFileUpload)
					.setSection(section)
					.setP1(`chatType_${dialogModel?.type}`)
					.setP5(`chatId_${dialogModel.chatId}`);

				const isChannelOrComment = helper?.isChannelOrComment;
				if (isChannelOrComment)
				{
					analytics.setP4(`parentChatId_${dialogModel.parentChatId}`);
				}

				const isCollabType = helper?.isCollab;
				if (isCollabType)
				{
					const collabId = this.store.getters['dialoguesModel/collabModel/getCollabIdByDialogId'](dialogModel.dialogId);
					const userModel = this.store.getters['usersModel/getById'](dialogModel.dialogId);
					analytics.setP2(`user_${userModel?.type}`);
					analytics.setP4(`collabId_${collabId}`);
				}

				analytics.send();
			}
			catch (error)
			{
				console.error(`${this.constructor.name}.sendToastShownFilesMoreThenHundred.catch:`, error);
			}
		}

		/**
		 * @param {DialogHelper} dialogHelper
		 * @return {string}
		 */
		#getChatCategory(dialogHelper)
		{
			if (dialogHelper.isChannelOrComment)
			{
				return Analytics.Category.channel;
			}

			if (dialogHelper.isCollab)
			{
				return Analytics.Category.collab;
			}

			return Analytics.Category.chat;
		}

		/**
		 * @return {string}
		 */
		#getChatSection()
		{
			switch (MessengerParams.getComponentCode())
			{
				case ComponentCode.imChannelMessenger: return Analytics.Section.channelTab;
				case ComponentCode.imCollabMessenger: return Analytics.Section.collabTab;
				default: return Analytics.Section.chatTab;
			}
		}
	}

	module.exports = { FileSending };
});
