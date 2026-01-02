/**
 * @module im/messenger/provider/services/sync/fillers/collab
 */
jn.define('im/messenger/provider/services/sync/fillers/collab', (require, exports, module) => {
	const { SyncFillerBase } = require('im/messenger/provider/services/sync/fillers/base');
	const {
		EventType,
		ComponentCode,
		WaitingEntity,
		DialogType,
		BotCode,
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
		 * @param {SyncRequestResultReceivedEvent} data
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
			const filteredResult = this.filterOnlyCollab(result);
			logger.log(`${this.constructor.name}.prepareResult after filterOnlyCollab:`, filteredResult);

			return this.filterUsers(filteredResult);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 * @return {SyncListResult}
		 */
		filterOnlyCollab(syncListResult)
		{
			const collabChatIds = this.findCollabChatIds(syncListResult.chats);
			const collabChatIdsSet = new Set(collabChatIds);

			const filteredMessages = syncListResult.messages.filter(
				(message) => collabChatIdsSet.has(message.chat_id),
			) || [];
			const messageIds = new Set(filteredMessages.map((message) => message.id));
			const dialogIds = Object.fromEntries(
				Object.entries(syncListResult.dialogIds || {})
					.filter(([chatId]) => collabChatIdsSet.has(Number(chatId))),
			);

			return {
				...syncListResult,
				messages: filteredMessages,
				dialogIds,
				recentItems: syncListResult.recentItems.filter((item) => collabChatIdsSet.has(item.chatId)),
				chats: syncListResult.chats.filter((chat) => collabChatIdsSet.has(chat.id)),
				reactions: syncListResult.reactions.filter((reaction) => messageIds.has(reaction.messageId)),
				files: syncListResult.files.filter((file) => collabChatIdsSet.has(file.chatId)),
				pins: syncListResult.pins.filter((pin) => collabChatIdsSet.has(pin.chatId)),
				users: syncListResult.users.filter((user) => user.botData?.code !== BotCode.copilot),
				messagesAutoDeleteConfigs: syncListResult.messagesAutoDeleteConfigs.filter((config) => {
					return collabChatIdsSet.has(config.chatId);
				}),
			};
		}

		/**
		 *
		 * @param {Array<SyncRawChat>} chats
		 * @return {Array<number>}
		 */
		findCollabChatIds(chats)
		{
			const result = [];
			for (const chat of chats)
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
