/* eslint-disable es/no-nullish-coalescing-operators */

/**
 * @module im/messenger/controller/dialog/chat/dialog
 */
jn.define('im/messenger/controller/dialog/chat/dialog', (require, exports, module) => {
	/* global include */
	/* region external import */

	/* region native import */
	include('InAppNotifier');
	// const { Filesystem, utils } = require('native/filesystem');

	/* endregion native import */

	/* region mobile import */
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const { inAppUrl } = require('in-app-url');
	// const { NotifyManager } = require('notify-manager');
	const { clone } = require('utils/object');
	// const { withCurrentDomain } = require('utils/url');
	const { Uuid } = require('utils/uuid');
	const { throttle, debounce } = require('utils/function');

	/* endregion mobile import */

	/* region immobile import */
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const {
		EventType,
		// FeatureFlag,
		ReactionType,
		FileType,
		ErrorType,
		ErrorCode,
		SubTitleIconType,
		DialogType,
		UserRole,
		OwnMessageStatus,
		ComponentCode,
	} = require('im/messenger/const');

	const { defaultUserIcon, ReactionAssets } = require('im/messenger/assets/common');

	const { RecentConverter, DialogConverter } = require('im/messenger/lib/converter');
	const { Counters } = require('im/messenger/lib/counters');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ObjectUtils } = require('im/messenger/lib/utils');
	const { Feature } = require('im/messenger/lib/feature');
	const { Calls } = require('im/messenger/lib/integration/immobile/calls');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');

	const { StatusField } = require('im/messenger/lib/element');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { MessageRest, DialogRest	} = require('im/messenger/provider/rest');
	const { ChatService, MessageService, DiskService } = require('im/messenger/provider/service');

	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { FileDownloadMenu } = require('im/messenger/controller/file-download-menu');
	const { ReactionViewerController } = require('im/messenger/controller/reaction-viewer');

	const { DialogView, AfterScrollMessagePosition } = require('im/messenger/view/dialog');

	/* endregion immobile import */
	/* endregion external import */

	/* region lib import */
	const { MentionManager } = require('im/messenger/controller/dialog/lib/mention');
	const { DraftManager } = require('im/messenger/controller/dialog/lib/draft-manager');
	const { HeaderButtons, HeaderTitle } = require('im/messenger/controller/dialog/lib/header');
	const { ReplyManager } = require('im/messenger/controller/dialog/lib/reply-manager');
	const { ScrollManager } = require('im/messenger/controller/dialog/lib/scroll-manager');
	const { MessageRenderer } = require('im/messenger/controller/dialog/lib/message-renderer');
	const { AudioMessagePlayer } = require('im/messenger/controller/dialog/lib/audio-player');
	const { MessageAvatarMenu } = require('im/messenger/controller/dialog/lib/message-avatar-menu');
	const { WebDialog } = require('im/messenger/controller/dialog/lib/web');
	const { DialogLocator } = require('im/messenger/controller/dialog/lib/locator');
	const { MessageMenu } = require('im/messenger/controller/dialog/lib/message-menu');
	const { PinManager } = require('im/messenger/controller/dialog/lib/pin');

	/* endregion lib import */

	/* region internal import */

	/* endregion internal import */

	const logger = LoggerManager.getInstance().getLogger('dialog--dialog');

	/**
	 * @class Dialog
	 */
	class Dialog
	{
		constructor()
		{
			/**
			 * @protected
			 * @type {MessengerCoreStore}
			 */
			this.store = serviceLocator.get('core').getStore();
			/**
			 * @protected
			 * @type {MessengerCoreStoreManager}
			 */
			this.storeManager = serviceLocator.get('core').getStoreManager();
			/**
			 * @protected
			 * @type {DialogId}
			 */
			this.dialogId = 0;

			this.locator = new DialogLocator();

			/**
			 * @private
			 * @type {DialogRepository}
			 */
			this.dialogRepository = serviceLocator.get('core').getRepository().dialog;

			/**
			 * @private
			 * @type {MessageRepository}
			 */
			this.messageRepository = serviceLocator.get('core').getRepository().message;

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
			 * @protected
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
			 * @type {ScrollManager}
			 */
			this.scrollManager = null;
			/**
			 * @private
			 * @type {DraftManager}
			 */
			this.draftManager = null;
			/**
			 * @protected
			 * @type {MentionManager}
			 */
			this.mentionManager = null;

			/**
			 * @protected
			 * @type {PinManager}
			 */
			this.pinManager = null;
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

			this.firstDbPagePromise = null;

			this.bindMethods();
			this.dialogDeleteHandler = () => {};

			this.startWriting = throttle(this.startWriting, 5000, this);
			this.startRecordVoiceMessage = throttle(this.startRecordVoiceMessage, 3000, this);
			this.joinUserChat = throttle(this.joinUserChat, 5000, this);

			this.locator
				.add('disk-service', this.diskService)
				.add('store', this.store)
			;
		}

		bindMethods() {
			/** @private */
			this.closeDialogHandler = this.onDialogClose.bind(this);
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
			/** @protected */
			this.statusFieldTapHandler = this.onStatusFieldTap.bind(this);
			/** @private */
			this.chatJoinButtonTapHandler = this.onChatJoinButtonTapHandler.bind(this);
			/** @private */
			this.messageAvatarTapHandler = this.onMessageAvatarTap.bind(this);
			/** @private */
			this.messageAvatarLongTapHandler = this.onMessageAvatarLongTap.bind(this);
			/** @private */
			this.messageQuoteTapHandler = this.onMessageQuoteTap.bind(this);
			/** @private */
			// this.messageLongTapHandler = this.onMessageLongTap.bind(this);
			/** @private */
			this.messageDoubleTapHandler = this.onMessageDoubleTap.bind(this);
			/** @private */
			// this.messageMenuReactionTapHandler = this.setReaction.bind(this);
			// this.messageMenuActionTapHandler = this.onMessageMenuAction.bind(this);
			/** @private */
			this.messageFileDownloadTapHandler = this.onMessageFileDownloadTap.bind(this);
			/** @private */
			this.messageFileUploadCancelTapHandler = this.onMessageFileUploadCancelTap.bind(this);
			/** @private */
			this.reactionTapHandler = debounce(this.setReaction.bind(this), 300);
			/** @private */
			this.imageTapHandler = this.onImageTap.bind(this);
			/** @private */
			this.audioTapHandler = this.onAudioTap.bind(this);
			/** @private */
			this.videoTapHandler = this.onVideoTap.bind(this);
			/** @private */
			this.fileTapHandler = this.onFileTap.bind(this);
			/** @private */
			this.richPreviewTapHandler = this.onRichPreviewTap.bind(this);
			/** @private */
			this.richNameTapHandler = this.onRichNameTap.bind(this);
			/** @private */
			this.richCancelTapHandler = this.onRichCancelTap.bind(this);
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
			this.hiddenHandler = this.onHidden.bind(this);
			/** @private */
			this.showHandler = this.onShow.bind(this);
			/** @private */
			this.setChatCollectionHandler = this.drawMessageList.bind(this);
			/** @private */
			this.messageUpdateHandler = this.messageUpdateHandlerRouter.bind(this);
			this.updateReactionHandler = this.redrawReactionMessages.bind(this);
			this.deleteHandler = this.deleteMessage.bind(this);
			/** @private */
			this.dialogUpdateHandlerRouter = this.dialogUpdateHandlerRouter.bind(this);
			/** @private */
			this.applicationStatusHandler = this.applicationStatusHandler.bind(this);
			/** @private */
			this.updateMessageCounterHandler = this.updateMessageCounter.bind(this);
			/** @private */
			this.redrawReactionMessageHandler = this.redrawReactionMessage.bind(this);
		}

		/** @protected */
		subscribeViewEvents()
		{
			this.view
				.on(EventType.dialog.attachTap, this.attachTapHandler)
				.on(EventType.dialog.resend, this.resendHandler)
				.on(EventType.dialog.loadTopPage, this.loadTopPageHandler)
				.on(EventType.dialog.loadBottomPage, this.loadBottomPageHandler)
				.on(EventType.dialog.scrollBegin, this.scrollBeginHandler)
				.on(EventType.dialog.scrollEnd, this.scrollEndHandler)
				.on(EventType.dialog.reply, this.replyHandler)
				.on(EventType.dialog.readyToReply, this.readyToReplyHandler)
				.on(EventType.dialog.visibleMessagesChanged, this.visibleMessagesChangedHandler)
				.on(EventType.dialog.messageRead, this.messageReadHandler)
				.on(EventType.dialog.scrollToNewMessages, this.scrollToNewMessagesHandler)
				.on(EventType.dialog.playbackCompleted, this.playbackCompletedHandler)
				.on(EventType.dialog.urlTap, this.urlTapHandler)
				.on(EventType.dialog.statusFieldTap, this.statusFieldTapHandler)
				.on(EventType.dialog.chatJoinButtonTap, this.chatJoinButtonTapHandler)
				.on(EventType.dialog.audioRecordingStart, this.audioRecordingStartHandler)
				.on(EventType.dialog.audioRecordingFinish, this.audioRecordingFinishHandler)
				.on(EventType.dialog.submitAudio, this.submitAudioHandler)
				.on(EventType.dialog.mentionTap, this.mentionTapHandler)
				.on(EventType.dialog.messageAvatarTap, this.messageAvatarTapHandler)
				.on(EventType.dialog.messageAvatarLongTap, this.messageAvatarLongTapHandler)
				.on(EventType.dialog.messageQuoteTap, this.messageQuoteTapHandler)
				// .on(EventType.dialog.messageLongTap, this.messageLongTapHandler)
				.on(EventType.dialog.messageDoubleTap, this.messageDoubleTapHandler)
				// .on(EventType.dialog.messageMenuReactionTap, this.messageMenuReactionTapHandler)
				// .on(EventType.dialog.messageMenuActionTap, this.messageMenuActionTapHandler)
				.on(EventType.dialog.messageFileDownloadTap, this.messageFileDownloadTapHandler)
				.on(EventType.dialog.messageFileUploadCancelTap, this.messageFileUploadCancelTapHandler)
				.on(EventType.dialog.reactionTap, this.reactionTapHandler)
				.on(EventType.dialog.reactionLongTap, this.reactionLongTapHandler)
				.on(EventType.view.barButtonTap, this.headerButtons.tapHandler)
				.on(EventType.view.close, this.closeHandler)
				.on(EventType.view.hidden, this.hiddenHandler)
				.on(EventType.view.show, this.showHandler)
				.on(EventType.dialog.like, this.onLikeHandler)
				.on(EventType.dialog.audioTap, this.audioTapHandler)
				.on(EventType.dialog.imageTap, this.imageTapHandler)
				.on(EventType.dialog.fileTap, this.fileTapHandler)
				.on(EventType.dialog.videoTap, this.videoTapHandler)
				.on(EventType.dialog.richNameTap, this.richNameTapHandler)
				.on(EventType.dialog.richPreviewTap, this.richPreviewTapHandler)
				.on(EventType.dialog.richCancelTap, this.richCancelTapHandler)
			;

			this.view.textField.on(EventType.dialog.textField.submit, this.submitHandler);
			this.view.textField.on(EventType.dialog.textField.quoteTap, this.quoteTapHandler);
			this.view.textField.on(EventType.dialog.textField.changeText, this.changeTextHandler);
			this.view.textField.on(EventType.dialog.textField.cancelQuote, this.cancelReplyHandler);

			this.view.ui.on('titleClick', () => {
				MessengerEmitter.emit(EventType.messenger.openSidebar, {
					dialogId: this.dialogId,
				});
			});

			this.messageMenuComponent.subscribeEvents();
			this.pinManager?.subscribeViewEvents();
		}

		/** @private */
		unsubscribeViewEvents()
		{
			this.mentionManager?.unsubscribeEvents();
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
				.on('applicationModel/setStatus', this.applicationStatusHandler)
			;

			this.pinManager?.subscribeStoreEvents();
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
				.off('applicationModel/setStatus', this.applicationStatusHandler)
			;

			this.pinManager?.unsubscribeStoreEvents();
		}

		/** @private */
		subscribeExternalEvents()
		{
			this.scrollManager.subscribeEvents();
			BX.addCustomEvent(EventType.dialog.external.close, this.closeDialogHandler);
		}

		/** @private */
		unsubscribeExternalEvents()
		{
			this.scrollManager.unsubscribeEvents();
			BX.removeCustomEvent(EventType.dialog.external.close, this.closeDialogHandler);
		}

		/** @protected */
		initManagers()
		{
			/**
			 * @private
			 * @type {ScrollManager}
			 */
			this.scrollManager = new ScrollManager({
				view: this.view,
				dialogId: this.getDialogId(),
			});

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
				initWithExternalForward: Boolean(this.forwardMessageId),
			});

			this.initMentionManager();

			this.replyManager.setDraftManager(this.draftManager);
			if (this.forwardMessageId)
			{
				this.replyManager.startForwardingMessage(this.forwardMessageId);
			}

			this.locator
				.add('reply-manager', this.replyManager)
				.add('mention-manager', this.mentionManager)
			;
		}

		initMentionManager()
		{
			/**
			 * @private
			 * @type {MentionManager}
			 */
			this.mentionManager = new MentionManager({
				view: this.view,
				dialogId: this.getDialogId(),
			});
		}

		initComponents()
		{
			this.messageMenuComponent = new MessageMenu({
				serviceLocator: this.locator,
				dialogId: this.getDialogId(),
			});
		}

		async open(options)
		{
			const {
				dialogId,
				// messageId,
				// withMessageHighlight,
				dialogTitleParams,
				forwardMessageId,
			} = options;

			this.dialogId = dialogId;
			// this.contextMessageId = messageId ?? null;
			// this.withMessageHighlight = withMessageHighlight ?? false;

			await this.store.dispatch('applicationModel/openDialogId', dialogId);
			await this.store.dispatch('recentModel/like', {
				id: dialogId,
				liked: false,
			});

			const isOpenlinesChat = dialogTitleParams && dialogTitleParams.chatType === 'lines';
			if (
				!Feature.isChatV2Enabled
				|| isOpenlinesChat
				|| this.isBot()
			)
			{
				this.openWebDialog(options);

				return;
			}

			if (forwardMessageId)
			{
				this.forwardMessageId = forwardMessageId;
			}

			this.pinManager = new PinManager({
				dialogId: this.dialogId,
				locator: this.locator,
			});

			const hasDialog = await this.loadDialogFromDb();
			if (hasDialog)
			{
				this.messageService = new MessageService({
					store: this.store,
					chatId: this.getChatId(),
				});
			}

			this.firstDbPagePromise = this.loadHistoryMessagesFromDb();

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

		onDialogClose({ dialogId })
		{
			if (String(this.getDialogId()) === String(dialogId))
			{
				this.view.back();

				logger.info('Dialog.onDialogClose: ', dialogId, ' complete');
			}
		}

		openLine(options)
		{
			this.openWebDialog(options);
		}

		/**
		 *
		 * @return {DialogId}
		 */
		getDialogId()
		{
			return this.dialogId;
		}

		/**
		 * @desc Return check is bot by current user dialog ( if is not group dialog )
		 * @return {boolean}
		 */
		isBot()
		{
			let isBot = false;
			const isGroupDialog = DialogHelper.isDialogId(this.dialogId);
			if (!isGroupDialog)
			{
				const userModel = this.store.getters['usersModel/getById'](this.dialogId);
				if (!Type.isUndefined(userModel))
				{
					isBot = userModel.bot;
				}
			}

			return isBot;
		}

		/**
		 * @return {DialoguesModelState|{}}
		 */
		getDialog()
		{
			const dialog = this.store.getters['dialoguesModel/getById'](this.dialogId);

			return dialog || {};
		}

		/**
		 * @desc check is canal
		 * @return {boolean}
		 */
		isCanal()
		{
			const dialog = this.getDialog();

			return dialog.type === DialogType.open;
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
				[ReactionType.like]: ReactionAssets.getLottieUrl(ReactionType.like),
				[ReactionType.kiss]: ReactionAssets.getLottieUrl(ReactionType.kiss),
				[ReactionType.cry]: ReactionAssets.getLottieUrl(ReactionType.cry),
				[ReactionType.laugh]: ReactionAssets.getLottieUrl(ReactionType.laugh),
				[ReactionType.angry]: ReactionAssets.getLottieUrl(ReactionType.angry),
				[ReactionType.facepalm]: ReactionAssets.getLottieUrl(ReactionType.facepalm),
				[ReactionType.wonder]: ReactionAssets.getLottieUrl(ReactionType.wonder),
			};
		}

		/** @private */
		getMessageReactionsSvg()
		{
			return {
				[ReactionType.like]: ReactionAssets.getSvgUrl(ReactionType.like),
				[ReactionType.kiss]: ReactionAssets.getSvgUrl(ReactionType.kiss),
				[ReactionType.cry]: ReactionAssets.getSvgUrl(ReactionType.cry),
				[ReactionType.laugh]: ReactionAssets.getSvgUrl(ReactionType.laugh),
				[ReactionType.angry]: ReactionAssets.getSvgUrl(ReactionType.angry),
				[ReactionType.facepalm]: ReactionAssets.getSvgUrl(ReactionType.facepalm),
				[ReactionType.wonder]: ReactionAssets.getSvgUrl(ReactionType.wonder),
			};
		}

		/**
		 * @private
		 * @return {{imageUrl?: string, defaultIconSvg?: string}}
		 */
		getCurrentUserAvatarForReactions()
		{
			const currentUser = this.store.getters['usersModel/getById'](MessengerParams.getUserId());
			if (!currentUser)
			{
				return {
					defaultIconSvg: defaultUserIcon(),
				};
			}

			if (currentUser && currentUser.avatar !== '')
			{
				return {
					imageUrl: currentUser.avatar,
				};
			}

			return {
				defaultIconSvg: defaultUserIcon(currentUser.color),
			};
		}

		getDialogType()
		{
			return 'messenger';
		}

		checkCanHaveAttachments()
		{
			return true;
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
						svg: this.getMessageReactionsSvg(),
						currentUserAvatar: this.getCurrentUserAvatarForReactions(),
					},
					autoplayVideo: Feature.isAutoplayVideoEnabled,
					code: 'im.dialog',
					dialogType: this.getDialogType(),
					canHaveAttachments: this.checkCanHaveAttachments(),
					pinPanel: this.pinManager?.getPinPanelParams(),
				},
			)
				.then(this.onWidgetReady.bind(this))
				.catch((error) => logger.error(error))
			;
		}

		/**
		 * @private
		 * @param widget
		 */
		async onWidgetReady(widget)
		{
			this.createView(widget);
			this.initManagers();
			this.initComponents();
			this.subscribeViewEvents();
			this.subscribeStoreEvents();
			this.subscribeExternalEvents();

			const hasSavedMessages = await this.loadSavedMessages();
			if (!hasSavedMessages)
			{
				logger.info(`Dialog: dialogId: ${this.dialogId} first load`);

				const recentMessage = DialogConverter.createRecentMessage(this.getDialogId());
				if (
					recentMessage
					&& this.getDialog().type !== DialogType.copilot
					// TODO delete fix after enable local storage in copilot component
				)
				{
					this.view.ui.setMessages([recentMessage]);
				}
				else
				{
					this.view.showMessageListLoader();
				}
			}

			this.loadChatWithMessages(this.dialogId)
				.then(() => this.handleLoadChatWithMessages())
				.catch((error) => this.handleLoadChatWithMessagesError(error))
			;
		}

		loadChatWithMessages()
		{
			// if (this.contextMessageId)
			// {
			// 	return this.chatService.loadChatWithContext(this.dialogId, this.contextMessageId);
			// }

			return this.chatService.loadChatWithMessages(this.dialogId);
		}

		handleLoadChatWithMessages()
		{
			if (!Type.isArrayFilled(this.getModelMessages()))
			{
				this.view.showWelcomeScreen();
			}

			this.view.hideMessageListLoader();
			this.view.setReadingMessageId(this.getDialog().lastReadId);
			this.messageService = new MessageService({
				store: this.store,
				chatId: this.getChatId(),
			});

			this.locator.add('message-service', this.messageService);

			const counter = this.getDialog().counter;
			if (counter > 0)
			{
				this.view.setNewMessageCounter(counter);
			}

			this.headerTitle.renderTitle();
			this.headerButtons.render(this.view);
			this.drawStatusField();
			this.resendMessages();
		}

		handleLoadChatWithMessagesError(loadChatError)
		{
			logger.error('Dialog.loadMessages error: ', loadChatError);

			let errors = loadChatError;
			if (!Type.isArray(loadChatError))
			{
				errors = [loadChatError];
			}

			for (const error of errors)
			{
				if (error.code === ErrorType.dialog.accessDenied)
				{
					MessengerEmitter.emit(
						EventType.messenger.dialogAccessError,
						{ dialogId: this.getDialogId() },
						ComponentCode.imMessenger,
					);
					this.view.back();

					return;
				}
			}

			throw loadChatError;
		}

		async loadSavedMessages()
		{
			await this.firstDbPagePromise;

			if (!Feature.isLocalStorageEnabled && !this.getDialog().inited)
			{
				return false;
			}

			const savedMessages = this.getModelMessages();
			if (Type.isArrayFilled(savedMessages))
			{
				logger.info(`Dialog: dialogId: ${this.dialogId} rerender`);
				await this.store.dispatch('messagesModel/forceUpdateByChatId', { chatId: this.getChatId() });
				this.drawStatusField();

				return true;
			}

			return false;
		}

		getModelMessages()
		{
			if (this.getChatId() > 0)
			{
				return this.store.getters['messagesModel/getByChatId'](this.getChatId());
			}

			return [];
		}

		async loadDialogFromDb()
		{
			const dialog = await this.dialogRepository.getByDialogId(this.getDialogId());

			logger.log('Dialog.loadDialogFromDb', dialog);
			if (dialog)
			{
				await this.store.dispatch('dialoguesModel/setFromLocalDatabase', dialog);

				return true;
			}

			return false;
		}

		async loadHistoryMessagesFromDb()
		{
			const chatId = this.getChatId();
			if (!chatId)
			{
				logger.warn(`Dialog.loadHistoryMessagesFromDb: we don't have a chatId for dialog ${this.getDialogId()}`);

				return;
			}

			const lastReadId = this.getDialog().lastReadId ?? 0;
			logger.info('Dialog.loadHistoryMessagesFromDb lastReadId', lastReadId);
			let result = {};
			if (lastReadId === 0)
			{
				result = await this.messageRepository.getList({
					chatId,
					limit: 51,
					offset: 0,
				});

				logger.error(`
					Dialog.loadHistoryMessagesFromDb
					received the latest messages because where is no lastReadId for dialog ${this.getDialogId()}
				`, this.getDialog(), result);
			}
			else
			{
				const topPage = await this.messageRepository.getList({
					chatId,
					lastId: lastReadId,
					limit: 50,
					direction: 'top',
				});

				const lastReadMessage = await this.messageRepository.getList({
					chatId,
					lastId: lastReadId,
					limit: 1,
				});

				const bottomPage = await this.messageRepository.getList({
					chatId,
					lastId: lastReadId,
					limit: 50,
					direction: 'bottom',
					order: 'asc',
				});

				result.messageList = [
					...topPage.messageList,
					...lastReadMessage.messageList,
					...bottomPage.messageList.reverse(),
				];
				result.userList = [
					...topPage.userList,
					...lastReadMessage.userList,
					...bottomPage.userList,
				];
				result.fileList = [
					...topPage.fileList,
					...lastReadMessage.fileList,
					...bottomPage.fileList,
				];
				result.reactionList = [
					...topPage.reactionList,
					...lastReadMessage.reactionList,
					...bottomPage.reactionList,
				];

				logger.info('Dialog.loadHistoryMessagesFromDb result by lastReadId', result);
			}

			if (Type.isArrayFilled(result.userList))
			{
				await this.store.dispatch('usersModel/setFromLocalDatabase', result.userList);
			}

			if (Type.isArrayFilled(result.fileList))
			{
				await this.store.dispatch('filesModel/setFromLocalDatabase', result.fileList);
			}

			if (Type.isArrayFilled(result.reactionList))
			{
				await this.store.dispatch('messagesModel/reactionsModel/setFromLocalDatabase', {
					reactions: result.reactionList,
				});
			}

			if (Type.isArrayFilled(result.messageList))
			{
				await this.store.dispatch('messagesModel/setFromLocalDatabase', {
					messages: result.messageList,
					clearCollection: true,
				});
			}

			await this.loadPinMessagesFromDb();
		}

		async loadPinMessagesFromDb()
		{
			const result = await serviceLocator.get('core').getRepository().pinMessage.getByChatId(this.getChatId());
			logger.info('Dialog.loadPinMessagesFromDb result', result);

			if (Type.isArrayFilled(result.users))
			{
				await this.store.dispatch('usersModel/setFromLocalDatabase', result.users);
			}

			if (Type.isArrayFilled(result.files))
			{
				await this.store.dispatch('filesModel/setFromLocalDatabase', result.files);
			}

			if (Type.isArrayFilled(result.pins))
			{
				await this.store.dispatch('messagesModel/pinModel/setFromLocalDatabase', {
					pins: result.pins,
					messages: result.messages,
				});
			}
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

			this.view.setInputPlaceholder(Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_INPUT_PLACEHOLDER_TEXT_V2'));

			this.manageTextField(true);

			this.locator.add('view', this.view);
		}

		/**
		 * @desc Method manage text field
		 * @param {boolean} [isFirstCall=false]
		 * @private
		 */
		manageTextField(isFirstCall = false)
		{
			const isNeedHide = this.isNeedHideTextFieldByPermission();

			if (isNeedHide)
			{
				this.view.hideTextField(false);

				const dialogModel = this.getDialog();
				if (this.isCanal() && dialogModel.role === UserRole.guest && !isFirstCall)
				{
					this.view.showChatJoinButton(Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_JOIN_BUTTON_TEXT'));
				}
			}
			else
			{
				this.view.showTextField(false);
				this.view.hideChatJoinButton();
			}
		}

		/**
		 * @desc Method check permission user for use text field
		 * @return {boolean}
		 * @private
		 */
		isNeedHideTextFieldByPermission()
		{
			const isCanPost = ChatPermission.isCanPost(this.getDialog());
			const isGroupDialog = DialogHelper.isDialogId(this.getDialogId());
			if (!isCanPost && isGroupDialog)
			{
				return true;
			}

			return false;
		}

		/* region event handlers */
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

			this.store.dispatch('applicationModel/closeDialogId', dialogId);
		}

		onHidden()
		{
			this.mentionManager?.onDialogHidden();
		}

		onShow()
		{
			this.mentionManager?.onDialogShow();
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

			if (this.isCanal() && this.getDialog().role === UserRole.guest)
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
		onPlaybackCompleted()
		{
			this.audioMessagePlayer.playNext();
		}

		onUrlTap(url)
		{
			logger.log('Dialog.onUrlTap: ', url);

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
		 * @param {{entityId: string, entityType: string} || string} params
		 */
		onMentionTap(params)
		{
			if (typeof params === 'string')
			{
				MessengerEmitter.emit(
					EventType.messenger.openDialog,
					{ dialogId: params.toString() },
					ComponentCode.imMessenger,
				);

				return;
			}

			if (params.entityType === 'chat')
			{
				const dialogId = DialogHelper.isDialogId(params.entityId) ? params.entityId : `chat${params.entityId}`;

				MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId }, ComponentCode.imMessenger);

				return;
			}
			MessengerEmitter.emit(
				EventType.messenger.openDialog,
				{ dialogId: params.entityId.toString() },
				ComponentCode.imMessenger,
			);
		}

		/**
		 * @param {string} imageId
		 * @param {string} messageId
		 */
		onImageTap(imageId, messageId, localUrl = null)
		{
			logger.log('Dialog.onImageTap', imageId, messageId, localUrl);

			if (Type.isStringFilled(localUrl))
			{
				viewer.openImage(localUrl);

				return;
			}

			const messages = this.view.getImageMessages();

			const imagesCollection = [];
			messages.forEach((message) => {
				const fileModel = this.store.getters['filesModel/getById'](message.image.id);
				if (!Type.isUndefined(fileModel))
				{
					imagesCollection.push({
						url: fileModel.urlShow,
						previewUrl: fileModel.urlPreview,
						default: message.id === messageId,
						description: fileModel.name,
					});
				}
			});

			viewer.openImageCollection(imagesCollection);
		}

		/**
		 *
		 * @param audioId
		 * @param messageId
		 * @param isPlaying
		 * @param playingTime
		 */
		onAudioTap(audioId, messageId, isPlaying, playingTime)
		{
			logger.log('Dialog.onAudioTap', audioId, messageId, isPlaying, playingTime);

			const fileModel = this.store.getters['filesModel/getById'](audioId);
			if (!fileModel || fileModel.type !== FileType.audio)
			{
				return;
			}

			const shouldPlayAudio = !isPlaying;
			if (shouldPlayAudio)
			{
				this.audioMessagePlayer.play(Number(messageId), playingTime);
			}
			else
			{
				this.audioMessagePlayer.stop(playingTime);
			}
		}

		/**
		 *
		 * @param videoId
		 * @param messageId
		 */
		onVideoTap(videoId, messageId, localUrl = null)
		{
			logger.log('Dialog.onVideoTap', videoId, messageId, localUrl);
			if (Type.isStringFilled(localUrl))
			{
				viewer.openVideo(localUrl);

				return;
			}

			const fileModel = this.store.getters['filesModel/getById'](Number(videoId));

			if (!fileModel || fileModel.type !== FileType.video)
			{
				return;
			}

			viewer.openVideo(fileModel.urlDownload);
		}

		/**
		 *
		 * @param fileId
		 * @param messageId
		 */
		onFileTap(fileId, messageId)
		{
			logger.log('Dialog.onFileTap', fileId, messageId);
			const fileModel = this.store.getters['filesModel/getById'](fileId);
			if (!fileModel || fileModel.type !== FileType.file)
			{
				return;
			}

			viewer.openDocument(fileModel.urlDownload, fileModel.name);
		}

		onRichPreviewTap(link, messageId)
		{
			logger.log('Dialog.onRichPreviewTap: ', link);

			inAppUrl.open(link);
		}

		onRichNameTap(link, messageId)
		{
			logger.log('Dialog.onRichNameTap: ', link);

			inAppUrl.open(link);
		}

		onRichCancelTap(messageId)
		{
			logger.log('Dialog.onRichCancelTap: ', messageId);

			const message = this.store.getters['messagesModel/getById'](messageId);
			if (message.richLinkId)
			{
				this.messageService.deleteRichLink(messageId, message.richLinkId);
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
			if (
				!isGroupDialog
				|| !dialog.lastMessageId
				|| !dialog.lastMessageViews
				|| !dialog.lastMessageViews.firstViewer
			)
			{
				return;
			}

			this.messageService.openUsersReadMessageList(messageId);
		}

		/**
		 * @private
		 */
		onChatJoinButtonTapHandler()
		{
			this.joinUserChat();
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

		onMessageAvatarLongTap(index, message)
		{
			const messageId = Number(message.id);
			const modelMessage = this.store.getters['messagesModel/getById'](messageId);
			if (!modelMessage)
			{
				return;
			}

			const authorId = modelMessage.authorId;
			if (authorId === 0)
			{
				return;
			}

			const user = this.store.getters['usersModel/getById'](authorId);
			if (!user)
			{
				return;
			}

			MessageAvatarMenu.createByAuthorId(authorId, { isBot: user.bot }).open();
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

		sendMessage(text)
		{
			this.view.clearInput();
			this.draftManager.saveDraft('');
			this.cancelWritingRequest();

			if (this.mentionManager?.isMentionProcessed)
			{
				this.mentionManager?.finishMentioning();
			}

			if (this.replyManager.isEditInProcess)
			{
				const messageId = this.replyManager.getEditMessage().id;
				this.messageService.updateText(messageId, text, this.getDialogId());
				this.replyManager.finishEditingMessage();

				return;
			}

			if (ObjectUtils.isStringFullSpace(text) && !this.replyManager.isForwardInProcess)
			{
				return;
			}

			const sendMessageParams = this.prepareMessageToSend(text);

			this.sendMessageToModel(sendMessageParams)
				.then(async () => {
					this.onCancelReply();
					this.draftManager.saveDraft('');

					/** @type {ScrollToBottomEvent} */
					const scrollToBottomEventData = {
						dialogId: this.getDialogId(),
						withAnimation: true,
						force: true,
					};

					BX.postComponentEvent(EventType.dialog.external.scrollToBottom, [scrollToBottomEventData]);

					await this.sendMessageToServer(sendMessageParams);
				})
				.catch((error) => logger.error('Dialog.sendMessage.error', error))
			;
		}

		prepareMessageToSend(text)
		{
			let forwardingMessage = null;
			const messageUuid = Uuid.getV4();

			const message = {
				chatId: this.getChatId(),
				authorId: MessengerParams.getUserId(),
				text: text.trim(),
				unread: false,
				templateId: messageUuid,
				date: new Date(),
				sending: true,
			};

			const requestParams = {
				dialogId: this.getDialogId(),
				text,
				templateId: messageUuid,
			};

			if (this.replyManager.isQuoteInProcess)
			{
				const quoteMessage = this.replyManager.getQuoteMessage();
				const quoteMessageId = Number(quoteMessage.id);
				message.params = { replyId: quoteMessageId };
				requestParams.replyId = quoteMessageId;
			}

			if (this.replyManager.isForwardInProcess)
			{
				const forwardMessage = this.replyManager.getForwardMessage(); // need to forward message to the same chat
				const forwardModel = this.store.getters['messagesModel/getById'](Number(forwardMessage.id));

				const forwardUuid = Uuid.getV4();

				forwardingMessage = {
					chatId: this.getChatId(),
					authorId: MessengerParams.getUserId(),
					text: forwardModel.text,
					unread: false,
					templateId: forwardUuid,
					date: new Date(),
					sending: true,
					forward: {
						id: this.buildForwardContextId(forwardModel.id),
						userId: forwardModel?.forward?.userId ?? forwardModel.authorId,
					},
					files: forwardModel.files,
					params: {
						ATTACH: forwardModel?.params?.ATTACH,
						replyId: forwardModel?.params?.replyId,
					},
				};

				requestParams.forwardIds = {
					[forwardUuid]: forwardModel.id,
				};

				if (this.replyManager.isQuoteInBackground) // TODO future scenario
				{
					const quoteMessage = this.replyManager.getQuoteMessage();
					const quoteMessageId = Number(quoteMessage.id);
					message.params = { replyId: quoteMessageId };
					requestParams.replyId = quoteMessageId;
				}
			}

			return {
				message,
				forwardingMessage,
				requestParams,
			};
		}

		async sendMessageToModel(sendMessageParams)
		{
			if (Type.isStringFilled(sendMessageParams.message.text)) // check when forward without comment message
			{
				await this.store.dispatch('messagesModel/add', sendMessageParams.message);
			}

			if (!Type.isNull(sendMessageParams.forwardingMessage))
			{
				await this.store.dispatch('messagesModel/add', sendMessageParams.forwardingMessage);
			}
		}

		/**
		 * @private
		 * @param {{message: Object, forwardingMessage: Object | null, requestParams: Object}} sendMessageParams
		 * @return {Promise}
		 */
		async sendMessageToServer(sendMessageParams)
		{
			logger.log('Dialog.sendMessageToServer', sendMessageParams);
			let id = 0;

			try
			{
				const response = await MessageRest.send(sendMessageParams.requestParams);
				logger.log('Dialog.sendMessageToServer response', response);

				if (response.id) // check when forward without comment message
				{
					id = response.id;
					await this.store.dispatch('messagesModel/updateWithId', {
						id: sendMessageParams.message.templateId,
						fields: {
							id: response.id,
							templateId: sendMessageParams.message.templateId,
							error: false,
						},
					});
				}

				if (Type.isPlainObject(response.uuidMap))
				{
					for await (const [uuid, messageId] of Object.entries(response.uuidMap))
					{
						id = messageId;
						await this.store.dispatch('messagesModel/updateWithId', {
							id: uuid,
							fields: {
								id: messageId,
								templateId: uuid,
								error: false,
							},
						});
					}
				}
			}
			catch (response)
			{
				logger.warn('Dialog.sendMessageToServer catch', response);
				id = sendMessageParams.message.templateId;

				this.store.dispatch('messagesModel/update', {
					id: sendMessageParams.message.templateId,
					fields: {
						error: true,
						errorReason: response.status,
					},
				});

				if (!Type.isNull(sendMessageParams.forwardingMessage))
				{
					id = sendMessageParams.forwardingMessage.templateId;
					this.store.dispatch('messagesModel/update', {
						id: sendMessageParams.forwardingMessage.templateId,
						fields: {
							error: true,
							errorReason: response.status,
						},
					});
				}
			}
			finally
			{
				this.setRecentNewMessage(id);
			}
		}

		buildForwardContextId(messageId)
		{
			const dialogId = this.getDialog().dialogId;
			if (dialogId.startsWith('chat'))
			{
				return `${dialogId}/${messageId}`;
			}

			const currentUser = serviceLocator.get('core').getUserId();

			return `${dialogId}:${currentUser}/${messageId}`;
		}

		/**
		 * @desc Resend all break messages from current chat ( if three days wait is expired )
		 * @private
		 */
		resendMessages()
		{
			const breakMessages = this.store.getters['messagesModel/getBreakMessages'](this.getChatId());
			logger.info('Dialog.resendMessages', breakMessages);
			const RESEND_TIME_HOLD = 200;
			const sortedMessages = this.sortMessagesByType(breakMessages);

			const isManualSend = (message) => this.isWaitSendExpired(message.date)
				|| message.errorReason === 0
				|| message.errorReason === ErrorCode.uploadManager.INTERNAL_SERVER_ERROR;

			sortedMessages.forEach((messages, key) => {
				if (Type.isArray(messages))
				{
					if (!isManualSend(messages[0]))
					{
						setTimeout(() => {
							this.resendGroupMessages(messages);
						}, key * RESEND_TIME_HOLD);
					}
				}
				else
				if (!isManualSend(messages))
				{
					setTimeout(() => {
						const bottomMessage = this.view.getBottomMessage();
						const isBottomMessage = bottomMessage.id === messages.id;
						this.resendMessage(Number(!isBottomMessage), messages);
					}, key * RESEND_TIME_HOLD);
				}
			});
		}

		/**
		 * @desc Sorted messages by type ( media or text )
		 * @param {Array<MessagesModelState>} messages
		 * @return {Array}
		 * @private
		 */
		sortMessagesByType(messages)
		{
			const sortByTypeGroups = [];
			messages.forEach((message, index) => {
				if (message.files.length > 0)
				{
					const previousMess = messages[index - 1];
					if (previousMess && previousMess.files.length > 0)
					{
						const previous = sortByTypeGroups[sortByTypeGroups.length - 1];
						if (Type.isArray(previous))
						{
							previous.push(message);
						}
						else
						{
							sortByTypeGroups.push([message]);
						}
					}
					else
					{
						sortByTypeGroups.push([message]);
					}
				}
				else
				{
					sortByTypeGroups.push(message);
				}
			});

			return sortByTypeGroups;
		}

		/**
		 * @desc Resend break message
		 * @param {number} index
		 * @param {object} message
		 * @private
		 */
		async resendMessage(index, message)
		{
			const modelMessage = this.store.getters['messagesModel/getById'](message.id);
			const messageToSend = {
				dialogId: this.getDialogId(),
				text: modelMessage.text,
				messageType: 'self',
				templateId: message.id,
			};

			if (index > 0)
			{
				this.messageRenderer.delete([message.id]);
				this.messageRenderer.render([modelMessage]);
				this.view.scrollToBottomSmoothly();
			}

			if (modelMessage.files.length > 0)
			{
				const fileId = modelMessage.files[0];
				const file = this.store.getters['filesModel/getById'](fileId);

				if (Type.isUndefined(file))
				{
					return;
				}

				const fileObj = {
					id: file.id,
					previewUrl: file.urlPreview,
					url: file.localUrl,
					type: file.type || FileType.file,
					name: file.name,
					...file.image,
				};
				this.store.dispatch('messagesModel/delete', { id: message.id })
					.catch((err) => logger.error('Dialog.resendMessage.deleteMessage', err));

				MessengerEmitter.emit(EventType.messenger.uploadFiles, {
					dialogId: this.getDialogId(),
					fileList: [fileObj],
				});
			}

			await this.store.dispatch('messagesModel/update', {
				id: message.id,
				fields: {
					error: false,
					errorReason: OwnMessageStatus.sending,
				},
			});

			this.sendMessageToServer({
				requestParams: messageToSend,
				message: modelMessage,
			});
		}

		/**
		 * @desc Resend broken messages group ( with file media )
		 * @param {array} messages
		 * @private
		 */
		resendGroupMessages(messages)
		{
			const sendFiles = [];
			messages.forEach((message) => {
				const modelMessage = this.store.getters['messagesModel/getById'](message.id);

				const fileId = modelMessage.files[0];
				const file = this.store.getters['filesModel/getById'](fileId);

				if (Type.isUndefined(file))
				{
					return;
				}

				const fileObj = {
					id: file.id,
					previewUrl: file.urlPreview,
					url: file.localUrl,
					type: file.type || FileType.file,
					name: file.name,
					...file.image,
				};
				sendFiles.push(fileObj);
				this.store.dispatch('messagesModel/delete', { id: message.id })
					.catch((err) => logger.error('Dialog.resendMessage.deleteMessage', err));
			});

			MessengerEmitter.emit(EventType.messenger.uploadFiles, {
				dialogId: this.getDialogId(),
				fileList: sendFiles,
			});

			this.view.scrollToBottomSmoothly();
		}

		setReaction(reaction, message, like)
		{
			const messageId = Number(message.id);

			this.messageService.setReaction(reaction, messageId);
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
		onMessageFileDownloadTap(index, message)
		{
			logger.log('Dialog.onMessageFileDownloadTap: ', index, message);

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
			logger.log('Dialog.onMessageFileUploadCancelTap: ', index, message);

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
			Notification.showComingSoon();
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

			if (this.replyManager.isForwardInProcess)
			{
				this.replyManager.finishForwardingMessage();
			}
		}

		/* endregion event handlers */

		loadTopPage()
		{
			if (!this.messageService)
			{
				return;
			}

			this.messageService.loadHistory();
		}

		loadBottomPage()
		{
			if (!this.messageService)
			{
				return;
			}

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
			const breakMessages = this.store.getters['messagesModel/getBreakMessages'](this.getChatId());

			let currentDialogMessageList = [];
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

			const uniqBreakMessage = breakMessages.filter((message) => {
				return !currentDialogMessageList.some((messageObj) => messageObj.id === message.id);
			});

			if (mutation.type === 'messagesModel/setChatCollection')
			{
				let endedMessageId = mutation.payload.data.messageList[mutation.payload.data.messageList.length - 1].id;
				if (mutation.payload.actionName === 'setChatCollection' && this.view.getBottomMessage())
				{
					endedMessageId = this.view.getBottomMessage().id;
				}

				this.removeStatusFieldByEndedMessage(endedMessageId);

				if (mutation.payload.actionName === 'add')
				{
					const messages = mutation.payload.data.messageList;
					const message = messages[messages.length - 1];
					if (message.authorId === MessengerParams.getUserId())
					{
						this.setRecentNewMessage(message.id)
							.catch((err) => logger.log('Dialog.drawMessageList.setRecentNewMessage.err', err));
					}
				}
			}

			if (uniqBreakMessage.length > 0)
			{
				currentDialogMessageList.push(...uniqBreakMessage);
				currentDialogMessageList = currentDialogMessageList.sort(
					(a, b) => a.date.getTime() - b.date.getTime(),
				);
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

			if (mutation.type === 'usersModel/set')
			{
				const currentUser = mutation.payload.data.userList
					.find((user) => user.id === MessengerParams.getUserId())
				;

				if (currentUser)
				{
					this.view.setCurrentUserAvatar(this.getCurrentUserAvatarForReactions());
				}
			}

			if (mutation.type === 'dialoguesModel/update' && mutation.payload.data && mutation.payload.data.fields.role)
			{
				this.manageTextField();
			}

			switch (mutation.payload.actionName)
			{
				case 'setLastMessageViews':
				case 'incrementLastMessageViews':
					this.drawStatusField();
					break;
				case 'addParticipants':
				case 'removeParticipants':
				case 'set':
					this.checkAvailableMention(mutation);
					this.redrawHeader(mutation);
					break;
				default:
					this.redrawHeader(mutation);
					break;
			}

			return true;
		}

		/**
		 * @desc The handler change status application model
		 * @param {Object} mutation
		 * @private
		 */
		applicationStatusHandler(mutation)
		{
			this.redrawHeader(mutation);

			if (mutation.payload.data && mutation.payload.data.status && !mutation.payload.data.status.value)
			{
				this.resendMessages();
			}
		}

		/**
		 * @desc Create new status field by current dialog data and draw it in view
		 * @param {boolean} [isCheckBottom=true]
		 * @return void
		 */
		drawStatusField(isCheckBottom = true)
		{
			const dialogModelState = this.getDialog();
			if (!dialogModelState.lastMessageId
				|| !dialogModelState.lastMessageViews
				|| !dialogModelState.lastMessageViews.firstViewer
				|| !dialogModelState.lastMessageViews.messageId)
			{
				return;
			}

			const message = this.store.getters['messagesModel/getById'](dialogModelState.lastMessageViews.messageId);
			if (!message)
			{
				return;
			}

			const bottomMessage = this.view.getBottomMessage();
			if (isCheckBottom && message.id !== Number(bottomMessage.id))
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
		 * @desc Remove status field in view by check equal ended message id
		 * @param {number|string} endedMessageId
		 * @return void
		 */
		removeStatusFieldByEndedMessage(endedMessageId)
		{
			const newMessageId = String(endedMessageId);
			const currentMessageId = this.messageRenderer.messageIdsStack[this.messageRenderer.messageIdsStack.length - 1];
			if (Type.isUndefined(currentMessageId) || String(currentMessageId) !== newMessageId)
			{
				this.removeStatusField();
			}
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
				messageId = mutation.payload.data.fields.id || messageId;
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
			else if (mutation.type === 'applicationModel/setStatus')
			{
				this.headerTitle.renderTitle();
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
		 * @void
		 */
		checkAvailableMention(mutation)
		{}

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
				.catch((err) => logger.log('Dialog.deleteCurrentDialog.recentModel/delete.catch:', err))
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
					})
						.catch((err) => logger.log('Dialog.openWebDialog.getOpenlineDialogByUserCode.catch:', err));

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

		/**
		 * @desc Call dialog rest request writing message method
		 * @param {string} dialogId
		 */
		startWriting(dialogId = this.getDialogId())
		{
			if (this.isAvailableInternet())
			{
				this.holdWritingTimerId = setTimeout(() => {
					DialogRest.writingMessage(dialogId)
						.then((resolve) => resolve)
						.catch((err) => logger.log('DialogRest.writingMessage.response', err));
				}, this.HOLD_WRITING_REST);
			}
		}

		isAvailableInternet()
		{
			return this.store.getters['applicationModel/getNetworkStatus']();
		}

		/**
		 * @desc Call dialog rest request record voice message method
		 * @param {string} dialogId
		 */
		startRecordVoiceMessage(dialogId = this.getDialogId())
		{
			if (this.isAvailableInternet())
			{
				this.holdWritingTimerId = setTimeout(() => {
					DialogRest.recordVoiceMessage(dialogId)
						.then((resolve) => resolve)
						.catch((err) => logger.log('DialogRest.writingMessage.response', err));
				}, this.HOLD_WRITING_REST);
			}
		}

		/**
		 * @desc Join to chat current user
		 * @private
		 */
		joinUserChat()
		{
			this.chatService.joinChat(this.getDialogId())
				.then((data) => {
					logger.info('Dialog.onChatJoinButtonTapHandler.data', data);

					if (data.answer && data.answer.result && data.answer.result.result === true)
					{
						this.view.hideChatJoinButton();

						const isCanPost = ChatPermission.isCanPost(this.getDialog());
						if (isCanPost)
						{
							this.view.showTextField(true);
						}
					}
				})
				.catch((data) => logger.error('Dialog.onChatJoinButtonTapHandler.error', data));
		}

		/**
		 * @desc Method is canceling timeout with request rest 'im.dialog.writing'
		 * @void
		 * @private
		 */
		cancelWritingRequest()
		{
			if (this.holdWritingTimerId)
			{
				clearTimeout(this.holdWritingTimerId);
				this.holdWritingTimerId = null;
			}
		}

		/**
		 * @desc Set recent item new message
		 * @param {string|number} messageId
		 * @return {Promise}
		 */
		setRecentNewMessage(messageId)
		{
			const dialogId = this.getDialogId();
			const recentModel = this.store.getters['recentModel/getById'](dialogId);
			const messageModel = this.store.getters['messagesModel/getById'](messageId);

			if (!recentModel && Type.isUndefined(messageModel.id))
			{
				return Promise.resolve(true);
			}

			const isMessageFile = Type.isArray(messageModel.files) && messageModel.files.length > 0;
			let subTitleIcon = isMessageFile ? SubTitleIconType.wait : SubTitleIconType.reply;
			if (messageModel.error)
			{
				const isManualSend = this.isWaitSendExpired(messageModel.date)
					|| messageModel.errorReason === 0
					|| messageModel.errorReason === ErrorCode.uploadManager.INTERNAL_SERVER_ERROR;

				subTitleIcon = isManualSend ? SubTitleIconType.error : SubTitleIconType.wait;
			}

			const recentItem = RecentConverter.fromPushToModel({
				id: dialogId,
				chat: recentModel ? recentModel.chat : this.getDialog(),
				message: {
					id: messageId,
					senderId: messageModel.authorId,
					text: isMessageFile ? `[${BX.message('IM_F_FILE')}]` : messageModel.text,
					date: new Date(),
					subTitleIcon,
				},
			});

			return this.store.dispatch('recentModel/set', [recentItem]);
		}

		/**
		 * @desc Check is expired 3 days after sending
		 * @param {object} dateMessageSend
		 * @return {boolean}
		 */
		isWaitSendExpired(dateMessageSend)
		{
			const dateSend = Type.isDate(dateMessageSend) ? dateMessageSend : new Date();
			const dateThreeDayAgo = new Date();
			dateThreeDayAgo.setDate(dateThreeDayAgo.getDate() - 3);

			return dateSend.getTime() < dateThreeDayAgo.getTime();
		}
	}

	module.exports = { Dialog };
});
