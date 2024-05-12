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

	const {
		EventType,
		FileType,
	} = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Feature } = require('im/messenger/lib/feature');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { ForwardSelector } = require('im/messenger/controller/forward-selector');
	const { DialogTextHelper } = require('im/messenger/controller/dialog/lib/helper/text');

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
		PinAction,
		UnpinAction,
		ForwardAction,
		ReplyAction,
		ProfileAction,
		EditAction,
		DeleteAction,
		DownloadToDeviceAction,
		DownloadToDiskAction,
	} = require('im/messenger/controller/dialog/lib/message-menu/action');
	const { ActionType } = require('im/messenger/controller/dialog/lib/message-menu/action-type');
	const { MessageMenuMessage } = require('im/messenger/controller/dialog/lib/message-menu/message');
	const { MessageMenuView } = require('im/messenger/controller/dialog/lib/message-menu/view');

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
				ActionType.pin,
				ActionType.unpin,
				ActionType.forward,
				ActionType.downloadToDevice,
				ActionType.downloadToDisk,
				ActionType.profile,
				ActionType.edit,
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
				return;
			}

			const messageModel = this.store.getters['messagesModel/getById'](messageId);

			if (!messageModel || !('id' in messageModel))
			{
				return;
			}
			const fileModel = this.store.getters['filesModel/getById'](messageModel.files[0]);
			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const userModel = this.store.getters['usersModel/getById'](messageModel.authorId);

			const contextMenuMessage = new MessageMenuMessage({
				messageModel,
				fileModel,
				dialogModel,
				userModel,
				isPinned: this.store.getters['messagesModel/pinModel/isPinned'](messageModel.id),
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
				[ActionType.pin]: this.addPinAction.bind(this),
				[ActionType.unpin]: this.addUnpinAction.bind(this),
				[ActionType.forward]: this.addForwardAction.bind(this),
				[ActionType.reply]: this.addReplyAction.bind(this),
				[ActionType.profile]: this.addProfileAction.bind(this),
				[ActionType.edit]: this.addEditAction.bind(this),
				[ActionType.delete]: this.addDeleteAction.bind(this),
				[ActionType.downloadToDevice]: this.addDownloadToDeviceAction.bind(this),
				[ActionType.downloadToDisk]: this.addDownloadToDiskAction.bind(this),
			};
		}

		registerActionHandlers()
		{
			this.handlers = {
				[ActionType.copy]: this.onCopy.bind(this),
				[ActionType.reply]: this.onReply.bind(this),
				[ActionType.pin]: this.onPin.bind(this),
				[ActionType.unpin]: this.onUnpin.bind(this),
				[ActionType.forward]: this.onForward.bind(this),
				[ActionType.profile]: this.onProfile.bind(this),
				[ActionType.edit]: this.onEdit.bind(this),
				[ActionType.delete]: this.onDelete.bind(this),
				[ActionType.downloadToDevice]: this.onDownloadToDevice.bind(this),
				[ActionType.downloadToDisk]: this.onDownloadToDisk.bind(this),
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
			if (!message.isPossiblePin())
			{
				menu.addAction(UnpinAction);
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
		 * @param {Message} message
		 */
		onCopy(message)
		{
			const modelMessage = this.store.getters['messagesModel/getById'](message.id);

			DialogTextHelper.copyToClipboard(modelMessage);
		}

		/**
		 * @param {Message} message
		 */
		onReply(message)
		{
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

			const messageModel = this.store.getters['messagesModel/getById'](message.id);
			if (!messageModel.id)
			{
				return;
			}

			this.locator.get('message-service')
				.unpinMessage(messageModel.id)
			;
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

			const forwardController = new ForwardSelector();
			forwardController.open({
				messageId: message.id,
				fromDialogId: this.dialogId,
				locator: this.locator,
			});
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

			UserProfile.show(messageModel.authorId, { backdrop: true });
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

			this.locator.get('message-service')
				.delete(messageModel, this.dialogId)
			;
		}

		/**
		 * @param {Message} message
		 */
		onDownloadToDevice(message)
		{
			const messageModel = this.store.getters['messagesModel/getById'](message.id);
			if (!messageModel.id)
			{
				return;
			}

			const file = this.store.getters['filesModel/getById'](messageModel.files[0]);

			const hasFile = Boolean(file);
			const isImageMessage = hasFile && file.type === FileType.image;

			void NotifyManager.showLoadingIndicator();
			Filesystem.downloadFile(withCurrentDomain(file.urlDownload))
				.then((localPath) => {
					utils.saveToLibrary(localPath)
						.then(() => {
							const successMessage = isImageMessage
								? Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_PHOTO_TO_GALLERY_SUCCESS')
								: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_VIDEO_TO_GALLERY_SUCCESS')
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
				})
			;
		}

		/**
		 * @param {Message} message
		 */
		onDownloadToDisk(message)
		{
			const messageModel = this.store.getters['messagesModel/getById'](message.id);
			if (!messageModel.id)
			{
				return;
			}

			const file = this.store.getters['filesModel/getById'](messageModel.files[0]);
			const hasFile = Boolean(file);
			const isImageMessage = hasFile && file.type === FileType.image;

			this.locator.get('disk-service')
				.save(file.id)
				.then(() => {
					const successMessage = isImageMessage
						? Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_PHOTO_TO_DISK_SUCCESS')
						: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_MENU_DOWNLOAD_VIDEO_TO_DISK_SUCCESS')
					;

					InAppNotifier.showNotification({
						title: successMessage,
						time: 3,
						backgroundColor: '#E6000000',
					});
				})
				.catch((error) => {
					logger.error('MessageMenu onDownloadToDisk error', error);
				})
			;
		}
	}

	module.exports = { MessageMenu };
});
