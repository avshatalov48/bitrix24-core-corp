/**
 * @module im/messenger/provider/service/classes/sync/fillers/sync-filler-database
 */
jn.define('im/messenger/provider/service/classes/sync/fillers/sync-filler-database', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { EventType, ComponentCode, WaitingEntity } = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { ChatDataProvider } = require('im/messenger/provider/data');

	const { SyncFillerBase } = require('im/messenger/provider/service/classes/sync/fillers/sync-filler-base');

	const logger = LoggerManager.getInstance().getLogger('sync-service');

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
		 * @param {object} data
		 * @param {string} data.uuid
		 * @param {SyncListResult} data.result
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
			cloneResult.addedRecent = cloneResult.addedRecent.map((recentItem) => {
				if (recentItem.user?.id === 0)
				{
					// eslint-disable-next-line no-param-reassign
					recentItem.user = null;
				}

				return recentItem;
			});

			cloneResult.updatedMessages.users = result.updatedMessages.users.filter((user) => user.id !== 0);
			cloneResult.messages.users = result.messages.users.filter((user) => user.id !== 0);
			cloneResult.addedPins.users = result.messages.users.filter((user) => user.id !== 0);

			return cloneResult;
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
			const {
				messages,
				updatedMessages = {},
				addedChats,
				addedRecent,
				completeDeletedMessages,
				deletedChats,
				completeDeletedChats,
				addedPins,
				deletedPins,
				dialogIds,
			} = syncListResult;

			const addedChatsWithDialogIds = await this.fillDatabaseFromDialogues(addedChats, dialogIds);
			await this.fillWasCompletelySyncDialogues(addedChatsWithDialogIds, messages.messages);

			await this.fillDatabaseFromMessages(messages, dialogIds);
			await this.updateDatabaseFromMessages(updatedMessages);
			await this.updateDatabaseFromPins(addedPins, deletedPins);

			const completeDeletedMessageIdList = Object.values(completeDeletedMessages);
			if (Type.isArrayFilled(completeDeletedMessageIdList))
			{
				await this.messageRepository.deleteByIdList(completeDeletedMessageIdList);
				await this.pinMessageRepository.deleteByMessageIdList(completeDeletedMessageIdList);
			}

			await this.fillDatabaseFromRecent(addedRecent);

			await this.processDeletedChats(ChatDataProvider.source.database, deletedChats);
			await this.processCompletelyDeletedChats(ChatDataProvider.source.database, completeDeletedChats);
		}

		/**
		 * @param {SyncListResult['addedChats']} addedChats
		 * @param {SyncListResult['dialogIds']} dialogIds
		 *
		 * @return {Promise<Array>}
		 */
		async fillDatabaseFromDialogues(addedChats, dialogIds)
		{
			if (Type.isArrayFilled(addedChats))
			{
				const addedChatsWithDialogIds = [];
				addedChats.forEach((chat) => {
					const chatId = chat.id;
					const dialogId = chat.dialogId;
					if (chatId && !dialogId)
					{
						// eslint-disable-next-line no-param-reassign
						chat.dialogId = dialogIds[chatId];
					}

					addedChatsWithDialogIds.push(chat);
				});

				await this.dialogRepository.saveFromRest(addedChatsWithDialogIds);

				return addedChatsWithDialogIds;
			}

			return [];
		}

		/**
		 * @param {SyncListResult['addedChats']} addedChats
		 * @param {SyncListResult['messages']['messages']} messages
		 * @return {Promise<void>}
		 */
		async fillWasCompletelySyncDialogues(addedChats, messages)
		{
			const messageIdCollection = {};
			messages.forEach((message) => {
				messageIdCollection[message.id] = true;
			});

			const completelySyncDialogIdList = [];
			addedChats.forEach((chat) => {
				if (messageIdCollection[chat.last_message_id])
				{
					completelySyncDialogIdList.push(chat.dialogId);
				}
			});

			if (Type.isArrayFilled(completelySyncDialogIdList))
			{
				await this.dialogRepository.setWasCompletelySyncByIdList(completelySyncDialogIdList, true);
			}
		}

		/**
		 *
		 * @param {SyncListResult['messages']} syncMessages
		 * @param {SyncListResult['dialogIds']} dialogIds
		 * @return {Promise<void>}
		 */
		async fillDatabaseFromMessages(syncMessages, dialogIds)
		{
			const {
				users,
				files,
				reactions,
				messages,
			} = syncMessages;

			if (Type.isArrayFilled(users))
			{
				await this.userRepository.saveFromRest(users);
			}

			if (Type.isArrayFilled(files))
			{
				await this.fileRepository.saveFromRest(files);
			}

			if (Type.isArrayFilled(reactions))
			{
				await this.reactionRepository.saveFromRest(reactions);
			}

			const messagesLinkedList = await this.messageContextCreator.createMessageLinkedListForSyncResult(
				messages,
				dialogIds,
			);
			if (Type.isArrayFilled(messagesLinkedList))
			{
				await this.messageRepository.saveFromRest(messagesLinkedList);
			}
		}

		/**
		 *
		 * @param {SyncListResult['updatedMessages']} updatedMessages
		 * @return {Promise<void>}
		 */
		async updateDatabaseFromMessages(updatedMessages)
		{
			const {
				users,
				files,
				reactions,
				messages,
			} = updatedMessages;

			if (Type.isArrayFilled(users))
			{
				await this.userRepository.saveFromRest(users);
			}

			if (Type.isArrayFilled(files))
			{
				await this.fileRepository.saveFromRest(files);
			}

			if (Type.isArrayFilled(reactions))
			{
				await this.reactionRepository.saveFromRest(reactions);
			}

			if (Type.isArrayFilled(messages))
			{
				logger.log('SyncService: updatedMessages', messages);

				const updatedMessageIdList = messages.map((message) => message.id);
				const existingMessages = await this.messageRepository.messageTable.getListByIds(updatedMessageIdList, false);
				const existingMessagesIdCollection = {};
				existingMessages.items.forEach((message) => {
					existingMessagesIdCollection[message.id] = true;
				});

				const updatedMessagesToSave = messages.filter((message) => existingMessagesIdCollection[message.id]);
				if (Type.isArrayFilled(updatedMessagesToSave))
				{
					logger.log('SyncService: updatedMessagesToSave', updatedMessagesToSave);

					await this.messageRepository.saveFromRest(updatedMessagesToSave);
				}
			}
		}

		/**
		 *
		 * @param {SyncListResult['addedPins']} addedPins
		 * @param {SyncListResult['deletedPins']} deletedPins
		 * @return {Promise<void>}
		 */
		async updateDatabaseFromPins(addedPins, deletedPins)
		{
			const {
				additionalMessages,
				users,
				pins,
				files,
			} = addedPins;

			if (Type.isArrayFilled(users))
			{
				await this.userRepository.saveFromRest(users);
			}

			if (Type.isArrayFilled(files))
			{
				await this.fileRepository.saveFromRest(files);
			}

			if (Type.isArrayFilled(pins) && Type.isArrayFilled(additionalMessages))
			{
				await this.pinMessageRepository.saveFromRest(pins, additionalMessages);
			}

			const deletedPinIdList = Object.values(deletedPins);
			if (Type.isArrayFilled(deletedPinIdList))
			{
				await this.pinMessageRepository.deletePinsByIdList(deletedPinIdList);
			}
		}

		/**
		 * @param {SyncListResult['addedRecent']} addedRecent
		 * @return {Promise<void>}
		 */
		async fillDatabaseFromRecent(addedRecent)
		{
			const recentUsers = [];
			addedRecent.forEach((recentItem) => {
				if (recentItem.user)
				{
					recentUsers.push(recentItem.user);
				}
			});

			if (Type.isArrayFilled(recentUsers))
			{
				await this.userRepository.saveFromRest(recentUsers);
			}

			if (Type.isArrayFilled(addedRecent))
			{
				await this.recentRepository.saveFromRest(addedRecent);
			}
		}
	}

	module.exports = { SyncFillerDatabase };
});
