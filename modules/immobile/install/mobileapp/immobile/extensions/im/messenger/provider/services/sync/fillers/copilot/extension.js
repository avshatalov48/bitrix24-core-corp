/**
 * @module im/messenger/provider/services/sync/fillers/copilot
 */
jn.define('im/messenger/provider/services/sync/fillers/copilot', (require, exports, module) => {
	const { SyncFillerBase } = require('im/messenger/provider/services/sync/fillers/base');
	const {
		EventType,
		ComponentCode,
		BotCode,
		WaitingEntity,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('sync-service');

	/**
	 * @class SyncFillerCopilot
	 */
	class SyncFillerCopilot extends SyncFillerBase
	{
		/**
		 * @override
		 * @param {SyncRequestResultReceivedEvent} data
		 */
		async fillData(data)
		{
			logger.log('SyncFillerCopilot.fillData:', data);
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
				logger.error('SyncFillerCopilot.fillData error: ', error);

				MessengerEmitter.emit(EventType.sync.requestResultSaved, {
					uuid,
					error: `SyncFillerCopilot.fillData error: ${error.message}`,
				}, ComponentCode.imMessenger);
			}
		}

		getUuidPrefix()
		{
			return WaitingEntity.sync.filler.copilot;
		}

		/**
		 * @override
		 * @param {SyncListResult} result
		 * @return {SyncListResult}
		 */
		prepareResult(result)
		{
			const filteredResult = this.filterOnlyCopilot(result);
			logger.log(`${this.constructor.name}.prepareResult after filterOnlyCopilot:`, filteredResult);

			return this.filterUsers(filteredResult);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 * @return {SyncListResult}
		 */
		filterOnlyCopilot(syncListResult) {
			if (!syncListResult.copilot)
			{
				return {
					...syncListResult,
					recentItems: [],
					chats: [],
					messages: [],
					dialogIds: [],
					files: [],
					pins: [],
					reactions: [],
					messagesAutoDeleteConfigs: [],
					users: syncListResult.users.filter((user) => user.botData?.code === BotCode.copilot),
				};
			}

			const copilotChatIds = this.findCopilotChatIds(syncListResult.chats);
			const copilotChatIdsSet = new Set(copilotChatIds);

			const filteredMessages = syncListResult.messages.filter(
				(message) => copilotChatIdsSet.has(message.chat_id),
			) || [];
			const messageIds = new Set(filteredMessages.map((message) => message.id));

			return {
				...syncListResult,
				messages: filteredMessages,
				dialogIds: Object.fromEntries(
					Object.entries(syncListResult.dialogIds || {})
						.filter(([chatId]) => copilotChatIdsSet.has(Number(chatId))),
				),
				recentItems: syncListResult.recentItems.filter((item) => copilotChatIdsSet.has(item.chatId)),
				chats: syncListResult.chats.filter((chat) => copilotChatIdsSet.has(chat.id)),
				files: syncListResult.files.filter((file) => copilotChatIdsSet.has(file.chatId)),
				pins: syncListResult.pins.filter((pin) => copilotChatIdsSet.has(pin.chatId)),
				reactions: syncListResult.reactions.filter((reaction) => messageIds.has(reaction.messageId)),
				users: syncListResult.users.filter((user) => user.botData?.code === BotCode.copilot),
				messagesAutoDeleteConfigs: syncListResult.messagesAutoDeleteConfigs.filter((config) => {
					return copilotChatIdsSet.has(config.chatId);
				}),
			};
		}
	}

	module.exports = {
		SyncFillerCopilot,
	};
});
