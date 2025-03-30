/**
 * @module im/messenger/provider/service/classes/sync/fillers/sync-filler-base
 */
jn.define('im/messenger/provider/service/classes/sync/fillers/sync-filler-base', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogType, EventType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { UserManager } = require('im/messenger/lib/user-manager');
	const { MessageContextCreator } = require('im/messenger/provider/service/classes/message-context-creator');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { ChatDataProvider, RecentDataProvider } = require('im/messenger/provider/data');

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
			this.recentRepository = this.core.getRepository().recent;

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
		 * @param {object} event
		 * @param {string} event.uuid
		 * @param {SyncListResult} event.result
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
		 * @abstract
		 * @param {object} data
		 * @param {string} data.uuid
		 * @param {SyncListResult} data.result
		 */
		async fillData(data)
		{
			throw new Error('SyncFillerBase.fillData must be override in subclass');
		}

		/**
		 * @abstract
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
			return result;
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
				deletedChats,
				completeDeletedChats,
				dialogIds,
			} = syncListResult;

			const {
				users,
				files,
				reactions,
			} = messages;
			const messagesToSave = messages.messages;

			const pinnedUsers = addedPins.users ?? [];
			const recentUsers = addedRecent.map((recentItem) => recentItem.user) ?? [];
			const pinnedFiles = addedPins.files ?? [];

			const filteredUsers = [...users, ...pinnedUsers, ...recentUsers].filter((user) => user.id !== 0);
			const usersUniqueCollection = [...new Map(filteredUsers.map((user) => [user.id, user])).values()];
			const usersPromise = this.store.dispatch('usersModel/set', usersUniqueCollection);

			const addedChatsWithDialogIds = addedChats.map((chat) => {
				const dialog = chat;
				const chatId = dialog.id;
				const dialogId = dialog.dialogId;
				if (chatId && !dialogId)
				{
					// eslint-disable-next-line no-param-reassign
					chat.dialogId = dialogIds[chatId];
				}

				return dialog;
			});

			const dialoguesPromise = this.store.dispatch('dialoguesModel/set', addedChatsWithDialogIds);
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

			await this.store.dispatch('recentModel/update', addedRecent);

			const openChatIdList = this.getOpenChatsToAddMessages();
			if (Type.isArrayFilled(openChatIdList))
			{
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

				await Promise.all(messagesPromise);
			}

			this.closeDeletedCommentsChats(completeDeletedMessages);
			await this.processDeletedChats(ChatDataProvider.source.model, deletedChats);
			await this.processCompletelyDeletedChats(ChatDataProvider.source.model, completeDeletedChats);
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

		/**
		 * @param {ChatDataProvider['source']} source
		 * @param {SyncListResult['deletedChats']} deletedChats
		 * @returns {Promise<void>}
		 */
		async processDeletedChats(source, deletedChats)
		{
			await this.processCompletelyDeletedChats(source, deletedChats);
		}

		/**
		 * @param {ChatDataProvider['source']} source
		 * @param {SyncListResult['completeDeletedChats']} completeDeletedChats
		 * @returns {Promise<void>}
		 */
		async processCompletelyDeletedChats(source, completeDeletedChats)
		{
			const chatIdList = Object.values(completeDeletedChats);
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
					if (helper.isChannel)
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
		 * @param {SyncListResult['completeDeletedMessages']} completeDeletedMessages
		 */
		closeDeletedCommentsChats(completeDeletedMessages)
		{
			for (const messageId of Object.values(completeDeletedMessages))
			{
				const commentInfo = this.store.getters['commentModel/getByMessageId'](messageId);
				if (!commentInfo)
				{
					continue;
				}
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
	}

	module.exports = {
		SyncFillerBase,
	};
});
