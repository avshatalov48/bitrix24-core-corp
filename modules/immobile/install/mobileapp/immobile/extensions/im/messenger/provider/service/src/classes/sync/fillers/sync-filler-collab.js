/**
 * @module im/messenger/provider/service/classes/sync/fillers/sync-filler-collab
 */
jn.define('im/messenger/provider/service/classes/sync/fillers/sync-filler-collab', (require, exports, module) => {
	const { SyncFillerBase } = require('im/messenger/provider/service/classes/sync/fillers/sync-filler-base');
	const {
		EventType,
		ComponentCode,
		WaitingEntity,
		DialogType ,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sync-service');

	/**
	 * @class SyncFillerCollab
	 */
	class SyncFillerCollab extends SyncFillerBase
	{
		/**
		 * @override
		 * @param {object} data
		 * @param {string} data.uuid
		 * @param {SyncListResult} data.result
		 */
		async fillData(data)
		{
			logger.log(`${this.constructor.name}.fillData:`, data);
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
				logger.error(`${this.constructor.name}.fillData error: `, error);

				MessengerEmitter.emit(EventType.sync.requestResultSaved, {
					uuid,
					error: `${this.constructor.name}.fillData error: ${error.message}`,
				}, ComponentCode.imMessenger);
			}
		}

		getUuidPrefix()
		{
			return WaitingEntity.sync.filler.collab;
		}

		/**
		 * @override
		 * @param {SyncListResult} result
		 * @return {SyncListResult}
		 */
		prepareResult(result)
		{
			return this.filterOnlyCollab(result);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 * @return {SyncListResult}
		 */
		filterOnlyCollab(syncListResult)
		{
			const collabChatIds = this.findCollabChatIds(syncListResult.addedChats);

			syncListResult.addedRecent = syncListResult.addedRecent.filter((recentItem) => {
				return (collabChatIds.includes(recentItem.chat_id));
			});

			syncListResult.addedChats = syncListResult.addedChats.filter((chat) => {
				return (collabChatIds.includes(chat.id));
			});

			syncListResult.messages.messages = syncListResult.messages.messages.filter((message) => {
				return (collabChatIds.includes(message.chat_id));
			});

			syncListResult.messages.files = syncListResult.messages.files.filter((file) => {
				return (collabChatIds.includes(file.chatId));
			});

			return syncListResult;
		}

		/**
		 *
		 * @param {Array<RawChat>} addedChats
		 * @return {Array<number>}
		 */
		findCollabChatIds(addedChats)
		{
			const result = [];
			for (const chat of addedChats)
			{
				if (chat.type === DialogType.collab)
				{
					result.push(chat.id);
				}
			}

			return result;
		}
	}

	module.exports = {
		SyncFillerCollab,
	};
});
