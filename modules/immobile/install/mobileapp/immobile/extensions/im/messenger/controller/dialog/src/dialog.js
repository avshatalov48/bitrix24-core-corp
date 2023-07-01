/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/controller/dialog/dialog
 */
jn.define('im/messenger/controller/dialog/dialog', (require, exports, module) => {

	/* region import */

	const { Type } = require('type');
	const { Loc } = require('loc');
	const { getPressedColor } = require('utils/color');
	const { clone, isEqual } = require('utils/object');
	const { Haptics } = require('haptics');
	const { inAppUrl } = require('in-app-url');
	include('InAppNotifier');

	const {
		EventType,
		FeatureFlag,
		DialogType,
		MessageType,
		ReactionType,
		FileType,
	} = require('im/messenger/const');
	const {
		MessageRest,
	} = require('im/messenger/provider/rest');
	const {
		ChatService,
		MessageService,
	} = require('im/messenger/provider/service');
	const {
		ChatAvatar,
		ChatTitle,
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
	} = require('im/messenger/lib/element');

	const { ReplyManager } = require('im/messenger/controller/dialog/reply-manager');
	const { MessageRenderer } = require('im/messenger/controller/dialog/message-renderer');

	const {
		DialogView,
		AfterScrollMessagePosition,
	} = require('im/messenger/view/dialog');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Logger } = require('im/messenger/lib/logger');
	const { Uuid } = require('utils/uuid');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Counters } = require('im/messenger/lib/counters');
	const { AudioMessagePlayer } = require('im/messenger/controller/dialog/audio-player');
	const { WebDialog } = require('im/messenger/controller/dialog/web');
	const { Calls } = require('im/messenger/lib/integration/immobile/calls');
	const { HeaderMenu } = require('im/messenger/controller/dialog/header/menu');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { backgroundCache } = require('im/messenger/lib/background-cache');
	const { parser } = require('im/messenger/lib/parser');
	const { DialogConverter } = require('im/messenger/lib/converter');
	const {
		uploader,
		UploadTask,
	} = require('im/messenger/lib/uploader');

	/* endregion import */

	/**
	 * @class Dialog
	 */
	class Dialog
	{
		constructor({ storeManager } = {})
		{
			if (storeManager)
			{
				this.store = storeManager.store;
				this.storeManager = storeManager;
			}
			else
			{
				throw new Error('DialogList: options.storeManager is required');
			}

			this.dialogId = 0;
			this.titleParams = null;

			this.chatService = new ChatService(this.store);
			this.messageService = null;
			this.messageRenderer = null;
			this.audioMessagePlayer = new AudioMessagePlayer(this.store);

			/* region View event handlers */

			this.submitHandler = this.sendMessage.bind(this);
			this.attachTapHandler = this.onAttachTap.bind(this);
			this.resendHandler = this.resendMessage.bind(this);
			this.loadTopPageHandler = this.loadTopPage.bind(this);
			this.loadBottomPageHandler = this.loadBottomPage.bind(this);
			this.scrollBeginHandler = this.onScrollBegin.bind(this);
			this.scrollEndHandler = this.onScrollEnd.bind(this);
			this.likeHandler = this.onLike.bind(this);
			this.replyHandler = this.onReply.bind(this);
			this.readyToReplyHandler = this.onReadyToReply.bind(this);
			this.quoteTapHandler = this.onQuoteTap.bind(this);
			this.cancelReplyHandler = this.onCancelReply.bind(this);
			this.viewableMessagesChangedHandler = this.onViewableMessagesChanged.bind(this);
			this.messageReadHandler = this.onReadMessage.bind(this);
			this.scrollToNewMessagesHandler = this.onScrollToNewMessages.bind(this);
			this.playAudioButtonTapHandler = this.onAudioButtonTap.bind(this);
			this.playbackCompletedHandler = this.onPlaybackCompleted.bind(this);
			this.urlTapHandler = this.onUrlTap.bind(this);
			this.mentionTapHandler = this.onMentionTap.bind(this);
			this.messageTapHandler = this.onMessageTap.bind(this);
			this.messageAvatarTapHandler = this.onMessageAvatarTap.bind(this);
			this.messageQuoteTapHandler = this.onMessageQuoteTap.bind(this);
			this.messageLongTapHandler = this.onMessageLongTap.bind(this);
			this.messageDoubleTapHandler = this.onMessageDoubleTap.bind(this);
			this.messageMenuReactionTapHandler = this.onLike.bind(this);
			this.messageMenuActionTapHandler = this.onMessageMenuAction.bind(this);
			this.closeHandler = this.onClose.bind(this);

			/* endregion View event handlers */

			//render functions
			this.storeHandler = this.drawMessageList.bind(this);
			this.updateHandler = this.redrawMessage.bind(this);
			this.dialogUpdateHandler = this.redrawHeader.bind(this);
			this.dialogDeleteHandler = () => {};

			if (FeatureFlag.dialog.nativeSupported)
			{
				//TODO: generalize the approach to background caching
				Dialog.preloadAssets();
			}
		}

		static preloadAssets()
		{
			backgroundCache.downloadImages([
				CopyAction.imageUrl,
				QuoteAction.imageUrl,
				ProfileAction.imageUrl,
				EditAction.imageUrl,
				DeleteAction.imageUrl,
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
				.on(EventType.dialog.like, this.likeHandler)
				.on(EventType.dialog.reply, this.replyHandler)
				.on(EventType.dialog.readyToReply, this.readyToReplyHandler)
				.on(EventType.dialog.quoteTap, this.quoteTapHandler)
				.on(EventType.dialog.cancelReply, this.cancelReplyHandler)
				.on(EventType.dialog.viewableMessagesChanged, this.viewableMessagesChangedHandler)
				.on(EventType.dialog.messageRead, this.messageReadHandler)
				.on(EventType.dialog.scrollToNewMessages, this.scrollToNewMessagesHandler)
				.on(EventType.dialog.playAudioButtonTap, this.playAudioButtonTapHandler)
				.on(EventType.dialog.playbackCompleted, this.playbackCompletedHandler)
				.on(EventType.dialog.urlTap, this.urlTapHandler)
				.on(EventType.dialog.mentionTap, this.mentionTapHandler)
				.on(EventType.dialog.messageTap, this.messageTapHandler)
				.on(EventType.dialog.messageAvatarTap, this.messageAvatarTapHandler)
				.on(EventType.dialog.messageQuoteTap, this.messageQuoteTapHandler)
				.on(EventType.dialog.messageLongTap, this.messageLongTapHandler)
				.on(EventType.dialog.messageDoubleTap, this.messageDoubleTapHandler)
				.on(EventType.dialog.messageMenuReactionTap, this.messageMenuReactionTapHandler)
				.on(EventType.dialog.messageMenuActionTap, this.messageMenuActionTapHandler)
				.on(EventType.view.close, this.closeHandler)
			;
		}

		unsubscribeViewEvents()
		{
			this.view.removeAll();
		}

		subscribeStoreEvents()
		{
			this.storeManager
				.on('messagesModel/store', this.storeHandler)
				.on('messagesModel/update', this.updateHandler)
				.on('messagesModel/updateWithId', this.updateHandler)
				.on('dialoguesModel/add', this.dialogUpdateHandler)
				.on('dialoguesModel/update', this.dialogUpdateHandler)
				.on('dialoguesModel/delete', this.dialogDeleteHandler)
			;
		}

		unsubscribeStoreEvents()
		{
			this.storeManager
				.off('messagesModel/store', this.storeHandler)
				.off('messagesModel/update', this.updateHandler)
				.off('messagesModel/updateWithId', this.updateHandler)
				.off('dialoguesModel/add', this.dialogUpdateHandler)
				.off('dialoguesModel/update', this.dialogUpdateHandler)
				.off('dialoguesModel/delete', this.dialogDeleteHandler)
			;
		}

		initManagers()
		{
			this.replyManager = new ReplyManager({
				store: this.store,
				dialogView: this.view,
			});
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
				!FeatureFlag.dialog.nativeSupported
				|| !chatSettings.chatBetaEnable
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

		createWidget(titleParams = null)
		{
			if (!titleParams)
			{
				titleParams = this.createTitleParams();
			}

			this.titleParams = titleParams;
			PageManager.openWidget(
				'chat.dialog',
				{
					titleParams: this.titleParams,
				},
			)
				.then(this.onWidgetReady.bind(this))
				.catch(error => Logger.error(error))
			;
		}

		onWidgetReady(widget)
		{
			this.createView(widget);
			this.drawHeaderButtons();
			this.subscribeViewEvents();
			this.subscribeStoreEvents();
			this.initManagers();

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

			loadMessagesPromise.then(() => {
				this.view.hideMessageListLoader();
				this.messageService = new MessageService({
					store: this.store,
					chatId: this.getChatId(),
				});

				this._redrawHeader();
			});
		}

		createView(widget)
		{
			this.view = new DialogView({
				ui: widget,
				dialogId: this.getDialogId(),
				chatId: this.getChatId(),
			});

			this.messageRenderer = new MessageRenderer({
				store: this.store,
				view: this.view,
				dialogId: this.getDialogId(),
				chatId: this.getChatId(),
			});

			this.view.setInputPlaceholder(Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_INPUT_PLACEHOLDER_TEXT'));
		}
		createTitleParams()
		{
			const dialogId = this.getDialogId();
			const avatar = ChatAvatar.createFromDialogId(dialogId);
			const title = ChatTitle.createFromDialogId(dialogId);

			return {
				...avatar.getTitleParams(),
				...title.getTitleParams(),
				//callback: '1',
			};
		}

		drawHeaderButtons()
		{
			const isDialogWithUser = !DialogHelper.isDialogId(this.getDialogId());
			if (isDialogWithUser)
			{
				this.drawUserHeaderButtons();
				return;
			}

			this.drawDialogHeaderButtons();
		}

		drawUserHeaderButtons()
		{
			const dialogId = this.getDialogId();
			const userData = this.store.getters['usersModel/getUserById'](dialogId);
			if (!userData)
			{
				return;
			}

			if (
				!userData
				|| userData.bot
				|| userData.network
				|| MessengerParams.getUserId() === Number(dialogId)
			)
			{
				return;
			}

			this.view.setRightButtons([
				{
					type: 'call_video',
					badgeCode: 'call_video',
					testId: 'DIALOG_HEADER_VIDEO_CALL_BUTTON',
					callback: this.createVideoCall.bind(this),
				},
			]);
		}

		drawDialogHeaderButtons()
		{
			const dialogId = this.getDialogId();
			const dialogData = this.store.getters['dialoguesModel/getById'](dialogId);
			if (!dialogData)
			{
				return;
			}

			const maxParticipants = 24;
			if (
				dialogData.userCounter > maxParticipants
				|| dialogData.entityType === 'VIDEOCONF' && dialogData.entityData1 === 'BROADCAST'
			)
			{
				if (dialogData.type === DialogType.call)
				{
					return;
				}

				this.view.setRightButtons([{
					type: 'user_plus',
					callback: () => {},
				}]);

				return;
			}

			this.view.setRightButtons([
				{
					type: 'call_video',
					badgeCode: 'call_video',
					testId: 'DIALOG_HEADER_VIDEO_CALL_BUTTON',
					callback: this.createVideoCall.bind(this),
				},
			]);
		}

		onClose()
		{
			const dialogId = this.getDialogId();
			this.unsubscribeStoreEvents();
			this.unsubscribeViewEvents();
			this.audioMessagePlayer.stop();

			this.store.dispatch('applicationModel/closeDialogId', dialogId)
				.then(() => {
					MessengerEmitter.emit(EventType.messenger.closeDialog, dialogId);
				})
			;
		}

		onViewableMessagesChanged(indexList = [], messageList = [])
		{
			if (!this.view.scrollToFirstUnreadCompleted)
			{
				return;
			}

			//TODO: refactor
			const date = this.store.getters['messagesModel/getMessageById'](this.view.getTopMessage().id).date;
			if (date)
			{
				const dateText = DateFormatter.getDateGroupFormat(date);

				this.view.setFloatingText(dateText);
			}

			if (indexList.includes(0))
			{
				this.view.hideScrollToNewMessagesButton();

				return;
			}

			this.view.showScrollToNewMessagesButton();
		}

		onReadMessage(messageId)
		{
			if (!this.view.scrollToFirstUnreadCompleted)
			{
				return;
			}

			this.chatService.readMessage(this.getChatId(), messageId);
		}

		onScrollToNewMessages()
		{
			this.view.scrollToBottomSmoothly();
		}

		onScrollBegin()
		{
			this.view.showFloatingText();
		}

		onScrollEnd()
		{
			this.view.hideFloatingText();
		}

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

		onPlaybackCompleted()
		{
			this.audioMessagePlayer.playNext();
		}

		onUrlTap(url)
		{
			Logger.log('Dialog.onUrlTap: ', url);

			inAppUrl.open(url);
		}

		onMentionTap(userId)
		{
			MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId: userId.toString() });
		}

		onMessageTap(index, message)
		{
			const modelMessage = this.store.getters['messagesModel/getMessageById'](Number(message.id));
			if (!modelMessage || !modelMessage.files[0])
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

		onMessageAvatarTap(index, message)
		{
			const messageId = Number(message.id);
			const modelMessage = this.store.getters['messagesModel/getMessageById'](messageId);
			if (!modelMessage)
			{
				return;
			}

			const authorId = modelMessage.authorId;
			const user = this.store.getters['usersModel/getUserById'](authorId);
			if (!user)
			{
				return;
			}

			if (!user.bot)
			{
				UserProfile.show(authorId, { backdrop: true });
			}
		}

		onMessageLongTap(index, message)
		{
			const messageId = Number(message.id);
			const isRealMessage = Type.isNumber(messageId);

			const modelMessage = this.store.getters['messagesModel/getMessageById'](messageId);
			const userId = MessengerParams.getUserId();
			const isYourMessage = modelMessage.authorId === userId;
			const isSystemMessage = modelMessage.authorId === 0;
			const isDeletedMessage = modelMessage.params.IS_DELETED === 'Y';
			const isMessageHasText = modelMessage.text !== '';

			if (!isRealMessage)
			{
				return;
			}

			const menu =
				MessageMenu
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

			if (!isYourMessage && !isSystemMessage)
			{
				menu.addAction(ProfileAction);
			}

			if (isYourMessage && !isDeletedMessage)
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

		onMessageDoubleTap(index, message)
		{
			if (!message.showReaction)
			{
				return;
			}

			this.onLike(index, message);
		}

		sendMessage(text)
		{
			this.view.clearInput();

			let shouldScrollToBottom = true;
			if (this.replyManager.isEditInProcess)
			{
				shouldScrollToBottom = false;

				const editMessageId = this.replyManager.getEditMessage().id;
				this.messageService.updateText(editMessageId, text);
				this.replyManager.finishEditingMessage();

				return;
			}

			if (text === '')
			{
				return;
			}

			if (this.replyManager.isQuoteInProcess)
			{
				const quoteText = this.replyManager.getQuoteText();
				text = quoteText + text;

				this.onCancelReply();
			}

			const uuid = Uuid.getV4();

			const message = {
				chatId: this.getChatId(),
				authorId: MessengerParams.getUserId(),
				text,
				unread: false,
				uuid,
				date: new Date(),
				sending: true,
			};

			this.store.dispatch('messagesModel/add', message).then(() => {
				if (shouldScrollToBottom)
				{
					this.view.scrollToBottomSmoothly();
				}

				const message = {
					dialogId: this.getDialogId(),
					text,
					messageType: 'self',
					uuid,
				};

				this._sendMessage(message);
			});
		}

		onAttachTap()
		{
			this.showComingSoonNotification();
			return;

			this.view.showAttachPicker((fileList) => {
				fileList.forEach((file) => {
					// const task = UploadTask.createFromFile(file);
					//
					// uploader.addTask(task);
				});
			});
		}

		_sendMessage(message)
		{
			Logger.log('Dialog._sendMessage', message);

			MessageRest
				.send(message)
				.then((response) => {
					this.store.dispatch('messagesModel/updateWithId', {
						id: message.uuid,
						fields: {
							id: response.data(),
							error: false,
						},
					});
				})
				.catch(() => {
					this.store.dispatch('messagesModel/update', {
						id: message.uuid,
						fields: {
							error: true,
						},
					});
				})
			;
		}

		resendMessage(index, message)
		{
			const modelMessage = this.store.getters['messagesModel/getMessageById'](message.id);
			const messageToSend = {
				dialogId: this.getDialogId(),
				text: modelMessage.text,
				messageType: 'self',
				uuid: message.id,
			};

			this._sendMessage(messageToSend);
		}

		onLike(index, message, like)
		{
			const messageId = Number(message.id);
			const likeList = this.store.getters['messagesModel/getMessageReaction'](messageId, ReactionType.like);
			if (likeList.includes(MessengerParams.getUserId()))
			{
				this.messageService.removeReaction(ReactionType.like, Number(messageId));
			}
			else
			{
				this.messageService.addReaction(ReactionType.like, Number(messageId));
			}
		}

		onMessageMenuAction(actionId, message)
		{
			const messageId = Number(message.id);
			const modelMessage = this.store.getters['messagesModel/getMessageById'](messageId);
			if (!message)
			{
				return;
			}

			switch (actionId)
			{
				case CopyAction.id:
					Application.copyToClipboard(parser.prepareCopy(modelMessage));

					InAppNotifier.showNotification({
						title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_COPIED'),
						time: 1,
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
			}
		}

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

		onReadyToReply()
		{
			Haptics.impactMedium();
		}

		onQuoteTap(message)
		{
			const messageId = message.id;
			const modelMessage = this.store.getters['messagesModel/getMessageById'](messageId);
			if (!modelMessage)
			{
				return;
			}

			this.view.scrollToMessageById(messageId, true, () => {
				//TODO: temporary solution for highlight message after scroll, generalize and refactor
				let viewMessage = DialogConverter.createMessageList([ modelMessage ])[0];
				viewMessage.style.backgroundColor = getPressedColor('#ffffff');
				this.view.updateMessageById(messageId, viewMessage);

				setTimeout(() => {
					viewMessage = DialogConverter.createMessageList([ modelMessage ])[0];
					this.view.updateMessageById(messageId, viewMessage);
				}, 500);
			}, AfterScrollMessagePosition.center);
		}

		onMessageQuoteTap()
		{
			this.showComingSoonNotification();
		}

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

		drawMessageList(mutation)
		{
			const messageList = clone(mutation.payload.messages);

			const currentDialogMessageList = [];
			messageList.forEach(message => {
				if (message.chatId !== this.getChatId())
				{
					return;
				}

				currentDialogMessageList.push(message);
			});

			if (!Type.isArrayFilled(currentDialogMessageList))
			{
				return;
			}

			this.messageRenderer.render(currentDialogMessageList);
		}

		redrawMessage(mutation)
		{
			let messageId = mutation.payload.id;
			const isSendError = mutation.payload.fields.error;
			if (isSendError === false && Uuid.isV4(messageId))
			{
				messageId = mutation.payload.fields.id;
			}

			const message = this.store.getters['messagesModel/getMessageById'](messageId);
			if (!message || message.chatId !== this.getChatId())
			{
				return;
			}

			this.messageRenderer.render([ message ]);
		}

		redrawHeader(mutation)
		{
			const dialogId = mutation.payload.dialogId;
			if (dialogId !== this.getDialogId())
			{
				return;
			}

			this._redrawHeader();
		}

		_redrawHeader()
		{
			const actualTitleParams = this.createTitleParams();
			const isTitleParamsActual = isEqual(actualTitleParams, this.titleParams);
			if (isTitleParamsActual)
			{
				Logger.info('Dialog._redrawHeader: header is up-to-date, redrawing is cancelled.');

				return;
			}

			Logger.info('Dialog._redrawHeader: before: ', this.titleParams, ' after: ', actualTitleParams);
			this.view.setTitle(this.createTitleParams());
			this.titleParams = actualTitleParams;
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
			return new Promise(resolve => {
				if (Type.isStringFilled(options.userCode))
				{
					WebDialog.getOpenlineDialogByUserCode(options.userCode).then(dialog => {
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
				dialogTitleParams
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
			});
		}
	}

	module.exports = { Dialog };
});
