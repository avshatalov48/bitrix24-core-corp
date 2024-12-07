/**
 * @module im/messenger/provider/service/classes/sync/fillers/sync-filler-channel
 */
jn.define('im/messenger/provider/service/classes/sync/fillers/sync-filler-channel', (require, exports, module) => {
	const { Type } = require('type');
	const {
		DialogType,
		EventType,
		ComponentCode,
		WaitingEntity,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { ChatDataProvider } = require('im/messenger/provider/data');

	const { SyncFillerBase } = require('im/messenger/provider/service/classes/sync/fillers/sync-filler-base');

	const logger = LoggerManager.getInstance().getLogger('sync-service');

	/**
	 * @class SyncFillerChannel
	 */
	class SyncFillerChannel extends SyncFillerBase
	{
		/**
		 * @override
		 * @param {SyncListResult} result
		 * @return {SyncListResult}
		 */
		prepareResult(result)
		{
			return this.filterWithOnlyOpenChannels(result);
		}

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

		/**
		 * @param {SyncListResult} syncListResult
		 * @return {SyncListResult}
		 */
		filterWithOnlyOpenChannels(syncListResult)
		{
			const openChannelsChatIds = this.findOpenChannelsChatIds(syncListResult.addedChats);

			syncListResult.addedRecent = []; // the channel recent should not be updated on synchronization

			syncListResult.addedChats = syncListResult.addedChats.filter((chat) => {
				return openChannelsChatIds.includes(chat.id);
			});

			syncListResult.messages.messages = syncListResult.messages.messages.filter((message) => {
				return openChannelsChatIds.includes(message.chat_id);
			});

			syncListResult.messages.files = syncListResult.messages.files.filter((file) => {
				return openChannelsChatIds.includes(file.chatId);
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

		/**
		 *
		 * @param {Array<RawChat>} addedChats
		 * @return {Array<number>}
		 */
		findOpenChannelsChatIds(addedChats)
		{
			const result = [];
			for (const chat of addedChats)
			{
				if (chat.type === DialogType.openChannel)
				{
					result.push(chat.id);
				}
			}

			return result;
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
