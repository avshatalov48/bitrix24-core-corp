/* eslint no-undef: 0 */
/**
 * @module im/messenger/controller/dialog/lib/message-menu/controller
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/controller', (require, exports, module) => {
	const { Type } = require('type');
	const { Haptics } = require('haptics');
	const { clone } = require('utils/object');

	const {
		EventType,
		OwnMessageStatus,
		MessageParams,
		MessageMenuActionType,
	} = require('im/messenger/const');
	const { getLogger } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');

	const {
		LikeReaction,
		KissReaction,
		LaughReaction,
		WonderReaction,
		CryReaction,
		AngryReaction,
		FacepalmReaction,
	} = require('im/messenger/controller/dialog/lib/message-menu/reaction');
	const { MessageMenuMessage } = require('im/messenger/controller/dialog/lib/message-menu/message');
	const { MessageMenuView } = require('im/messenger/controller/dialog/lib/message-menu/view');
	const { MessageMenu } = require('im/messenger/controller/dialog/lib/message-menu/menu');

	const logger = getLogger('dialog--message-menu');

	/**
	 * @class MessageMenuController
	 */
	class MessageMenuController
	{
		/**
		 * @param {MessageMenuControllerCreateParams} params
		 */
		constructor({ dialogLocator, getDialog })
		{
			/** @type {DialogLocator} */
			this.dialogLocator = dialogLocator;
			this.getDialog = getDialog;
			/** @type {DialogConfigurator} */
			this.configurator = this.dialogLocator.get('configurator');
			this.store = dialogLocator.get('store');
			/** @type {Record<string, function(Message): void>} */
			this.handlers = {};

			this.actions = {};

			this.messageLongTapHandler = this.onMessageLongTap.bind(this);
			this.messageMenuActionTapHandler = this.onMessageMenuActionTap.bind(this);
			this.messageMenuReactionTapHandler = this.onMessageMenuReactionTap.bind(this);
		}

		get dialogId()
		{
			return this.getDialog().dialogId;
		}

		subscribeEvents()
		{
			this.dialogLocator.get('view')
				.on(EventType.dialog.messageMenuActionTap, this.messageMenuActionTapHandler)
				.on(EventType.dialog.messageMenuReactionTap, this.messageMenuReactionTapHandler)
				.on(EventType.dialog.messageLongTap, this.messageLongTapHandler)
			;
		}

		unsubscribeEvents()
		{
			this.dialogLocator.get('view')
				.off(EventType.dialog.messageMenuActionTap, this.messageMenuActionTapHandler)
				.off(EventType.dialog.messageMenuReactionTap, this.messageMenuReactionTapHandler)
				.off(EventType.dialog.messageLongTap, this.messageLongTapHandler)
			;
		}

		/**
		 * @param {number} messageId
		 * @return {IMessageContextMenu}
		 */
		createBaseMessageContextMenuControllerByMessageId(messageId)
		{
			const baseContextMenuController = new MessageMenu({
				getDialog: this.getDialog,
				relatedEntity: this.configurator.getRelatedEntity(),
			});

			baseContextMenuController.setDialogLocator(this.dialogLocator);

			return baseContextMenuController;
		}

		/**
		 * @param {number} messageId
		 * @return {Promise<IMessageContextMenu>}
		 */
		async createMessageContextMenuControllerByMessageId(messageId)
		{
			const MessageContextMenuControllerClass = await this.configurator
				.getMessageContextMenuControllerClassByMessageId(messageId)
			;

			const contextMenuController = new MessageContextMenuControllerClass({
				getDialog: this.getDialog,
				relatedEntity: this.configurator.getRelatedEntity(),
			});

			if (contextMenuController instanceof MessageMenu)
			{
				contextMenuController.setDialogLocator(this.dialogLocator);
			}

			return contextMenuController;
		}

		/**
		 * @param {MessageMenuMessage} message
		 * @return {Promise<string[]>}
		 */
		async getOrderedActions(message)
		{
			const controller = await this.createMessageContextMenuControllerByMessageId(message.messageModel.id);
			const orderedActions = await controller.getOrderedActions();

			return [
				MessageMenuActionType.reaction,
				...orderedActions,
			];
		}

		/**
		 * @param {MessageMenuMessage} message
		 * @return {Promise<string[]>}
		 */
		async getOrderedActionsForErrorMessage(message)
		{
			return [
				MessageMenuActionType.resend,
				MessageMenuActionType.delete,
			];
		}

		/**
		 * @param index
		 * @param {Message} message
		 */
		async onMessageLongTap(index, message)
		{
			logger.log('MessageMenuController onMessageLongTap', message);
			const messageId = Number(message.id);
			const isRealMessage = Type.isNumber(messageId);
			if (!isRealMessage)
			{
				this.#processFileErrorMessage(message);

				return;
			}

			if (!ChatPermission.canOpenMessageMenu(this.dialogId))
			{
				Haptics.notifyFailure();

				return;
			}

			const messageModel = this.#getMessageModel(messageId);

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

			const contextMenuMessage = this.createMessageMenuMessage(messageId);

			await this.registerActions(contextMenuMessage.messageModel.id);
			await this.registerActionHandlers(contextMenuMessage.messageModel.id);

			const menu = new MessageMenuView();
			const orderedActions = await this.getOrderedActions(contextMenuMessage);
			orderedActions
				.forEach((actionId) => this.actions[actionId](menu, contextMenuMessage))
			;
			menu.clearUnnecessarySeparators();

			this.dialogLocator.get('view')
				.showMenuForMessage(message, menu)
			;
			Haptics.impactMedium();
		}

		/**
		 * @param messageId
		 * @returns {MessageMenuMessage}
		 */
		createMessageMenuMessage(messageId)
		{
			const messageModel = clone(this.#getMessageModel(messageId));
			const fileModel = clone(this.store.getters['filesModel/getById'](messageModel.files[0]));
			const dialogModel = clone(this.store.getters['dialoguesModel/getById'](this.dialogId));
			const userModel = clone(this.store.getters['usersModel/getById'](messageModel.authorId));
			const commentInfo = clone(this.store.getters['commentModel/getByMessageId'](messageModel.id));

			let isUserSubscribed = false;

			if (commentInfo)
			{
				isUserSubscribed = commentInfo.isUserSubscribed;
			}

			if (!commentInfo && messageModel.authorId === serviceLocator.get('core').getUserId())
			{
				isUserSubscribed = true;
			}

			return new MessageMenuMessage({
				messageModel,
				fileModel,
				dialogModel,
				userModel,
				isPinned: this.store.getters['messagesModel/pinModel/isPinned'](messageModel.id),
				isUserSubscribed,
			});
		}

		/**
		 * @param {Message} message
		 */
		async #processFileErrorMessage(message)
		{
			if (message.status !== OwnMessageStatus.error)
			{
				Haptics.notifyFailure();

				return;
			}

			const messageModel = this.#getMessageModel(message.id);
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
			const orderedActions = await this.getOrderedActionsForErrorMessage(contextMenuMessage);
			orderedActions.forEach((actionId) => this.actions[actionId](menu, contextMenuMessage));

			this.dialogLocator.get('view').showMenuForMessage(message, menu);
			Haptics.impactMedium();
		}

		/**
		 * @param {string} actionId
		 * @param {Message} message
		 * @param {Object} params
		 */
		async onMessageMenuActionTap(actionId, message, params)
		{
			await this.registerActionHandlers(message.id);

			logger.log('MessageMenuController onMessageMenuActionTap', actionId, message);
			if (!(actionId in this.handlers))
			{
				logger.error('Message Menu: unknown action', actionId, message);
			}

			const messageMenuMessage = this.createMessageMenuMessage(message.id);

			return this.handlers[actionId](messageMenuMessage, params);
		}

		onMessageMenuReactionTap(reactionId, message)
		{
			logger.log('MessageMenuController onMessageMenuReactionTap', reactionId, message);

			this.dialogLocator.get('message-service').setReaction(reactionId, message.id);
		}

		/**
		 * @param messageId
		 * @return {Promise<void>}
		 */
		async registerActions(messageId)
		{
			const baseController = this.createBaseMessageContextMenuControllerByMessageId(messageId);
			const controller = await this.createMessageContextMenuControllerByMessageId(messageId);
			this.actions = {
				[MessageMenuActionType.reaction]: this.addReactionAction.bind(this),
				...controller.getActions(),
				...baseController.getActions(),
			};
		}

		/**
		 * @param messageId
		 * @return {Promise<void>}
		 */
		async registerActionHandlers(messageId)
		{
			const baseController = this.createBaseMessageContextMenuControllerByMessageId(messageId);
			const controller = await this.createMessageContextMenuControllerByMessageId(messageId);
			this.handlers = {
				...controller.getActionHandlers(),
				...baseController.getActionHandlers(),
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

		#getMessageModel(messageId)
		{
			return this.store.getters['messagesModel/getById'](messageId);
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

	module.exports = { MessageMenuController };
});
