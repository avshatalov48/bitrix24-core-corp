/**
 * @module im/messenger/provider/service/classes/sync/fillers/sync-filler-chat
 */
jn.define('im/messenger/provider/service/classes/sync/fillers/sync-filler-chat', (require, exports, module) => {
	const { SyncFillerBase } = require('im/messenger/provider/service/classes/sync/fillers/sync-filler-base');
	const { EventType, ComponentCode, WaitingEntity } = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sync-service');

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
			return this.filterWithoutCopilot(result);
		}

		/**
		 * @override
		 * @param {object} data
		 * @param {string} data.uuid
		 * @param {SyncListResult} data.result
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

		/**
		 * @param {SyncListResult} syncListResult
		 * @return {SyncListResult}
		 */
		filterWithoutCopilot(syncListResult)
		{
			const copilotChatIds = this.findCopilotChatIds(syncListResult.addedChats);
			// const copilotMessageIds = this.findCopilotMessageIds(syncListResult.messages.messages, copilotChatIds);

			syncListResult.addedRecent = syncListResult.addedRecent.filter((recentItem) => {
				return !(copilotChatIds.includes(recentItem.chat_id));
			});

			syncListResult.addedChats = syncListResult.addedChats.filter((chat) => {
				return !(copilotChatIds.includes(chat.id));
			});

			syncListResult.messages.messages = syncListResult.messages.messages.filter((message) => {
				return !(copilotChatIds.includes(message.chat_id));
			});

			syncListResult.messages.files = syncListResult.messages.files.filter((file) => {
				return !(copilotChatIds.includes(file.chatId));
			});

			syncListResult.messages.users = syncListResult.messages.users.filter((user) => {
				if (!user.botData)
				{
					return true;
				}

				return user.botData.code !== 'copilot';
			});

			return syncListResult;
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
