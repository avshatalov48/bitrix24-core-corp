/**
 * @module im/messenger/controller/dialog/lib/message-menu/menu
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/menu', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('im/messenger/loc');
	const { Alert, confirmDestructiveAction } = require('alert');
	const { Icon } = require('assets/icons');
	const { isOnline } = require('device/connection');
	const { EventType, MessageMenuActionType, PinCount } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');
	const { showDeleteChannelPostAlert } = require('im/messenger/lib/ui/alert');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { ForwardSelector } = require('im/messenger/controller/selector/forward');
	const { DialogTextHelper } = require('im/messenger/controller/dialog/lib/helper/text');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');
	const { getLogger } = require('im/messenger/lib/logger');
	const { Color } = require('tokens');

	const {
		CopyAction,
		CopyLinkAction,
		PinAction,
		UnpinAction,
		ForwardAction,
		CreateAction,
		ReplyAction,
		ProfileAction,
		EditAction,
		DeleteAction,
		DownloadToDeviceAction,
		DownloadToDiskAction,
		FeedbackAction,
		ResendAction,
		SubscribeAction,
		UnsubscribeAction,
		MultiSelectAction,
		FinishVoteAction,
		RevoteAction,
		OpenVoteResultAction,
	} = require('im/messenger/controller/dialog/lib/message-menu/action');
	const { MessageCreateMenu } = require('im/messenger/controller/dialog/lib/message-create-menu');
	const { MessageHelper } = require('im/messenger/lib/helper');
	const { MessageContextMenu } = require('im/messenger/api/dialog-integration/message/context-menu');
	const { fileSaver } = require('im/messenger/controller/dialog/lib/message-menu/src/saver/file-saver');
	const { diskSaver } = require('im/messenger/controller/dialog/lib/message-menu/src/saver/disk-saver');

	const logger = getLogger('dialog--message-menu');

	/**
	 * @class MessageMenu
	 * @implements {IMessageContextMenu}
	 */
	class MessageMenu extends MessageContextMenu
	{
		/**
		 * @type {number}
		 */
		maxPins = PinCount.max;

		/**
		 * @return {MessengerCoreStore}
		 */
		get store()
		{
			return serviceLocator.get('core').getStore();
		}

		/**
		 * @return {VoteManager}
		 */
		get voteManager()
		{
			return this.dialogLocator.get('vote-manager');
		}

		/**
		 * @param {DialogLocator} dialogLocator
		 */
		setDialogLocator(dialogLocator)
		{
			this.dialogLocator = dialogLocator;
		}

		/**
		 * @abstract
		 * @return {Record<string, (MessageMenuView, MessageMenuMessage) => void>}
		 */
		getActions()
		{
			return {
				[MessageMenuActionType.copy]: this.addCopyAction.bind(this),
				[MessageMenuActionType.copyLink]: this.addCopyLinkAction.bind(this),
				[MessageMenuActionType.pin]: this.addPinAction.bind(this),
				[MessageMenuActionType.unpin]: this.addUnpinAction.bind(this),
				[MessageMenuActionType.subscribe]: this.addSubscribeAction.bind(this),
				[MessageMenuActionType.unsubscribe]: this.addUnsubscribeAction.bind(this),
				[MessageMenuActionType.forward]: this.addForwardAction.bind(this),
				[MessageMenuActionType.create]: this.addCreateAction.bind(this),
				[MessageMenuActionType.reply]: this.addReplyAction.bind(this),
				[MessageMenuActionType.profile]: this.addProfileAction.bind(this),
				[MessageMenuActionType.edit]: this.addEditAction.bind(this),
				[MessageMenuActionType.delete]: this.addDeleteAction.bind(this),
				[MessageMenuActionType.downloadToDevice]: this.addDownloadToDeviceAction.bind(this),
				[MessageMenuActionType.downloadToDisk]: this.addDownloadToDiskAction.bind(this),
				[MessageMenuActionType.feedback]: this.addFeedbackAction.bind(this),
				[MessageMenuActionType.multiselect]: this.addMultiselectAction.bind(this),
				[MessageMenuActionType.resend]: this.addResendAction.bind(this),
				[MessageMenuActionType.finishVote]: this.addFinishVoteAction.bind(this),
				[MessageMenuActionType.revote]: this.addRevoteAction.bind(this),
				[MessageMenuActionType.openVoteResult]: this.addOpenVoteResultAction.bind(this),
			};
		}

		/**
		 * @abstract
		 * @return {Object<string, (MessageMenuMessage) => void>}
		 */
		getActionHandlers()
		{
			return {
				[MessageMenuActionType.copy]: this.onCopy.bind(this),
				[MessageMenuActionType.copyLink]: this.onCopyLink.bind(this),
				[MessageMenuActionType.reply]: this.onReply.bind(this),
				[MessageMenuActionType.pin]: this.onPin.bind(this),
				[MessageMenuActionType.unpin]: this.onUnpin.bind(this),
				[MessageMenuActionType.subscribe]: this.onSubscribe.bind(this),
				[MessageMenuActionType.unsubscribe]: this.onUnsubscribe.bind(this),
				[MessageMenuActionType.forward]: this.onForward.bind(this),
				[MessageMenuActionType.create]: this.onCreate.bind(this),
				[MessageMenuActionType.profile]: this.onProfile.bind(this),
				[MessageMenuActionType.edit]: this.onEdit.bind(this),
				[MessageMenuActionType.delete]: this.onDelete.bind(this),
				[MessageMenuActionType.downloadToDevice]: this.onDownloadToDevice.bind(this),
				[MessageMenuActionType.downloadToDisk]: this.onDownloadToDisk.bind(this),
				[MessageMenuActionType.feedback]: this.onFeedback.bind(this),
				[MessageMenuActionType.multiselect]: this.onMultiSelect.bind(this),
				[MessageMenuActionType.resend]: this.onResend.bind(this),
				[MessageMenuActionType.finishVote]: this.onFinishVote.bind(this),
				[MessageMenuActionType.revote]: this.onRevote.bind(this),
				[MessageMenuActionType.openVoteResult]: this.onOpenVoteResult.bind(this),
				[MessageMenuActionType.goToMessage]: this.onGoToMessage.bind(this),
			};
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addCopyAction(menu, message)
		{
			if (message.isPossibleCopy())
			{
				menu.addAction(CopyAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addCopyLinkAction(menu, message)
		{
			if (message.isPossibleCopyLink())
			{
				menu.addAction(CopyLinkAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addPinAction(menu, message)
		{
			if (message.isPossiblePin())
			{
				menu.addAction(PinAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addUnpinAction(menu, message)
		{
			if (message.isPossibleUnpin())
			{
				menu.addAction(UnpinAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addSubscribeAction(menu, message)
		{
			if (message.isPossibleSubscribe())
			{
				menu.addAction(SubscribeAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addUnsubscribeAction(menu, message)
		{
			if (message.isPossibleUnsubscribe())
			{
				menu.addAction(UnsubscribeAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addForwardAction(menu, message)
		{
			if (message.isPossibleForward())
			{
				menu.addAction(ForwardAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addCreateAction(menu, message)
		{
			if (message.isPossibleCreate() && MessageCreateMenu.hasActions())
			{
				menu.addAction(CreateAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addReplyAction(menu, message)
		{
			if (message.isPossibleReply())
			{
				menu.addAction(ReplyAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addProfileAction(menu, message)
		{
			if (message.isPossibleShowProfile())
			{
				menu.addAction(ProfileAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addEditAction(menu, message)
		{
			if (message.isPossibleEdit())
			{
				menu.addAction(EditAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addDeleteAction(menu, message)
		{
			if (message.isPossibleDelete() && message.isDialogCopilot() && menu.actionList.length === 0)
			{
				menu.addAction(DeleteAction);

				return;
			}

			if (message.isPossibleDelete())
			{
				menu.addSeparator();
				menu.addAction(DeleteAction);
			}

			if (message.isPossibleMultiselect())
			{
				menu.addSeparator();
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addDownloadToDeviceAction(menu, message)
		{
			if (message.isPossibleDownloadToDevice())
			{
				menu.addSeparator();
				menu.addAction(DownloadToDeviceAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addDownloadToDiskAction(menu, message)
		{
			if (message.isPossibleSaveFile())
			{
				menu.addAction(DownloadToDiskAction);
				menu.addSeparator();
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addFeedbackAction(menu, message)
		{
			if (message.isPossibleCallFeedback())
			{
				menu.addAction(FeedbackAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addMultiselectAction(menu, message)
		{
			if (message.isPossibleMultiselect())
			{
				menu.addSeparator();
				menu.addAction(MultiSelectAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addResendAction(menu, message)
		{
			if (message.isPossibleResend())
			{
				menu.addAction(ResendAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addFinishVoteAction(menu, message)
		{
			if (message.isPossibleFinishVote())
			{
				menu.addAction(FinishVoteAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addRevoteAction(menu, message)
		{
			if (message.isPossibleRevote())
			{
				menu.addAction(RevoteAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addOpenVoteResultAction(menu, message)
		{
			if (message.isPossibleOpenVoteResult())
			{
				menu.addAction(OpenVoteResultAction);
			}
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onCopy(message)
		{
			const modelMessage = this.#getMessageModel(message.messageModel.id);

			DialogTextHelper.copyToClipboard(
				modelMessage.text,
				{
					parentWidget: this.#getUiView(),
				},
			);
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onCopyLink(message)
		{
			const messageHelper = MessageHelper.createById(message.messageModel.id);

			const link = messageHelper?.getLinkToMessage();
			if (!Type.isStringFilled(link))
			{
				return;
			}

			DialogTextHelper.copyToClipboard(
				link,
				{
					notificationText: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_COPY_LINK_SUCCESS'),
					notificationIcon: Icon.LINK,
					parentWidget: this.#getUiView(),
				},
				true,
			);

			if (messageHelper?.isVoteModelExist)
			{
				AnalyticsService.getInstance().sendVoteMessageLinkCopied(
					this.getDialog().dialogId,
					messageHelper.voteModel.voteId,
				);
			}
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onReply(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const replyManager = this.dialogLocator.get('reply-manager');

			if (
				replyManager.isQuoteInProcess
				&& message.messageModel.id === replyManager.getQuoteMessage().id
			)
			{
				return;
			}

			const textFieldManager = this.dialogLocator.get('text-field-manager');
			if (textFieldManager?.isHide())
			{
				return;
			}

			replyManager.startQuotingMessage(message.messageModel);
		}

		/**
		 * @param {MessageMenuMessage} message
		 * @param {GoToMessageContextEvent} [params={}]
		 * @return {Promise<void>}
		 */
		onGoToMessage(message, params = {})
		{
			const messageId = message.messageModel?.id;
			if (!messageId || !this.#checkOnlineStatus())
			{
				return Promise.reject();
			}

			const dialogId = this.getDialog().dialogId;

			return this.dialogLocator.get('context-manager').goToMessageContext({
				dialogId,
				messageId,
				...params,
			});
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onPin(message)
		{
			const dialogId = this.#getDialogId();

			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const messageModel = this.#getMessageModel(message.messageModel.id);
			if (!messageModel.id)
			{
				return;
			}

			if (this.#isPinsLimitReached(messageModel.chatId))
			{
				this.#showPinsLimitAlert();

				AnalyticsService.getInstance().sendPinnedMessageLimitException({ dialogId });

				return;
			}

			this.dialogLocator.get('message-service')
				.pinMessage(messageModel.id)
			;

			AnalyticsService.getInstance().sendMessagePin({
				dialogId,
				chatId: messageModel.chatId,
			});
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onUnpin(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const messageModel = this.#getMessageModel(message.messageModel.id);
			if (!messageModel.id)
			{
				return;
			}

			this.dialogLocator.get('message-service')
				.unpinMessage(messageModel.id)
			;

			AnalyticsService.getInstance().sendMessageUnpin({
				dialogId: this.#getDialogId(),
				chatId: messageModel.chatId,
			});
		}

		onSubscribe(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			Notification.showToast(ToastType.subscribeToComments, this.#getUiView());
			const modelMessage = this.#getMessageModel(message.messageModel.id);
			this.dialogLocator.get('chat-service').subscribeToCommentsByPostId(modelMessage.id);
		}

		onUnsubscribe(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			Notification.showToast(ToastType.unsubscribeFromComments, this.#getUiView());
			const modelMessage = this.#getMessageModel(message.messageModel.id);
			this.dialogLocator.get('chat-service').unsubscribeFromCommentsByPostId(modelMessage.id);
		}

		/**
		 * @param {MessageMenuMessage} message
		 * @param parentWidget
		 * @param onItemSelected
		 * @param {boolean} [closeOnSelect=true]
		 * @return {Promise}
		 */
		onForward(message, { parentWidget, onItemSelected, closeOnSelect = true } = {})
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return Promise.reject();
			}

			const forwardSelector = new ForwardSelector({
				messageIds: [message.messageModel.id],
				fromDialogId: this.#getDialogId(),
				locator: this.dialogLocator,
				onDialogSelected: onItemSelected,
				closeOnSelect,
			});

			return forwardSelector.open({ parentWidget });
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onCreate(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const messageModel = this.#getMessageModel(message.messageModel.id);
			if (!messageModel)
			{
				return;
			}

			MessageCreateMenu.open(this.#getDialogId(), messageModel, this.store);
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onProfile(message)
		{
			const messageModel = this.#getMessageModel(message.messageModel.id);
			if (!messageModel.id)
			{
				return;
			}

			UserProfile.show(messageModel.authorId, {
				backdrop: true,
				openingDialogId: this.#getDialogId(),
			});
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onEdit(message)
		{
			this.dialogLocator.get('reply-manager').startEditingMessage(message.messageModel);
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onDelete(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const messageModel = this.#getMessageModel(message.messageModel.id);
			if (!messageModel.id)
			{
				return;
			}

			if (Type.isNumber(messageModel.id))
			{
				AnalyticsService.getInstance().sendMessageDeleteActionClicked({
					messageId: messageModel.id,
					dialogId: this.#getDialogId(),
				});
			}

			const helper = DialogHelper.createByDialogId(this.#getDialogId());

			if (helper?.isChannel)
			{
				showDeleteChannelPostAlert({
					deleteCallback: () => {
						this.dialogLocator.get('message-service')
							.delete(messageModel, this.#getDialogId())
						;
					},
					cancelCallback: () => {
						if (Type.isNumber(messageModel.id))
						{
							AnalyticsService.getInstance().sendMessageDeletingCanceled({
								messageId: messageModel.id,
								dialogId: this.#getDialogId(),
							});
						}
					},
				});

				return;
			}

			this.dialogLocator.get('message-service')
				.delete(messageModel, this.#getDialogId())
			;

			this.#deleteQuotingMessage(message.messageModel.id);
		}

		/**
		 * @param {string} messageId
		 */
		#deleteQuotingMessage(messageId)
		{
			const isQuoteInProcess = this.dialogLocator.get('reply-manager')?.isQuoteInProcess;
			if (!isQuoteInProcess)
			{
				return;
			}

			const quoteMessage = this.dialogLocator.get('reply-manager').getQuoteMessage();
			if (quoteMessage?.id !== messageId)
			{
				return;
			}

			this.dialogLocator.get('reply-manager').finishQuotingMessage();
		}

		/**
		 * @param {MessageMenuMessage} message
		 * @param {LayoutWidget} parentWidget
		 */
		async onDownloadToDevice(message, { parentWidget } = {})
		{
			const messageId = message.messageModel?.id;
			if (!messageId || !this.#checkOnlineStatus())
			{
				return;
			}

			const messageHelper = MessageHelper.createById(messageId);

			if (!messageHelper.isWithFile)
			{
				return;
			}

			void fileSaver({
				locator: this.dialogLocator,
				dialogId: this.#getDialogId(),
				messageHelper: MessageHelper.createById(messageId),
				parentWidget,
			}).save();
		}

		/**
		 * @param {MessageMenuMessage} message
		 * @param {Object} parentWidget
		 */
		onDownloadToDisk(message, { parentWidget = null } = {})
		{
			const messageId = message.messageModel?.id;
			if (!messageId || !this.#checkOnlineStatus())
			{
				return;
			}

			void diskSaver({
				locator: this.dialogLocator,
				dialogId: this.#getDialogId(),
				messageHelper: MessageHelper.createById(messageId),
				parentWidget,
			}).save();
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onFeedback(message)
		{
			let formId = 'copilotRoles';
			if (message.isAiAssistantMessage())
			{
				formId = 'aiAssistant';
			}

			const openFormFallback = () => {
				const hiddenFields = encodeURIComponent(JSON.stringify({
					from_domain: currentDomain,
					back_version: Application.getAppVersion(),
					os_phone: Application.getPlatform(),
					app_version: Application.getApiVersion(),
					region_model: env.languageId,
					phone_model: device.model,
					os_version: device.version,
				}));

				PageManager.openPage({
					backgroundColor: Color.bgSecondary.toHex(),
					url: `${env.siteDir}mobile/settings?formId=${formId}&hiddenFields=${hiddenFields}`,
					backdrop: {
						mediumPositionPercent: 80,
						onlyMediumPosition: true,
						forceDismissOnSwipeDown: true,
						swipeAllowed: true,
						swipeContentAllowed: true,
						horizontalSwipeAllowed: false,
						navigationBarColor: Color.bgSecondary.toHex(),
						enableNavigationBarBorder: false,
					},
					titleParams: {
						text: Loc.getMessage('FEEDBACK_FORM_TITLE'),
					},
					enableNavigationBarBorder: false,
					modal: true,
					cache: true,
				});
			};

			requireLazy('layout/ui/feedback-form-opener').then(({ FeedbackForm }) => {
				if (FeedbackForm)
				{
					(new FeedbackForm({
						formId,
						senderPage: 'MessageMenu',
					})).openInBackdrop();
				}
				else
				{
					openFormFallback();
				}
			}).catch(() => {
				openFormFallback();
			});
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onMultiSelect(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			this.dialogLocator.get('select-manager')
				.enableMultiSelectMode(String(message.messageModel.id))
				.catch((error) => logger.error(
					`${this.constructor.name}.onMultiSelect(${String(message.messageModel.id)}).enableMultiSelectMode catch:`,
					error,
				))
			;
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onResend(message)
		{
			MessengerEmitter.emit(EventType.dialog.external.resend, {
				index: null,
				message: message.messageModel,
			});
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onFinishVote(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const messageModel = this.store.getters['messagesModel/getById'](message.messageModel.id);
			if (!messageModel.id)
			{
				return;
			}

			confirmDestructiveAction({
				title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_FINISH_VOTE_CONFIRM_TITLE'),
				description: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_FINISH_VOTE_CONFIRM_DESCRIPTION'),
				destructionText: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_FINISH_VOTE_CONFIRM_FINISH'),
				onDestruct: () => this.voteManager?.finishVote(messageModel.id),
			});
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onRevote(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const messageModel = this.store.getters['messagesModel/getById'](message.messageModel.id);
			if (!messageModel.id)
			{
				return;
			}

			void this.voteManager?.revote(messageModel.id);
		}

		/**
		 * @param {MessageMenuMessage} message
		 */
		onOpenVoteResult(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const messageModel = this.store.getters['messagesModel/getById'](message.messageModel.id);
			if (!messageModel.id)
			{
				return;
			}

			void this.voteManager?.openVoteResult(messageModel.id);
		}

		/**
		 * @param {number} chatId
		 * @returns {boolean}
		 */
		#isPinsLimitReached(chatId)
		{
			const pinsCounter = this.store.getters['messagesModel/pinModel/getPinsCounter'](chatId);

			return pinsCounter >= this.maxPins;
		}

		#showPinsLimitAlert()
		{
			Alert.alert(
				Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_PIN_LIMIT_ALERT_TITLE', {
					'#MAX_PINS#': this.maxPins,
				}),
				'',
				null,
				Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_PIN_LIMIT_ALERT_BUTTON_NAME'),
			);
		}

		/**
		 * @param {MessageId} messageId
		 * @returns {MessagesModelState|{}}
		 */
		#getMessageModel(messageId)
		{
			return this.store.getters['messagesModel/getById'](messageId);
		}

		/**
		 * @param {MessageId} messageId
		 * @returns {Array<FilesModelState>}
		 */
		#getFilesModel(messageId)
		{
			return this.store.getters['messagesModel/getMessageFiles'](messageId);
		}

		#checkOnlineStatus()
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return false;
			}

			return true;
		}

		#getUiView()
		{
			return this.dialogLocator.get('view').ui;
		}

		/**
		 * @returns {DialogId}
		 */
		#getDialogId()
		{
			return this.getDialog().dialogId;
		}
	}

	module.exports = {
		MessageMenu,
	};
});
