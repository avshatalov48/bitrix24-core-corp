/**
 * @module im/messenger/provider/service/classes/sync/fillers/sync-filler-base
 */
jn.define('im/messenger/provider/service/classes/sync/fillers/sync-filler-base', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogType, EventType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { UserManager } = require('im/messenger/lib/user-manager');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sync-service');

	/**
	 * @class SyncFillerBase
	 */
	class SyncFillerBase
	{
		constructor()
		{
			this.core = serviceLocator.get('core');
			this.store = this.core.getStore();

			this.userManager = new UserManager(this.store);

			this.dialogRepository = this.core.getRepository().dialog;
			this.userRepository = this.core.getRepository().user;
			this.fileRepository = this.core.getRepository().file;
			this.reactionRepository = this.core.getRepository().reaction;
			this.messageRepository = this.core.getRepository().message;
			this.pinMessageRepository = this.core.getRepository().pinMessage;

			this.bindMethods();
			this.subscribeEvents();
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
		 * @param {SyncListResult} event
		 */
		async onSyncRequestResultReceive(event)
		{
			await this.fillData(event);
		}

		/**
		 * @param {object} data
		 * @param {string} data.uuid
		 * @param {SyncListResult} data.result
		 */
		async fillData(data)
		{
			throw new Error('SyncFillerBase.fillData must be override in subclass');
		}

		/**
		 * @param {SyncListResult} result
		 * @return {SyncListResult}
		 */
		prepareResult(result)
		{
			return result;
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
				addedPins,
				deletedPins,
			} = syncListResult;

			await this.fillDatabaseFromMessages(messages);
			await this.updateDatabaseFromMessages(updatedMessages);
			await this.updateDatabaseFromPins(addedPins, deletedPins);

			if (Type.isArrayFilled(addedChats))
			{
				// TODO: refactor when the dialogId will be in addedChats
				const addedRecentChatIds = {};
				addedRecent.forEach((recentItem) => {
					addedRecentChatIds[recentItem.chat_id] = recentItem.id;
				});

				const addedChatsWithDialogIds = [];
				addedChats.forEach((chat) => {
					const chatId = chat.id;
					const dialogId = chat.dialogId;
					if (chatId && !dialogId)
					{
						// eslint-disable-next-line no-param-reassign
						chat.dialogId = addedRecentChatIds[chatId];
					}

					addedChatsWithDialogIds.push(chat);
				});

				await this.dialogRepository.saveFromRest(addedChatsWithDialogIds);
			}

			const deletedChatsIdList = Object.values(deletedChats);
			if (Type.isArrayFilled(deletedChatsIdList))
			{
				await this.dialogRepository.deleteByChatIdList(deletedChatsIdList);
				await this.messageRepository.deleteByChatIdList(deletedChatsIdList);
				await this.pinMessageRepository.deleteByChatIdList(deletedChatsIdList);
			}

			const completeDeletedMessageIdList = Object.values(completeDeletedMessages);
			if (Type.isArrayFilled(completeDeletedMessageIdList))
			{
				await this.messageRepository.deleteByIdList(completeDeletedMessageIdList);
				await this.pinMessageRepository.deleteByMessageIdList(completeDeletedMessageIdList);
			}
		}

		/**
		 *
		 * @param {SyncListResult['messages']} syncMessages
		 * @return {Promise<void>}
		 */
		async fillDatabaseFromMessages(syncMessages)
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

			if (Type.isArrayFilled(messages))
			{
				await this.messageRepository.saveFromRest(messages);
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
		 * @private
		 * @param {SyncListResult} syncListResult
		 * @return Promise
		 */
		async updateModels(syncListResult)
		{
			const {
				messages,
				addedChats,
				addedRecent,
				completeDeletedMessages,
				addedPins,
				deletedPins,
			} = syncListResult;

			const {
				users,
				files,
				reactions,
			} = messages;

			const pinnedUsers = addedPins.users ?? [];
			const pinnedFiles = addedPins.files ?? [];

			const messagesToSave = messages.messages;

			const usersPromise = this.store.dispatch('usersModel/set', [...users, ...pinnedUsers]);

			// TODO: refactor when the dialogId will be in addedChats
			const addedRecentChatIds = {};
			addedRecent.forEach((recentItem) => {
				addedRecentChatIds[recentItem.chat_id] = recentItem.id;
			});

			const dialogs = addedChats.map((chat) => {
				const dialog = chat;
				const chatId = dialog.id;
				const dialogId = dialog.dialogId;
				if (chatId && !dialogId)
				{
					// eslint-disable-next-line no-param-reassign
					chat.dialogId = addedRecentChatIds[chatId];
				}

				return dialog;
			});

			const dialoguesPromise = this.store.dispatch('dialoguesModel/set', dialogs);
			const filesPromise = this.store.dispatch('filesModel/set', [...files, ...pinnedFiles]);
			const reactionPromise = this.store.dispatch('messagesModel/reactionsModel/set', {
				reactions,
			});

			const pinPromises = [
				this.store.dispatch('messagesModel/pinModel/deleteByIdList', {
					idList: Object.values(deletedPins ?? []),
				}),
				this.store.dispatch('messagesModel/pinModel/setList', {
					pins: addedPins.pins ?? [],
					messages: addedPins.additionalMessages ?? [],
				}),
			];

			await Promise.all([
				usersPromise,
				dialoguesPromise,
				filesPromise,
				reactionPromise,
			]);

			await Promise.all(pinPromises);

			const openChatIdList = this.getOpenChatsToAddMessages();
			if (!Type.isArrayFilled(openChatIdList))
			{
				return Promise.resolve();
			}

			const openChatsMessages = messagesToSave.filter((message) => {
				return openChatIdList.includes(message.chat_id);
			});

			const completeDeletedMessageIdList = Object.values(completeDeletedMessages);
			const messagesPromise = [
				this.store.dispatch('messagesModel/setChatCollection', {
					messages: openChatsMessages,
				}),
				this.store.dispatch('messagesModel/deleteByIdList', {
					idList: completeDeletedMessageIdList,
				}),
				this.store.dispatch('messagesModel/pinModel/deleteMessagesByIdList', {
					idList: completeDeletedMessageIdList,
				}),
			];

			return Promise.all(messagesPromise);
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
		 *
		 * @param {Array<RawChat>} addedChats
		 * @return {Array<number>}
		 */
		findCopilotChatIds(addedChats)
		{
			const result = [];
			for (const chat of addedChats)
			{
				if (chat.type === DialogType.copilot)
				{
					result.push(chat.id);
				}
			}

			return result;
		}

		/**
		 * @param {Array<RawMessage>} messages
		 * @param {Array<number>} copilotChatIds
		 * @return {Array<number>}
		 */
		findCopilotMessageIds(messages, copilotChatIds)
		{
			const result = [];

			for (const message of messages)
			{
				if (copilotChatIds.includes(message.chat_id))
				{
					result.push(message.id);
				}
			}

			return result;
		}
	}

	module.exports = {
		SyncFillerBase,
	};
});
