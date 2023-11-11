/**
 * @module im/messenger/controller/dialog/dialog
 */
jn.define('im/messenger/controller/dialog/dialog', (require, exports, module) => {
	/* region import */

	const { Filesystem, utils } = require('native/filesystem');

	const { Type } = require('type');
	const { Loc } = require('loc');
	const { getPressedColor } = require('utils/color');
	const { clone, isEqual } = require('utils/object');
	const { ObjectUtils } = require('im/messenger/lib/utils');
	const { Uuid } = require('utils/uuid');
	const { withCurrentDomain } = require('utils/url');
	const { Haptics } = require('haptics');
	const { inAppUrl } = require('in-app-url');
	const { NotifyManager } = require('notify-manager');
	const { throttle, debounce } = require('utils/function');
	include('InAppNotifier');

	const {
		EventType,
		FeatureFlag,
		MessageType,
		MessageIdType,
		ReactionType,
		FileType,
		ErrorType,
	} = require('im/messenger/const');
	const {
		MessageRest, DialogRest,
	} = require('im/messenger/provider/rest');
	const {
		ChatService,
		MessageService,
		DiskService,
	} = require('im/messenger/provider/service');
	const {
		MessageMenu,
		LikeReaction,
		KissReaction,
		LaughReaction,
		WonderReaction,
		CryReaction,
		AngryReaction,
		FacepalmReaction,
		CopyAction,
		QuoteAction,
		ProfileAction,
		EditAction,
		DeleteAction,
		DownloadToDeviceAction,
		DownloadToDiskAction,
		StatusField,
	} = require('im/messenger/lib/element');
	const { core } = require('im/messenger/core');
	const { ReplyManager } = require('im/messenger/controller/dialog/reply-manager');
	const { DraftManager } = require('im/messenger/controller/dialog/draft-manager');
	const { MessageRenderer } = require('im/messenger/controller/dialog/message-renderer');
	const { HeaderTitle } = require('im/messenger/controller/dialog/header/title');
	const { HeaderButtons } = require('im/messenger/controller/dialog/header/buttons');
	const {
		DialogView,
		AfterScrollMessagePosition,
	} = require('im/messenger/view/dialog');
	const { Logger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Counters } = require('im/messenger/lib/counters');
	const { AudioMessagePlayer } = require('im/messenger/controller/dialog/audio-player');
	const { WebDialog } = require('im/messenger/controller/dialog/web');
	const { Calls } = require('im/messenger/lib/integration/immobile/calls');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { backgroundCache } = require('im/messenger/lib/background-cache');
	const { parser } = require('im/messenger/lib/parser');
	const { DialogConverter } = require('im/messenger/lib/converter');
	const { FileDownloadMenu } = require('im/messenger/controller/file-download-menu');
	const { ReactionViewerController } = require('im/messenger/controller/reaction-viewer');
	const { defaultUserIcon } = require('im/messenger/assets/common');
	const { DialogHelper } = require('im/messenger/lib/helper');

	/* endregion import */

	/**
	 * @class Dialog
	 */
	class Dialog
	{
		constructor()
		{
			/**
			 * @private
			 * @type {MessengerCoreStore}
			 */
			this.store = core.getStore();
			/**
			 * @private
			 * @type {MessengerCoreStoreManager}
			 */
			this.storeManager = core.getStoreManager();
			/**
			 * @private
			 * @type {string || number}
			 */
			this.dialogId = 0;
			/**
			 * @private
			 * @type {DialogHeaderTitleParams}
			 */
			this.titleParams = null;
			/**
			 * @private
			 * @type {ChatService}
			 */
			this.chatService = new ChatService();
			/**
			 * @private
			 * @type {DiskService}
			 */
			this.diskService = new DiskService();
			/**
			 * @private
			 * @type {MessageService}
			 */
			this.messageService = null;
			/**
			 * @private
			 * @type {MessageRenderer}
			 */
			this.messageRenderer = null;
			/**
			 * @private
			 * @type {HeaderTitle}
			 */
			this.headerTitle = null;
			/**
			 * @private
			 * @type {HeaderButtons}
			 */
			this.headerButtons = null;
			/**
			 * @private
			 * @type {DraftManager}
			 */
			this.draftManager = null;
			/**
			 * @desc Id timer timeout for canceling request rest
			 * @private
			 * @type {number|null}
			 */
			this.holdWritingTimerId = null;
			/**
			 * @desc This hold for start request to rest method 'im.dialog.writing'
			 * @constant
			 * @private
			 * @type {number}
			 */
			this.HOLD_WRITING_REST = 3000;
			/**
			 * @private
			 * @type {AudioMessagePlayer}
			 */
			this.audioMessagePlayer = new AudioMessagePlayer(this.store);

			/**
			 * @private
			 * @type {boolean}
			 */
			this.needScrollToBottom = false;

			/* region View event handlers */

			/** @private */
			this.submitHandler = this.sendMessage.bind(this);
			/** @private */
			this.changeTextHandler = this.onChangeText.bind(this);
			/** @private */
			this.attachTapHandler = this.onAttachTap.bind(this);
			/** @private */
			this.resendHandler = this.resendMessage.bind(this);
			/** @private */
			this.loadTopPageHandler = this.loadTopPage.bind(this);
			/** @private */
			this.loadBottomPageHandler = this.loadBottomPage.bind(this);
			/** @private */
			this.scrollBeginHandler = this.onScrollBegin.bind(this);
			/** @private */
			this.scrollEndHandler = this.onScrollEnd.bind(this);
			this.replyHandler = this.onReply.bind(this);
			/** @private */
			this.readyToReplyHandler = this.onReadyToReply.bind(this);
			/** @private */
			this.quoteTapHandler = this.onQuoteTap.bind(this);
			/** @private */
			this.cancelReplyHandler = this.onCancelReply.bind(this);
			/** @private */
			this.visibleMessagesChangedHandler = this.onVisibleMessagesChanged.bind(this);
			/** @private */
			this.messageReadHandler = this.onReadMessage.bind(this);
			/** @private */
			this.scrollToNewMessagesHandler = this.onScrollToNewMessages.bind(this);
			/** @private */
			this.playAudioButtonTapHandler = this.onAudioButtonTap.bind(this);
			/** @private */
			this.playbackCompletedHandler = this.onPlaybackCompleted.bind(this);
			/** @private */
			this.urlTapHandler = this.onUrlTap.bind(this);
			/** @private */
			this.audioRecordingStartHandler = this.onAudioRecordingStart.bind(this);
			/** @private */
			this.audioRecordingFinishHandler = this.onAudioRecordingFinish.bind(this);
			/** @private */
			this.submitAudioHandler = this.sendAudio.bind(this);
			/** @private */
			this.mentionTapHandler = this.onMentionTap.bind(this);
			/** @private */
			this.messageTapHandler = this.onMessageTap.bind(this);
			/** @private */
			this.statusFieldTapHandler = this.onStatusFieldTap.bind(this);
			/** @private */
			this.messageAvatarTapHandler = this.onMessageAvatarTap.bind(this);
			/** @private */
			this.messageQuoteTapHandler = this.onMessageQuoteTap.bind(this);
			/** @private */
			this.messageLongTapHandler = this.onMessageLongTap.bind(this);
			/** @private */
			this.messageDoubleTapHandler = this.onMessageDoubleTap.bind(this);
			/** @private */
			this.messageMenuReactionTapHandler = this.setReaction.bind(this);
			this.messageMenuActionTapHandler = this.onMessageMenuAction.bind(this);
			/** @private */
			this.messageFileDownloadTapHandler = this.onMessageFileDownloadTap.bind(this);
			/** @private */
			this.messageFileUploadCancelTapHandler = this.onMessageFileUploadCancelTap.bind(this);
			/** @private */
			this.reactionTapHandler = debounce(this.setReaction.bind(this), 300);

			/**
			 * @private
			 * @deprecated
			 */
			this.onLikeHandler = debounce(this.onLike.bind(this), 300);
			/** @private */
			this.reactionLongTapHandler = this.openReactionViewer.bind(this);
			/** @private */
			this.closeHandler = this.onClose.bind(this);

			/** @private */
			this.scrollToBottomHandler = this.onScrollToBottom.bind(this);

			/* endregion View event handlers */

			// render functions
			/** @private */
			this.setChatCollectionHandler = this.drawMessageList.bind(this);
			/** @private */
			this.messageUpdateHandler = this.messageUpdateHandlerRouter.bind(this);
			this.updateReactionHandler = this.redrawReactionMessages.bind(this);
			this.deleteHandler = this.deleteMessage.bind(this);
			/** @private */
			this.dialogUpdateHandlerRouter = this.dialogUpdateHandlerRouter.bind(this);
			/** @private */
			this.updateMessageCounterHandler = this.updateMessageCounter.bind(this);
			/** @private */
			this.redrawReactionMessageHandler = this.redrawReactionMessage.bind(this);
			this.dialogDeleteHandler = () => {};

			if (FeatureFlag.dialog.nativeSupported)
			{
				// TODO: generalize the approach to background caching
				Dialog.preloadAssets();
			}

			this.startWriting = throttle(this.startWriting, 5000, this);
			this.startRecordVoiceMessage = throttle(this.startRecordVoiceMessage, 3000, this);
		}

		/** @private */
		static preloadAssets()
		{
			backgroundCache.downloadImages([
				CopyAction.imageUrl,
				QuoteAction.imageUrl,
				ProfileAction.imageUrl,
				EditAction.imageUrl,
				DeleteAction.imageUrl,
				DownloadToDeviceAction.imageUrl,
				DownloadToDiskAction.imageUrl,
				LikeReaction.imageUrl,
				KissReaction.imageUrl,
				LaughReaction.imageUrl,
				WonderReaction.imageUrl,
				CryReaction.imageUrl,
				AngryReaction.imageUrl,
				FacepalmReaction.imageUrl,
			]);

			backgroundCache.downloadLottieAnimations([
				LikeReaction.lottieUrl,
				KissReaction.lottieUrl,
				LaughReaction.lottieUrl,
				WonderReaction.lottieUrl,
				CryReaction.lottieUrl,
				AngryReaction.lottieUrl,
				FacepalmReaction.lottieUrl,
			]);
		}

		/** @private */
		subscribeViewEvents()
		{
			this.view
				.on(EventType.dialog.submit, this.submitHandler)
				.on(EventType.dialog.attachTap, this.attachTapHandler)
				.on(EventType.dialog.resend, this.resendHandler)
				.on(EventType.dialog.loadTopPage, this.loadTopPageHandler)
				.on(EventType.dialog.loadBottomPage, this.loadBottomPageHandler)
				.on(EventType.dialog.scrollBegin, this.scrollBeginHandler)
				.on(EventType.dialog.scrollEnd, this.scrollEndHandler)
				.on(EventType.dialog.reply, this.replyHandler)
				.on(EventType.dialog.readyToReply, this.readyToReplyHandler)
				.on(EventType.dialog.quoteTap, this.quoteTapHandler)
				.on(EventType.dialog.cancelReply, this.cancelReplyHandler)
				.on(EventType.dialog.visibleMessagesChanged, this.visibleMessagesChangedHandler)
				.on(EventType.dialog.messageRead, this.messageReadHandler)
				.on(EventType.dialog.scrollToNewMessages, this.scrollToNewMessagesHandler)
				.on(EventType.dialog.playAudioButtonTap, this.playAudioButtonTapHandler)
				.on(EventType.dialog.playbackCompleted, this.playbackCompletedHandler)
				.on(EventType.dialog.urlTap, this.urlTapHandler)
				.on(EventType.dialog.statusFieldTap, this.statusFieldTapHandler)
				.on(EventType.dialog.audioRecordingStart, this.audioRecordingStartHandler)
				.on(EventType.dialog.audioRecordingFinish, this.audioRecordingFinishHandler)
				.on(EventType.dialog.submitAudio, this.submitAudioHandler)
				.on(EventType.dialog.mentionTap, this.mentionTapHandler)
				.on(EventType.dialog.messageTap, this.messageTapHandler)
				.on(EventType.dialog.messageAvatarTap, this.messageAvatarTapHandler)
				.on(EventType.dialog.messageQuoteTap, this.messageQuoteTapHandler)
				.on(EventType.dialog.messageLongTap, this.messageLongTapHandler)
				.on(EventType.dialog.messageDoubleTap, this.messageDoubleTapHandler)
				.on(EventType.dialog.messageMenuReactionTap, this.messageMenuReactionTapHandler)
				.on(EventType.dialog.messageMenuActionTap, this.messageMenuActionTapHandler)
				.on(EventType.dialog.messageFileDownloadTap, this.messageFileDownloadTapHandler)
				.on(EventType.dialog.messageFileUploadCancelTap, this.messageFileUploadCancelTapHandler)
				.on(EventType.dialog.changeText, this.draftManager.changeTextHandler)
				.on(EventType.dialog.reactionTap, this.reactionTapHandler)
				.on(EventType.dialog.reactionLongTap, this.reactionLongTapHandler)
				.on(EventType.dialog.changeText, this.changeTextHandler)
				.on(EventType.view.barButtonTap, this.headerButtons.tapHandler)
				.on(EventType.view.barButtonLongTap, this.headerButtons.longTapHandler)
				.on(EventType.view.close, this.closeHandler)
				.on(EventType.dialog.like, this.onLikeHandler)
			;

			this.view.ui.on('titleClick', () => {
				MessengerEmitter.emit(EventType.messenger.openSidebar, {
					dialogId: this.dialogId,
				});
			});
		}

		/** @private */
		unsubscribeViewEvents()
		{
			this.view.removeAll();
		}

		/** @private */
		subscribeStoreEvents()
		{
			this.storeManager
				.on('messagesModel/setChatCollection', this.setChatCollectionHandler)
				.on('messagesModel/update', this.messageUpdateHandler)
				.on('messagesModel/updateWithId', this.messageUpdateHandler)
				.on('messagesModel/reactionsModel/set', this.updateReactionHandler)
				.on('messagesModel/reactionsModel/updateWithId', this.redrawReactionMessageHandler)
				.on('messagesModel/reactionsModel/add', this.redrawReactionMessageHandler)
				.on('messagesModel/delete', this.deleteHandler)
				.on('dialoguesModel/add', this.dialogUpdateHandlerRouter)
				.on('dialoguesModel/update', this.dialogUpdateHandlerRouter)
				.on('dialoguesModel/update', this.updateMessageCounterHandler)
				.on('dialoguesModel/delete', this.dialogDeleteHandler)
				.on('usersModel/set', this.dialogUpdateHandlerRouter)
			;
		}

		/** @private */
		unsubscribeStoreEvents()
		{
			this.storeManager
				.off('messagesModel/setChatCollection', this.setChatCollectionHandler)
				.off('messagesModel/update', this.messageUpdateHandler)
				.off('messagesModel/updateWithId', this.messageUpdateHandler)
				.off('messagesModel/reactionsModel/set', this.updateReactionHandler)
				.off('messagesModel/reactionsModel/updateWithId', this.redrawReactionMessageHandler)
				.off('messagesModel/reactionsModel/add', this.redrawReactionMessageHandler)
				.off('messagesModel/delete', this.deleteHandler)
				.off('dialoguesModel/add', this.dialogUpdateHandlerRouter)
				.off('dialoguesModel/update', this.dialogUpdateHandlerRouter)
				.off('dialoguesModel/update', this.updateMessageCounterHandler)
				.off('dialoguesModel/delete', this.dialogDeleteHandler)
				.off('usersModel/set', this.dialogUpdateHandlerRouter)
			;
		}

		/** @private */
		subscribeExternalEvents()
		{
			BX.addCustomEvent(EventType.dialog.external.scrollToBottom, this.scrollToBottomHandler);
		}

		/** @private */
		unsubscribeExternalEvents()
		{
			BX.removeCustomEvent(EventType.dialog.external.scrollToBottom, this.scrollToBottomHandler);
		}

		/** @private */
		initManagers()
		{
			/**
			 * @private
			 * @type {ReplyManager}
			 */
			this.replyManager = new ReplyManager({
				store: this.store,
				dialogView: this.view,
			});

			/**
			 * @private
			 * @type {DraftManager}
			 */
			this.draftManager = new DraftManager({
				store: this.store,
				view: this.view,
				dialogId: this.getDialogId(),
				replyManager: this.replyManager,
			});

			this.replyManager.setDraftManager(this.draftManager);
		}

		open(options)
		{
			const {
				dialogId,
				dialogTitleParams,
			} = options;

			this.dialogId = dialogId;

			this.store.dispatch('applicationModel/openDialogId', dialogId);
			this.store.dispatch('recentModel/like', {
				id: dialogId,
				liked: false,
			});

			const chatSettings = Application.storage.getObject('settings.chat', {
				chatBetaEnable: false,
			});
			const isOpenlinesChat = dialogTitleParams && dialogTitleParams.chatType === 'lines';
			if (
				!MessengerParams.isBetaAvailable()
				|| !chatSettings.chatBetaEnable
				|| !FeatureFlag.dialog.nativeSupported
				|| isOpenlinesChat
			)
			{
				this.openWebDialog(options);

				return;
			}

			let titleParams = null;
			if (dialogTitleParams)
			{
				titleParams = {
					text: dialogTitleParams.name,
					detailText: dialogTitleParams.description,
					imageUrl: dialogTitleParams.avatar,
					useLetterImage: true,
				};

				if (!dialogTitleParams.avatar || dialogTitleParams.avatar === '')
				{
					titleParams.imageColor = dialogTitleParams.color;
				}
			}

			this.createWidget(titleParams);
		}

		openLine(options)
		{
			this.openWebDialog(options);
		}

		getDialogId()
		{
			return this.dialogId;
		}

		/**
		 * @return {DialoguesModelState|{}}
		 */
		getDialog()
		{
			const dialog = this.store.getters['dialoguesModel/getById'](this.dialogId);

			return dialog || {};
		}

		getChatId()
		{
			const dialog = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (dialog && dialog.chatId && dialog.chatId > 0)
			{
				return dialog.chatId;
			}

			return 0;
		}

		/** @private */
		getMessageReactionsLottie()
		{
			return {
				[ReactionType.like]: LikeReaction.lottieUrl,
				[ReactionType.kiss]: KissReaction.lottieUrl,
				[ReactionType.cry]: CryReaction.lottieUrl,
				[ReactionType.laugh]: LaughReaction.lottieUrl,
				[ReactionType.angry]: AngryReaction.lottieUrl,
				[ReactionType.facepalm]: FacepalmReaction.lottieUrl,
				[ReactionType.wonder]: WonderReaction.lottieUrl,
			};
		}

		/**
		 * @private
		 */
		getCurrentUserAvatarForReactions()
		{
			const currentUser = this.store.getters['usersModel/getById'](MessengerParams.getUserId());

			if (currentUser.avatar !== '')
			{
				return {
					imageUrl: currentUser.avatar,
				};
			}

			return {
				defaultIconSvg: defaultUserIcon(currentUser.color),
			};
		}

		/** @private */
		createWidget(titleParams = null)
		{
			if (!titleParams)
			{
				titleParams = HeaderTitle.createTitleParams(this.getDialogId(), this.store);
			}
			this.headerButtons = new HeaderButtons({
				store: this.store,
				dialogId: this.getDialogId(),
			});

			PageManager.openWidget(
				'chat.dialog',
				{
					dialogId: this.getDialogId(),
					titleParams,
					rightButtons: this.headerButtons.getButtons(),
					reactions: {
						lottie: this.getMessageReactionsLottie(),
						currentUserAvatar: this.getCurrentUserAvatarForReactions(),
					},
				},
			)
				.then(this.onWidgetReady.bind(this))
				.catch((error) => Logger.error(error))
			;
		}

		/**
		 * @private
		 * @param widget
		 */
		onWidgetReady(widget)
		{
			this.createView(widget);
			this.initManagers();
			this.subscribeViewEvents();
			this.subscribeStoreEvents();
			this.subscribeExternalEvents();

			let savedMessages = [];
			if (this.getChatId() > 0)
			{
				savedMessages = this.store.getters['messagesModel/getByChatId'](this.getChatId());
			}

			let loadMessagesPromise;
			if (!Type.isArrayFilled(savedMessages) || !this.getDialog().inited)
			{
				Logger.info(`Dialog: dialogId: ${this.dialogId} first load`);
				loadMessagesPromise = this.chatService.loadChatWithMessages(this.dialogId);
				this.view.showMessageListLoader();
			}
			else
			{
				Logger.info(`Dialog: dialogId: ${this.dialogId} rerender`);
				loadMessagesPromise = this.store.dispatch('messagesModel/forceUpdateByChatId', { chatId: this.getChatId() });
			}

			loadMessagesPromise
				.then(() => {
					this.view.hideMessageListLoader();
					this.messageService = new MessageService({
						store: this.store,
						chatId: this.getChatId(),
					});
					const counter = this.getDialog().counter;
					if (counter > 0)
					{
						this.view.setNewMessageCounter(counter);
					}

					this.headerTitle.renderTitle();
					this.headerButtons.render(this.view);
					this.drawStatusField();
				})
				.catch((error) => {
					if (error === ErrorType.dialog.accessError)
					{
						MessengerEmitter.emit(EventType.messenger.dialogAccessError, { dialogId: this.getDialogId() });
						this.view.back();

						return;
					}

					throw error;
				})
			;
		}

		/**
		 * @private
		 * @param widget
		 */
		createView(widget)
		{
			this.view = new DialogView({
				ui: widget,
				dialogId: this.getDialogId(),
				chatId: this.getChatId(),
				lastReadId: this.getDialog().lastReadId,
			});

			this.messageRenderer = new MessageRenderer({
				view: this.view,
				dialogId: this.getDialogId(),
				chatId: this.getChatId(),
			});

			this.headerTitle = new HeaderTitle({
				store: this.store,
				view: this.view,
				dialogId: this.getDialogId(),
			});

			this.headerTitle.startRender();

			this.view.setInputPlaceholder(Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_INPUT_PLACEHOLDER_TEXT'));
		}

		/**
		 * @private
		 */
		onClose()
		{
			const dialogId = this.getDialogId();
			this.unsubscribeExternalEvents();
			this.unsubscribeStoreEvents();
			this.unsubscribeViewEvents();
			this.audioMessagePlayer.stop();
			this.headerTitle.stopRender();

			this.store.dispatch('applicationModel/closeDialogId', dialogId)
				.then(() => {
					MessengerEmitter.emit(EventType.messenger.closeDialog, dialogId);
				})
			;
		}

		/**
		 * @private
		 */
		onVisibleMessagesChanged({ indexList = [], messageList = [] })
		{
			if (!this.view.scrollToFirstUnreadCompleted)
			{
				return;
			}

			const date = this.store.getters['messagesModel/getById'](this.view.getTopMessageOnScreen().id).date;
			if (date)
			{
				const dateText = DateFormatter.getDateGroupFormat(date);

				this.view.setFloatingText(dateText);
			}
		}

		/**
		 * @private
		 */
		onReadMessage(messageIdList)
		{
			if (!this.view.scrollToFirstUnreadCompleted)
			{
				return;
			}

			messageIdList.forEach((messageId) => {
				this.chatService.readMessage(this.getChatId(), messageId);
			});
		}

		/**
		 * @private
		 */
		onScrollToNewMessages()
		{
			if (this.needScrollToBottom)
			{
				this.view.scrollToBottomSmoothly();
				this.needScrollToBottom = false;
			}
			else
			{
				this.view.scrollToLastReadMessage();
				this.needScrollToBottom = true;
			}
		}

		/**
		 * @private
		 */
		onScrollBegin()
		{
			this.needScrollToBottom = false;

			this.view.showFloatingText();
		}

		/**
		 * @private
		 */
		onScrollEnd()
		{
			this.view.hideFloatingText();
		}

		/**
		 * @private
		 */
		onScrollToBottom(options)
		{
			const { chatId } = options;
			if (this.getChatId() !== chatId)
			{
				return;
			}

			Logger.log('EventType.dialog.external.scrollToBottom', options);
			this.view.scrollToBottomSmoothly();
		}

		/**
		 * @private
		 */
		onAudioButtonTap(index, message, isPlaying, playingTime)
		{
			const shouldPlayAudio = !isPlaying;
			if (shouldPlayAudio)
			{
				this.audioMessagePlayer.play(Number(message.id), playingTime);
			}
			else
			{
				this.audioMessagePlayer.stop(playingTime);
			}
		}

		/**
		 * @private
		 */
		onPlaybackCompleted()
		{
			this.audioMessagePlayer.playNext();
		}

		onUrlTap(url)
		{
			Logger.log('Dialog.onUrlTap: ', url);

			inAppUrl.open(url);
		}

		/**
		 * @private
		 */
		onAudioRecordingStart()
		{
			if (this.view.checkIsScrollToNewMessageButtonVisible())
			{
				this.view.hideScrollToNewMessagesButton();
			}
			this.startRecordVoiceMessage();
		}

		/**
		 * @private
		 */
		onAudioRecordingFinish()
		{}

		/**
		 * @private
		 */
		sendAudio(audioFile)
		{
			const file = {
				url: audioFile.localAudioUrl,
			};

			MessengerEmitter.emit(EventType.messenger.uploadFiles, {
				dialogId: this.getDialogId(),
				fileList: [file],
			});
		}

		/**
		 * @private
		 */
		onMentionTap(userId)
		{
			MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId: userId.toString() });
		}

		/**
		 * @private
		 */
		onMessageTap(index, message)
		{
			const modelMessage = this.store.getters['messagesModel/getById'](Number(message.id));
			if (!modelMessage || !('id' in modelMessage) || !modelMessage.files[0])
			{
				return;
			}

			const file = this.store.getters['filesModel/getById'](modelMessage.files[0]);
			if (file && file.type === MessageType.image)
			{
				viewer.openImage(file.urlShow, file.name);
			}

			if (file && file.type === FileType.video)
			{
				viewer.openVideo(file.urlDownload);
			}

			if (file && file.type === FileType.file)
			{
				viewer.openDocument(file.urlDownload, file.name);
			}
		}

		/**
		 * @private
		 */
		onStatusFieldTap()
		{
			const dialog = this.getDialog();
			const messageId = dialog.lastMessageId;
			const isGroupDialog = DialogHelper.isDialogId(this.getDialogId());
			if (!isGroupDialog)
			{
				return;
			}

			if (!dialog.lastMessageId)
			{
				return;
			}

			this.messageService.openUsersReadMessageList(messageId);
		}

		/**
		 * @private
		 */
		onMessageAvatarTap(index, message)
		{
			const messageId = Number(message.id);
			const modelMessage = this.store.getters['messagesModel/getById'](messageId);
			if (!modelMessage)
			{
				return;
			}

			const authorId = modelMessage.authorId;
			const user = this.store.getters['usersModel/getById'](authorId);
			if (!user)
			{
				return;
			}

			if (!user.bot)
			{
				UserProfile.show(authorId, { backdrop: true });
			}
		}

		/**
		 * @private
		 */
		onMessageLongTap(index, message)
		{
			const messageId = Number(message.id);
			const isRealMessage = Type.isNumber(messageId);

			const modelMessage = this.store.getters['messagesModel/getById'](messageId);

			if (!modelMessage || !('id' in modelMessage))
			{
				return;
			}

			const userId = MessengerParams.getUserId();
			const isYourMessage = modelMessage.authorId === userId;
			const isSystemMessage = modelMessage.authorId === 0;
			const isDeletedMessage = modelMessage.params.IS_DELETED === 'Y';
			const isMessageHasText = modelMessage.text !== '';
			let isImageMessage = false;
			let isVideoMessage = false;

			const file = this.store.getters['filesModel/getById'](modelMessage.files[0]);
			if (file && file.type === FileType.image)
			{
				isImageMessage = true;
			}

			if (file && file.type === FileType.video)
			{
				isVideoMessage = true;
			}

			if (!isRealMessage)
			{
				return;
			}

			const menu = MessageMenu
				.create()
				.addReaction(LikeReaction)
				.addReaction(KissReaction)
				.addReaction(LaughReaction)
				.addReaction(WonderReaction)
				.addReaction(CryReaction)
				.addReaction(AngryReaction)
				.addReaction(FacepalmReaction)
			;

			menu.addAction(QuoteAction);

			if (isMessageHasText)
			{
				menu.addAction(CopyAction);
			}

			if (
				(
					isImageMessage
					|| isVideoMessage
				)
				&& FeatureFlag.native.utilsSaveToLibrarySupported
				&& !isDeletedMessage
			)
			{
				menu
					.addAction(DownloadToDeviceAction)
					.addAction(DownloadToDiskAction)
				;
			}

			if (!isYourMessage && !isSystemMessage)
			{
				menu.addAction(ProfileAction);
			}

			if (isYourMessage && !isDeletedMessage && !isSystemMessage)
			{
				menu.addAction(EditAction);
				menu
					.addSeparator()
					.addAction(DeleteAction)
				;
			}

			this.view.showMenuForMessage(message, menu);
			Haptics.impactMedium();
		}

		/**
		 * @private
		 */
		onMessageDoubleTap(index, message)
		{
			if (!message.showReaction)
			{
				return;
			}

			this.setReaction(ReactionType.like, message);
		}

		sendMessage(text)
		{
			this.view.clearInput();
			this.draftManager.saveDraft('');
			this.cancelWritingRequest();

			let shouldScrollToBottom = true;
			if (this.replyManager.isEditInProcess)
			{
				shouldScrollToBottom = false;

				const editMessageId = this.replyManager.getEditMessage().id;
				this.messageService.updateText(editMessageId, text);
				this.replyManager.finishEditingMessage();

				return;
			}

			if (ObjectUtils.isStringFullSpace(text))
			{
				return;
			}

			const uuid = Uuid.getV4();

			const message = {
				chatId: this.getChatId(),
				authorId: MessengerParams.getUserId(),
				text: text.trim(),
				unread: false,
				templateId: uuid,
				date: new Date(),
				sending: true,
			};

			const messageSendOption = {
				dialogId: this.getDialogId(),
				text,
				messageType: 'self',
				templateId: uuid,
			};

			if (this.replyManager.isQuoteInProcess)
			{
				const quoteMessage = this.replyManager.getQuoteMessage();
				const quoteMessageId = Type.isString(quoteMessage.id)
					? parseInt(quoteMessage.id, 10)
					: quoteMessage.id;
				message.params = { replyId: quoteMessageId };
				messageSendOption.replyId = quoteMessageId;
				this.onCancelReply();
			}

			this.draftManager.saveDraft('');

			this.store.dispatch('messagesModel/add', message).then(() => {
				if (shouldScrollToBottom)
				{
					this.view.scrollToBottomSmoothly();
				}

				this._sendMessage(messageSendOption);
			});
		}

		/**
		 * @desc Handler change text in input message zone ( native region )
		 * @param {string} text
		 */
		onChangeText(text)
		{
			if (text && !ObjectUtils.isStringFullSpace(text))
			{
				this.startWriting();
			}

			this.draftManager.changeTextHandler(text);
		}

		/**
		 * @private
		 */
		onAttachTap()
		{
			this.view.showAttachPicker((fileList) => {
				MessengerEmitter.emit(EventType.messenger.uploadFiles, {
					dialogId: this.getDialogId(),
					fileList,
				});
			});
		}

		/**
		 * @private
		 */
		_sendMessage(message)
		{
			Logger.log('Dialog._sendMessage', message);

			MessageRest
				.send(message)
				.then((response) => {
					this.store.dispatch('messagesModel/updateWithId', {
						id: message.templateId,
						fields: {
							id: response.data(),
							error: false,
						},
					});
				})
				.catch(() => {
					this.store.dispatch('messagesModel/update', {
						id: message.templateId,
						fields: {
							error: true,
						},
					});
				})
			;
		}

		resendMessage(index, message)
		{
			const modelMessage = this.store.getters['messagesModel/getById'](message.id);
			const messageToSend = {
				dialogId: this.getDialogId(),
				text: modelMessage.text,
				messageType: 'self',
				templateId: message.id,
			};

			this._sendMessage(messageToSend);
		}

		setReaction(reaction, message, like)
		{
			const messageId = Number(message.id);
			const reactions = this.store.getters['messagesModel/reactionsModel/getByMessageId'](messageId);

			if (reactions && reactions.ownReactions.has(reaction))
			{
				this.messageService.removeReaction(reaction, Number(messageId));
			}
			else
			{
				this.messageService.addReaction(reaction, Number(messageId));
			}
		}

		/**
		 * @deprecated
		 * @param index
		 * @param message
		 * @param like
		 */
		onLike(index, message, like)
		{
			const messageId = Number(message.id);
			const reactions = this.store.getters['messagesModel/reactionsModel/getByMessageId'](messageId);

			if (reactions && reactions.ownReactions.has(ReactionType.like))
			{
				this.messageService.removeReaction(ReactionType.like, Number(messageId));
			}
			else
			{
				this.messageService.addReaction(ReactionType.like, Number(messageId));
			}
		}

		/**
		 * @private
		 */
		onMessageMenuAction(actionId, message)
		{
			const messageId = Number(message.id);
			const modelMessage = this.store.getters['messagesModel/getById'](messageId);
			if (!message)
			{
				return;
			}

			const file = this.store.getters['filesModel/getById'](modelMessage.files[0]);
			const hasFile = Boolean(file);
			const isImageMessage = hasFile && file.type === FileType.image;
			const isVideoMessage = hasFile && file.type === FileType.video;
			const isImageOrVideo = isImageMessage || isVideoMessage;

			switch (actionId)
			{
				case CopyAction.id:
					Application.copyToClipboard(parser.prepareCopy(modelMessage));

					InAppNotifier.showNotification({
						title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_COPIED'),
						time: 1,
						backgroundColor: '#E6000000',
					});
					break;

				case QuoteAction.id:
					this.onReply(0, message);
					break;

				case ProfileAction.id:
					UserProfile.show(modelMessage.authorId, { backdrop: true });
					break;

				case EditAction.id:
					this.replyManager.startEditingMessage(message);
					break;

				case DeleteAction.id:
					this.messageService.delete(modelMessage.id);
					break;

				case DownloadToDeviceAction.id:
					if (!isImageOrVideo || !FeatureFlag.native.utilsSaveToLibrarySupported)
					{
						return;
					}

					NotifyManager.showLoadingIndicator();
					Filesystem.downloadFile(withCurrentDomain(file.urlDownload)).then((localPath) => {
						utils.saveToLibrary(localPath)
							.then(() => {
								const successMessage = isImageMessage
									? Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_DOWNLOAD_PHOTO_TO_GALLERY_SUCCESS')
									: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_DOWNLOAD_VIDEO_TO_GALLERY_SUCCESS')
								;

								InAppNotifier.showNotification({
									title: successMessage,
									time: 3,
									backgroundColor: '#E6000000',
								});
							})
							.finally(() => {
								NotifyManager.hideLoadingIndicatorWithoutFallback();
							});
					});
					break;
				case DownloadToDiskAction.id:
					if (!isImageOrVideo)
					{
						return;
					}

					this.diskService.save(file.id)
						.then(() => {
							const successMessage = isImageMessage
								? Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_DOWNLOAD_PHOTO_TO_DISK_SUCCESS')
								: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_DOWNLOAD_VIDEO_TO_DISK_SUCCESS')
							;

							InAppNotifier.showNotification({
								title: successMessage,
								time: 3,
								backgroundColor: '#E6000000',
							});
						})
					;
					break;
			}
		}

		/**
		 * @private
		 */
		onMessageFileDownloadTap(index, message)
		{
			Logger.log('Dialog.onMessageFileDownloadTap: ', index, message);

			const fileList = this.store.getters['messagesModel/getMessageFiles'](message.id);
			if (!Type.isArrayFilled(fileList))
			{
				return;
			}

			FileDownloadMenu
				.createByFileId(fileList[0].id)
				.open()
			;
		}

		/**
		 * @private
		 */
		onMessageFileUploadCancelTap(index, message)
		{
			Logger.log('Dialog.onMessageFileUploadCancelTap: ', index, message);

			const modelMessage = this.store.getters['messagesModel/getById'](message.id);
			if (!modelMessage || !modelMessage.id || !modelMessage.files || !modelMessage.files[0])
			{
				return;
			}

			MessengerEmitter.emit(EventType.messenger.cancelFileUpload, {
				messageId: modelMessage.id,
				fileId: modelMessage.files[0],
			});
		}

		/**
		 * @private
		 */
		onReply(index, message)
		{
			if (
				this.replyManager.isQuoteInProcess
				&& message.id === this.replyManager.getQuoteMessage().id
			)
			{
				return;
			}

			this.replyManager.startQuotingMessage(message);
		}

		/**
		 * @private
		 */
		onReadyToReply()
		{
			Haptics.impactMedium();
		}

		/**
		 * @private
		 */
		onQuoteTap(message)
		{
			const messageId = message.id;
			const modelMessage = this.store.getters['messagesModel/getById'](messageId);
			if (!modelMessage)
			{
				return;
			}

			this.view.scrollToMessageById(messageId, true, () => {
				// TODO: Highlight message
			}, AfterScrollMessagePosition.center);
		}

		/**
		 * @private
		 */
		onMessageQuoteTap()
		{
			this.showComingSoonNotification();
		}

		/**
		 * @private
		 */
		onCancelReply()
		{
			if (this.replyManager.isEditInProcess)
			{
				this.replyManager.finishEditingMessage();

				return;
			}

			if (this.replyManager.isQuoteInProcess)
			{
				this.replyManager.finishQuotingMessage();
			}
		}

		loadTopPage()
		{
			this.messageService.loadHistory();
		}

		loadBottomPage()
		{
			this.messageService.loadUnread();
		}

		/**
		 * @param {object} mutation
		 * @param {object} mutation.payload
		 * @param {Array<MessagesModelState>} mutation.payload.data.messageList
		 */
		drawMessageList(mutation)
		{
			const messageList = clone(mutation.payload.data.messageList);

			const currentDialogMessageList = [];
			messageList.forEach((message) => {
				if (message.chatId !== this.getChatId())
				{
					return;
				}

				const validateQuoteMessage = this.validateQuote(message);
				currentDialogMessageList.push({
					...validateQuoteMessage,
					reactions: this.store.getters['messagesModel/reactionsModel/getByMessageId'](message.id),
				});
			});

			if (!Type.isArrayFilled(currentDialogMessageList))
			{
				return;
			}

			if (mutation.type === 'messagesModel/setChatCollection')
			{
				this.removeStatusField();
			}

			this.messageRenderer.render(currentDialogMessageList);
		}

		/**
		 * @desc Check and validate text message quote
		 * @param {MessagesModelState} message
		 * @return {MessagesModelState} validateQuoteMessage
		 */
		validateQuote(message)
		{
			const validateQuoteMessage = message;
			if (this.replyManager.isHasQuote(validateQuoteMessage))
			{
				const quoteText = this.replyManager.getQuoteText({ id: message.params.replyId });
				if (Type.isStringFilled(quoteText))
				{
					validateQuoteMessage.text = `${quoteText}${message.text}`;
				}
			}

			return validateQuoteMessage;
		}

		/**
		 * @desc The method routes the dialog handlers by the name of the action from the store
		 * @param {Object} mutation
		 * @return {Boolean}
		 * @private
		 */
		dialogUpdateHandlerRouter(mutation)
		{
			const isDialogType = mutation.type.startsWith('dialoguesModel');
			if (isDialogType && mutation.payload.data.dialogId)
			{
				const currentDialogId = String(this.getDialogId());
				const mutationDialogId = String(mutation.payload.data.dialogId);
				if (mutationDialogId !== currentDialogId)
				{
					return false;
				}
			}

			switch (mutation.payload.actionName)
			{
				case 'setLastMessageViews':
				case 'incrementLastMessageViews':
					this.drawStatusField();
					break;
				default:
					this.redrawHeader(mutation);
					break;
			}

			return true;
		}

		/**
		 * @desc Create new status field by current dialog data and draw it in view
		 * @return void
		 */
		drawStatusField()
		{
			const dialogModelState = this.getDialog();
			if (!dialogModelState.lastMessageId
				|| !dialogModelState.lastMessageViews
				|| !dialogModelState.lastMessageViews.firstViewer)
			{
				return;
			}

			const message = this.store.getters['messagesModel/getById'](dialogModelState.lastMessageId);
			if (!message)
			{
				return;
			}
			const isGroupDialog = DialogHelper.isDialogId(this.getDialogId());
			const statusField = new StatusField({
				lastMessageViews: dialogModelState.lastMessageViews,
				isGroupDialog,
			});

			if (this.messageService !== null)
			{
				this.messageService.createUsersReadCache();
			}
			this.view.setStatusField(statusField.statusType, statusField.statusText);
		}

		/**
		 * @desc Remove status field in view
		 * @return void
		 */
		removeStatusField()
		{
			this.view.clearStatusField();
		}

		/**
		 * @param {object} mutation
		 * @param {string} mutation.payload.actionName
		 * @param {ReactionsModelState[]} mutation.payload.data.reactionList
		 */
		redrawReactionMessages(mutation)
		{
			if (mutation.payload.actionName !== 'setFromPullEvent')
			{
				return;
			}

			/** @type {Array<MessagesModelState>} */
			const result = [];
			mutation.payload.data.reactionList.forEach((reaction) => {
				const messageId = reaction.messageId;

				const message = this.store.getters['messagesModel/getById'](messageId);
				if (!message || message.chatId !== this.getChatId())
				{
					return;
				}

				result.push(message);
			});

			if (result.length === 0)
			{
				return;
			}

			this.drawMessageList({
				type: mutation.type,
				payload: {
					data: {
						messageList: result,
					},
				},
			});
		}

		/**
		 * @param {ReactionsModelState} mutation.payload.data.reaction
		 */
		redrawReactionMessage(mutation)
		{
			const message = this.store.getters['messagesModel/getById'](mutation.payload.data.reaction.messageId);

			this.drawMessageList({
				type: mutation.type,
				payload: {
					data: {
						messageList: [message],
					},
				},
			});
		}

		/**
		 *
		 * @param {ReactionType} reactionId
		 * @param {Message} message
		 */
		openReactionViewer(reactionId, message)
		{
			ReactionViewerController.open(message.id, reactionId, this.view.ui);
		}

		/**
		 * @desc The method routes the handlers by the name of the action from the store
		 * @param {Object} mutation
		 * @return {Boolean}
		 * @private
		 */
		messageUpdateHandlerRouter(mutation)
		{
			if (mutation.payload.actionName === 'updateLoadTextProgress')
			{
				this.updateProgressFileHandler(mutation);
			}
			else
			{
				this.redrawMessage(mutation);
			}
		}

		redrawMessage(mutation)
		{
			let messageId = mutation.payload.data.id;
			if (Uuid.isV4(messageId))
			{
				messageId = mutation.payload.data.fields.id;
			}

			const message = this.store.getters['messagesModel/getById'](messageId);
			if (!message || message.chatId !== this.getChatId())
			{
				return;
			}
			const cloneMessage = clone(message);
			const validateQuoteMessage = this.validateQuote(cloneMessage);
			this.messageRenderer.render([validateQuoteMessage]);
		}

		/**
		 * @desc Method is calling view -> native method for update load text progress in message
		 * @param {Object} mutation
		 * @return {Boolean}
		 * @private
		 */
		updateProgressFileHandler(mutation)
		{
			const messageId = mutation.payload.data.id;
			const message = this.store.getters['messagesModel/getById'](messageId);
			if (!message || message.chatId !== this.getChatId())
			{
				return false;
			}

			const file = this.store.getters['filesModel/getById'](message.files[0]); // TODO if there is more than one file in the message?
			if (!file)
			{
				return false;
			}

			const data = {
				messageId,
				currentBytes: file.uploadData.byteSent,
				totalBytes: file.uploadData.byteTotal,
				textProgress: message.loadText,
			};

			return this.view.updateUploadProgressByMessageId(data);
		}

		deleteMessage(mutation)
		{
			const messageId = mutation.payload.data.id;

			this.messageRenderer.delete([messageId]);
		}

		/**
		 * @private
		 * @param {Object} mutation
		 * @param {MessengerStoreMutation} mutation.type
		 * @param {any} mutation.payload
		 */
		redrawHeader(mutation)
		{
			if (mutation.type === 'usersModel/set')
			{
				const opponent = mutation.payload.data.userList
					.find((user) => user.id.toString() === this.getDialogId().toString())
				;
				if (opponent)
				{
					this.headerTitle.renderTitle();
				}
			}
			else
			{
				const dialogId = mutation.payload.data.dialogId;
				if (dialogId === this.getDialogId() || String(dialogId) === this.getDialogId())
				{
					this.headerTitle.renderTitle();
				}
			}
		}

		/**
		 * @private
		 * @param {Object} mutation
		 * @param {MessengerStoreMutation} mutation.type
		 * @param {DialoguesModelState} mutation.payload.data
		 */
		updateMessageCounter(mutation)
		{
			if (
				String(mutation.payload.data.dialogId) !== String(this.getDialogId())
				|| !Type.isNumber(mutation.payload.data.fields.counter)
			)
			{
				return;
			}

			const counter = mutation.payload.data.fields.counter;

			this.view.setNewMessageCounter(counter);
		}

		deleteCurrentDialog()
		{
			this.store.dispatch('recentModel/delete', { id: this.getDialogId() })
				.then(() => Counters.update())
			;
			this.store.dispatch('dialoguesModel/delete', { id: this.getDialogId() });
			this.store.dispatch('usersModel/delete', { id: this.getDialogId() });
		}

		openWebDialog(options)
		{
			return new Promise((resolve) => {
				if (Type.isStringFilled(options.userCode))
				{
					WebDialog.getOpenlineDialogByUserCode(options.userCode).then((dialog) => {
						options.dialogId = dialog.dialog_id;
						WebDialog.open(options);
					});

					return;
				}

				WebDialog.open(options);
				resolve();
			});
		}

		static getOpenDialogParams(options = {})
		{
			const {
				dialogId,
				dialogTitleParams,
			} = options;

			return WebDialog.getOpenDialogParams(dialogId, dialogTitleParams);
		}

		static getOpenLineParams(options = {})
		{
			const {
				userCode,
				dialogTitleParams,
			} = options;

			return WebDialog.getOpenLineParams(userCode, dialogTitleParams);
		}

		createAudioCall()
		{
			Calls.createAudioCall(this.getDialogId());
		}

		createVideoCall()
		{
			Calls.createVideoCall(this.getDialogId());
		}

		showComingSoonNotification()
		{
			InAppNotifier.showNotification({
				title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_COMING_SOON'),
				time: 1,
				backgroundColor: '#E6000000',
			});
		}

		/**
		 * @desc Call dialog rest request writing message method
		 * @param {string} dialogId
		 */
		startWriting(dialogId = this.getDialogId())
		{
			this.holdWritingTimerId = setTimeout(() => {
				DialogRest.writingMessage(dialogId)
					.then((resolve) => resolve)
					.catch((err) => Logger.log('DialogRest.writingMessage.response', err));
			}, this.HOLD_WRITING_REST);
		}

		/**
		 * @desc Call dialog rest request record voice message method
		 * @param {string} dialogId
		 */
		startRecordVoiceMessage(dialogId = this.getDialogId())
		{
			this.holdWritingTimerId = setTimeout(() => {
				DialogRest.recordVoiceMessage(dialogId)
					.then((resolve) => resolve)
					.catch((err) => Logger.log('DialogRest.writingMessage.response', err));
			}, this.HOLD_WRITING_REST);
		}

		/**
		 * @desc Method is canceling timeout with request rest 'im.dialog.writing'
		 * @void
		 * @private
		 */
		cancelWritingRequest()
		{
			if (Type.isNumber(this.holdWritingTimerId))
			{
				clearTimeout(this.holdWritingTimerId);
				this.holdWritingTimerId = null;
			}
		}
	}

	module.exports = { Dialog };
});
