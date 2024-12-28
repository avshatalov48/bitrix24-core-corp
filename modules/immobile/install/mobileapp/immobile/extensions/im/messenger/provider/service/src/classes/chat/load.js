/**
 * @module im/messenger/provider/service/classes/chat/load
 */
jn.define('im/messenger/provider/service/classes/chat/load', (require, exports, module) => {
	/* global ChatMessengerCommon, ChatUtils */
	const { Type } = require('type');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { RestManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod, EventType, DialogType } = require('im/messenger/const');
	const { ChatDataExtractor } = require('im/messenger/provider/service/classes/chat-data-extractor');
	const { MessageService } = require('im/messenger/provider/service/message');
	const { Counters } = require('im/messenger/lib/counters');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { RecentConverter } = require('im/messenger/lib/converter');
	const { runAction } = require('im/messenger/lib/rest');
	const { MessageContextCreator } = require('im/messenger/provider/service/classes/message-context-creator');

	const logger = LoggerManager.getInstance().getLogger('dialog--chat-service');

	/**
	 * @class LoadService
	 */
	class LoadService
	{
		constructor()
		{
			/**
			 * @type {MessengerCoreStore}
			 */
			this.store = serviceLocator.get('core').getStore();
			this.restManager = new RestManager();
			this.contextCreator = new MessageContextCreator();
		}

		async loadChatWithMessages(dialogId)
		{
			if (!Type.isStringFilled(dialogId))
			{
				return Promise.reject(new Error('ChatService: loadChatWithMessages: dialogId is not provided'));
			}

			const params = {
				dialogId,
				messageLimit: MessageService.getMessageRequestLimit(),
				ignoreMark: true, // TODO: remove when we support look later to start receiving messages from the flagged one
			};

			return this.requestChat(RestMethod.imV2ChatLoad, params);
		}

		loadChatWithContext(dialogId, messageId)
		{
			const params = {
				dialogId,
				messageId,
				messageLimit: MessageService.getMessageRequestLimit(),
			};

			return this.requestChat(RestMethod.imV2ChatLoadInContext, params);
		}

		async loadCommentChatWithMessages(dialogId)
		{
			if (!Type.isStringFilled(dialogId))
			{
				return Promise.reject(new Error('ChatService: loadCommentChatWithMessages: dialogId is not provided'));
			}

			const params = {
				dialogId,
				messageLimit: MessageService.getMessageRequestLimit(),
				autoJoin: 'Y',
				createIfNotExists: 'Y',
				ignoreMark: true, // TODO: remove when we support look later to start receiving messages from the flagged one
			};

			return this.requestChat(RestMethod.imV2ChatLoad, params);
		}

		/**
		 * @param {number} postId
		 * @return {Promise<DialogId>}
		 */
		async loadCommentChatWithMessagesByPostId(postId)
		{
			if (!Type.isNumber(postId))
			{
				return Promise.reject(new Error('ChatService: loadCommentChatWithMessagesByPostId: postId is not provided'));
			}

			const params = {
				postId,
				messageLimit: MessageService.getMessageRequestLimit(),
				autoJoin: 'Y',
				createIfNotExists: 'Y',
				ignoreMark: true, // TODO: remove when we support look later to start receiving messages from the flagged one
			};

			await this.requestChat(RestMethod.imV2ChatLoad, params);

			const dialog = this.store.getters['dialoguesModel/getByParentMessageId'](postId);
			if (!dialog)
			{
				// alarm
				return 0;
			}
			const commentInfo = this.store.getters['commentModel/getByMessageId'](postId);
			const messageModel = this.store.getters['messagesModel/getById'](postId);
			const currentUserId = serviceLocator.get('core').getUserId();

			this.store.dispatch('commentModel/setComments', {
				messageId: postId,
				dialogId: dialog.dialogId,
				chatId: dialog.chatId,
				lastUserIds: [],
				messageCount: dialog.messageCount,
				isUserSubscribed: commentInfo?.isUserSubscribed ?? Number(messageModel.authorId) === Number(currentUserId),
			});

			return dialog.dialogId;
		}

		/**
		 * @desc rest get member entity
		 * @return {Promise<memberEntities:Array<*>>}
		 */
		loadChatMemberEntitiesList(dialogId)
		{
			if (!Type.isStringFilled(dialogId))
			{
				return Promise.reject(new Error('ChatService: loadChatMemberEntitiesList: dialogId is not provided'));
			}

			return runAction(RestMethod.imV2ChatMemberEntitiesList, {
				data: {
					dialogId,
				},
			})
				.then(
					(response) => {
						if (response?.errors?.length > 0)
						{
							logger.error(`${this.constructor.name}.restMemberEntitiesList.error:`, response.errors);

							return response.errors;
						}
						logger.log(`${this.constructor.name}.restMemberEntitiesList.memberEntities:`, response.memberEntities);

						return response.memberEntities;
					},
				)
				.catch((error) => logger.error(`${this.constructor.name}.restMemberEntitiesList.error:`, error))
		}

		async requestChat(actionName, params)
		{
			const { dialogId } = params;
			logger.log('ChatLoadService.requestChat: request', actionName, params);

			const actionResult = await runAction(actionName, { data: params })
				.catch((error) => {
					logger.error('ChatLoadService.requestChat.catch:', error);

					throw error;
				})
			;

			logger.log('ChatLoadService.requestChat: response', actionName, params, actionResult);

			await this.updateModels(actionResult);

			if (this.isDialogLoadedMarkNeeded(actionName))
			{
				return this.markDialogAsLoaded(dialogId);
			}

			return true;
		}

		async getByDialogId(dialogId)
		{
			if (!Type.isStringFilled(dialogId))
			{
				return Promise.reject(new Error('ChatService: getChatByDialogId: dialogId is not provided'));
			}

			const params = {
				dialogId,
			};

			const actionResult = await runAction(RestMethod.imV2ChatGet, { data: params })
				.catch((error) => {
					logger.error('ChatService.getChatByDialogId.catch:', error);

					throw error;
				})
			;

			const extractor = new ChatDataExtractor(actionResult);
			logger.log('ChatService.getChatByDialogId: response', params, actionResult, extractor);

			return extractor.getMainChat();
		}

		markDialogAsLoaded(dialogId)
		{
			return this.store.dispatch('dialoguesModel/update', {
				dialogId,
				fields: {
					inited: true,
				},
			});
		}

		isDialogLoadedMarkNeeded(actionName)
		{
			return actionName !== RestMethod.imV2ChatShallowLoad;
		}

		/**
		 * @private
		 */
		async updateModels(response)
		{
			const extractor = new ChatDataExtractor(response);
			if (this.isCopilotDialog(extractor))
			{
				return this.updateModelsCopilot(extractor);
			}

			const usersPromise = [
				this.store.dispatch('usersModel/set', extractor.getUsers()),
				this.store.dispatch('usersModel/addShort', extractor.getAdditionalUsers()),
			];
			const dialogList = extractor.getChats();

			void await this.store.dispatch('dialoguesModel/set', dialogList);

			const collabPromise = this.store.dispatch('dialoguesModel/collabModel/set', extractor.getCollabInfo());

			const filesPromise = this.store.dispatch('filesModel/set', extractor.getFiles());
			const reactionPromise = this.store.dispatch('messagesModel/reactionsModel/set', {
				reactions: extractor.getReactions(),
			});
			const commentPromise = this.store.dispatch('commentModel/setComments', extractor.getCommentInfo());

			const messages = await this.contextCreator
				.createMessageDoublyLinkedListForDialog(extractor.getMainChat(), extractor.getMessages())
			;
			const messagesWithUploadingMessages = this.addUploadingMessagesToMessageList(messages);

			const messagesPromise = [
				this.store.dispatch('messagesModel/store', extractor.getMessagesToStore()),
				this.store.dispatch('messagesModel/setChatCollection', {
					messages: messagesWithUploadingMessages,
					clearCollection: true,
				}),
				this.store.dispatch('messagesModel/pinModel/setChatCollection', {
					pins: extractor.getPins(),
					messages: extractor.getPinnedMessages(),
				}),
			];

			await Promise.all([
				usersPromise,
				filesPromise,
				reactionPromise,
				commentPromise,
				collabPromise,
			]);

			await Promise.all(messagesPromise);

			await this.updateCounters(dialogList);

			return extractor.getChatId();
		}

		/**
		 * @private
		 */
		async updateModelsCopilot(extractor)
		{
			const usersPromise = [
				this.store.dispatch('usersModel/set', extractor.getUsers()),
				this.store.dispatch('usersModel/addShort', extractor.getAdditionalUsers()),
			];
			const dialogData = { ...extractor.getMainChat(), tariffRestrictions: extractor.getTariffRestrictions() };
			this.setRecent(extractor).catch((err) => logger.log('LoadService.updateModels.setRecent error', err));
			const copilotData = { dialogId: extractor.getDialogId(), ...extractor.getCopilot() };
			const copilotPromise = this.store.dispatch('dialoguesModel/copilotModel/setCollection', copilotData);

			void await this.store.dispatch('dialoguesModel/set', dialogData);

			const filesPromise = this.store.dispatch('filesModel/set', extractor.getFiles());
			const reactionPromise = this.store.dispatch('messagesModel/reactionsModel/set', {
				reactions: extractor.getReactions(),
			});

			const messages = await this.contextCreator
				.createMessageDoublyLinkedListForDialog(extractor.getMainChat(), extractor.getMessages())
			;
			const messagesWithUploadingMessages = this.addUploadingMessagesToMessageList(messages);
			const messagesPromise = [
				this.store.dispatch('messagesModel/store', extractor.getMessagesToStore()),
				this.store.dispatch('messagesModel/setChatCollection', {
					messages: messagesWithUploadingMessages,
					clearCollection: true,
				}),
				this.store.dispatch('messagesModel/pinModel/setChatCollection', {
					pins: extractor.getPins(),
					messages: extractor.getPinnedMessages(),
				}),
			];

			await Promise.all([
				usersPromise,
				filesPromise,
				reactionPromise,
				copilotPromise,
			]);

			await Promise.all(messagesPromise);

			await this.updateCounters([dialogData]);

			return extractor.getChatId();
		}

		/**
		 * @desc check is copilot dialog
		 * @param {ChatDataExtractor} extractor
		 * @return {Boolean}
		 */
		isCopilotDialog(extractor)
		{
			const dialogData = extractor.getMainChat();

			return dialogData.type === DialogType.copilot;
		}

		/**
		 * @desc Set recent item by extract data response
		 * @param {ChatDataExtractor} extractor
		 * @return {Promise}
		 */
		setRecent(extractor)
		{
			const messages = ChatUtils.objectClone(extractor.getMessages());
			const message = messages[messages.length - 1];
			if (Type.isNil(message))
			{
				return Promise.resolve(false);
			}

			message.text = ChatMessengerCommon.purifyText(
				message.text || '',
				message.params,
			);
			message.senderId = message.author_id;

			const userId = message.author_id || message.authorId;
			const userData = extractor.getUsers().filter((user) => user.id === userId);

			const recentItem = RecentConverter.fromPushToModel({
				id: extractor.getDialogId(),
				chat: extractor.getMainChat(),
				user: userData,
				message,
				counter: 0,
				liked: false,
			});

			return this.store.dispatch('recentModel/set', [recentItem]);
		}

		/**
		 *
		 * @param {Array<object>} rawDialogModelList
		 * @return {Array<object>}
		 */
		prepareDialogues(rawDialogModelList)
		{
			return rawDialogModelList.map((rawDialogModel) => {
				if (!(rawDialogModel.last_id || rawDialogModel.lastId) || !rawDialogModel.counter)
				{
					return rawDialogModel;
				}

				const dialogId = rawDialogModel.dialog_id ?? rawDialogModel.dialogId;
				const localDialogModel = this.store.getters['dialoguesModel/getById'](dialogId);
				if (!localDialogModel)
				{
					return rawDialogModel;
				}

				const lastReadId = rawDialogModel.last_id ?? rawDialogModel.lastId;
				if (localDialogModel.lastReadId >= lastReadId)
				{
					rawDialogModel.last_id = localDialogModel.lastReadId;
					rawDialogModel.counter = localDialogModel.counter;
				}

				return rawDialogModel;
			});
		}

		/**
		 * @param {Array<Partial<DialoguesModelState>>} dialogues
		 */
		async updateCounters(dialogues)
		{
			const dialoguesWithCounter = dialogues
				.filter((rawDialog) => Type.isNumber(rawDialog.counter))
			;

			const recentList = [];
			for (const dialog of dialoguesWithCounter)
			{
				const recentItem = this.store.getters['recentModel/getById'](dialog.dialogId);
				if (!recentItem || recentItem.counter === dialog.counter)
				{
					continue;
				}

				recentList.push({
					...recentItem,
					counter: dialog.counter,
				});
			}

			if (recentList.length === 0)
			{
				logger.log('ChatLoadService: there are no recent elements to update');

				return;
			}

			logger.warn('ChatLoadService: recent list to update with new counters', recentList);

			await this.store.dispatch('recentModel/update', recentList);

			MessengerEmitter.emit(EventType.messenger.renderRecent);

			Counters.updateDelayed();
		}

		/**
		 * @param {Array<RawMessage>} messageList
		 * @returns {Array<MessagesModelState | RawMessage>}
		 */
		addUploadingMessagesToMessageList(messageList)
		{
			if (!Type.isArrayFilled(messageList))
			{
				return messageList;
			}

			const chatId = messageList[0].chat_id;
			/** @type {Map<number, Array<MessagesModelState>>} */
			const uploadingCollection = new Map();
			const uploadingMessageList = this.store.getters['messagesModel/getUploadingMessages'](chatId);
			if (!Type.isArrayFilled(uploadingMessageList))
			{
				return messageList;
			}

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
				const messageIndex = messageList.findIndex((message) => message.id === messageId);
				if (messageIndex === -1)
				{
					continue;
				}

				if (messageIndex === messageList.length - 1)
				{
					messageList.push(...uploadingMessages);
				}
				else
				{
					messageList.splice(messageIndex + 1, 0, ...uploadingMessages);
				}
			}

			return messageList;
		}
	}

	module.exports = {
		LoadService,
	};
});
