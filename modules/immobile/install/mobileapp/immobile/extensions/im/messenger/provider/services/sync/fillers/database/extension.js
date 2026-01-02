/**
 * @module im/messenger/provider/services/sync/fillers/database
 */
jn.define('im/messenger/provider/services/sync/fillers/database', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { EventType, ComponentCode, WaitingEntity } = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { ChatDataProvider } = require('im/messenger/provider/data');

	const { SyncFillerBase } = require('im/messenger/provider/services/sync/fillers/base');

	const logger = getLoggerWithContext('sync-service', 'SyncFillerDatabase');

	/**
	 * @class SyncFillerDatabase
	 */
	class SyncFillerDatabase extends SyncFillerBase
	{
		subscribeEvents()
		{
			this.emitter.on(EventType.sync.requestResultReceived, this.onSyncRequestResultReceive);
		}

		/**
		 * @param {SyncRequestResultReceivedEvent} data
		 */
		async fillData(data)
		{
			logger.log('SyncFillerDatabase.fillData:', data);
			const {
				uuid,
				result,
			} = data;

			try
			{
				await this.updateDatabase(this.prepareResult(result));

				MessengerEmitter.emit(EventType.sync.requestResultSaved, {
					uuid,
				}, ComponentCode.imMessenger);
			}
			catch (error)
			{
				logger.error('SyncFillerDatabase.fillData error: ', error);

				MessengerEmitter.emit(EventType.sync.requestResultSaved, {
					uuid,
					error: `SyncFillerDatabase.fillData error: ${error.message}`,
				}, ComponentCode.imMessenger);
			}
		}

		/**
		 * @param {SyncListResult} result
		 */
		async fillDataWithoutEmit(result)
		{
			logger.log('fillDataWithoutEmit:', result);

			try
			{
				await this.updateDatabase(this.prepareResult(result));
			}
			catch (error)
			{
				logger.error('fillDataWithoutEmit catch:', error);

				throw error;
			}
		}

		/**
		 * @param {SyncListResult} result
		 * @return {SyncListResult}
		 */
		prepareResult(result)
		{
			return this.filterUsers(result);
		}

		getUuidPrefix()
		{
			return WaitingEntity.sync.filler.database;
		}

		/**
		 * @private
		 * @param {SyncListResult} syncListResult
		 * @return Promise
		 */
		async updateDatabase(syncListResult)
		{
			await this.fillUsers(syncListResult.users, syncListResult.usersShort);
			await this.fillFiles(syncListResult.files, syncListResult.dialogIds);
			await this.fillDialogues(syncListResult);
			await this.fillReactions(syncListResult);
			await this.fillMessages(syncListResult);
			await this.fillPins(syncListResult);
			await this.fillRecent(syncListResult);
			await this.fillCopilot(syncListResult);
		}

		/**
		 * @param {Array<SyncRawUser>} users
		 * @param {Array<SyncRawShortUser>} shortUsers
		 * @return {Promise<void>}
		 */
		async fillUsers(users, shortUsers)
		{
			const allUsers = [...users, ...shortUsers];
			if (!Type.isArrayFilled(allUsers))
			{
				return;
			}

			await this.userRepository.saveFromRest(allUsers);
		}

		/**
		 * @param {Array<SyncRawFile>} files
		 * @param {Record<number, string>} dialogIds
		 * @return {Promise<void>}
		 */
		async fillFiles(files, dialogIds)
		{
			if (!Type.isArrayFilled(files))
			{
				return;
			}

			const processedFiles = files.map((file) => ({
				...file,
				dialogId: dialogIds[file.chatId],
			}));

			await this.fileRepository.saveFromRest(processedFiles);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 * @return {Promise<void>}
		 */
		async fillMessages(syncListResult)
		{
			const { messages, dialogIds, messageSync } = syncListResult;

			const messagesLinkedList = await this.messageContextCreator.createMessageLinkedListForSyncResult(
				clone(messages),
				dialogIds,
			);

			await this.fillAddedMessages(messagesLinkedList, messageSync.addedMessages);
			await this.fillUpdatedMessages(messagesLinkedList, messageSync.updatedMessages);
			await this.fillCompleteDeletedMessages(messageSync.completeDeletedMessages);
			await this.fillLastMessages(messagesLinkedList, messageSync.addedMessages, messageSync.updatedMessages);
		}

		/**
		 * @param {Array<SyncRawMessage>} messages
		 * @param {Record<number, number> | []} addedMessagesIds
		 * @return {Promise<void>}
		 */
		async fillAddedMessages(messages, addedMessagesIds)
		{
			if (Type.isArray(addedMessagesIds) && !Type.isArrayFilled(addedMessagesIds))
			{
				return;
			}

			const addMessagesIdSet = new Set(Object.values(addedMessagesIds));
			const addedMessages = messages.filter((message) => addMessagesIdSet.has(message.id));

			await this.messageRepository.saveFromRest(addedMessages);
		}

		/**
		 * @param {Array<SyncRawMessage>} messages
		 * @param {Record<number, number> | []} updatedMessagesIds
		 * @return {Promise<void>}
		 */
		async fillUpdatedMessages(messages, updatedMessagesIds)
		{
			if (Type.isArray(updatedMessagesIds) && !Type.isArrayFilled(updatedMessagesIds))
			{
				return;
			}

			const updateMessagesIdSet = new Set(Object.values(updatedMessagesIds));
			const updateMessages = messages.filter((message) => updateMessagesIdSet.has(message.id));

			await this.messageRepository.saveFromRest(updateMessages);
		}

		/**
		 * @param {Record<number, number> | []} completeDeletedMessagesIds
		 * @return {Promise<void>}
		 */
		async fillCompleteDeletedMessages(completeDeletedMessagesIds)
		{
			if (Type.isArray(completeDeletedMessagesIds) && !Type.isArrayFilled(completeDeletedMessagesIds))
			{
				return;
			}

			const completeDeleteMessageIds = Object.values(completeDeletedMessagesIds);

			await this.messageRepository.deleteByIdList(completeDeleteMessageIds);
		}

		/**
		 * @param {Array<SyncRawMessage>} messages
		 * @param {Record<number, number> | []} addedMessagesIds
		 * @param {Record<number, number> | []} updatedMessagesIds
		 * @return {Promise<void>}
		 */
		async fillLastMessages(messages, addedMessagesIds, updatedMessagesIds)
		{
			if (!Type.isArrayFilled(messages))
			{
				return;
			}

			const addedMessagesIdSet = new Set(Object.values(addedMessagesIds));
			const updateMessagesIdSet = new Set(Object.values(updatedMessagesIds));
			const recentMessages = messages.filter((message) => {
				return !updateMessagesIdSet.has(message.id) && !addedMessagesIdSet.has(message.id);
			});

			if (!Type.isArrayFilled(recentMessages))
			{
				return;
			}

			await this.messageRepository.saveFromRest(recentMessages);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 * @return {Promise<void>}
		 */
		async fillPins(syncListResult)
		{
			const { pins, pinSync, additionalMessages, messageSync } = syncListResult;

			await this.fillAddedPins(pins, pinSync.addedPins, additionalMessages);
			await this.fillDeletedPins(pinSync.deletedPins, messageSync.completeDeletedMessages);
		}

		/**
		 * @param {Array<SyncRawPin>} pins
		 * @param {Record<number, number> | []} addedPinsIds
		 * @param {Array<SyncRawMessage>} additionalMessages
		 * @return {Promise<void>}
		 */
		async fillAddedPins(pins, addedPinsIds, additionalMessages)
		{
			if (Type.isArray(addedPinsIds) && !Type.isArrayFilled(addedPinsIds))
			{
				return;
			}

			const { addedPins, additionalPinMessages } = this.prepareAddedPins(pins, addedPinsIds, additionalMessages);

			await this.pinMessageRepository.saveFromRest(addedPins, additionalPinMessages);
		}

		/**
		 * @param {Record<number, number> | []} deletedPinsIds
		 * @param {Record<number, number> | []} completeDeletedMessagesIds
		 * @return {Promise<void>}
		 */
		async fillDeletedPins(deletedPinsIds, completeDeletedMessagesIds)
		{
			if (!Type.isArray(deletedPinsIds))
			{
				await this.pinMessageRepository.deletePinsByIdList(Object.values(deletedPinsIds));
			}

			if (!Type.isArray(completeDeletedMessagesIds))
			{
				await this.pinMessageRepository.deleteByMessageIdList(Object.values(completeDeletedMessagesIds));
			}
		}

		/**
		 * @param {SyncListResult} syncListResult
		 * @return {Promise<void>}
		 */
		async fillRecent(syncListResult)
		{
			const { recentItems, chatSync, messages } = syncListResult;
			await this.fillAddedRecent(recentItems, chatSync.addedRecent, messages);
		}

		/**
		 * @param {Array<SyncRawRecentItem>} recentItems
		 * @param {Record<string, number> | []} addedRecentIds
		 * @param {Array<SyncRawMessage>} messages
		 * @return {Promise<void>}
		 */
		async fillAddedRecent(recentItems, addedRecentIds, messages)
		{
			if (Type.isArray(addedRecentIds) && !Type.isArrayFilled(addedRecentIds))
			{
				return;
			}

			const processedRecentItems = this.prepareAddedRecent(recentItems, addedRecentIds, messages);

			await this.recentRepository.saveFromRest(processedRecentItems);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 * @return {Promise<void>}
		 */
		async fillReactions(syncListResult)
		{
			if (!Type.isArrayFilled(syncListResult.reactions))
			{
				return;
			}

			const reactions = this.getReactionsFromSyncListResult(syncListResult);

			await this.reactionRepository.saveFromRest(reactions);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 */
		async fillDialogues(syncListResult)
		{
			const addedChats = await this.fillAddedDialogues(
				syncListResult.chats,
				syncListResult.chatSync.addedChats,
				syncListResult.dialogIds,
			);
			await this.fillWasCompletelySyncDialogues(addedChats, syncListResult.messages);
			await this.processDeletedChats(ChatDataProvider.source.database, syncListResult.chatSync.deletedChats);
			await this.processCompletelyDeletedChats(
				ChatDataProvider.source.database,
				syncListResult.chatSync.completeDeletedChats,
			);
		}

		/**
		 * @param {Array<SyncRawChat>} chats
		 * @param {Record<string, number> | []} addedChatsIds
		 * @param {Record<number, string>} dialogIds
		 * @return {Promise<Array>}
		 */
		async fillAddedDialogues(chats, addedChatsIds, dialogIds)
		{
			if (Type.isArray(addedChatsIds) && !Type.isArrayFilled(addedChatsIds))
			{
				return [];
			}

			const preparedDialogsData = this.prepareDialogues(chats, addedChatsIds, dialogIds);

			await this.dialogRepository.saveFromRest(preparedDialogsData);

			return preparedDialogsData;
		}

		/**
		 * @param {SyncListResult} syncListResult
		 */
		async fillCopilot(syncListResult)
		{
			const { copilot, dialogIds, messages } = syncListResult;
			if (Type.isNil(copilot))
			{
				return;
			}

			const preparedCopilotItems = this.prepareCopilotItems(copilot, dialogIds, messages);

			if (preparedCopilotItems.length > 0)
			{
				await this.copilotRepository.saveFromRest(preparedCopilotItems);
			}
		}

		/**
		 * @param {Array<SyncRawChat>} chats
		 * @param {Array<SyncRawMessage>} messages
		 * @return {Promise<void>}
		 */
		async fillWasCompletelySyncDialogues(chats, messages)
		{
			const messageIdSet = new Set(messages.map((message) => message.id));

			const completelySyncDialogIdList = chats
				.filter((chat) => messageIdSet.has(chat.lastMessageId))
				.map((chat) => chat.dialogId);

			if (completelySyncDialogIdList.length > 0)
			{
				await this.dialogRepository.setWasCompletelySyncByIdList(completelySyncDialogIdList, true);
			}
		}
	}

	module.exports = { SyncFillerDatabase };
});
