/**
 * @module im/messenger/provider/services/sync/fillers/base
 */
jn.define('im/messenger/provider/services/sync/fillers/base', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { DialogType, EventType, MessageStatus } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { UserManager } = require('im/messenger/lib/user-manager');
	const { MessageContextCreator } = require('im/messenger/provider/services/lib/message-context-creator');
	const { getLogger } = require('im/messenger/lib/logger');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { ChatDataProvider, RecentDataProvider } = require('im/messenger/provider/data');
	const { MessengerCounterSender } = require('im/messenger/lib/counters/counter-manager/messenger/sender');

	const logger = getLogger('sync-service');

	/**
	 * @class SyncFillerBase
	 */
	class SyncFillerBase
	{
		constructor()
		{
			this.core = serviceLocator.get('core');
			/** @type {MessengerCoreStore} */
			this.store = this.core.getStore();

			this.userManager = new UserManager(this.store);
			/** @type {DialogRepository} */
			this.dialogRepository = this.core.getRepository().dialog;
			/** @type {UserRepository} */
			this.userRepository = this.core.getRepository().user;
			/** @type {FileRepository} */
			this.fileRepository = this.core.getRepository().file;
			/** @type {ReactionRepository} */
			this.reactionRepository = this.core.getRepository().reaction;
			/** @type {MessageRepository} */
			this.messageRepository = this.core.getRepository().message;
			/** @type {PinMessageRepository} */
			this.pinMessageRepository = this.core.getRepository().pinMessage;
			/** @type {RecentRepository} */
			this.recentRepository = this.core.getRepository().recent;
			/** @type {CopilotRepository} */
			this.copilotRepository = this.core.getRepository().copilot;

			this.messageContextCreator = new MessageContextCreator();

			this.bindMethods();
			this.subscribeEvents();
		}

		get emitter()
		{
			return serviceLocator.get('emitter');
		}

		bindMethods()
		{
			this.onSyncRequestResultReceive = this.onSyncRequestResultReceive.bind(this);
		}

		subscribeEvents()
		{
			BX.addCustomEvent(EventType.sync.requestResultReceived, this.onSyncRequestResultReceive);
		}

		/**
		 * @param {SyncRequestResultReceivedEvent} event
		 */
		async onSyncRequestResultReceive(event)
		{
			if (!this.checkEventUuid(event.uuid))
			{
				return;
			}

			await this.fillData(event);
		}

		/**
		 * @param {SyncRequestResultReceivedEvent} data
		 */
		async fillData(data)
		{
			throw new Error('SyncFillerBase.fillData must be override in subclass');
		}

		/**
		 * @return {string}
		 *
		 * @desc the method should return a prefix unique for each filler, which determines the need for event processing
		 */
		getUuidPrefix()
		{
			throw new Error('SyncFillerBase.getUuidPrefix must be override in subclass');
		}

		/**
		 * @param {string} uuid
		 * @return {boolean}
		 *
		 * @desc the method should check whether the uuid of the event is valid for this filler or not
		 */
		checkEventUuid(uuid)
		{
			return uuid.startsWith(this.getUuidPrefix());
		}

		/**
		 * @param {SyncListResult} result
		 * @return {SyncListResult}
		 */
		prepareResult(result)
		{
			return this.filterUsers(result);
		}

		/**
		 * @param {SyncListResult} result
		 * @return {SyncListResult}
		 */
		filterUsers(result)
		{
			const cloneResult = clone(result);
			cloneResult.usersShort = result.usersShort.filter((user) => user.id > 0);
			cloneResult.users = result.users.filter((user) => user.id > 0);

			return cloneResult;
		}

		/**
		 * @param {SyncListResult} syncListResult
		 * @return Promise
		 */
		async updateModels(syncListResult)
		{
			logger.log(`${this.constructor.name}.updateModels:`, syncListResult);

			await this.processUsers(syncListResult);
			this.closeDeletedCommentsChats(syncListResult.messageSync.completeDeletedMessages);
			await this.processDialogues(syncListResult);
			await this.processFiles(syncListResult.files);
			await this.processReactions(syncListResult);
			await this.processMessages(syncListResult);
			await this.processPins(syncListResult);
			await this.processRecent(syncListResult);
			await this.processCopilot(syncListResult);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 */
		async processUsers(syncListResult)
		{
			const { users, usersShort } = syncListResult;
			const usersUniqueCollection = [...new Map([...users, ...usersShort].map((user) => [user.id, user])).values()];

			if (!Type.isArrayFilled(usersUniqueCollection))
			{
				return;
			}

			await this.store.dispatch('usersModel/setFromSync', usersUniqueCollection);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 */
		async processDialogues(syncListResult)
		{
			await this.processAddedDialogues(syncListResult);
			await this.processDeletedChats(ChatDataProvider.source.model, syncListResult.chatSync.deletedChats);
			await this.processCompletelyDeletedChats(
				ChatDataProvider.source.model,
				syncListResult.chatSync.completeDeletedChats,
			);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 */
		async processAddedDialogues(syncListResult)
		{
			const { chats, chatSync, dialogIds, messagesAutoDeleteConfigs } = syncListResult;
			const preparedAddedDialogsData = this.prepareDialogues(chats, chatSync.addedChats, dialogIds);
			const preparedDialogsData = this.prepareAutoDeleteDialoguesData(
				preparedAddedDialogsData,
				messagesAutoDeleteConfigs,
			);

			await this.store.dispatch('dialoguesModel/setFromSync', preparedDialogsData);
		}

		/**
		 * @param {Array<SyncRawChat>} chats
		 * @param {Record<string, number> | []} addedChatsIds
		 * @param {Record<number, string>} dialogIds
		 * @return {Array<SyncRawChat>}
		 */
		prepareDialogues(chats, addedChatsIds, dialogIds)
		{
			const addedChatsIdSet = new Set(Object.values(addedChatsIds));
			const addedChats = chats.filter((chat) => {
				return addedChatsIdSet.has(chat.id);
			});

			return addedChats.map((chat) => {
				const { id: chatId, dialogId } = chat;

				const processedChat = { ...chat };

				if (chatId && !dialogId)
				{
					processedChat.dialogId = dialogIds[chatId];
				}
				else if (dialogId && !chatId)
				{
					const [foundId] = Object.entries(dialogIds)
						.find(([, value]) => dialogId === value) || [];
					processedChat.id = Number(foundId);
				}

				return processedChat;
			}).filter(Boolean);
		}

		/**
		 * @param {Array<SyncRawChat>} dialogues
		 * @param {Array<MessagesAutoDeleteConfigs>} messagesAutoDeleteConfigs
		 * @return {Array<object>}
		 */
		prepareAutoDeleteDialoguesData(dialogues, messagesAutoDeleteConfigs)
		{
			if (!Type.isArrayFilled(messagesAutoDeleteConfigs))
			{
				return dialogues;
			}

			messagesAutoDeleteConfigs.forEach((config) => {
				const dialogData = dialogues.find((dialog) => dialog.id === config.chatId);
				if (dialogData)
				{
					dialogData.messagesAutoDeleteDelay = config.delay;
				}
			});

			return dialogues;
		}

		/**
		 * @param {Array<SyncRawFile>} files
		 * @return Promise
		 */
		async processFiles(files)
		{
			if (!Type.isArrayFilled(files))
			{
				return;
			}

			await this.store.dispatch('filesModel/setFromSync', files);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 * @return Promise
		 */
		async processReactions(syncListResult)
		{
			if (!Type.isArrayFilled(syncListResult.reactions))
			{
				return;
			}

			const reactions = this.getReactionsFromSyncListResult(syncListResult);

			await this.store.dispatch('messagesModel/reactionsModel/setFromSync', { reactions });
		}

		/**
		 * @param {SyncListResult} syncListResult
		 * @return Array<SyncReaction>
		 */
		getReactionsFromSyncListResult(syncListResult)
		{
			let reactions = syncListResult.reactions;
			const messageIdCollection = {};
			reactions.forEach((reaction) => {
				messageIdCollection[reaction.messageId] = {
					chatId: 0,
					dialogId: '',
				};
			});
			syncListResult.messages.forEach((message) => {
				const messageId = message.id;
				if (messageIdCollection[messageId])
				{
					const chatId = message.chat_id;
					messageIdCollection[messageId].dialogId = syncListResult.dialogIds[chatId];
				}
			});
			reactions = reactions.map((reaction) => {
				// eslint-disable-next-line no-param-reassign
				reaction.dialogId = messageIdCollection[reaction.messageId].dialogId;

				return reaction;
			});

			return reactions;
		}

		/**
		 * @param {SyncListResult} syncListResult
		 */
		async processMessages(syncListResult)
		{
			const { messages, messageSync, recentItems } = syncListResult;

			await this.processAddedAndUpdatedMessages(
				messages,
				messageSync.addedMessages,
				messageSync.updatedMessages,
				recentItems,
			);
			await this.processCompleteDeletedMessages(messageSync.completeDeletedMessages);
		}

		/**
		 * @param {Array<SyncRawMessage>} messages
		 * @param {Record<number, number> | []} addedMessagesIds
		 * @param {Record<number, number> | []} updatedMessagesIds
		 * @param {Array<SyncRawRecentItem>} recentItems
		 */
		async processAddedAndUpdatedMessages(messages, addedMessagesIds, updatedMessagesIds, recentItems)
		{
			const openChatIdList = this.getOpenChatsToAddMessages();
			if (!Type.isArrayFilled(openChatIdList) && !Type.isArrayFilled(recentItems))
			{
				return;
			}

			const openChatsMessages = messages.filter((message) => {
				return openChatIdList.includes(message.chat_id);
			});

			const recentMessageIdList = new Set(recentItems.map((item) => item.messageId));
			const recentMessages = messages.filter((message) => {
				return recentMessageIdList.has(message.id);
			});

			const availableMessages = [...new Set([...openChatsMessages, ...recentMessages])];
			const addMessagesIdSet = new Set(Object.values(addedMessagesIds));
			const addedMessages = availableMessages.filter((message) => addMessagesIdSet.has(message.id));
			await this.store.dispatch('messagesModel/setFromSync', {
				messages: addedMessages,
			});

			const updateMessagesIdSet = new Set(Object.values(updatedMessagesIds));
			const updateMessages = availableMessages.filter((message) => updateMessagesIdSet.has(message.id));
			await this.store.dispatch('messagesModel/updateListFromSync', {
				messageList: updateMessages,
			});
		}

		/**
		 * @param {Record<number, number> | []} completeDeletedMessagesIds
		 */
		async processCompleteDeletedMessages(completeDeletedMessagesIds)
		{
			if (Type.isArray(completeDeletedMessagesIds) && !Type.isArrayFilled(completeDeletedMessagesIds))
			{
				return;
			}

			const completeDeleteMessageIds = Object.values(completeDeletedMessagesIds);

			await this.store.dispatch('messagesModel/deleteByIdList', {
				idList: completeDeleteMessageIds,
			});
		}

		/**
		 * @private
		 * @return Number[]
		 */
		getOpenChatsToAddMessages()
		{
			const openDialogs = this.store.getters['applicationModel/getOpenDialogs']();
			const openChats = this.store.getters['dialoguesModel/getByIdList'](openDialogs);
			const openChatIdList = [];
			openChats.forEach((chat) => {
				if (chat.inited && chat.hasNextPage === false)
				{
					openChatIdList.push(chat.chatId);
				}
			});

			return openChatIdList;
		}

		/**
		 * @param {SyncListResult} syncListResult
		 */
		async processPins(syncListResult)
		{
			const { pins, pinSync, additionalMessages } = syncListResult;

			await this.processAddedPins(pins, pinSync.addedPins, additionalMessages);
			await this.processDeletedPins(pinSync.deletedPins);
		}

		/**
		 * @param {Array<SyncRawPin>} pins
		 * @param {Record<number, number> | []} addedPinsIds
		 * @param {Array<SyncRawMessage>} additionalMessages
		 */
		async processAddedPins(pins, addedPinsIds, additionalMessages)
		{
			if (Type.isArray(addedPinsIds) && !Type.isArrayFilled(addedPinsIds))
			{
				return;
			}

			const { addedPins, additionalPinMessages } = this.prepareAddedPins(pins, addedPinsIds, additionalMessages);

			await this.store.dispatch('messagesModel/pinModel/setListFromSync', {
				pins: addedPins ?? [],
				messages: additionalPinMessages ?? [],
			});
		}

		/**
		 * @param {Record<number, number> | []} deletedPinsIds
		 * @return {Promise<void>}
		 */
		async processDeletedPins(deletedPinsIds)
		{
			if (Type.isArray(deletedPinsIds) && !Type.isArrayFilled(deletedPinsIds))
			{
				return;
			}

			await this.store.dispatch('messagesModel/pinModel/deleteByIdListFromSync', {
				idList: Object.values(deletedPinsIds) ?? [],
			});
		}

		/**
		 * @param {SyncListResult} syncListResult
		 */
		async processRecent(syncListResult)
		{
			const { chatSync, recentItems, messages } = syncListResult;

			const processedRecentItems = this.prepareAddedRecent(recentItems, chatSync.addedRecent, messages);

			await this.store.dispatch('recentModel/setFromSync', processedRecentItems);
		}

		/**
		 * @param {Array<SyncRawPin>} pins
		 * @param {Record<number, number> | []} addedPinsIds
		 * @param {Array<SyncRawMessage>} additionalMessages
		 * @return {{addedPins:Array<SyncRawPin>, additionalPinMessages:Array<SyncRawMessage>}}
		 */
		prepareAddedPins(pins, addedPinsIds, additionalMessages)
		{
			const addedPinsIdSet = new Set(Object.values(addedPinsIds));
			const addedPins = pins.filter((pin) => {
				return addedPinsIdSet.has(pin.id);
			});

			const addedPinsMessageIdSet = new Set(addedPins.map((pin) => pin.messageId));
			const additionalPinMessages = additionalMessages.filter((additionalMessage) => {
				return addedPinsMessageIdSet.has(additionalMessage.id);
			});

			return { addedPins, additionalPinMessages };
		}

		/**
		 * @param {Array<SyncRawRecentItem>} recentItems
		 * @param {Record<string, number> | []} addedRecentIds
		 * @param {Array<SyncRawMessage>} messages
		 * @return {Array<Object>}
		 */
		prepareAddedRecent(recentItems, addedRecentIds, messages)
		{
			const addedRecentIdSet = new Set(Object.values(addedRecentIds));
			const addedRecentItems = recentItems.filter((recentItem) => {
				return addedRecentIdSet.has(recentItem.chatId);
			});

			return addedRecentItems.map((recentItem) => {
				const messageData = messages.find((message) => message.id === recentItem.messageId);
				if (!messageData)
				{
					return { ...recentItem };
				}
				const preparedMessage = this.fillMessageStatus(messageData);

				return { ...recentItem, message: preparedMessage };
			});
		}

		/**
		 * @param {SyncListResult} syncListResult
		 */
		async processCopilot(syncListResult)
		{
			const { copilot, dialogIds, messages } = syncListResult;
			if (Type.isNil(copilot))
			{
				return;
			}

			const preparedCopilotItems = this.prepareCopilotItems(copilot, dialogIds, messages);
			if (preparedCopilotItems.length > 0)
			{
				await this.store.dispatch('dialoguesModel/copilotModel/setFromSync', preparedCopilotItems);
			}
		}

		/**
		 * @param {CopilotSyncData} copilot
		 * @param {Record<string, number> | []} dialogIds
		 * @param {Array<SyncRawMessage>} messages
		 * @return {Array<CopilotModelState>}
		 */
		prepareCopilotItems(copilot, dialogIds, messages)
		{
			const messageIdMap = new Map(messages.map((message) => [message.id, message]));

			return Object.entries(dialogIds).map(([chatId, dialogId]) => {
				const chat = copilot.chats?.find((copilotChat) => copilotChat.dialogId === dialogId) || null;

				const copilotMessages = copilot.messages?.filter((copilotMessage) => {
					const message = messageIdMap.get(copilotMessage.id);

					return message && String(message.chat_id) === chatId;
				}) || [];

				return {
					aiProvider: copilot.aiProvider,
					dialogId,
					chats: chat ? [chat] : [],
					roles: copilot.roles,
					messages: copilotMessages,
				};
			});
		}

		/**
		 * @param {string} source
		 * @param {Record<string, number> | []} deletedChatsIds
		 * @returns {Promise<void>}
		 */
		async processDeletedChats(source, deletedChatsIds)
		{
			await this.processCompletelyDeletedChats(source, deletedChatsIds);
		}

		/**
		 * @param {string} source
		 * @param {Record<string, number> | []} completeDeletedChatsIds
		 * @returns {Promise<void>}
		 */
		async processCompletelyDeletedChats(source, completeDeletedChatsIds)
		{
			const chatIdList = Object.values(completeDeletedChatsIds);
			if (!Type.isArrayFilled(chatIdList))
			{
				return;
			}
			logger.log(`${this.constructor.name}.processCompletelyDeletedChats`, chatIdList);

			const chatProvider = new ChatDataProvider();
			const recentProvider = new RecentDataProvider();

			for (const chatId of chatIdList)
			{
				const chatData = this.store.getters['dialoguesModel/getByChatId'](chatId);

				if (Type.isPlainObject(chatData))
				{
					const helper = DialogHelper.createByModel(chatData);
					if (helper?.isChannel)
					{
						const commentChatData = this.store.getters['dialoguesModel/getByParentChatId'](chatData.chatId);

						if (
							Type.isPlainObject(commentChatData)
							&& this.store.getters['applicationModel/isDialogOpen'](commentChatData.dialogId)
						)
						{
							chatProvider.delete({ dialogId: commentChatData.dialogId });
							this.closeDeletedChat({
								dialogId: commentChatData.dialogId,
								chatType: commentChatData.type,
								shouldSendDeleteAnalytics: false,
								shouldShowAlert: false,
								parentChatId: commentChatData.parentChatId,
							});
						}
					}

					this.closeDeletedChat({
						dialogId: chatData.dialogId,
						chatType: chatData.type,
					});
				}
				// recent should be first deleting because he's find chat by ChatDataProvider by chatId
				// eslint-disable-next-line no-await-in-loop
				await recentProvider.deleteFromSource(source, { chatId })
					.then(() => chatProvider.deleteFromSource(source, { chatId }))
				;
			}
		}

		/**
		 * @param {Record<number, number> | []} completeDeletedMessagesIds
		 */
		closeDeletedCommentsChats(completeDeletedMessagesIds)
		{
			if (Type.isArray(completeDeletedMessagesIds) && !Type.isArrayFilled(completeDeletedMessagesIds))
			{
				return;
			}

			const deletedCommentsChatList = [];
			for (const messageId of Object.values(completeDeletedMessagesIds))
			{
				const commentInfo = this.store.getters['commentModel/getByMessageId'](messageId);
				if (!commentInfo)
				{
					continue;
				}

				deletedCommentsChatList.push(commentInfo.chatId);

				const messageData = this.store.getters['messagesModel/getById'](messageId);
				if (!messageData.id)
				{
					continue;
				}

				this.closeDeletedChat({
					dialogId: commentInfo.dialogId,
					parentChatId: messageData.chatId,
					chatType: DialogType.comment,
				});
			}

			if (Type.isArrayFilled(deletedCommentsChatList))
			{
				MessengerCounterSender.getInstance().sendDeleteChats(deletedCommentsChatList);
			}
		}

		closeDeletedChat({
			dialogId,
			chatType,
			parentChatId = 0,
			shouldSendDeleteAnalytics = true,
			shouldShowAlert = true,
		})
		{
			if (this.store.getters['applicationModel/isDialogOpen'](dialogId))
			{
				MessengerEmitter.emit(EventType.dialog.external.delete, {
					dialogId,
					chatType,
					parentChatId,
					shouldSendDeleteAnalytics,
					shouldShowAlert,
				});
			}
		}

		/**
		 *
		 * @param {Array<SyncRawChat>} allChats
		 * @return {Array<number>}
		 */
		findCopilotChatIds(allChats)
		{
			const result = [];
			for (const chatData of allChats)
			{
				if (chatData.type === DialogType.copilot)
				{
					result.push(chatData.id);
				}
			}

			return result;
		}

		/**
		 * @param {SyncRawMessage} message
		 * @return message
		 */
		fillMessageStatus(message)
		{
			const messageData = message;
			messageData.status = MessageStatus.received;
			if (message && Type.isBoolean(messageData.viewedByOthers) && messageData.viewedByOthers)
			{
				messageData.status = MessageStatus.delivered;
			}

			return messageData;
		}
	}

	module.exports = {
		SyncFillerBase,
	};
});
