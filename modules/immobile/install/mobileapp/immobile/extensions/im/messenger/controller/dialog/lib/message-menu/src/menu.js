/* eslint no-undef: 0 */
/**
 * @module im/messenger/controller/dialog/lib/message-menu/message-menu
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/message-menu', (require, exports, module) => {
	include('InAppNotifier');
	const { Filesystem, utils } = require('native/filesystem');

	const { Type } = require('type');
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const { NotifyManager } = require('notify-manager');
	const { withCurrentDomain } = require('utils/url');
	const { Icon } = require('assets/icons');
	const { Theme } = require('im/lib/theme');

	const {
		EventType,
		FileType,
		OwnMessageStatus,
		MessageParams,
	} = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { isOnline } = require('device/connection');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Feature } = require('im/messenger/lib/feature');
	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');
	const { showDeleteChannelPostAlert } = require('im/messenger/lib/ui/alert');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { ForwardSelector } = require('im/messenger/controller/forward-selector');
	const { DialogTextHelper } = require('im/messenger/controller/dialog/lib/helper/text');
	const { SelectManager } = require('im/messenger/controller/dialog/lib/select-manager');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { AnalyticsService } = require('im/messenger/provider/service');

	const {
		LikeReaction,
		KissReaction,
		LaughReaction,
		WonderReaction,
		CryReaction,
		AngryReaction,
		FacepalmReaction,
	} = require('im/messenger/controller/dialog/lib/message-menu/reaction');
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
	} = require('im/messenger/controller/dialog/lib/message-menu/action');
	const { ActionType } = require('im/messenger/controller/dialog/lib/message-menu/action-type');
	const { MessageMenuMessage } = require('im/messenger/controller/dialog/lib/message-menu/message');
	const { MessageMenuView } = require('im/messenger/controller/dialog/lib/message-menu/view');
	const { MessageCreateMenu } = require('im/messenger/controller/dialog/lib/message-create-menu');
	const { MessageHelper } = require('im/messenger/lib/helper');

	const logger = LoggerManager.getInstance().getLogger('dialog--message-menu');

	/**
	 * @class MessageMenu
	 */
	class MessageMenu
	{
		/**
		 *
		 * @param {DialogLocator} serviceLocator
		 * @param {DialogId} dialogId
		 */
		constructor({ serviceLocator, dialogId })
		{
			/** @type {DialogLocator} */
			this.locator = serviceLocator;
			this.dialogId = dialogId;
			this.store = serviceLocator.get('store');
			/** @type {Record<string, function(Message): void>} */
			this.handlers = {};

			this.actions = {};

			this.messageLongTapHandler = this.onMessageLongTap.bind(this);
			this.messageMenuActionTapHandler = this.onMessageMenuActionTap.bind(this);
			this.messageMenuReactionTapHandler = this.onMessageMenuReactionTap.bind(this);

			this.registerActions();
			this.registerActionHandlers();
		}

		subscribeEvents()
		{
			this.locator.get('view')
				.on(EventType.dialog.messageMenuActionTap, this.messageMenuActionTapHandler)
				.on(EventType.dialog.messageMenuReactionTap, this.messageMenuReactionTapHandler)
				.on(EventType.dialog.messageLongTap, this.messageLongTapHandler)
			;
		}

		unsubscribeEvents()
		{
			this.locator.get('view')
				.off(EventType.dialog.messageMenuActionTap, this.messageMenuActionTapHandler)
				.off(EventType.dialog.messageMenuReactionTap, this.messageMenuReactionTapHandler)
				.off(EventType.dialog.messageLongTap, this.messageLongTapHandler)
			;
		}

		/**
		 * @param {Message} message
		 * @return {Array<string>}
		 */
		getOrderedActions(message)
		{
			return [
				ActionType.reaction,
				ActionType.reply,
				ActionType.copy,
				ActionType.copyLink,
				ActionType.subscribe,
				ActionType.unsubscribe,
				ActionType.pin,
				ActionType.unpin,
				ActionType.forward,
				ActionType.create,
				ActionType.downloadToDevice,
				ActionType.downloadToDisk,
				ActionType.profile,
				ActionType.edit,
				ActionType.delete,
				ActionType.multiselect,
			];
		}

		/**
		 * @param {Message} message
		 * @return {Array<string>}
		 */
		getOrderedActionsForErrorMessage(message)
		{
			return [
				ActionType.resend,
				ActionType.delete,
			];
		}

		/**
		 * @param index
		 * @param {Message} message
		 */
		onMessageLongTap(index, message)
		{
			logger.log('MessageMenu onMessageLongTap', message);
			const messageId = Number(message.id);
			const isRealMessage = Type.isNumber(messageId);
			if (!isRealMessage)
			{
				this.#processFileErrorMessage(message);

				return;
			}

			if (!ChatPermission.isCanOpenMessageMenu(this.dialogId))
			{
				Haptics.notifyFailure();

				return;
			}

			const messageModel = this.store.getters['messagesModel/getById'](messageId);

			if (!messageModel || !('id' in messageModel))
			{
				Haptics.notifyFailure();

				return;
			}

			if (this.isMenuNotAvailableByComponentId(messageModel))
			{
				Haptics.notifyFailure();

				return;
			}

			const fileModel = this.store.getters['filesModel/getById'](messageModel.files[0]);
			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const userModel = this.store.getters['usersModel/getById'](messageModel.authorId);
			const commentInfo = this.store.getters['commentModel/getByMessageId'](messageModel.id);

			let isUserSubscribed = false;

			if (commentInfo)
			{
				isUserSubscribed = commentInfo.isUserSubscribed;
			}

			if (!commentInfo && messageModel.authorId === serviceLocator.get('core').getUserId())
			{
				isUserSubscribed = true;
			}

			const contextMenuMessage = new MessageMenuMessage({
				messageModel,
				fileModel,
				dialogModel,
				userModel,
				isPinned: this.store.getters['messagesModel/pinModel/isPinned'](messageModel.id),
				isUserSubscribed,
			});

			const menu = new MessageMenuView();

			this.getOrderedActions(message)
				.forEach((actionId) => this.actions[actionId](menu, contextMenuMessage))
			;

			this.locator.get('view')
				.showMenuForMessage(message, menu)
			;
			Haptics.impactMedium();
		}

		/**
		 * @param {Message} message
		 */
		#processFileErrorMessage(message)
		{
			if (message.status !== OwnMessageStatus.error)
			{
				Haptics.notifyFailure();

				return;
			}

			const messageModel = this.store.getters['messagesModel/getById'](message.id);
			if (!messageModel || !('id' in messageModel))
			{
				Haptics.notifyFailure();

				return;
			}

			if (this.isMenuNotAvailableByComponentId(messageModel))
			{
				Haptics.notifyFailure();

				return;
			}

			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const contextMenuMessage = new MessageMenuMessage({
				messageModel,
				dialogModel,
			});

			const menu = new MessageMenuView();
			this.getOrderedActionsForErrorMessage()
				.forEach((actionId) => this.actions[actionId](menu, contextMenuMessage))
			;

			this.locator.get('view')
				.showMenuForMessage(message, menu)
			;
			Haptics.impactMedium();
		}

		/**
		 * @param {string} actionId
		 * @param {Message} message
		 */
		onMessageMenuActionTap(actionId, message)
		{
			logger.log('MessageMenu onMessageMenuActionTap', actionId, message);
			if (!(actionId in this.handlers))
			{
				logger.error('Message Menu: unknown action', actionId, message);
			}

			this.handlers[actionId](message);
		}

		onMessageMenuReactionTap(reactionId, message)
		{
			logger.log('MessageMenu onMessageMenuReactionTap', reactionId, message);

			this.locator.get('message-service')
				.setReaction(reactionId, message.id)
			;
		}

		registerActions()
		{
			this.actions = {
				[ActionType.reaction]: this.addReactionAction.bind(this),
				[ActionType.copy]: this.addCopyAction.bind(this),
				[ActionType.copyLink]: this.addCopyLinkAction.bind(this),
				[ActionType.pin]: this.addPinAction.bind(this),
				[ActionType.unpin]: this.addUnpinAction.bind(this),
				[ActionType.subscribe]: this.addSubscribeAction.bind(this),
				[ActionType.unsubscribe]: this.addUnsubscribeAction.bind(this),
				[ActionType.forward]: this.addForwardAction.bind(this),
				[ActionType.create]: this.addCreateAction.bind(this),
				[ActionType.reply]: this.addReplyAction.bind(this),
				[ActionType.profile]: this.addProfileAction.bind(this),
				[ActionType.edit]: this.addEditAction.bind(this),
				[ActionType.delete]: this.addDeleteAction.bind(this),
				[ActionType.downloadToDevice]: this.addDownloadToDeviceAction.bind(this),
				[ActionType.downloadToDisk]: this.addDownloadToDiskAction.bind(this),
				[ActionType.feedback]: this.addFeedbackAction.bind(this),
				[ActionType.multiselect]: this.addMultiselectAction.bind(this),
				[ActionType.resend]: this.addResendAction.bind(this),
			};
		}

		registerActionHandlers()
		{
			this.handlers = {
				[ActionType.copy]: this.onCopy.bind(this),
				[ActionType.copyLink]: this.onCopyLink.bind(this),
				[ActionType.reply]: this.onReply.bind(this),
				[ActionType.pin]: this.onPin.bind(this),
				[ActionType.unpin]: this.onUnpin.bind(this),
				[ActionType.subscribe]: this.onSubscribe.bind(this),
				[ActionType.unsubscribe]: this.onUnsubscribe.bind(this),
				[ActionType.forward]: this.onForward.bind(this),
				[ActionType.create]: this.onCreate.bind(this),
				[ActionType.profile]: this.onProfile.bind(this),
				[ActionType.edit]: this.onEdit.bind(this),
				[ActionType.delete]: this.onDelete.bind(this),
				[ActionType.downloadToDevice]: this.onDownloadToDevice.bind(this),
				[ActionType.downloadToDisk]: this.onDownloadToDisk.bind(this),
				[ActionType.feedback]: this.onFeedback.bind(this),
				[ActionType.multiselect]: this.onMultiSelect.bind(this),
				[ActionType.resend]: this.onResend.bind(this),
			};
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addReactionAction(menu, message)
		{
			if (message.isPossibleReact())
			{
				menu
					.addReaction(LikeReaction)
					.addReaction(KissReaction)
					.addReaction(LaughReaction)
					.addReaction(WonderReaction)
					.addReaction(CryReaction)
					.addReaction(AngryReaction)
					.addReaction(FacepalmReaction)
				;
			}
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
			if (Feature.isMessageMenuAirIconSupported && message.isPossibleCopyLink())
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
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addDownloadToDeviceAction(menu, message)
		{
			if (message.isPossibleSaveToLibrary())
			{
				menu.addAction(DownloadToDeviceAction);
			}
		}

		/**
		 * @param {MessageMenuView} menu
		 * @param {MessageMenuMessage} message
		 */
		addDownloadToDiskAction(menu, message)
		{
			if (message.isPossibleSaveToLibrary())
			{
				menu.addAction(DownloadToDiskAction);
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
		 * @param {Message} message
		 */
		onCopy(message)
		{
			const modelMessage = this.store.getters['messagesModel/getById'](message.id);

			DialogTextHelper.copyToClipboard(
				modelMessage.text,
				{
					parentWidget: this.locator.get('view').ui,
				},
			);
		}

		/**
		 * @param {Message} message
		 */
		onCopyLink(message)
		{
			const link = MessageHelper.createById(message.id)?.getLinkToMessage();
			if (!Type.isStringFilled(link))
			{
				return;
			}

			DialogTextHelper.copyToClipboard(
				link,
				{
					notificationText: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_COPY_LINK_SUCCESS'),
					notificationIcon: Icon.LINK,
					parentWidget: this.locator.get('view').ui,
				},
			);
		}

		/**
		 * @param {Message} message
		 */
		onReply(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const replyManager = this.locator.get('reply-manager');

			if (
				replyManager.isQuoteInProcess
				&& message.id === replyManager.getQuoteMessage().id
			)
			{
				return;
			}

			replyManager.startQuotingMessage(message);
		}

		/**
		 * @param {Message} message
		 */
		onPin(message)
		{
			if (!Feature.isMessagePinSupported)
			{
				Notification.showComingSoon();

				return;
			}

			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const messageModel = this.store.getters['messagesModel/getById'](message.id);
			if (!messageModel.id)
			{
				return;
			}

			this.locator.get('message-service')
				.pinMessage(messageModel.id)
			;
		}

		/**
		 * @param {Message} message
		 */
		onUnpin(message)
		{
			if (!Feature.isMessagePinSupported)
			{
				Notification.showComingSoon();

				return;
			}

			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const messageModel = this.store.getters['messagesModel/getById'](message.id);
			if (!messageModel.id)
			{
				return;
			}

			this.locator.get('message-service')
				.unpinMessage(messageModel.id)
			;
		}

		onSubscribe(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			Notification.showToast(ToastType.subscribeToComments, this.locator.get('view').ui);
			const modelMessage = this.store.getters['messagesModel/getById'](message.id);
			this.locator.get('chat-service').subscribeToCommentsByPostId(modelMessage.id);
		}

		onUnsubscribe(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			Notification.showToast(ToastType.unsubscribeFromComments, this.locator.get('view').ui);
			const modelMessage = this.store.getters['messagesModel/getById'](message.id);
			this.locator.get('chat-service').unsubscribeFromCommentsByPostId(modelMessage.id);
		}

		/**
		 * @param {Message} message
		 */
		onForward(message)
		{
			if (!Feature.isMessageForwardSupported)
			{
				Notification.showComingSoon();

				return;
			}

			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const forwardController = new ForwardSelector();
			forwardController.open({
				messageIds: [message.id],
				fromDialogId: this.dialogId,
				locator: this.locator,
			});
		}

		/**
		 * @param {Message} message
		 */
		onCreate(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const messageModel = this.store.getters['messagesModel/getById'](message.id);
			if (!messageModel)
			{
				return;
			}

			MessageCreateMenu.open(this.dialogId, messageModel, this.store);
		}

		/**
		 * @param {Message} message
		 */
		onProfile(message)
		{
			const messageModel = this.store.getters['messagesModel/getById'](message.id);
			if (!messageModel.id)
			{
				return;
			}

			UserProfile.show(messageModel.authorId, {
				backdrop: true,
				openingDialogId: this.dialogId,
			});
		}

		/**
		 * @param {Message} message
		 */
		onEdit(message)
		{
			this.locator.get('reply-manager')
				.startEditingMessage(message)
			;
		}

		/**
		 * @param {Message} message
		 */
		onDelete(message)
		{
			const messageModel = this.store.getters['messagesModel/getById'](message.id);
			if (!messageModel.id)
			{
				return;
			}

			if (Type.isNumber(messageModel.id))
			{
				AnalyticsService.getInstance().sendMessageDeleteActionClicked({
					messageId: messageModel.id,
					dialogId: this.dialogId,
				});
			}

			const helper = DialogHelper.createByDialogId(this.dialogId);

			if (helper?.isChannel)
			{
				showDeleteChannelPostAlert({
					deleteCallback: () => {
						this.locator.get('message-service')
							.delete(messageModel, this.dialogId)
						;
					},
					cancelCallback: () => {
						if (Type.isNumber(messageModel.id))
						{
							AnalyticsService.getInstance().sendMessageDeletingCanceled({
								messageId: messageModel.id,
								dialogId: this.dialogId,
							});
						}
					},
				});

				return;
			}

			this.locator.get('message-service')
				.delete(messageModel, this.dialogId)
			;
		}

		/**
		 * @param {Message} message
		 */
		onDownloadToDevice(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const messageModel = this.store.getters['messagesModel/getById'](message.id);
			if (!messageModel.id)
			{
				return;
			}

			const file = this.store.getters['filesModel/getById'](messageModel.files[0]);
			if (!file)
			{
				return;
			}

			void NotifyManager.showLoadingIndicator();
			Filesystem.downloadFile(withCurrentDomain(file.urlDownload))
				.then((localPath) => {
					AnalyticsService.getInstance().sendDownloadToDevice({
						fileType: file.type,
						dialogId: this.dialogId,
					});

					if (file.type === FileType.audio)
					{
						this.#saveFile(localPath, messageModel);

						return;
					}

					this.#saveToLibrary(localPath, file.type, messageModel);
				})
				.catch((error) => {
					logger.error(`${this.constructor.name}.onDownloadToDevice.downloadFile.catch:`, error);
				});
		}

		/**
		 * @param {String} localPath
		 * @param {FileType} fileType
		 * @param {MessagesModelState} messageModel
		 */
		#saveToLibrary(localPath, fileType, messageModel)
		{
			return utils.saveToLibrary(localPath)
				.then(() => {
					const successMessages = {
						[FileType.image]: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_PHOTO_TO_GALLERY_SUCCESS'),
						[FileType.video]: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_VIDEO_TO_GALLERY_SUCCESS'),
					};
					const successMessage = successMessages[fileType];
					if (successMessages)
					{
						Notification.showToastWithParams({ message: successMessage, svgType: 'image' }, this.locator.get('view').ui);
					}
				})
				.catch((error) => {
					logger.error(`${this.constructor.name}.saveToLibrary.catch:`, error);
					const locDescMessage = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_GALLERY_FAILURE');
					const locSettingsMessage = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_GALLERY_FAILURE_SETTINGS');
					const locNoMessage = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_GALLERY_FAILURE_NO');

					navigator.notification.confirm(
						'',
						(buttonId) => {
							if (buttonId === 2)
							{
								Application.openSettings();
							}
						},
						locDescMessage,
						[
							locNoMessage,
							locSettingsMessage,
						],
					);
				})
				.finally(() => {
					NotifyManager.hideLoadingIndicatorWithoutFallback();
				});
		}

		/**
		 * @param {String} localPath
		 */
		#saveFile(localPath)
		{
			return utils.saveFile(localPath)
				.catch((error) => {
					logger.error(`${this.constructor.name}.saveFile.catch:`, error);
				})
				.finally(() => {
					NotifyManager.hideLoadingIndicatorWithoutFallback();
				});
		}

		/**
		 * @param {Message} message
		 */
		onDownloadToDisk(message)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const messageModel = this.store.getters['messagesModel/getById'](message.id);
			if (!messageModel.id)
			{
				return;
			}

			const file = this.store.getters['filesModel/getById'](messageModel.files[0]);
			if (!file)
			{
				return;
			}

			this.locator.get('disk-service')
				.save(file.id)
				.then(() => {
					const successMessages = {
						[FileType.image]: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_PHOTO_TO_DISK_SUCCESS'),
						[FileType.video]: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_VIDEO_TO_DISK_SUCCESS'),
						[FileType.audio]: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_AUDIO_TO_DISK_SUCCESS'),
					};
					const successMessage = successMessages[file.type];
					if (successMessages)
					{
						Notification.showToastWithParams({ message: successMessage, svgType: 'catalogueSuccess' }, this.locator.get('view').ui);
					}

					AnalyticsService.getInstance().sendDownloadToDisk({
						fileType: file.type,
						dialogId: this.dialogId,
					});
				})
				.catch((error) => {
					logger.error(`${this.constructor.name}.onDownloadToDisk.catch`, error);
					const errorMessage = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_TO_DISK_FAILURE');
					Notification.showToastWithParams(
						{ message: errorMessage, svgType: 'catalogue', backgroundColor: Theme.colors.accentMainAlert },
						this.locator.get('view').ui,
					);
				})
			;
		}

		/**
		 * @param {Message} message
		 */
		onFeedback(message)
		{
			const { openFeedbackForm } = require('layout/ui/feedback-form-opener');
			openFeedbackForm('copilotRoles');
		}

		/**
		 * @param {Message} message
		 */
		onMultiSelect(message)
		{
			(new SelectManager(this.locator, this.dialogId))
				.enableMultiSelectMode(message.id)
				.catch((error) => logger.error(`${this.constructor.name}.onMultiSelect(${message.id}).enableMultiSelectMode catch:`, error))
			;
		}

		/**
		 * @param {Message} message
		 */
		onResend(message)
		{
			MessengerEmitter.emit(EventType.dialog.external.resend, {
				index: null,
				message,
			});
		}

		/**
		 * @param {MessagesModelState} message
		 * @return {Boolean}
		 */
		isMenuNotAvailableByComponentId(message)
		{
			const componentId = message.params?.componentId;
			if (Type.isNil(componentId))
			{
				return false;
			}

			const componentIdsNotAvailableMenu = [
				MessageParams.ComponentId.SignMessage,
				MessageParams.ComponentId.CallMessage,
			];
			if (componentIdsNotAvailableMenu.includes(componentId))
			{
				return true;
			}

			const isCreateBannerMessage = componentId?.includes('CreationMessage');
			if (isCreateBannerMessage)
			{
				return true;
			}

			return false;
		}
	}

	module.exports = { MessageMenu };
});
