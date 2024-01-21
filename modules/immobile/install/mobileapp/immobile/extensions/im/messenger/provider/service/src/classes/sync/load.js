/**
 * @module im/messenger/provider/service/classes/sync/load
 */
jn.define('im/messenger/provider/service/classes/sync/load', (require, exports, module) => {
	const { Type } = require('type');

	const { core } = require('im/messenger/core');
	const { RestMethod } = require('im/messenger/const');
	const { UserManager } = require('im/messenger/lib/user-manager');
	const { runAction } = require('im/messenger/lib/rest');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sync-service');

	/**
	 * @class LoadService
	 */
	class LoadService
	{
		/**
		 * @return {LoadService}
		 */
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new this();
			}

			return this.instance;
		}

		constructor()
		{
			this.store = core.getStore();

			this.userManager = new UserManager(this.store);

			this.dialogRepository = core.getRepository().dialog;
			this.userRepository = core.getRepository().user;
			this.fileRepository = core.getRepository().file;
			this.reactionRepository = core.getRepository().reaction;
			this.messageRepository = core.getRepository().message;
		}

		async loadPage({ fromDate, fromId })
		{
			const syncListOptions = {
				filter: {},
				limit: 500,
			};

			if (Type.isNumber(fromId))
			{
				syncListOptions.filter.lastId = fromId;
			}
			else
			{
				syncListOptions.filter.lastDate = fromDate;
			}

			try
			{
				const result = await runAction(RestMethod.imV2SyncList, { data: syncListOptions });

				return await this.handleSyncList(result);
			}
			catch (error)
			{
				logger.error('LoadService.loadPage error: ', error);

				return Promise.resolve({
					hasMore: true,
				});
			}
		}

		/**
		 * @param {SyncListResult} result
		 * @return {Promise<{hasMore: boolean, lastId: number}>}
		 */
		async handleSyncList(result)
		{
			logger.info('RestMethod.imV2SyncList result: ', result);

			try
			{
				await this.updateDatabase(result);
				await this.updateModels(result);
			}
			catch (error)
			{
				logger.error('LoadService.handleSyncList error: ', error);
			}

			return {
				hasMore: result.hasMore,
				lastId: result.lastId,
			};
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
				addedChats,
				addedRecent,
				completeDeletedMessages,
				deletedChats,
			} = syncListResult;

			const {
				users,
				files,
				reactions,
			} = messages;

			const messagesToSave = messages.messages;

			if (Type.isArrayFilled(users))
			{
				await this.userRepository.saveFromRest(users);
			}

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

			if (Type.isArrayFilled(files))
			{
				await this.fileRepository.saveFromRest(files);
			}

			if (Type.isArrayFilled(reactions))
			{
				await this.reactionRepository.saveFromRest(reactions);
			}

			if (Type.isArrayFilled(messagesToSave))
			{
				await this.messageRepository.saveFromRest(messagesToSave);
			}

			const deletedChatsIdList = Object.values(deletedChats);
			if (Type.isArrayFilled(deletedChatsIdList))
			{
				await this.dialogRepository.deleteByChatIdList(deletedChatsIdList);
				await this.messageRepository.deleteByChatIdList(deletedChatsIdList);
			}

			const completeDeletedMessageIdList = Object.values(completeDeletedMessages);
			if (Type.isArrayFilled(completeDeletedMessageIdList))
			{
				await this.messageRepository.deleteByIdList(completeDeletedMessageIdList);
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
			} = syncListResult;

			const {
				users,
				files,
				reactions,
			} = messages;

			const messagesToSave = messages.messages;

			const usersPromise = this.store.dispatch('usersModel/set', users);

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
			const filesPromise = this.store.dispatch('filesModel/set', files);
			const reactionPromise = this.store.dispatch('messagesModel/reactionsModel/set', {
				reactions,
			});

			await Promise.all([
				usersPromise,
				dialoguesPromise,
				filesPromise,
				reactionPromise,
			]);

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
	}

	module.exports = {
		LoadService,
	};
});
