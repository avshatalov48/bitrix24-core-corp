/**
 * @module im/messenger-v2/provider/services/sync/fillers/store
 */
jn.define('im/messenger-v2/provider/services/sync/fillers/store', (require, exports, module) => {
	const { Type } = require('type');

	const { DialogType, RecentTab } = require('im/messenger/const');
	const { DialogHelper, CounterHelper } = require('im/messenger/lib/helper');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { ChatDataProvider, RecentDataProvider } = require('im/messenger/provider/data');
	const { SyncFillerBase } = require('im/messenger/provider/services/sync/fillers/base');

	const logger = getLoggerWithContext('sync-service', 'SyncFillerStore');

	/**
	 * @class SyncFillerStore
	 */
	class SyncFillerStore extends SyncFillerBase
	{
		/**
		 * @param {SyncListResult} result
		 */
		async fillDataWithoutEmit(result)
		{
			logger.log('fillDataWithoutEmit:', result);

			try
			{
				await this.updateModels(this.prepareResult(result));
			}
			catch (error)
			{
				logger.error('fillDataWithoutEmit catch:', error);

				throw error;
			}
		}

		async updateModels(syncListResult)
		{
			await super.updateModels(syncListResult);
			await this.processCounters(syncListResult);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 */
		async processRecent(syncListResult)
		{
			const { chatSync, recentItems, messages, chats } = syncListResult;

			const processedRecentItems = this.prepareAddedRecent(recentItems, chatSync.addedRecent, messages);
			if (processedRecentItems.length > 0)
			{
				await this.setRecentItems(processedRecentItems, chats);
			}
		}

		/**
		 * @param {Array<SyncRawRecentItem>} items
		 * @param {Array<SyncRawChat>} chats
		 * @returns {Promise<void>}
		 */
		async setRecentItems(items, chats)
		{
			const chatTypeById = new Map();
			for (const chat of chats)
			{
				chatTypeById.set(String(chat.id), chat.type);
			}

			const groups = {
				[RecentTab.chat]: [],
				[RecentTab.copilot]: [],
				[RecentTab.collab]: [],
				[RecentTab.tasksTask]: [],
			};

			for (const item of items)
			{
				const chatType = chatTypeById.get(String(item.chatId));
				if (chatType === DialogType.copilot)
				{
					groups[RecentTab.copilot].push(item);
				}

				if (chatType === DialogType.collab)
				{
					groups[RecentTab.collab].push(item);
				}

				if (chatType === DialogType.tasksTask)
				{
					groups[RecentTab.tasksTask].push(item);

					continue;
				}

				groups[RecentTab.chat].push(item);
			}

			await this.store.dispatch('recentModel/setGroupCollection', {
				groups,
			});
		}

		/**
		 * @param {'model' | 'database'} source
		 * @param {Record<string, number> | []} completeDeletedChatsIds
		 */
		async processCompletelyDeletedChats(source, completeDeletedChatsIds)
		{
			const chatIdList = Object.values(completeDeletedChatsIds);
			if (!Type.isArrayFilled(chatIdList))
			{
				return;
			}
			logger.log('processCompletelyDeletedChats', chatIdList);

			const chatProvider = new ChatDataProvider();
			const recentProvider = new RecentDataProvider();

			await Promise.all(chatIdList.map(async (chatId) => {
				const chatDataProvider = await chatProvider.get({ chatId });
				if (chatDataProvider.hasData())
				{
					const chatData = chatDataProvider.getData();
					const dialogHelper = DialogHelper.createByModel(chatData);
					if (dialogHelper?.isChannel)
					{
						const commentChatData = this.store.getters['dialoguesModel/getByParentChatId'](chatData.chatId);

						if (
							Type.isPlainObject(commentChatData)
							&& this.store.getters['applicationModel/isDialogOpen'](commentChatData.dialogId)
						)
						{
							await chatProvider.delete({ dialogId: commentChatData.dialogId });
							this.closeDeletedChat({
								dialogId: commentChatData.dialogId,
								chatType: commentChatData.type,
								parentChatId: commentChatData.parentChatId,
								shouldShowAlert: false,
								shouldSendDeleteAnalytics: false,
							});
						}
						await this.store.dispatch('recentModel/deleteOpenChannel', { id: dialogHelper.dialogId });
						await chatProvider.deleteFromSource(source, { chatId });
					}
					else
					{
						await recentProvider.deleteFromSource(source, { chatId });
						await chatProvider.deleteFromSource(source, { chatId });
					}

					this.closeDeletedChat({
						dialogId: chatData.dialogId,
						chatType: chatData.type,
					});
				}
			}));
		}

		/**
		 * @param {SyncListResult} syncListResult
		 */
		async processCounters(syncListResult)
		{
			logger.log('processCounters', syncListResult);
			const { chatSync, chats } = syncListResult;

			const deletedChats = [
				...Object.values(chatSync.deletedChats),
				...Object.values(chatSync.completeDeletedChats),
			];

			const counterStateList = Object.values(chats).map((chat) => {
				return {
					chatId: chat.id,
					counter: chat.counter,
					type: CounterHelper.getCounterTypeByDialogType(chat.type),
					parentChatId: chat.parentChatId,
					disabled: CounterHelper.getDisabledByMuteList(chat.muteList),
				};
			});

			await this.store.dispatch('counterModel/setList', {
				counterList: counterStateList,
			});

			await this.store.dispatch('counterModel/delete', {
				chatIdList: deletedChats,
			});
		}
	}

	module.exports = {
		SyncFillerStore,
	};
});
