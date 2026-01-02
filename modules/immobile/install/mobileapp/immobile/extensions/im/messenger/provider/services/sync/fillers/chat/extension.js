/**
 * @module im/messenger/provider/services/sync/fillers/chat
 */
jn.define('im/messenger/provider/services/sync/fillers/chat', (require, exports, module) => {
	const { SyncFillerBase } = require('im/messenger/provider/services/sync/fillers/base');
	const {
		EventType,
		ComponentCode,
		WaitingEntity,
		DialogType,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('sync-service');

	/**
	 * @class SyncFillerChat
	 */
	class SyncFillerChat extends SyncFillerBase
	{
		subscribeEvents()
		{
			this.emitter.on(EventType.sync.requestResultReceived, this.onSyncRequestResultReceive);
		}

		/**
		 * @override
		 * @param {SyncListResult} result
		 * @return {SyncListResult}
		 */
		prepareResult(result)
		{
			const filteringChatId = [];
			Object.values(result.chatSync.addedRecent).forEach((chatId) => {
				const chatData = result.chats.find((chat) => chat.id === chatId);
				if (chatData?.type === DialogType.tasksTask)
				{
					filteringChatId.push(chatId);
				}
			});

			for (const chatId of filteringChatId)
			{
				delete result.chatSync.addedRecent[chatId];
				result.recentItems = result.recentItems.filter((recentItem) => recentItem.chatId !== chatId);
			}

			return result;
		}

		/**
		 * @override
		 * @param {SyncRequestResultReceivedEvent} data
		 */
		async fillData(data)
		{
			logger.log('SyncFillerChat.fillData:', data);
			const {
				uuid,
				result,
			} = data;

			try
			{
				await this.updateModels(this.prepareResult(result));

				MessengerEmitter.emit(EventType.sync.requestResultSaved, {
					uuid,
				}, ComponentCode.imMessenger);
			}
			catch (error)
			{
				logger.error('SyncFillerChat.fillData error: ', error);

				MessengerEmitter.emit(EventType.sync.requestResultSaved, {
					uuid,
					error: `SyncFillerChat.fillData error: ${error.message}`,
				}, ComponentCode.imMessenger);
			}
		}

		getUuidPrefix()
		{
			return WaitingEntity.sync.filler.chat;
		}
	}

	module.exports = {
		SyncFillerChat,
	};
});
