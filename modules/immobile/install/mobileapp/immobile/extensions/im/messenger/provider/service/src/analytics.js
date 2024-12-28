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
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const { MessageDelete } = require('im/messenger/provider/service/classes/analytics/message-delete');
	const { ChatDelete } = require('im/messenger/provider/service/classes/analytics/chat-delete');
	const { ChatDataProvider } = require('im/messenger/provider/data');
	const { DialogEdit } = require('im/messenger/provider/service/classes/analytics/dialog-edit');
	const { ChatCreate } = require('im/messenger/provider/service/classes/analytics/chat-create');
	const { CollabEntities } = require('im/messenger/provider/service/classes/analytics/collab-entities');
	const { TariffRestrictions } = require('im/messenger/provider/service/classes/analytics/tariff-restrictions');
	const { FileSending } = require('im/messenger/provider/service/classes/analytics/file-sending');
	const { DownloadFile } = require('im/messenger/provider/service/classes/analytics/download-file');
	const { EntityManager } = require('im/messenger/provider/service/classes/analytics/entity-manager');
	const { ImagePicker } = require('im/messenger/provider/service/classes/analytics/image-picker');

	const { AnalyticsHelper } = require('im/messenger/provider/service/classes/analytics/helper');

	/** @type {AnalyticsService} */
	let instance = null;

	/**
	 * @class AnalyticsService
	 */
	class AnalyticsService
	{
		/** @type {MessageDelete} */
		#messageDelete;
		/** @type {ChatDelete} */
		#chatDelete;
		/** @type {DialogEdit} */
		#dialogEdit;
		/** @type {ChatCreate} */
		#chatCreate;
		/** @type {CollabEntities} */
		#collabEntities;
		/** @type {MessengerCoreStore} */
		#store;
		/** @type {TariffRestrictions} */
		#tariffRestrictions;
		/** @type {FileSending} */
		#fileSending;
		/** @type {DownloadFile} */
		#downloadFile;
		/** @type {EntityManager} */
		#entityManager;
		/** @type {ImagePicker} */
		#imagePicker;

		static getInstance()
		{
			instance ??= new this();

			return instance;
		}

		/** @protected */
		get messageDelete()
		{
			this.#messageDelete = this.#messageDelete ?? new MessageDelete();

			return this.#messageDelete;
		}

		/** @protected */
		get chatDelete()
		{
			this.#chatDelete = this.#chatDelete ?? new ChatDelete();

			return this.#chatDelete;
		}

		/** @protected */
		get dialogEdit()
		{
			this.#dialogEdit = this.#dialogEdit ?? new DialogEdit();

			return this.#dialogEdit;
		}

		/** @protected */
		get chatCreate()
		{
			this.#chatCreate = this.#chatCreate ?? new ChatCreate();

			return this.#chatCreate;
		}

		/** @protected */
		get collabEntities()
		{
			this.#collabEntities = this.#collabEntities ?? new CollabEntities();

			return this.#collabEntities;
		}

		/** @protected */
		get store()
		{
			this.#store = this.#store ?? serviceLocator.get('core').getStore();

			return this.#store;
		}

		/** @protected */
		get tariffRestrictions()
		{
			this.#tariffRestrictions = this.#tariffRestrictions ?? new TariffRestrictions();

			return this.#tariffRestrictions;
		}

		/** @protected */
		get fileSending()
		{
			this.#fileSending = this.#fileSending ?? new FileSending();

			return this.#fileSending;
		}

		/** @protected */
		get downloadFile()
		{
			this.#downloadFile = this.#downloadFile ?? new DownloadFile();

			return this.#downloadFile;
		}

		/** @protected */
		get entityManager()
		{
			this.#entityManager = this.#entityManager ?? new EntityManager();

			return this.#entityManager;
		}

		/** @protected */
		get imagePicker()
		{
			this.#imagePicker = this.#imagePicker ?? new ImagePicker();

			return this.#imagePicker;
		}

		sendMessageDeleteActionClicked({ messageId, dialogId })
		{
			return this.messageDelete.sendMessageDeleteActionClicked({ messageId, dialogId });
		}

		sendMessageDeletingCanceled({ messageId, dialogId })
		{
			return this.messageDelete.sendMessageDeletingCanceled({ messageId, dialogId });
		}

		sendToastShownMessageNotFound({ dialogId, context })
		{
			return this.messageDelete.sendToastShownMessageNotFound({ dialogId, context });
		}

		sendToastShownChannelPublicationNotFound({ chatId, parentChatId })
		{
			return this.messageDelete.sendToastShownChannelPublicationNotFound({ chatId, parentChatId });
		}

		sendChatDeletePopupShown({ dialogId })
		{
			return this.chatDelete.sendChatDeletePopupShown({ dialogId });
		}

		sendChatDeleteCanceled({ dialogId })
		{
			return this.chatDelete.sendChatDeleteCanceled({ dialogId });
		}

		sendChatDeleteConfirmed({ dialogId })
		{
			return this.chatDelete.sendChatDeleteConfirmed({ dialogId });
		}

		sendToastShownChatDelete({ chatId, chatType, isChatOpened = false })
		{
			return this.chatDelete.sendToastShownChatDelete({
				chatId,
				chatType,
				isChatOpened,
			});
		}

		sendCollabEntityOpened({ dialogId, entityType })
		{
			return this.collabEntities.sendCollabEntityOpened({ dialogId, entityType });
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
				.setP2(AnalyticsHelper.getP2ByUserType())
				.setP3(p3)
				.setP5(AnalyticsHelper.getFormattedChatId(chatData.chatId))
			;

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

			analytics.send();
		}

		sendUserAddButtonClicked({ dialogId })
		{
			const chatData = this.store.getters['dialoguesModel/getById'](dialogId);

			const helper = DialogHelper.createByModel(chatData);
			if (!helper)
			{
				return;
			}

			const analytics = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(chatData.type))
				.setEvent(Analytics.Event.clickAddUser)
				.setSection(Analytics.Section.chatSidebar)
				.setP2(AnalyticsHelper.getP2ByUserType())
				.setP5(AnalyticsHelper.getFormattedChatId(chatData.chatId))
			;

			if (helper.isCollab)
			{
				analytics.setP4(AnalyticsHelper.getFormattedCollabIdByDialogId(chatData.dialogId));
			}

			analytics.send();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendDialogEditHeaderMenuClick(dialogId)
		{
			return this.dialogEdit.sendDialogEditHeaderMenuClick(dialogId);
		}

		/**
		 * @param {DialogId|DialoguesModelState} dialog
		 */
		sendDialogEditButtonDoneDialogInfoClick(dialog)
		{
			return this.dialogEdit.sendDialogEditButtonDoneDialogInfoClick(dialog);
		}

		/**
		 * @param {{category, type, section}} params
		 */
		sendStartCreation(params)
		{
			return this.chatCreate.sendStartCreation(params);
		}

		/**
		 * @param {{dialog: DialoguesModelState|{}}} params
		 */
		sendAnalyticsShowBannerByStart(params)
		{
			return this.tariffRestrictions.sendAnalyticsShowBannerByStart(params);
		}

		/**
		 * @param {AnalyticsEvent} params
		 */
		sendAnalyticsOpenPlanLimitWidget(params)
		{
			return this.tariffRestrictions.sendAnalyticsOpenPlanLimitWidget(params);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} filesCount
		 */
		sendToastShownGalleryLimitExceeded({ dialogId, filesCount })
		{
			return this.fileSending.sendToastShownGalleryLimitExceeded({ dialogId, filesCount });
		}

		/**
		 * @param {string} temporaryMessageId
		 * @param {string} fileId
		 */
		async sendFileUploadCancel({ temporaryMessageId, fileId })
		{
			return this.fileSending.sendFileUploadCancel({ temporaryMessageId, fileId });
		}

		/**
		 * @param {Object} params
		 * @param {FileType} params.fileType
		 * @param {DialogId} params.dialogId
		 */
		sendDownloadToDevice(params)
		{
			return this.downloadFile.sendDownloadToDevice(params);
		}

		/**
		 * @param {Object} params
		 * @param {FileType} params.fileType
		 * @param {DialogId} params.dialogId
		 */
		sendDownloadToDisk(params)
		{
			return this.downloadFile.sendDownloadToDisk(params);
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendOpenCreateTask(dialogId)
		{
			return this.entityManager.sendClickToOpenCreateTask(dialogId);
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendOpenCreateMeeting(dialogId)
		{
			return this.entityManager.sendClickToOpenCreateMeeting(dialogId);
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendShowImagePicker(dialogId)
		{
			return this.imagePicker.sendShowImagePicker(dialogId);
		}
	}

	module.exports = { AnalyticsService };
});
