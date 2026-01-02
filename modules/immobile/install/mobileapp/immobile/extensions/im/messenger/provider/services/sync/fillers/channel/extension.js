/**
 * @module im/messenger/provider/services/sync/fillers/channel
 */
jn.define('im/messenger/provider/services/sync/fillers/channel', (require, exports, module) => {
	const { Type } = require('type');
	const {
		DialogType,
		EventType,
		ComponentCode,
		WaitingEntity,
		BotCode,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { ChatDataProvider } = require('im/messenger/provider/data');

	const { SyncFillerBase } = require('im/messenger/provider/services/sync/fillers/base');

	const logger = LoggerManager.getInstance().getLogger('sync-service');

	/**
	 * @class SyncFillerChannel
	 */
	class SyncFillerChannel extends SyncFillerBase
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

		/**
		 * @override
		 * @param {SyncListResult} result
		 * @return {SyncListResult}
		 */
		prepareResult(result)
		{
			const filteredResult = this.filterWithOnlyOpenChannels(result);
			logger.log(`${this.constructor.name}.prepareResult after filterWithOnlyOpenChannels:`, filteredResult);

			return this.filterUsers(filteredResult);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 */
		async processRecent(syncListResult)
		{
			// eslint-disable-next-line no-param-reassign
			syncListResult.recentItems = syncListResult.recentItems.map((recentItem) => {
				return {
					...recentItem,
					pinned: false,
				};
			});

			return super.processRecent(syncListResult);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 * @return {SyncListResult}
		 */
		filterWithOnlyOpenChannels(syncListResult)
		{
			const openChannelsChatIds = this.findOpenChannelsChatIds(syncListResult.chats);
			const openChatIdsSet = new Set(openChannelsChatIds);

			const filteredMessages = syncListResult.messages.filter(
				(message) => openChatIdsSet.has(message.chat_id),
			) || [];
			const messageIds = new Set(filteredMessages.map((message) => message.id));

			return {
				...syncListResult,
				messages: filteredMessages,
				dialogIds: Object.fromEntries(
					Object.entries(syncListResult.dialogIds || {})
						.filter(([chatId]) => openChatIdsSet.has(Number(chatId))),
				),
				recentItems: syncListResult.recentItems.filter((item) => openChatIdsSet.has(item.chatId)),
				chats: syncListResult.chats.filter((chat) => openChatIdsSet.has(chat.id)),
				reactions: syncListResult.reactions.filter((reaction) => messageIds.has(reaction.messageId)),
				files: syncListResult.files.filter((file) => openChatIdsSet.has(file.chatId)),
				pins: syncListResult.pins.filter((pin) => openChatIdsSet.has(pin.chatId)),
				users: syncListResult.users.filter((user) => user.botData?.code !== BotCode.copilot),
				messagesAutoDeleteConfigs: syncListResult.messagesAutoDeleteConfigs.filter((config) => {
					return openChatIdsSet.has(config.chatId);
				}),
			};
		}

		/**
		 *
		 * @param {Array<SyncRawChat>} chats
		 * @return {Array<number>}
		 */
		findOpenChannelsChatIds(chats)
		{
			const result = [];
			for (const chat of chats)
			{
				if (chat.type === DialogType.openChannel)
				{
					result.push(chat.id);
				}
			}

			return result;
		}

		/**
		 * @param {SyncRawMessage} message
		 * @return message
		 */
		fillMessageStatus(message)
		{
			return message;
		}

		getUuidPrefix()
		{
			return WaitingEntity.sync.filler.channel;
		}

		async processDeletedChats(source, deletedChats)
		{
			const chatIdList = Object.values(deletedChats);
			if (!Type.isArrayFilled(chatIdList))
			{
				return;
			}

			const chatProvider = new ChatDataProvider();
			for (const chatId of chatIdList)
			{
				const chatData = this.store.getters['dialoguesModel/getByChatId'](chatId);

				if (Type.isPlainObject(chatData))
				{
					const helper = DialogHelper.createByModel(chatData);
					if (helper?.isChannel)
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
								parentChatId: commentChatData.parentChatId,
								shouldShowAlert: false,
								shouldSendDeleteAnalytics: false,
							});
						}
					}
					this.closeDeletedChat({
						dialogId: chatData.dialogId,
						chatType: chatData.type,
					});
				}
			}
		}
	}

	module.exports = { SyncFillerChannel };
});
