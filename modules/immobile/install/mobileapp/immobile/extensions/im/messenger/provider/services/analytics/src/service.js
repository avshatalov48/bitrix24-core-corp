/**
 * @module im/messenger/provider/services/analytics/service
 */
jn.define('im/messenger/provider/services/analytics/service', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	const { Analytics } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { AnalyticsHelper } = require('im/messenger/provider/services/analytics/helper');

	const { MessageDelete } = require('im/messenger/provider/services/analytics/message-delete');
	const { ChatDelete } = require('im/messenger/provider/services/analytics/chat-delete');
	const { DialogEdit } = require('im/messenger/provider/services/analytics/dialog-edit');
	const { ChatCreate } = require('im/messenger/provider/services/analytics/chat-create');
	const { Messenger } = require('im/messenger/provider/services/analytics/messenger');
	const { CollabEntities } = require('im/messenger/provider/services/analytics/collab-entities');
	const { TariffRestrictions } = require('im/messenger/provider/services/analytics/tariff-restrictions');
	const { FileSending } = require('im/messenger/provider/services/analytics/file-sending');
	const { DownloadFile } = require('im/messenger/provider/services/analytics/download-file');
	const { EntityManager } = require('im/messenger/provider/services/analytics/entity-manager');
	const { ImagePicker } = require('im/messenger/provider/services/analytics/image-picker');
	const { MessagePin } = require('im/messenger/provider/services/analytics/message-pin');
	const { MessageType } = require('im/messenger/provider/services/analytics/message-type');
	const { VoteAnalytics } = require('im/messenger/provider/services/analytics/vote');
	const { MessageCreateMenu } = require('im/messenger/provider/services/analytics/message-create-menu');
	const { ChatOpen } = require('im/messenger/provider/services/analytics/chat-open');
	const { ChatPin } = require('im/messenger/provider/services/analytics/chat-pin');
	const { NavigationTab } = require('im/messenger/provider/services/analytics/navigation-tab');
	const { AudioAnalytics } = require('im/messenger/provider/services/analytics/src/audio');

	/** @type {AnalyticsService} */
	let instance = null;

	/**
	 * @class AnalyticsService
	 */
	class AnalyticsService
	{
		/** @type {AudioAnalytics} */
		#audio;
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
		/** @type {MessagePin} */
		#messagePin;
		/** @type {MessageType} */
		#messageType;
		/** @type {VoteAnalytics} */
		#vote;
		/** @type {Messenger} */
		#messenger;
		/** @type {MessageCreateMenu} */
		#messageCreateMenu;
		/** @type {ChatOpen} */
		#chatOpen;
		/** @type {ChatPin} */
		#chatPin;
		/** @type {NavigationTab} */
		#navigation;

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

		/** @protected */
		get messagePin()
		{
			this.#messagePin = this.#messagePin ?? new MessagePin();

			return this.#messagePin;
		}

		/** @protected */
		get messageType()
		{
			this.#messageType = this.#messageType ?? new MessageType();

			return this.#messageType;
		}

		/** @protected */
		get vote()
		{
			this.#vote = this.#vote ?? new VoteAnalytics();

			return this.#vote;
		}

		/** @protected */
		get messenger()
		{
			this.#messenger = this.#messenger ?? new Messenger();

			return this.#messenger;
		}

		/** @protected */
		get messageCreateMenu()
		{
			this.#messageCreateMenu = this.#messageCreateMenu ?? new MessageCreateMenu();

			return this.#messageCreateMenu;
		}

		/** @protected */
		get chatOpen()
		{
			this.#chatOpen = this.#chatOpen ?? new ChatOpen();

			return this.#chatOpen;
		}

		/** @protected */
		get chatPin()
		{
			this.#chatPin = this.#chatPin ?? new ChatPin();

			return this.#chatPin;
		}

		get audio()
		{
			this.#audio = this.#audio ?? new AudioAnalytics();

			return this.#audio;
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendClickToPlayAudioInChat({ dialogId })
		{
			return this.audio.sendClickToPlayAudioInChat({ dialogId });
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendClickToPauseAudioInChat({ dialogId })
		{
			return this.audio.sendClickToPauseAudioInChat({ dialogId });
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {AudioRate} speed
		 */
		sendClickToChangeAudioSpeedInChat({ dialogId, speed })
		{
			return this.audio.sendClickToChangeAudioSpeedInChat({ dialogId, speed });
		}

		/** @protected */
		get navigation()
		{
			this.#navigation = this.#navigation ?? new NavigationTab();

			return this.#navigation;
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

		/**
		 * @param {DialogId} dialogId
		 * @param {string} section
		 */
		sendChatOpen({ dialogId, context })
		{
			return this.chatOpen.sendChatOpen({ dialogId, context });
		}

		sendPinChatNotes()
		{
			return this.chatPin.sendPinChatNotes();
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {string} section
		 */
		sendOpenCopilotDialog({ dialogId, context })
		{
			return this.chatOpen.sendOpenCopilotDialog({ dialogId, context });
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
		 * @param {sendAnalyticsParams} params
		 */
		sendDownloadToDevice(params)
		{
			return this.downloadFile.sendDownloadToDevice(params);
		}

		/**
		 * @param {sendAnalyticsParams} params
		 */
		sendDownloadToDisk(params)
		{
			return this.downloadFile.sendDownloadToDisk(params);
		}

		/**
		 * @param {sendAnalyticsParams} params
		 */
		sendDownloadError(params)
		{
			return this.downloadFile.sendDownloadError(params);
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

		/**
		 * @param {DialogId} dialogId
		 * @param {number} chatId
		 */
		sendMessagePin({ dialogId, chatId })
		{
			return this.messagePin.sendMessagePin({ dialogId, chatId });
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} chatId
		 */
		sendMessageUnpin({ dialogId, chatId })
		{
			return this.messagePin.sendMessageUnpin({ dialogId, chatId });
		}

		sendTypeMessageChatNotes()
		{
			return this.messageType.sendTypeMessageChatNotes();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendPinListOpened({ dialogId })
		{
			return this.messagePin.sendPinListOpened({ dialogId });
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendPinnedMessageLimitException({ dialogId })
		{
			return this.messagePin.sendPinnedMessageLimitException({ dialogId });
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendOpenCreateVote(dialogId)
		{
			this.vote.sendClickToOpenCreateVote(dialogId);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} voteId
		 * @param {Object} voteData
		 */
		sendOnVotePublished(dialogId, voteId, voteData)
		{
			this.vote.sendOnVotePublished(dialogId, voteId, voteData);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} voteId
		 */
		sendVoteVoted(dialogId, voteId)
		{
			this.vote.sendVoteVoted(dialogId, voteId);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} voteId
		 * @param {Object} voteData
		 */
		sendVoteFinished(dialogId, voteId, voteData)
		{
			this.vote.sendVoteFinished(dialogId, voteId, voteData);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} voteId
		 */
		sendVoteCancelled(dialogId, voteId)
		{
			this.vote.sendVoteCancelled(dialogId, voteId);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} voteId
		 */
		sendVoteResultLinkCopied(dialogId, voteId)
		{
			this.vote.sendVoteResultLinkCopied(dialogId, voteId);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} voteId
		 */
		sendVoteMessageLinkCopied(dialogId, voteId)
		{
			this.vote.sendVoteMessageLinkCopied(dialogId, voteId);
		}

		sendOpenDialogCreator()
		{
			this.messenger.sendOpenDialogCreator();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendOpenMessageCreateMenu(dialogId)
		{
			this.messageCreateMenu.sendOpenCreateMenu(dialogId);
		}

		/**
		 * @param {string} currentTab
		 * @param {object} analyticsOptions
		 */
		sendChangeNavigationTab(currentTab, analyticsOptions)
		{
			this.navigation.sendChangeTab(currentTab, analyticsOptions);
		}
	}

	module.exports = { AnalyticsService };
});
