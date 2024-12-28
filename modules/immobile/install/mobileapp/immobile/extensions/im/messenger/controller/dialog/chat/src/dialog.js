/* eslint-disable es/no-nullish-coalescing-operators */
/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/controller/dialog/chat/dialog
 */
jn.define('im/messenger/controller/dialog/chat/dialog', (require, exports, module) => {
	/* global include */
	/* region external import */

	/* region native import */
	include('InAppNotifier');

	/* endregion native import */

	/* region mobile import */
	const AppTheme = require('apptheme');
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const { inAppUrl } = require('in-app-url');
	const { clone } = require('utils/object');
	const { Uuid } = require('utils/uuid');
	const { throttle, debounce } = require('utils/function');
	const { transparent } = require('utils/color');
	const { openPhoneMenu } = require('communication/phone-menu');
	const { isOnline } = require('device/connection');
	/* endregion mobile import */

	/* region immobile import */
	const { serviceLocator, ServiceLocator } = require('im/messenger/lib/di/service-locator');

	const {
		EventType,
		ReactionType,
		FileType,
		ErrorType,
		ErrorCode,
		SubTitleIconType,
		DialogType,
		DialogWidgetType,
		UserRole,
		OwnMessageStatus,
		ComponentCode,
		Setting,
		MessageStatus,
		OpenDialogContextType,
		MessageIdType,
		AttachPickerId,
		ViewName,
	} = require('im/messenger/const');

	const { defaultUserIcon, ReactionAssets } = require('im/messenger/assets/common');

	const { RecentConverter, DialogConverter } = require('im/messenger/lib/converter');
	const { Counters } = require('im/messenger/lib/counters');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const {
		DialogHelper,
		Url,
	} = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ObjectUtils } = require('im/messenger/lib/utils');
	const { Feature } = require('im/messenger/lib/feature');
	const { Calls } = require('im/messenger/lib/integration/immobile/calls');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');

	const {
		StatusField,
		CheckInMessageHandler,
		BannerMessageHandler,
		CallMessageHandler,
		ChatAvatar,
	} = require('im/messenger/lib/element');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { MessageRest, DialogRest	} = require('im/messenger/provider/rest');
	const {
		ChatService,
		MessageService,
		DiskService,
		AnalyticsService,
		SendingService,
	} = require('im/messenger/provider/service');

	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { FileDownloadMenu } = require('im/messenger/controller/file-download-menu');
	const { ReactionViewerController } = require('im/messenger/controller/reaction-viewer');
	const {
		ChatSidebarController,
		ChannelSidebarController,
		CommentSidebarController,
		CollabSidebarController,
	} = require('im/messenger/controller/sidebar');

	const {
		DialogView,
		AfterScrollMessagePosition,
	} = require('im/messenger/view/dialog');
	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');
	const { ChatDataProvider, RecentDataProvider } = require('im/messenger/provider/data');
	const { openPlanLimitsWidgetByError } = require('im/messenger/lib/plan-limit');

	/* endregion immobile import */
	/* endregion external import */

	/* region lib import */
	const { AttachManager } = require('im/messenger/controller/dialog/lib/attach-manager');
	const { KeyboardManager } = require('im/messenger/controller/dialog/lib/keyboard-manager');
	const { MentionManager } = require('im/messenger/controller/dialog/lib/mention');
	const { DraftManager } = require('im/messenger/controller/dialog/lib/draft-manager');
	const { HeaderButtons, HeaderTitle } = require('im/messenger/controller/dialog/lib/header');
	const { ReplyManager } = require('im/messenger/controller/dialog/lib/reply-manager');
	const { ScrollManager } = require('im/messenger/controller/dialog/lib/scroll-manager');
	const { ContextManager } = require('im/messenger/controller/dialog/lib/context-manager');
	const { MessageRenderer } = require('im/messenger/controller/dialog/lib/message-renderer');
	const { AudioMessagePlayer } = require('im/messenger/controller/dialog/lib/audio-player');
	const { MessageAvatarMenu } = require('im/messenger/controller/dialog/lib/message-avatar-menu');
	const { WebDialog } = require('im/messenger/controller/dialog/lib/web');
	const { MessageMenu } = require('im/messenger/controller/dialog/lib/message-menu');
	const { PinManager } = require('im/messenger/controller/dialog/lib/pin');
	const { PullWatchManager } = require('im/messenger/controller/dialog/lib/pull-watch-manager');
	const { CommentButton } = require('im/messenger/controller/dialog/lib/comment-button');
	const { DialogEmitter } = require('im/messenger/controller/dialog/lib/emitter');
	const { DialogTextHelper } = require('im/messenger/controller/dialog/lib/helper/text');
	const { checkIsOpenDialogSupported } = require('im/messenger/lib/open-dialog-check');
	const { EntityManager } = require('im/messenger/controller/dialog/lib/entity-manager');

	/* endregion lib import */

	/* region internal import */
	/* endregion internal import */

	const logger = LoggerManager.getInstance().getLogger('dialog--dialog');

	/**
	 * @class Dialog
	 */
	class Dialog
	{
		/**
		 * @type {SendingService}
		 */
		#sendingService = null;

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

			/**
			 * @protected
			 * @type {number}
			 */
			this.chatId = 0;

			/**
			 * @desc static data of the object instance in case the data has been erased from storage
			 * @protected
			 * @type {{chatId: number, parentChatId: number, chatType: string}}
			 */
			this.internalState = {
				chatId: 0,
				parentChatId: 0,
				chatType: DialogType.chat,
			};

			/**
			 *
			 * @type {DialogLocator}
			 */
			this.locator = new ServiceLocator();

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
			 * @type {AttachManager}
			 */
			this.attachManager = null;
			/**
			 * @protected
			 * @type {KeyboardManager}
			 */
			this.keyboardManager = null;
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
			 * @protected
			 * @type {CheckInMessageHandler}
			 */
			this.checkInMessageHandler = null;

			/**
			 * @protected
			 * @type {BannerMessageHandler}
			 */
			this.bannerMessageHandler = null;

			/**
			 * @protected
			 * @type {CallMessageHandler}
			 */
			this.callMessageHandler = null;

			// eslint-disable-next-line no-undef
			this.emitter = new DialogEmitter();
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

			this.pullWatchManager = null;

			/**
			 * @protected
			 * @type {number|string|null}
			 */
			this.contextMessageId = null;
			/**
			 * @protected
			 * @type {boolean}
			 */
			this.withMessageHighlight = false;

			/**
			 * @private
			 * @type {boolean}
			 */
			this.needScrollToBottom = false;

			this.firstDbPagePromise = null;

			this.chatType = null;

			/**
			 * @desc it's need for show notification and close chat if deleting chat on the stack
			 * @private
			 * @type {boolean}
			 */
			this.isChatDeleted = false;
			/**
			 * @desc it's need for skip notifications about chat deleting
			 * @private
			 * @type {boolean}
			 */
			this.isChatDeletedByCurrentUserFromMobile = false;

			this.isShown = true;

			this.openingContext = OpenDialogContextType.default;

			/**
			 * @private
			 * @type {ChatSidebarController | ChannelSidebarController | CommentSidebarController | CollabSidebarController | null}
			 */
			this.sidebar = null;

			this.resendQueuePromise = Promise.resolve();

			this.bindMethods();

			this.startWriting = throttle(this.startWriting, 5000, this);
			this.startRecordVoiceMessage = throttle(this.startRecordVoiceMessage, 3000, this);
			this.joinUserChat = throttle(this.joinUserChat, 5000, this);

			this.locator
				.add('chat-service', this.chatService)
				.add('disk-service', this.diskService)
				.add('store', this.store)
			;
		}

		get isOpenInContext()
		{
			return !Type.isNull(this.contextMessageId);
		}

		/**
		 * @return {SendingService}
		 */
		get sendingService()
		{
			this.#sendingService = this.#sendingService ?? SendingService.getInstance();

			return this.#sendingService;
		}

		bindMethods()
		{
			/** @private */
			this.deleteDialogHandler = this.deleteDialogHandler.bind(this);
			/** @private */
			this.submitHandler = this.sendMessage.bind(this);
			/** @private */
			this.changeTextHandler = this.changeTextHandler.bind(this);
			/** @private */
			this.attachTapHandler = this.attachTapHandler.bind(this);
			/** @private */
			this.resendHandler = this.resendMessage.bind(this);
			/** @private */
			this.loadTopPageHandler = this.loadTopPage.bind(this);
			/** @private */
			this.loadBottomPageHandler = this.loadBottomPage.bind(this);
			/** @private */
			this.scrollBeginHandler = this.scrollBeginHandler.bind(this);
			/** @private */
			this.scrollEndHandler = this.scrollEndHandler.bind(this);
			this.replyHandler = this.replyHandler.bind(this);
			/** @private */
			this.readyToReplyHandler = this.readyToReplyHandler.bind(this);
			/** @private */
			this.quoteTapHandler = this.quoteTapHandler.bind(this);
			/** @private */
			this.cancelReplyHandler = this.cancelReplyHandler.bind(this);
			/** @private */
			this.visibleMessagesChangedHandler = this.visibleMessagesChangedHandler.bind(this);
			/** @private */
			this.messageReadHandler = this.messageReadHandler.bind(this);
			/** @private */
			this.scrollToNewMessagesHandler = this.scrollToNewMessagesHandler.bind(this);
			/** @private */
			this.playbackCompletedHandler = this.playbackCompletedHandler.bind(this);
			/** @private */
			this.urlTapHandler = this.urlTapHandler.bind(this);
			/** @private */
			this.audioRecordingStartHandler = this.audioRecordingStartHandler.bind(this);
			/** @private */
			this.audioRecordingFinishHandler = this.audioRecordingFinishHandler.bind(this);
			/** @private */
			this.submitAudioHandler = this.sendAudio.bind(this);
			/** @private */
			this.mentionTapHandler = this.mentionTapHandler.bind(this);
			/** @protected */
			this.statusFieldTapHandler = this.statusFieldTapHandler.bind(this);
			/** @private */
			this.chatJoinButtonTapHandler = this.onChatJoinButtonTapHandler.bind(this);
			/** @private */
			this.messageAvatarTapHandler = this.messageAvatarTapHandler.bind(this);
			/** @private */
			this.messageAvatarLongTapHandler = this.messageAvatarLongTapHandler.bind(this);
			/** @private */
			this.messageQuoteTapHandler = this.messageQuoteTapHandler.bind(this);
			/** @private */
			// this.messageLongTapHandler = this.onMessageLongTap.bind(this);
			/** @private */
			this.messageDoubleTapHandler = this.messageDoubleTapHandler.bind(this);
			/** @private */
			// this.messageMenuReactionTapHandler = this.setReaction.bind(this);
			// this.messageMenuActionTapHandler = this.onMessageMenuAction.bind(this);
			/** @private */
			this.messageFileDownloadTapHandler = this.messageFileDownloadTapHandler.bind(this);
			/** @private */
			this.fileDownloadTapHandler = this.fileDownloadTapHandler.bind(this);
			/** @private */
			this.messageFileUploadCancelTapHandler = this.messageFileUploadCancelTapHandler.bind(this);
			/** @private */
			this.reactionTapHandler = debounce(this.setReaction.bind(this), 300);
			/** @private */
			this.imageTapHandler = this.imageTapHandler.bind(this);
			/** @private */
			this.audioTapHandler = this.audioTapHandler.bind(this);
			/** @private */
			this.audioRateTapHandler = this.audioRateTapHandler.bind(this);
			/** @private */
			this.videoTapHandler = this.videoTapHandler.bind(this);
			/** @private */
			this.fileTapHandler = this.fileTapHandler.bind(this);
			/** @private */
			this.forwardTapHandler = this.forwardTapHandler.bind(this);
			/** @private */
			this.sendTapHandler = this.sendTapHandler.bind(this);
			/** @private */
			this.putTapHandler = this.putTapHandler.bind(this);
			/** @private */
			this.phoneTapHandler = this.phoneTapHandler.bind(this);

			this.channelCommentTapHandler = this.channelCommentTapHandler.bind(this);
			/** @private */
			this.reactionLongTapHandler = this.openReactionViewer.bind(this);
			/** @private */
			this.closeHandler = this.closeHandler.bind(this);
			/** @private */
			this.hiddenHandler = this.hiddenHandler.bind(this);
			/** @private */
			this.showHandler = this.showHandler.bind(this);
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
			/** @private */
			this.updateCommentRouter = this.updateCommentRouter.bind(this);
			/** @private */
			this.deleteChannelCountersHandler = this.deleteChannelCountersHandler.bind(this);
			/** @private */
			this.openSidebar = this.openSidebar.bind(this);

			/** @private */
			this.insertTextHandler = this.insertTextHandler.bind(this);
			/** @private */
			this.sendMessageExternalHandler = this.sendMessageExternalHandler.bind(this);
			/** @private */
			this.collabInfoUpdateHandler = this.collabInfoUpdateHandler.bind(this);
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
				.on(EventType.dialog.messageDoubleTap, this.messageDoubleTapHandler)
				.on(EventType.dialog.fileDownloadTap, this.fileDownloadTapHandler)
				.on(EventType.dialog.messageFileDownloadTap, this.messageFileDownloadTapHandler)
				.on(EventType.dialog.messageFileUploadCancelTap, this.messageFileUploadCancelTapHandler)
				.on(EventType.dialog.reactionTap, this.reactionTapHandler)
				.on(EventType.dialog.reactionLongTap, this.reactionLongTapHandler)
				.on(EventType.view.barButtonTap, this.headerButtons.tapHandler)
				.on(EventType.view.close, this.closeHandler)
				.on(EventType.view.hidden, this.hiddenHandler)
				.on(EventType.view.show, this.showHandler)
				.on(EventType.dialog.audioTap, this.audioTapHandler)
				.on(EventType.dialog.audioRateTap, this.audioRateTapHandler)
				.on(EventType.dialog.imageTap, this.imageTapHandler)
				.on(EventType.dialog.fileTap, this.fileTapHandler)
				.on(EventType.dialog.sendTap, this.sendTapHandler)
				.on(EventType.dialog.putTap, this.putTapHandler)
				.on(EventType.dialog.phoneTap, this.phoneTapHandler)
				.on(EventType.dialog.videoTap, this.videoTapHandler)
				.on(EventType.dialog.forwardTap, this.forwardTapHandler)
				.on(EventType.dialog.channelCommentTap, this.channelCommentTapHandler)
				.on(EventType.dialog.titleClick, this.openSidebar)
			;

			this.view.textField.on(EventType.dialog.textField.submit, this.submitHandler);
			this.view.textField.on(EventType.dialog.textField.quoteTap, this.quoteTapHandler);
			this.view.textField.on(EventType.dialog.textField.changeText, this.changeTextHandler);
			this.view.textField.on(EventType.dialog.textField.cancelQuote, this.cancelReplyHandler);

			this.messageMenuComponent.subscribeEvents();
			this.pinManager?.subscribeViewEvents();
			this.checkInMessageHandler?.subscribeEvents();
			this.bannerMessageHandler?.subscribeEvents();
			this.callMessageHandler?.subscribeEvents();
		}

		/** @private */
		unsubscribeViewEvents()
		{
			this.mentionManager?.unsubscribeEvents();
			this.checkInMessageHandler?.unsubscribeEvents();
			this.bannerMessageHandler?.unsubscribeEvents();
			this.callMessageHandler?.unsubscribeEvents();
			this.messageMenuComponent?.unsubscribeEvents();
			this.view.removeAll();
		}

		/** @protected */
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
				.on('usersModel/set', this.dialogUpdateHandlerRouter)
				.on('dialoguesModel/collabModel/setGuestCount', this.collabInfoUpdateHandler)
				.on('applicationModel/setStatus', this.applicationStatusHandler)
				.on('commentModel/setComments', this.updateCommentRouter)
				.on('commentModel/setCounters', this.updateCommentRouter)
				.on('commentModel/setCommentsWithCounters', this.updateCommentRouter)
				.on('commentModel/deleteChannelCounters', this.deleteChannelCountersHandler)
			;

			this.pinManager?.subscribeStoreEvents();
		}

		/** @protected */
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
				.off('usersModel/set', this.dialogUpdateHandlerRouter)
				.off('applicationModel/setStatus', this.applicationStatusHandler)
				.off('commentModel/setComments', this.updateCommentRouter)
				.off('commentModel/setCounters', this.updateCommentRouter)
				.off('commentModel/setCommentsWithCounters', this.updateCommentRouter)
				.off('commentModel/deleteChannelCounters', this.deleteChannelCountersHandler)
			;

			this.pinManager?.unsubscribeStoreEvents();
		}

		/** @private */
		subscribeExternalEvents()
		{
			this.scrollManager.subscribeEvents();
			BX.addCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
			BX.addCustomEvent(EventType.dialog.external.sendMessage, this.sendMessageExternalHandler);
			BX.addCustomEvent(EventType.dialog.external.textarea.insertText, this.insertTextHandler);
			BX.addCustomEvent(EventType.dialog.external.resend, this.resendHandler);
		}

		/** @private */
		unsubscribeExternalEvents()
		{
			this.scrollManager.unsubscribeEvents();
			BX.removeCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
			BX.removeCustomEvent(EventType.dialog.external.sendMessage, this.sendMessageExternalHandler);
			BX.removeCustomEvent(EventType.dialog.external.textarea.insertText, this.insertTextHandler);
			BX.removeCustomEvent(EventType.dialog.external.resend, this.resendHandler);
		}

		/** @protected */
		initManagers()
		{
			/**
			 * @protected
			 * @type {AttachManager}
			 */
			this.attachManager = new AttachManager(serviceLocator, this.locator);

			/**
			 * @protected
			 * @type {KeyboardManager}
			 */
			this.keyboardManager = new KeyboardManager(this.locator);

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
			 * @type {ContextManager}
			 */
			this.contextManager = new ContextManager({
				dialogId: this.getDialogId(),
				dialogLocator: this.locator,
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
			 * @type {EntityManager}
			 */
			this.entityManager = new EntityManager(this.dialogId, this.store);

			/**
			 * @private
			 * @type {DraftManager}
			 */
			this.draftManager = new DraftManager({
				store: this.store,
				view: this.view,
				dialogId: this.getDialogId(),
				replyManager: this.replyManager,
				initWithExternalForward: this.forwardMessageIds?.length > 0,
			});
			this.commentButton = new CommentButton(this.view, this.getDialogId(), this.locator);
			this.pullWatchManager = new PullWatchManager(this.dialogId);
			this.initMentionManager();

			this.replyManager.setDraftManager(this.draftManager);
			if (this.forwardMessageIds?.length > 0)
			{
				this.replyManager.startForwardingMessages(this.forwardMessageIds);
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

			this.replyManager.setDraftManager(this.draftManager);

			this.locator
				.add('context-manager', this.contextManager)
				.add('reply-manager', this.replyManager)
				.add('mention-manager', this.mentionManager)
			;
		}

		initComponents()
		{
			this.messageMenuComponent = new MessageMenu({
				serviceLocator: this.locator,
				dialogId: this.getDialogId(),
			});

			this.checkInMessageHandler = new CheckInMessageHandler(serviceLocator, this.locator);
			this.bannerMessageHandler = new BannerMessageHandler(serviceLocator, this.locator);
			this.callMessageHandler = new CallMessageHandler(serviceLocator, this.locator);
		}

		/**
		 * @param {DialogOpenOptions} options
		 * @param {PageManager} parentWidget
		 * @return {Promise<void>}
		 */
		async open(options, parentWidget = PageManager)
		{
			const {
				dialogId,
				messageId,
				withMessageHighlight,
				dialogTitleParams,
				forwardMessageIds,
				chatType = DialogType.chat,
				context = OpenDialogContextType.default,
			} = options;

			// early exit if the dialog data is in local storage
			const isSupportedDialog = await checkIsOpenDialogSupported(dialogId);
			if (isSupportedDialog === false)
			{
				Feature.showUnsupportedWidget();

				return;
			}

			this.dialogCode = `im.dialog-${this.getDialogId()}-${Uuid.getV4()}`;
			this.dialogId = dialogId;
			this.locator.add('dialogId', this.dialogId);
			serviceLocator.add(this.getDialogId(), this);
			this.contextMessageId = messageId ?? null;
			this.withMessageHighlight = withMessageHighlight ?? false;
			this.openingContext = context;

			this.chatType = chatType;
			await this.store.dispatch('applicationModel/openDialogId', dialogId);
			await this.store.dispatch('recentModel/like', {
				id: dialogId,
				liked: false,
			});

			const isOpenlinesChat = dialogTitleParams && dialogTitleParams.chatType === 'lines';
			if (
				!Feature.isChatV2Enabled
				|| isOpenlinesChat
				|| (this.isBot() && !Feature.isChatDialogWidgetSupportsBots)
			)
			{
				this.openWebDialog(options);

				return;
			}

			if (forwardMessageIds)
			{
				this.forwardMessageIds = forwardMessageIds;
			}

			this.pinManager = new PinManager({
				dialogId: this.dialogId,
				locator: this.locator,
			});

			const hasDialog = await this.loadDialogFromDb();
			if (hasDialog)
			{
				this.setInternalState();
				this.messageService = new MessageService({
					store: this.store,
					chatId: this.getChatId(),
				});

				this.locator.add('message-service', this.messageService);
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

			this.createWidget(titleParams, parentWidget);
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
		 * @return {CollabItem|{}}
		 */
		getCollabInfo()
		{
			const collabInfo = this.store.getters['dialoguesModel/collabModel/getByDialogId'](this.dialogId);

			return collabInfo || {};
		}

		/**
		 * @param {String} [chatId=null]
		 * @return {DialoguesModelState|{}}
		 */
		getDialogByChatId(chatId = null)
		{
			const dialog = this.store.getters['dialoguesModel/getByChatId'](chatId || this.getDialog()?.chatId);

			return dialog || {};
		}

		/**
		 * @param dialogData
		 * @returns {MessagesModelState | null}
		 */
		getParentMessage(dialogData = this.getDialog())
		{
			if (!this.isComment(dialogData))
			{
				return null;
			}

			const message = this.store.getters['messagesModel/getById'](dialogData.parentMessageId);
			if (!message.id)
			{
				return null;
			}

			return message;
		}

		/**
		 * @desc check is canal
		 * @return {boolean}
		 */
		isOpenChat()
		{
			return DialogHelper.createByDialogId(this.getDialogId())?.isOpenChat;
		}

		/**
		 * @param {DialoguesModelState} [dialogData=this.getDialog()]
		 * @return {DialoguesModelState|{}}
		 */
		isChannel(dialogData = this.getDialog())
		{
			return DialogHelper.createByModel(dialogData)?.isChannel;
		}

		/**
		 * @param {DialoguesModelState} [dialogData=this.getDialog()]
		 * @return {DialoguesModelState|{}}
		 */
		isComment(dialogData = this.getDialog())
		{
			return DialogHelper.createByModel(dialogData)?.isComment;
		}

		getDialogHelper()
		{
			return DialogHelper.createByDialogId(this.getDialogId());
		}

		isMuted()
		{
			const dialog = this.getDialog();

			return dialog.muteList?.includes(MessengerParams.getUserId());
		}

		/**
		 * @desc
		 */
		setInternalState()
		{
			const chatData = this.getDialog();
			if (!chatData.chatId)
			{
				return;
			}

			this.internalState = {
				chatId: chatData.chatId,
				chatType: chatData.type,
				parentChatId: chatData.parentChatId ?? 0,
			};
		}

		getChatId()
		{
			if (this.chatId === 0)
			{
				this.chatId = this.getChatIdFromStore();
			}

			return this.chatId;
		}

		getChatIdFromStore()
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
		 * @return {{imageUrl?: string, defaultIconSvg?: string, avatar?: object}}
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
					avatar: ChatAvatar.createFromDialogId(currentUser.id).getReactionAvatarProps(),
				};
			}

			return {
				defaultIconSvg: defaultUserIcon(currentUser.color),
			};
		}

		getDialogType()
		{
			if (this.getDialog().type === DialogType.collab)
			{
				return DialogWidgetType.collab;
			}

			return DialogWidgetType.chat;
		}

		checkCanHaveAttachments()
		{
			return true;
		}

		/** @private */
		createWidget(titleParams = null, parentWidget = PageManager)
		{
			if (!titleParams)
			{
				titleParams = HeaderTitle.createTitleParams(this.getDialogId(), this.store);
			}
			this.headerButtons = new HeaderButtons({
				store: this.store,
				dialogId: this.getDialogId(),
				locator: this.locator,
			});

			PageManager.openWidget(
				'chat.dialog',
				{
					dialogId: this.getDialogId(),
					titleParams,
					rightButtons: this.headerButtons.getButtons(true),
					reactions: {
						lottie: this.getMessageReactionsLottie(),
						svg: this.getMessageReactionsSvg(),
						currentUserAvatar: this.getCurrentUserAvatarForReactions(),
					},
					autoplayVideo: Feature.isAutoplayVideoEnabled,
					code: this.dialogCode,
					dialogType: this.getDialogType(),
					canHaveAttachments: this.checkCanHaveAttachments(),
					pinPanel: this.pinManager?.getPinPanelParams(),
				},
				parentWidget,
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

			if ((this.isComment()) && this.getChatId() > 0)
			{
				void await this.store.dispatch('messagesModel/clearChatCollection', { chatId: this.getChatId() });
			}

			const hasSavedMessages = await this.loadSavedMessages();
			if (!hasSavedMessages)
			{
				this.renderRecentMessage();
			}

			this.loadChatWithMessages()
				.then(() => this.handleLoadChatWithMessages())
				.catch((error) => this.handleLoadChatWithMessagesError(error))
				.finally(async () => {
					// if at the time of open there was no chat data in the local storage
					const isSupportedDialog = await checkIsOpenDialogSupported(this.dialogId);
					if (isSupportedDialog === false)
					{
						this.view.once(EventType.view.close, () => {
							Feature.showUnsupportedWidget();
						});
						this.view.back();

						return;
					}

					this.handleMessageNotFoundError();
				})
			;
		}

		renderRecentMessage()
		{
			logger.info(`Dialog: dialogId: ${this.dialogId} first load`);

			const recentMessage = DialogConverter.createMessageFromRecent(this.getDialogId());
			if (
				recentMessage
				&& this.isOpenInContext === false
				&& MessengerParams.isFullChatHistoryAvailable()
			)
			{
				this.view.ui.setMessages([recentMessage]);
			}
			else
			{
				this.view.showMessageListLoader();
			}
		}

		async loadChatWithMessages()
		{
			if (this.contextMessageId)
			{
				try
				{
					await this.chatService.loadChatWithContext(this.dialogId, this.contextMessageId);

					return true;
				}
				catch (error)
				{
					logger.error(`Dialog.loadChatWithMessages dialogId=${this.dialogId} contextMessageId=${this.contextMessageId} error`, error);

					if (!Type.isArray(error))
					{
						error = [error];
					}
					this.isMessageNotFoundError = error.some((currentError) => {
						return currentError?.code === 'MESSAGE_NOT_FOUND';
					});

					this.contextMessageId = null;
					this.withMessageHighlight = false;
					this.view.resetContextOptions();

					await openPlanLimitsWidgetByError(error[0]);

					return this.chatService.loadChatWithMessages(this.dialogId);
				}
			}

			if (this.chatType === DialogType.comment)
			{
				return this.chatService.loadCommentChatWithMessages(this.dialogId);
			}

			return this.chatService.loadChatWithMessages(this.dialogId);
		}

		async handleLoadChatWithMessages()
		{
			if (!Type.isArrayFilled(this.getModelMessages()))
			{
				if (this.isHistoryLimitExceeded())
				{
					logger.log(`${this.constructor.name}.handleLoadChatWithMessages set PlanLimitMessage`);
					await this.setPlanLimitsBanner();
					this.sendAnalyticsShowBannerByStart();
				}
				else
				{
					this.view.showWelcomeScreen();
				}
			}
			this.pullWatchManager.subscribe();
			this.setInternalState();

			this.view.hideMessageListLoader();
			this.setInputPlaceholder();
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

			this.commentButton.init();
			if (this.isComment())
			{
				this.pinManager.showDiscussionMessage(this.getDialog().parentMessageId);
			}

			if (this.isChannel())
			{
				this.view.setSendButtonColors({
					enabled: AppTheme.colors.accentExtraAqua,
					disabled: AppTheme.colors.accentExtraAqua,
				});
			}

			this.headerTitle.renderTitle();
			this.headerButtons.render(this.view);
			this.drawStatusField();
			this.resendMessages();
			this.sendAnalyticsOpenDialog();
			BX.postComponentEvent(EventType.messenger.openDialogComplete, [{
				chatData: this.getDialog(),
				error: null,
			}]);
		}

		handleLoadChatWithMessagesError(loadChatError)
		{
			logger.error('Dialog.loadMessages error: ', loadChatError);

			/**
			 * @type {Array<Error>}
			 */
			let errors = loadChatError;
			if (!Type.isArray(loadChatError))
			{
				errors = [loadChatError];
			}

			BX.postComponentEvent(EventType.messenger.openDialogComplete, [{
				chatData: {
					...this.getDialog(),
					dialogId: this.getDialogId(),
				},
				error: errors[0].message,
			}]);

			for (const error of errors)
			{
				if ([ErrorType.dialog.accessDenied, ErrorType.dialog.chatNotFound].includes(error.code))
				{
					const chatProvider = new ChatDataProvider();
					const recentProvider = new RecentDataProvider();

					recentProvider.delete({ dialogId: this.getDialogId() })
						.then(() => chatProvider.delete({ dialogId: this.getDialogId() }))
						.then(() => {
							Notification.showToast(ToastType.chatAccessDenied);
							this.view.back();
						})
						.catch((deleteChatError) => {
							logger.error(`${this.constructor.name}.handleLoadChatWithMessagesError error`, deleteChatError);
						})
					;

					return;
				}
			}

			throw loadChatError;
		}

		handleMessageNotFoundError()
		{
			if (!this.isMessageNotFoundError)
			{
				return;
			}

			Notification.showToast(ToastType.messageNotFound, this.view.ui);

			AnalyticsService.getInstance().sendToastShownMessageNotFound({
				dialogId: this.getDialogId(),
				context: this.openingContext,
			});
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

		async loadChatWithMessagesFromServer()
		{
			if (this.chatType === DialogType.comment)
			{
				return this.chatService.loadCommentChatWithMessages(this.dialogId);
			}

			return this.chatService.loadChatWithMessages(this.dialogId);
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
			if (!Feature.isLocalStorageEnabled)
			{
				return false;
			}

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
			if (
				!Feature.isLocalStorageEnabled
				|| this.chatType === DialogType.comment
			)
			{
				return;
			}

			if (this.isComment())
			{
				return;
			}

			if (this.isChannel() && this.getDialogHelper()?.isCurrentUserGuest)
			{
				return;
			}

			const chatId = this.getChatId();
			if (!chatId)
			{
				logger.warn(`Dialog.loadHistoryMessagesFromDb: we don't have a chatId for dialog ${this.getDialogId()}`);

				return;
			}

			const lastReadId = this.getDialog().lastReadId ?? 0;
			const contextMessageId = this.contextMessageId ?? lastReadId;
			logger.info('Dialog.loadHistoryMessagesFromDb lastReadId', lastReadId, 'contextMessageId', contextMessageId);
			let result;
			if (contextMessageId === 0)
			{
				result = await this.messageRepository.getTopPage({
					chatId,
					limit: 51,
				});

				logger.error(`
					Dialog.loadHistoryMessagesFromDb
					received the latest messages because where is no lastReadId for dialog ${this.getDialogId()}
				`, this.getDialog(), result);
			}
			else
			{
				const context = await this.messageService.loadLocalStorageContext(contextMessageId);
				result = context.result;

				logger.info('Dialog.loadHistoryMessagesFromDb result by lastReadId', result);
			}

			if (!Type.isUndefined(result.dialogFields))
			{
				await this.store.dispatch('dialoguesModel/update', {
					dialogId: this.dialogId,
					fields: result.dialogFields,
				});
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

			if (Type.isArrayFilled(result.additionalMessageList))
			{
				await this.store.dispatch('messagesModel/store', result.additionalMessageList);
			}

			if (Type.isArrayFilled(result.messageList))
			{
				/** @type {Map<number, Array<MessagesModelState>>} */
				const uploadingCollection = new Map();
				const uploadingMessageList = this.store.getters['messagesModel/getUploadingMessages'](chatId);
				for (const uploadingMessage of uploadingMessageList)
				{
					if (!uploadingCollection.has(uploadingMessage.previousId))
					{
						uploadingCollection.set(uploadingMessage.previousId, []);
					}
					uploadingCollection.get(uploadingMessage.previousId).push(uploadingMessage);
				}

				for (const [messageId, uploadingMessages] of uploadingCollection.entries())
				{
					const messageIndex = result.messageList
						.findIndex((message) => message.id === messageId)
					;
					if (messageIndex === -1)
					{
						continue;
					}

					if (messageIndex === result.messageList.length - 1)
					{
						result.messageList.push(...uploadingMessages);
					}
					else
					{
						result.messageList.splice(messageIndex + 1, 0, ...uploadingMessages);
					}
				}

				await this.store.dispatch('messagesModel/setFromLocalDatabase', {
					messages: result.messageList,
					clearCollection: true,
				});
			}

			await this.loadPinMessagesFromDb();
			await this.loadApplicationSettingsFromDb();
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

		async loadApplicationSettingsFromDb()
		{
			const result = await serviceLocator.get('core')
				.getRepository().option.get(Setting.option.APP_SETTING_AUDIO_RATE, 1);
			logger.info(`${this.constructor.name}.loadApplicationSettingsFromDb result`, result);

			if (!Type.isUndefined(result))
			{
				await this.store.dispatch('applicationModel/setAudioRateSetting', Number(result))
					.catch((error) => logger.error(`${this.constructor.name}.loadApplicationSettingsFromDb.applicationModel/setAudioRateSetting.catch:`, error));
			}
		}

		/**
		 * @private
		 * @param widget
		 */
		createView(widget)
		{
			this.view = new DialogView({
				dialogCode: this.dialogCode,
				ui: widget,
				dialogId: this.getDialogId(),
				chatId: this.getChatId(),
				lastReadId: this.getDialog().lastReadId,
				viewName: ViewName.dialog,
			});
			if (this.contextMessageId)
			{
				this.view.setContextOptions(
					this.contextMessageId,
					this.withMessageHighlight,
					AfterScrollMessagePosition.top,
				);
			}

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

			this.setInputPlaceholder();
			this.manageTextField(true);

			this.locator.add('view', this.view);
			this.locator.add('header-buttons', this.headerButtons);
			this.locator.add('message-renderer', this.messageRenderer);
		}

		/**
		 * @desc Method manage text field
		 * @param {boolean} [isFirstCall=false]
		 * @private
		 */
		manageTextField(isFirstCall = false)
		{
			const isNeedHide = this.isNeedHideTextFieldByPermission();

			if (!isNeedHide)
			{
				this.view.showTextField(false);
				this.view.hideChatJoinButton();

				return;
			}

			this.view.hideTextField(false);

			// if (isFirstCall)
			// {
			// 	return;
			// }

			const dialogModel = this.getDialog();

			if (dialogModel.role === UserRole.guest)
			{
				if (this.isOpenChat())
				{
					this.view.showChatJoinButton({
						text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_JOIN_BUTTON_TEXT'),
						backgroundColor: transparent(AppTheme.colors.accentMainPrimaryalt, 1),
						testId: 'DIALOG_OPEN_CHAT_JOIN_BUTTON',
					});

					return;
				}

				if (this.isChannel() || this.isComment())
				{
					this.view.showChatJoinButton({
						text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_CHANNEL_JOIN_BUTTON_TEXT'),
						backgroundColor: transparent(AppTheme.colors.accentMainPrimaryalt, 1),
						testId: 'DIALOG_OPEN_CHANNEL_JOIN_BUTTON',
					});

					return;
				}
			}

			if (this.isMuted())
			{
				this.view.showChatJoinButton({
					text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_CHANNEL_JOIN_BUTTON_UNMUTE_TEXT'),
					backgroundColor: AppTheme.colors.chatOverallTech3.length === 7
						? transparent(AppTheme.colors.chatOverallTech3, 0.16)
						: AppTheme.colors.chatOverallTech3,
					testId: 'DIALOG_UNMUTE_BUTTON',
				});

				return;
			}

			this.view.showChatJoinButton({
				text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_CHANNEL_JOIN_BUTTON_MUTE_TEXT'),
				backgroundColor: AppTheme.colors.chatOverallTech3.length === 7
					? transparent(AppTheme.colors.chatOverallTech3, 0.16)
					: AppTheme.colors.chatOverallTech3,
				testId: 'DIALOG_MUTE_BUTTON',
			});
		}

		setInputPlaceholder()
		{
			let placeholder = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_INPUT_PLACEHOLDER_TEXT_V2');

			if (this.isChannel())
			{
				placeholder = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_CHANNEL_INPUT_PLACEHOLDER_TEXT');
			}
			else if (this.isComment())
			{
				placeholder = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_COMMENT_INPUT_PLACEHOLDER_TEXT');
			}

			this.view.setInputPlaceholder(placeholder);
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

			return !isCanPost && isGroupDialog;
		}

		isNeedDeleteMessages()
		{
			if (this.getChatId() === 0)
			{
				return false;
			}

			if (this.isComment())
			{
				return true;
			}

			if (this.isChannel() && this.getDialogHelper()?.isCurrentUserGuest)
			{
				return true;
			}

			return false;
		}

		/* region event handlers */
		/**
		 * @private
		 */
		closeHandler()
		{
			const dialogId = this.getDialogId();
			serviceLocator.delete(dialogId);
			this.unsubscribeExternalEvents();
			this.unsubscribeStoreEvents();
			this.unsubscribeViewEvents();
			this.audioMessagePlayer.stop();
			this.headerTitle.stopRender();
			this.contextManager.destructor();
			this.pullWatchManager.unsubscribe();

			if (this.isNeedDeleteMessages())
			{
				const chatId = this.getChatId();
				if (this.isChannel())
				{
					this.store.dispatch('commentModel/deleteComments');
				}

				this.store.dispatch('messagesModel/clearChatCollection', { chatId });
				this.store.dispatch('dialoguesModel/update', {
					dialogId,
					fields: {
						inited: false,
					},
				});
			}

			if (this.isChannel())
			{
				this.chatService.readChannelComments(this.getDialogId());
			}

			this.store.dispatch('applicationModel/closeDialogId', dialogId);
		}

		hiddenHandler()
		{
			this.isShown = false;

			this.mentionManager?.onDialogHidden();
		}

		showHandler()
		{
			this.isShown = true;
			this.mentionManager?.onDialogShow();

			if (this.isChatDeleted)
			{
				this.view.back();

				if (!this.isChatDeletedByCurrentUserFromMobile)
				{
					this.showDeletionToast();
				}
			}
		}

		/**
		 * @private
		 */
		visibleMessagesChangedHandler({ indexList = [], messageList = [] })
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
		messageReadHandler(messageIdList)
		{
			if (!this.view.scrollToFirstUnreadCompleted)
			{
				return;
			}

			if (
				this.getDialog().role === UserRole.guest
				&& (this.isChannel() || this.isOpenChat() || this.isComment())
			)
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
		async scrollToNewMessagesHandler()
		{
			if (this.needScrollToBottom)
			{
				if (Feature.isGoToMessageContextSupported)
				{
					await this.contextManager.goToBottomMessageContext();
				}
				else
				{
					await this.view.scrollToBottomSmoothly();
				}

				this.needScrollToBottom = false;
			}
			else
			{
				if (Feature.isGoToMessageContextSupported)
				{
					await this.contextManager.goToLastReadMessageContext();
				}
				else
				{
					await this.view.scrollToLastReadMessage();
				}

				this.needScrollToBottom = true;
			}
		}

		/**
		 * @private
		 */
		scrollBeginHandler()
		{
			this.needScrollToBottom = false;

			this.view.showFloatingText();
		}

		/**
		 * @private
		 */
		scrollEndHandler()
		{
			this.view.hideFloatingText();
		}

		/**
		 * @private
		 */
		playbackCompletedHandler()
		{
			this.audioMessagePlayer.playNext();
		}

		async urlTapHandler(url)
		{
			logger.log('Dialog.urlTapHandler: ', url);
			const isProcessed = await this.handleInternalUrl(url);
			if (isProcessed === true)
			{
				logger.log('Dialog: internal url was processed', url);

				return;
			}

			inAppUrl.open(url);
		}

		/**
		 * @param {string} url
		 * @return {Promise<boolean>}
		 */
		async handleInternalUrl(url)
		{
			const urlObject = new Url(url);
			// checking for a link to a message in the current dialog.
			if (urlObject.isLocal && url.includes('/online/'))
			{
				const queryParams = urlObject.queryParams;
				const isCurrentDialogMessage = (
					queryParams.IM_DIALOG
					&& queryParams.IM_DIALOG === this.getDialogId()
					&& queryParams.IM_MESSAGE
					&& Type.isNumber(parseInt(queryParams.IM_MESSAGE, 10))
				);

				if (isCurrentDialogMessage)
				{
					const messageId = parseInt(queryParams.IM_MESSAGE, 10);
					await this.contextManager.goToMessageContext({
						dialogId: this.getDialogId(),
						messageId,
					});

					return true;
				}
			}

			return false;
		}

		/**
		 * @private
		 */
		audioRecordingStartHandler()
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
		audioRecordingFinishHandler()
		{}

		/**
		 * @private
		 */
		sendAudio(audioFile)
		{
			const file = {
				url: audioFile.localAudioUrl,
			};

			this.sendingService.sendFiles(this.getDialogId(), [file])
				.catch((error) => logger.error(`${this.constructor.name}.sendAudio.sendFiles`, error))
			;
		}

		/**
		 * @private
		 * @param {{entityId: string, entityType: string} || string} params
		 */
		mentionTapHandler(params)
		{
			if (typeof params === 'string')
			{
				if (params === 'sidebar')
				{
					void this.openSidebar();

					return;
				}

				MessengerEmitter.emit(
					EventType.messenger.openDialog,
					{ dialogId: params.toString() },
					ComponentCode.imMessenger,
				);

				return;
			}

			if (['user', 'copilot'].includes(params.entityType) && params.entityId === 'sidebar')
			{
				void this.openSidebar();

				return;
			}

			if (params.entityId.includes('copy:id'))
			{
				try
				{
					let messageId = params.entityId.split('id')[1];
					messageId = parseInt(messageId, 10);
					const modelMessage = this.store.getters['messagesModel/getById'](messageId);
					const descAttach = modelMessage?.params?.ATTACH[0]?.DESCRIPTION;
					const text = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_COPY_LINK_TEXT');
					DialogTextHelper.copyToClipboard(
						descAttach,
						{
							notificationText: text,
							parentWidget: this.view.ui,
						},
					);
				}
				catch (error)
				{
					logger.error(`${this.constructor.name}.mentionTapHandler.DialogTextHelper.copyToClipboard.catch:`, error);
				}

				return;
			}

			if (params.entityType === 'chat')
			{
				const dialogId = DialogHelper.isDialogId(params.entityId) ? params.entityId : `chat${params.entityId}`;

				MessengerEmitter.emit(
					EventType.messenger.openDialog,
					{
						dialogId,
						context: OpenDialogContextType.mention,
					},
					ComponentCode.imMessenger,
				);

				return;
			}
			MessengerEmitter.emit(
				EventType.messenger.openDialog,
				{
					dialogId: params.entityId.toString(),
					context: OpenDialogContextType.mention,
				},
				ComponentCode.imMessenger,
			);
		}

		/**
		 * @param {string} imageId
		 * @param {string} messageId
		 */
		imageTapHandler(imageId, messageId, localUrl = null)
		{
			logger.log('Dialog.imageTapHandler', imageId, messageId, localUrl);

			if (Type.isStringFilled(localUrl))
			{
				viewer.openImage(localUrl);

				return;
			}

			const messageList = this.store.getters['messagesModel/getByChatId'](this.getChatId());
			const parentMessage = this.getParentMessage();
			if (parentMessage)
			{
				messageList.unshift(parentMessage);
			}

			const fileIdList = [];
			messageList.forEach((message) => {
				if (Type.isArrayFilled(message.files))
				{
					fileIdList.push(...message.files);
				}
			});

			const fileList = this.store.getters['filesModel/getByIdList'](fileIdList);
			// file?.type here needed after 205400 bug fix for already broken messages with disk files
			const imageList = fileList.filter((file) => file?.type === FileType.image);

			const imageCollection = [];
			imageList.forEach((image) => {
				imageCollection.push({
					url: image.urlShow,
					previewUrl: image.urlPreview,
					default: image.id === Number(imageId),
					description: image.name,
				});
			});

			logger.log('Dialog.imageTapHandler: openImageCollection', imageCollection);

			viewer.openImageCollection(imageCollection);
		}

		/**
		 *
		 * @param audioId
		 * @param messageId
		 * @param isPlaying
		 * @param playingTime
		 */
		audioTapHandler(audioId, messageId, isPlaying, playingTime)
		{
			logger.log('Dialog.audioTapHandler', audioId, messageId, isPlaying, playingTime);

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
		 * @param {string} audioId
		 * @param {number} messageId
		 * @param {number} playingTime
		 */
		async audioRateTapHandler(audioId, messageId, playingTime)
		{
			logger.log(`${this.constructor.name}.audioRateTapHandler`, audioId, messageId);
			const playingMessageModel = this.store.getters['messagesModel/getById'](messageId);
			if (!playingMessageModel || !playingMessageModel.files[0])
			{
				return;
			}
			playingMessageModel.playingTime = playingTime;

			try
			{
				await this.audioMessagePlayer.changeRate();
				await this.messageRenderer.render([playingMessageModel]);
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}.audioRateTapHandler.changeRate.catch:`, error);
			}
		}

		/**
		 *
		 * @param videoId
		 * @param messageId
		 */
		videoTapHandler(videoId, messageId, localUrl = null)
		{
			logger.log('Dialog.videoTapHandler', videoId, messageId, localUrl);
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
		fileTapHandler(fileId, messageId)
		{
			logger.log('Dialog.fileTapHandler', fileId, messageId);
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const fileModel = this.store.getters['filesModel/getById'](fileId);
			if (!fileModel)
			{
				return;
			}

			const canOpenDocument = (
				fileModel.type === FileType.file
				|| fileModel.type === FileType.audio
			);
			if (!canOpenDocument)
			{
				return;
			}

			viewer.openDocument(fileModel.urlDownload, fileModel.name);
		}

		/**
		 *
		 * @param {number} messageIndex
		 * @param message
		 */
		async forwardTapHandler(messageIndex, message)
		{
			logger.log('Dialog.forwardTapHandler', messageIndex, message);

			const modelMessage = this.store.getters['messagesModel/getById'](message.id);
			// eslint-disable-next-line es/no-optional-chaining
			const forwardIdData = modelMessage?.forward?.id.split('/');
			let dialogId = forwardIdData[0];
			if (dialogId.includes(':')) // forwarded personal message
			{
				const users = dialogId.split(':');
				const user1 = users[0];
				const user2 = users[1];
				const currentUserId = serviceLocator.get('core').getUserId();

				if (currentUserId === Number(user1))
				{
					dialogId = user2;
				}
				else if (currentUserId === Number(user2))
				{
					dialogId = user1;
				}
			}

			const messageId = forwardIdData[1];
			logger.log('Dialog.forwardTapHandler: forwardIdData: ', dialogId, messageId);

			await this.contextManager.goToMessageContext({
				dialogId,
				messageId,
				parentMessageId: message.id,
				context: OpenDialogContextType.forward,
			});
		}

		/**
		 * @description tap on bb-code [SEND] in message
		 * @param {string} sendText
		 */
		async sendTapHandler(sendText)
		{
			await this.sendMessage(sendText, null, false);
		}

		/**
		 * @description tap on bb-code [PUT] in message
		 * @param {string} putText
		 */
		putTapHandler(putText)
		{
			logger.log('Dialog.putTapHandler', putText);

			this.view.setInput(putText);
		}

		/**
		 * @param {string} phoneText
		 */
		phoneTapHandler(phoneText)
		{
			logger.log('Dialog.phoneTapHandler', phoneText);
			openPhoneMenu({
				number: phoneText,
				canUseTelephony: MessengerParams.canUseTelephony(),
				analyticsSection: 'chat',
			});
		}

		async channelCommentTapHandler(messageId)
		{
			logger.log(`Dialog.channelCommentTapHandler: ${messageId}`);

			const commentInfo = this.store.getters['commentModel/getByMessageId'](messageId);

			if (commentInfo && commentInfo.chatId > 0)
			{
				logger.log('Dialog.channelCommentTapHandler: comment info', commentInfo);
				MessengerEmitter.emit(EventType.messenger.openDialog, {
					dialogId: commentInfo.dialogId,
					chatType: DialogType.comment,
				});

				return;
			}
			await this.store.dispatch('commentModel/showLoader', { messageId })
				.catch((error) => {
					logger.error(`${this.constructor.name}.channelCommentTapHandler: show loader error`, error);
				})
			;

			logger.log('Dialog.channelCommentTapHandler: comment info is not found, load comment chat by post id');

			const commentDialogId = await this.chatService.loadCommentChatWithMessagesByPostId(Number(messageId));
			logger.log(`Dialog.channelCommentTapHandler: loaded comment dialogId ${commentDialogId}`);

			MessengerEmitter.emit(EventType.messenger.openDialog, {
				dialogId: commentDialogId,
				chatType: DialogType.comment,
			});

			this.store.dispatch('commentModel/hideLoader', { messageId })
				.catch((error) => {
					logger.error(`${this.constructor.name}.channelCommentTapHandler: hide loader error`, error);
				})
			;
		}

		/**
		 * @private
		 */
		async openSidebar()
		{
			const dialogId = this.getDialogId();
			logger.info('Dialog.openSidebar', dialogId);

			const dialogModel = this.store.getters['dialoguesModel/getById'](dialogId);
			// if curren role guest then open sidebar is none for it
			if (dialogModel && dialogModel.role && dialogModel.role === UserRole.guest)
			{
				Haptics.notifyFailure();

				return;
			}

			const openSidebarParams = {
				dialogId,
			};

			if (DialogType.comment === dialogModel.type)
			{
				this.sidebar = new CommentSidebarController(openSidebarParams);
				this.sidebar.open(this.view.ui);

				return;
			}

			if (DialogType.collab === dialogModel.type)
			{
				this.sidebar = new CollabSidebarController(openSidebarParams);
				this.sidebar.open(this.view.ui);

				return;
			}

			if ([DialogType.channel, DialogType.openChannel, DialogType.generalChannel].includes(dialogModel.type)
				&& dialogModel?.role !== UserRole.guest)
			{
				this.sidebar = new ChannelSidebarController(openSidebarParams);
				this.sidebar.open(this.view.ui);

				return;
			}

			this.sidebar = new ChatSidebarController(openSidebarParams);
			this.sidebar.open(this.view.ui);
		}

		/**
		 * @private
		 */
		statusFieldTapHandler()
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
			const dialog = this.getDialog();

			if (dialog.role === UserRole.guest)
			{
				this.joinUserChat();

				return;
			}

			if (this.isMuted())
			{
				this.chatService.unmuteChat(this.getDialogId());
			}
			else
			{
				this.chatService.muteChat(this.getDialogId());
			}
		}

		/**
		 * @private
		 */
		messageAvatarTapHandler(index, message)
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
				UserProfile.show(authorId, {
					backdrop: true,
					openingDialogId: this.getDialogId(),
				});
			}
		}

		messageAvatarLongTapHandler(index, message)
		{
			const messageId = Number(message.id);
			const modelMessage = this.store.getters['messagesModel/getById'](messageId);
			if (!modelMessage)
			{
				return;
			}

			if (modelMessage.id === this.getDialog().parentMessageId)
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

			if (user.bot)
			{
				return;
			}

			if (!ChatPermission.isCanOpenAvatarMenu(this.getDialog()))
			{
				return;
			}

			MessageAvatarMenu.createByAuthorId(authorId, {
				isBot: user.bot,
				isCanMention: ChatPermission.isCanMention(this.getDialog()),
				dialogId: this.getDialogId(),
			}).open();
			Haptics.impactMedium();
		}

		/**
		 * @private
		 */
		messageDoubleTapHandler(index, message)
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
		changeTextHandler(text)
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
		attachTapHandler()
		{
			let resolveImagePickerPromise = null;
			const closeImagePickerPromise = new Promise((resolve) => {
				resolveImagePickerPromise = resolve;
			});

			const selectedFilesHandler = (fileList) => {
				this.sendingService.sendFiles(this.getDialogId(), fileList)
					.catch((error) => logger.error(`${this.constructor.name}.attachTapHandler.sendFiles`, error))
				;
			};

			const closeCallback = () => resolveImagePickerPromise();

			const itemSelectedCallback = (id) => {
				const callbacks = {
					[AttachPickerId.task]: () => this.entityManager.createTask(),
					[AttachPickerId.meeting]: () => this.entityManager.createMeeting(),
				};

				if (callbacks[id])
				{
					void closeImagePickerPromise
						.then(() => callbacks[id]())
						.catch((error) => logger.error(`${this.constructor.name}.attachTapHandler.closeImagePickerPromise.catch:`, error));
				}
			};

			this.view.showAttachPicker(
				selectedFilesHandler,
				closeCallback,
				itemSelectedCallback,
			);
		}

		/**
		 * @param {object} options
		 * @param {string} options.dialogId
		 * @param {string} options.text
		 */
		insertTextHandler({ dialogId, text })
		{
			if (dialogId !== this.getDialogId())
			{
				return;
			}

			const inputText = `${this.view.getInput()} ${text}`;
			this.view.setInput(inputText);
		}

		/**
		 * @param {object} options
		 * @param {string} options.dialogId
		 * @param {string} options.text
		 * @param {boolean} options.shouldFinishTextFieldActions
		 */
		async sendMessageExternalHandler({ dialogId, text, shouldFinishTextFieldActions = false })
		{
			if (dialogId !== this.getDialogId())
			{
				return;
			}

			await this.sendMessage(text, null, shouldFinishTextFieldActions);
		}

		async sendMessage(text, promptCode = null, shouldFinishTextFieldActions = true)
		{
			if (shouldFinishTextFieldActions)
			{
				this.view.clearInput();
				this.draftManager.saveDraft('');
				this.cancelWritingRequest();
			}

			if (this.mentionManager?.isMentionProcessed)
			{
				this.mentionManager?.finishMentioning();
			}

			if (this.replyManager.isEditInProcess && shouldFinishTextFieldActions)
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

			const sendMessageParams = this.prepareMessageToSend(text, promptCode);
			await this.contextManager.goToBottomMessageContext();

			this.sendMessageToModel(sendMessageParams)
				.then(async () => {
					if (shouldFinishTextFieldActions)
					{
						this.cancelReplyHandler();
						this.draftManager.saveDraft('');
					}

					await this.view.scrollToBottomSmoothly();
					await this.sendMessageToServer(sendMessageParams);
				})
				.catch((error) => logger.error('Dialog.sendMessage.error', error))
			;
		}

		prepareMessageToSend(text, promptCode = null)
		{
			const forwardingMessages = [];
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

			if (promptCode)
			{
				requestParams.copilot = { promptCode };
			}

			if (this.replyManager.isQuoteInProcess)
			{
				const quoteMessage = this.replyManager.getQuoteMessage();
				const quoteMessageId = Number(quoteMessage.id);
				message.params = { replyId: quoteMessageId };
				requestParams.replyId = quoteMessageId;
			}

			if (this.replyManager.isForwardInProcess)
			{
				const forwardMessageIds = this.replyManager.getForwardMessageIds(); // need to forward message to the same chat
				for (const id of forwardMessageIds)
				{
					const forwardModel = this.store.getters['messagesModel/getById'](Number(id));

					const forwardUuid = Uuid.getV4();

					const prepareForwardMessage = {
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

					if (Type.isNil(requestParams.forwardIds))
					{
						requestParams.forwardIds = {};
					}
					requestParams.forwardIds[forwardUuid] = forwardModel.id;

					forwardingMessages.push(prepareForwardMessage);
				}

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
				forwardingMessages,
				requestParams,
			};
		}

		async sendMessageToModel(sendMessageParams)
		{
			if (Type.isStringFilled(sendMessageParams.message.text)) // check when forward without comment message
			{
				await this.store.dispatch('messagesModel/add', sendMessageParams.message);
			}

			if (Type.isArrayFilled(sendMessageParams.forwardingMessages))
			{
				const addForwardMessagePromiseCollection = [];
				for (const forwardingMessage of sendMessageParams.forwardingMessages)
				{
					addForwardMessagePromiseCollection.push(
						this.store.dispatch('messagesModel/add', forwardingMessage),
					);
				}
				await Promise.all(addForwardMessagePromiseCollection);
			}
		}

		/**
		 * @private
		 * @param {{message: Object, forwardingMessages: Array<Object|null>, requestParams: Object}} sendMessageParams
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

				const code = ErrorCode[response?.[0]?.code] || 0;
				await this.store.dispatch('messagesModel/update', {
					id: sendMessageParams.message.templateId,
					fields: {
						error: true,
						errorReason: code,
					},
				});

				if (Type.isArrayFilled(sendMessageParams.forwardingMessages))
				{
					const updateForwardMessagePromiseCollection = [];
					for (const forwardingMessage of sendMessageParams.forwardingMessages)
					{
						updateForwardMessagePromiseCollection.push(
							this.store.dispatch('messagesModel/update', {
								id: forwardingMessage.templateId,
								fields: {
									error: true,
									errorReason: code,
								},
							}),
						);
					}
					await Promise.all(updateForwardMessagePromiseCollection);
				}
			}
			finally
			{
				await this.setRecentNewMessage(id);
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
		async resendMessages()
		{
			let resolveResend = Promise.resolve();
			let rejectResend = Promise.reject();
			const resendPromise = new Promise((resolve, reject) => {
				resolveResend = resolve;
				rejectResend = reject;
			});

			this.resendQueuePromise = this.resendQueuePromise
				.then(() => {
					const resendInternalPromise = this.#resendInternal();

					resendInternalPromise
						.then(() => {
							logger.log(`${this.constructor.name}.resendInternal: complete`);

							resolveResend();
						})
						.catch((error) => {
							logger.log(`${this.constructor.name}.resendInternal error:`, error);

							// eslint-disable-next-line promise/no-return-wrap
							return Promise.resolve();
						})
					;
				})
			;

			await resendPromise;
		}

		async #resendInternal()
		{
			const breakMessages = this.store.getters['messagesModel/getBreakMessages'](this.getChatId());
			logger.info('Dialog.resendMessages', breakMessages);

			const isManualSend = (message) => this.isWaitSendExpired(message.date)
				|| message.errorReason === 0
				|| message.errorReason === ErrorCode.INTERNAL_SERVER_ERROR;

			for (const message of breakMessages)
			{
				if (isManualSend(message))
				{
					continue;
				}

				const bottomMessage = this.view.getBottomMessage();
				const isBottomMessage = bottomMessage.id === message.id;

				await this.resendMessage(Number(!isBottomMessage), message);
			}
		}

		/**
		 * @param {number} index
		 * @param {MessagesModelState|Message} message
		 * @returns {Promise<void>}
		 */
		async resendMessage(index, message)
		{
			if (Type.isPlainObject(index))
			{
				// eslint-disable-next-line no-param-reassign
				({ message } = index);
			}

			const modelMessage = this.store.getters['messagesModel/getById'](message.id);
			if (!('id' in modelMessage))
			{
				return;
			}

			if (Type.isArrayFilled(modelMessage.files))
			{
				await this.#resendMessageWithFiles(modelMessage);

				return;
			}

			await this.#resendTextMessage(index, modelMessage);
		}

		/**
		 * @desc Resend break message
		 * @param {number | {message: Message, index: null}} index
		 * @param {MessagesModelState} message
		 * @private
		 */
		async #resendTextMessage(index, message)
		{
			let messageIndex = 0;
			if (Type.isObject(index)) // when the method is called from an event with MessengerEmitter
			{
				// eslint-disable-next-line no-param-reassign
				({ message } = index);
				const bottomMessage = this.view.getBottomMessage();
				if (message.id !== bottomMessage.id)
				{
					messageIndex = 1;
				}
			}
			else
			{
				messageIndex = index;
			}

			logger.log(`${this.constructor.name}.resendMessage`, messageIndex, message);
			const modelMessage = this.store.getters['messagesModel/getById'](message.id);
			const messageToSend = {
				dialogId: this.getDialogId(),
				text: modelMessage.text,
				messageType: 'self',
				templateId: message.id,
			};

			if (index > 0)
			{
				await this.messageRenderer.delete([message.id]);
				await this.messageRenderer.render([modelMessage]);
				await this.view.scrollToBottomSmoothly();
			}

			await this.store.dispatch('messagesModel/update', {
				id: message.id,
				fields: {
					error: false,
					errorReason: OwnMessageStatus.sending,
				},
			});

			return this.sendMessageToServer({
				requestParams: messageToSend,
				message: modelMessage,
			});
		}

		/**
		 * @param {MessagesModelState} message
		 */
		async #resendMessageWithFiles(message)
		{
			const filesModelList = this.store.getters['filesModel/getListByMessageId'](message.id);

			if (!Type.isArrayFilled(filesModelList))
			{
				return Promise.resolve(true);
			}

			const fileList = filesModelList.map((file) => {
				const sizeParams = {
					width: file.image?.width ?? 0,
					height: file.image?.height ?? 0,
					previewWidth: file.image?.width ?? 0,
					previewHeight: file.image?.height ?? 0,
				};

				return {
					id: file.id,
					previewUrl: file.urlPreview,
					url: file.localUrl,
					type: file.type || FileType.file,
					name: file.name,
					...sizeParams,
				};
			});

			return this.sendingService.resendMessageWithFiles({
				dialogId: this.getDialogId(),
				temporaryMessageId: message.id,
				fileList,
			});
		}

		sendAnalyticsOpenDialog()
		{
			if (this.openingContext === OpenDialogContextType.chatCreation)
			{
				return;
			}

			AnalyticsService.getInstance().sendChatOpened({
				dialogId: this.getDialogId(),
				context: this.openingContext,
			}).catch((error) => {
				logger.error(`${this.constructor.name}.sendAnalyticsOpenDialog error`, error);
			});
		}

		setReaction(reaction, message, like)
		{
			const messageId = Number(message.id);

			this.messageService.setReaction(reaction, messageId);
		}

		/**
		 * @deprecated
		 * @private
		 */
		messageFileDownloadTapHandler(index, message)
		{
			if (Feature.isChatDialogWidgetFileDownloadTapEventSupported)
			{
				return;
			}

			logger.log('Dialog.messageFileDownloadTapHandler: ', index, message);
			const fileList = this.store.getters['messagesModel/getMessageFiles'](message.id);
			if (!Type.isArrayFilled(fileList))
			{
				return;
			}

			this.fileDownloadTapHandler(fileList[0].id, message.id);
		}

		/**
		 * @private
		 */
		fileDownloadTapHandler(fileId, messageId)
		{
			logger.log('Dialog.fileDownloadTapHandler: ', fileId, messageId);
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const file = this.store.getters['filesModel/getById'](fileId);
			if (!file)
			{
				return;
			}

			FileDownloadMenu
				.createByFileId({
					fileId: file.id,
					dialogId: this.getDialogId(),
				})
				.open()
			;
		}

		/**
		 * @private
		 */
		messageFileUploadCancelTapHandler(index, message, mediaId)
		{
			logger.log('Dialog.messageFileUploadCancelTapHandler: ', index, message, mediaId);
			const modelMessage = this.store.getters['messagesModel/getById'](message.id);
			if (!modelMessage || !modelMessage.id || !modelMessage.files || !modelMessage.files[0])
			{
				return;
			}

			this.sendingService.cancelFileUpload(modelMessage.id, modelMessage.files, mediaId)
				.catch((error) => logger.error(`${this.constructor.name}.messageFileUploadCancelTapHandler.cancelFileUpload catch:`, error));
		}

		/**
		 * @private
		 */
		replyHandler(index, message)
		{
			if (
				this.replyManager.isQuoteInProcess
				&& message.id === this.replyManager.getQuoteMessage().id
			)
			{
				return;
			}

			if (!ChatPermission.isCanReply(this.getDialog()))
			{
				return;
			}

			if (this.isChannel())
			{
				return;
			}

			if (this.isComment() && this.getDialog().parentMessageId === Number(message.id))
			{
				return;
			}

			this.replyManager.startQuotingMessage(message);
		}

		/**
		 * @private
		 */
		readyToReplyHandler(index, message)
		{
			if (!ChatPermission.isCanReply(this.getDialog()))
			{
				Haptics.notifyFailure();

				return;
			}

			if (this.isChannel())
			{
				Haptics.notifyFailure();

				return;
			}

			if (this.isComment() && this.getDialog().parentMessageId === Number(message.id))
			{
				Haptics.notifyFailure();

				return;
			}

			Haptics.impactMedium();
		}

		/**
		 * @private
		 */
		async quoteTapHandler(message)
		{
			logger.log(`${this.constructor.name}.quoteTapHandler:`, message);
			const messageId = message.id;
			const modelMessage = this.store.getters['messagesModel/getById'](messageId);
			if (!modelMessage)
			{
				return;
			}

			await this.contextManager.goToMessageContext({
				dialogId: this.getDialogId(),
				messageId,
			});
		}

		/**
		 * @private
		 */
		async messageQuoteTapHandler(dialogId, messageId)
		{
			await this.contextManager.goToMessageContext({
				dialogId,
				messageId,
			});
		}

		/**
		 * @private
		 */
		cancelReplyHandler()
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

		async deleteDialogHandler({
			dialogId,
			shouldSendDeleteAnalytics = true,
			shouldShowAlert = true,
			deleteByCurrentUserFromMobile = false,
		})
		{
			if (String(this.getDialogId()) !== String(dialogId))
			{
				return;
			}

			if (deleteByCurrentUserFromMobile)
			{
				this.isChatDeletedByCurrentUserFromMobile = true;
			}

			this.isChatDeleted = true;

			if (!this.isShown)
			{
				return;
			}

			this.view.back();
			if (shouldShowAlert && !this.isChatDeletedByCurrentUserFromMobile)
			{
				this.showDeletionToast();

				if (shouldSendDeleteAnalytics)
				{
					this.sendDeletionAnalytics();
				}
			}
		}

		/* endregion event handlers */

		showDeletionToast()
		{
			if (this.internalState.chatType === DialogType.comment)
			{
				Notification.showToast(ToastType.messageNotFound);

				return;
			}

			Notification.showToast(ToastType.chatAccessDenied);
		}

		sendDeletionAnalytics()
		{
			if (this.internalState.chatType === DialogType.comment)
			{
				AnalyticsService.getInstance().sendToastShownChannelPublicationNotFound({
					chatId: this.internalState.chatId,
					parentChatId: this.internalState.parentChatId,
				});

				return;
			}

			AnalyticsService.getInstance().sendToastShownChatDelete({
				chatId: this.internalState.chatId,
				isChatOpened: true,
				chatType: this.internalState.chatType,
			});
		}

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
		async drawMessageList(mutation)
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

			const parentMessage = this.getInitialPostMessage();
			if (parentMessage)
			{
				currentDialogMessageList.unshift(parentMessage);
			}

			await this.messageRenderer.render(currentDialogMessageList);
			this.drawStatusField();
		}

		/**
		 * @return {MessagesModelState|null}
		 */
		getInitialPostMessage()
		{
			if (!this.isComment())
			{
				return null;
			}

			const dialog = this.getDialog();

			if (dialog.hasPrevPage || Number(dialog.parentMessageId) === 0)
			{
				return null;
			}
			const message = this.store.getters['messagesModel/getById'](this.getDialog().parentMessageId);

			const validateQuoteMessage = this.validateQuote(message);

			return {
				...validateQuoteMessage,
				reactions: this.store.getters['messagesModel/reactionsModel/getByMessageId'](message.id),
			};
		}

		isParentMessage(messageId)
		{
			return Number(this.getDialog().parentMessageId) === Number(messageId);
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
		 * @param {MutationPayload<
		 * DialoguesUpdateData | DialoguesAddData | UsersSetData,
		 * DialoguesUpdateActions | DialoguesAddActions | UsersSetActions | CopilotUpdateData
		 * >} mutation.payload
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

			if (mutation.payload.data.fields?.type)
			{
				this.internalState.chatType = mutation.payload.data.fields?.type;
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

			if (
				mutation.payload.data
				&& (mutation.payload.data.fields?.role || mutation.payload.data.fields?.muteList))
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
					this.redrawHeaderButtons();
					break;
				case 'mute':
				case 'unmute': {
					this.redrawHeaderButtons();

					break;
				}

				case 'updateTariffRestrictions': {
					return this.messageRenderer.pushPlanLimitMessage();
				}
				default:
					this.redrawHeader(mutation);
					break;
			}

			return true;
		}

		/**
		 * @param {object} mutation
		 * @param {MutationPayload<CollabSetGuestCountData, CollabSetGuestCountActions>} mutation.payload
		 * @param {CollabSetGuestCountData} mutation.payload.data
		 */
		collabInfoUpdateHandler(mutation)
		{
			const { dialogId } = mutation.payload.data;

			if (this.dialogId !== dialogId)
			{
				return;
			}

			this.headerTitle.renderTitle();
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
			if (Type.isNil(message?.id))
			{
				return;
			}

			const bottomMessage = this.view.getBottomMessage();
			if (
				(isCheckBottom
				&& bottomMessage
				&& message.id !== Number(bottomMessage.id))
				|| bottomMessage.id === MessageIdType.planLimitBanner)
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
		async redrawReactionMessages(mutation)
		{
			if (mutation.payload.actionName !== 'setFromPullEvent')
			{
				return;
			}
			logger.log(`Dialog.redrawReactionMessages ${this.dialogId}`, mutation);

			/** @type {Array<MessagesModelState>} */
			const result = [];
			mutation.payload.data.reactionList.forEach((reaction) => {
				const messageId = reaction.messageId;

				const message = this.store.getters['messagesModel/getById'](messageId);

				if (!('id' in message))
				{
					return;
				}

				if (message.chatId !== this.getChatId() && message.id !== this.getDialog().parentMessageId)
				{
					return;
				}

				const validateQuoteMessage = this.validateQuote(message);
				result.push(validateQuoteMessage);
			});

			if (result.length === 0)
			{
				return;
			}
			logger.log(`Dialog.redrawReactionMessages ${this.dialogId}`, result);

			await this.messageRenderer.updateMessageList(result);
		}

		/**
		 * @param {ReactionsModelState} mutation.payload.data.reaction
		 */
		async redrawReactionMessage(mutation)
		{
			logger.log(`Dialog.redrawReactionMessage ${this.dialogId}`, mutation);
			const message = this.store.getters['messagesModel/getById'](mutation.payload.data.reaction.messageId);

			if (!('id' in message))
			{
				return;
			}

			if (message.chatId !== this.getChatId() && message.id !== this.getDialog().parentMessageId)
			{
				return;
			}

			const validateQuoteMessage = this.validateQuote(message);

			logger.log(`Dialog.redrawReactionMessage ${this.dialogId}`, validateQuoteMessage);

			await this.messageRenderer.updateMessageList([validateQuoteMessage]);
		}

		/**
		 * @param {MutationPayload<
		 * CommentsSetCommentsData | CommentsSetCountersData,
		 * CommentsSetCommentsActions | CommentsSetCountersActions
		 * >} mutation.payload
		 */
		async updateCommentRouter(mutation)
		{
			logger.log(`Dialog.updateCommentRouter ${this.dialogId}`, mutation);
			this.commentButton.redraw();
			const { data, actionName } = mutation.payload;
			if (!this.getDialog().inited)
			{
				return;
			}

			const actionListToRerender = [
				'setComments',
				'setCommentWithCounter',
				'updateComment',
				'setComment',
				'showLoader',
				'hideLoader',
			];

			if (actionListToRerender.includes(actionName))
			{
				const messageList = [];
				data.commentList.forEach((comment) => {
					const messageId = Type.isNumber(Number(comment.messageId))
						? Number(comment.messageId)
						: comment.messageId
					;

					if (this.messageRenderer.isMessageRendered(Number(messageId)))
					{
						const message = this.store.getters['messagesModel/getById'](messageId);
						messageList.push(this.validateQuote(message));
					}
				});
				logger.log('Dialog.updateCommentRouter: update messages by comment info data', messageList);

				if (messageList.length === 0)
				{
					return;
				}

				await this.messageRenderer.render(messageList, 'commentInfo');

				return;
			}

			if (actionName === 'setCounters')
			{
				const messageList = [];
				Object.entries(data.chatCounterMap).forEach(([channelChatId, countersMap]) => {
					if (this.getChatId() !== Number(channelChatId))
					{
						return;
					}

					Object.entries(countersMap).forEach(([commentChatId, counter]) => {
						const comment = this.store.getters['commentModel/getCommentInfoByCommentChatId'](Number(commentChatId));
						if (this.messageRenderer.isMessageRendered(comment?.messageId))
						{
							const message = this.store.getters['messagesModel/getById'](comment.messageId);
							messageList.push(this.validateQuote(message));
						}
					});
				});
				logger.log('Dialog.updateCommentRouter: update messages by new unread comment counters', messageList);
				if (messageList.length === 0)
				{
					return;
				}

				await this.messageRenderer.render(messageList, 'commentInfo');
			}
		}

		/**
		 *
		 * @param {MutationPayload<
		 * CommentsDeleteChannelCountersData,
		 * CommentsDeleteChannelCountersActions
		 * >} mutation.payload
		 */
		deleteChannelCountersHandler(mutation)
		{
			logger.log(`Dialog.deleteChannelCountersHandler ${this.dialogId}`, mutation);
			this.commentButton.redraw();

			if (mutation.payload.data.channelId !== this.getChatId())
			{
				return;
			}
			const { commentChatIdList } = mutation.payload.data;

			const updateMessageList = [];
			for (const commentChatId of commentChatIdList)
			{
				const commentInfo = this.store.getters['commentModel/getCommentInfoByCommentChatId'](commentChatId);
				if (!commentInfo)
				{
					continue;
				}

				const modelMessage = this.store.getters['messagesModel/getById'](commentInfo.messageId);
				if ('id' in modelMessage)
				{
					updateMessageList.push(this.validateQuote(modelMessage));
				}
			}

			if (updateMessageList.length === 0)
			{
				return;
			}

			this.messageRenderer.render(updateMessageList, 'commentInfo')
				.catch((error) => {
					logger.error('Dialog.deleteChannelCountersHandler error', error);
				});
		}

		/**
		 *
		 * @param {ReactionType} reactionId
		 * @param {Message} message
		 */
		openReactionViewer(reactionId, message)
		{
			ReactionViewerController.open({
				messageId: message.id,
				reactionId,
				dialogId: this.getDialogId(),
				parentLayout: this.view.ui,
			});
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

		async redrawMessage(mutation)
		{
			let messageId = mutation.payload.data.id;
			if (Uuid.isV4(messageId))
			{
				messageId = mutation.payload.data.fields.id || messageId;
			}

			const message = this.store.getters['messagesModel/getById'](messageId);
			if (!message || (message.chatId !== this.getChatId() && !this.isParentMessage(messageId)))
			{
				return;
			}
			const cloneMessage = clone(message);
			const validateQuoteMessage = this.validateQuote(cloneMessage);
			await this.messageRenderer.render([validateQuoteMessage]);
		}

		/**
		 * @desc Method is calling view -> native method for update load text progress in message
		 * @param {MutationPayload<MessagesUpdateData, MessagesUpdateActions>} payload
		 * @return {Boolean}
		 * @private
		 */
		updateProgressFileHandler({ payload = {} })
		{
			const messageId = payload?.data?.id;
			const fields = payload?.data?.fields;

			const message = this.store.getters['messagesModel/getById'](messageId);
			if (!message || message.chatId !== this.getChatId())
			{
				return false;
			}
			const isGallery = message.files.length > 1;

			const file = this.store.getters['filesModel/getById'](fields.uploadFileId || message.files[0]);
			if (!file)
			{
				return false;
			}

			const data = {
				messageId,
				currentBytes: file.uploadData.byteSent,
				totalBytes: file.uploadData.byteTotal,
				textProgress: isGallery ? '' : fields.loadText,
				mediaId: fields.uploadFileId,
			};

			return this.view.updateUploadProgressByMessageId(data);
		}

		async deleteMessage(mutation)
		{
			const messageId = mutation.payload.data.id;

			await this.messageRenderer.delete([messageId]);
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

		redrawHeaderButtons()
		{
			this.headerButtons.render(this.view);
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
					WebDialog.getOpenlineDialogByUserCode(options.userCode)
						.then((dialog) => {
							options.dialogId = dialog.dialog_id;
							if (options.dialogId === 0 && Type.isStringFilled(options.fallbackUrl))
							{
								Application.openUrl(options.fallbackUrl);

								return;
							}

							WebDialog.open(options);
						})
						.catch((err) => {
							logger.log('Dialog.openWebDialog.getOpenlineDialogByUserCode.catch:', err);
						})
					;

					return;
				}

				if (Type.isNumber(options.sessionId))
				{
					WebDialog.getOpenlineDialogBySessionId(options.sessionId)
						.then((dialog) => {
							options.dialogId = dialog.dialog_id;
							if (options.dialogId === 0 && Type.isStringFilled(options.fallbackUrl))
							{
								Application.openUrl(options.fallbackUrl);

								return;
							}

							WebDialog.open(options);
						})
						.catch((err) => {
							logger.log('Dialog.openWebDialog.getOpenlineDialogBySessionId.catch:', err);
						})
					;

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

		static getOpenLineParams(options)
		{
			return WebDialog.getOpenLineParams(options);
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
		async setRecentNewMessage(messageId)
		{
			if (Type.isNumber(messageId) || this.isComment())
			{
				return Promise.resolve(true);
			}

			const dialogId = this.getDialogId();
			const recentModel = this.store.getters['recentModel/getById'](dialogId);
			const messageModel = this.store.getters['messagesModel/getById'](messageId);

			if (!recentModel && Type.isUndefined(messageModel.id))
			{
				return Promise.resolve(true);
			}

			const isMessageFile = Type.isArray(messageModel.files) && messageModel.files.length > 0;
			const isUploadingMessage = this.store.getters['messagesModel/isUploadingMessage'](messageId);

			let subTitleIcon = SubTitleIconType.reply;
			if (isMessageFile && isUploadingMessage)
			{
				subTitleIcon = SubTitleIconType.wait;
			}

			if (messageModel.error)
			{
				const isManualSend = this.isWaitSendExpired(messageModel.date)
					|| messageModel.errorReason === 0
					|| messageModel.errorReason === ErrorCode.INTERNAL_SERVER_ERROR
				;

				subTitleIcon = isManualSend ? SubTitleIconType.error : SubTitleIconType.wait;
			}

			let status = MessageStatus.received;
			if (messageModel && Type.isBoolean(messageModel.viewedByOthers) && messageModel.viewedByOthers)
			{
				status = MessageStatus.delivered;
			}

			const date = new Date();

			let recentItem = RecentConverter.fromPushToModel({
				id: dialogId,
				chat: recentModel ? recentModel.chat : this.getDialog(),
				message: {
					id: messageId,
					senderId: messageModel.authorId,
					text: isMessageFile ? `[${BX.message('IM_F_FILE')}]` : messageModel.text,
					date,
					subTitleIcon,
					status,
				},
				lastActivityDate: date,
			});

			if (isUploadingMessage)
			{
				recentItem = {
					...recentModel,
					uploadingState: {
						lastActivityDate: date,
						message: recentItem.message,
					},
				};
			}

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

		/**
		 * @return {boolean}
		 */
		isHistoryLimitExceeded()
		{
			return DialogHelper.createByModel(this.getDialog())?.isHistoryLimitExceeded;
		}

		/**
		 * @return {Promise}
		 */
		setPlanLimitsBanner()
		{
			// FIXME change to pushMessages, when Android is ready to add,
			//  an empty field for the status field in the push\add methods
			return this.view.setMessages([this.messageRenderer.getPlanLimitMessage()])
				.catch((error) => logger.error(
					`${this.constructor.name}.setPlanLimitsBanner catch:`,
					error,
				));
		}

		/**
		 * @return {void}
		 */
		sendAnalyticsShowBannerByStart()
		{
			AnalyticsService.getInstance().sendAnalyticsShowBannerByStart({ dialog: this.getDialog() });
		}
	}

	module.exports = { Dialog };
});
