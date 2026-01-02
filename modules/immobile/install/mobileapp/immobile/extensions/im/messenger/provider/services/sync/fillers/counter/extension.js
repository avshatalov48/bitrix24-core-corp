/**
 * @module im/messenger/provider/services/sync/fillers/counter
 */
jn.define('im/messenger/provider/services/sync/fillers/counter', (require, exports, module) => {
	const { SyncFillerBase } = require('im/messenger/provider/services/sync/fillers/base');
	const {
		WaitingEntity,
		EventType,
		ComponentCode,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { CounterHelper } = require('im/messenger/lib/helper');
	const { CounterStorageWriter } = require('im/messenger/lib/counters/counter-manager/storage/writer');

	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const logger = getLoggerWithContext('sync-service', 'SyncFillerCounter');

	/**
	 * @class SyncFillerCounter
	 */
	class SyncFillerCounter extends SyncFillerBase
	{
		/**
		 * @param {CounterStorageWriter|undefined} storageWriter
		 */
		constructor(storageWriter)
		{
			super();
			/** @type {CounterStorageWriter} */
			this.storageWriter = storageWriter ?? CounterStorageWriter.getInstance();
		}

		getUuidPrefix()
		{
			return WaitingEntity.sync.filler.counter;
		}

		/**
		 * @override
		 * @param {SyncListResult} result
		 * @return {SyncListResult}
		 */
		prepareResult(result)
		{
			return result;
		}

		/**
		 * @param {SyncRequestResultReceivedEvent} data
		 */
		async fillData(data)
		{
			logger.log('fillData', data);
			const {
				uuid,
				result,
			} = data;

			try
			{
				this.applyData(result);

				MessengerEmitter.emit(
					EventType.sync.requestResultSaved,
					{ uuid },
					ComponentCode.imMessenger,
				);
			}
			catch (error)
			{
				logger.error('fillData catch:', error);

				MessengerEmitter.emit(EventType.sync.requestResultSaved, {
					uuid,
					error: `fillData error: ${error.message}`,
				}, ComponentCode.imMessenger);
			}
		}

		/**
		 * @param {SyncListResult} result
		 */
		async fillDataWithoutEmit(result)
		{
			logger.log('fillDataWithoutEmit:', result);

			try
			{
				this.applyData(result);
			}
			catch (error)
			{
				logger.error('fillDataWithoutEmit catch: ', error);

				throw error;
			}
		}

		/**
		 * @param {SyncListResult} data
		 * @void
		 */
		applyData(data)
		{
			const counterCollectionToUpdate = this.#prepareCounterCollectionToUpdate(data);
			logger.log('counterCollectionToUpdate', counterCollectionToUpdate);

			const chatIdListToDelete = this.#prepareChatIdListToDelete(data);
			logger.log('chatIdListToDelete', chatIdListToDelete);

			void this.storageWriter.setCollection(counterCollectionToUpdate);
			void this.storageWriter.deleteFromCollection(chatIdListToDelete);
		}

		/**
		 * @param {SyncListResult} syncListResult
		 * @return {CounterStateCollection}
		 */
		#prepareCounterCollectionToUpdate(syncListResult)
		{
			/** @type {CounterStateCollection} */
			const counterCollection = {};

			const addedChatsIdSet = new Set(Object.values(syncListResult.chatSync.addedChats));
			const addedChats = syncListResult.chats.filter((chat) => {
				return addedChatsIdSet.has(chat.id);
			});

			addedChats.forEach((dialog) => {
				const chatId = dialog.id;
				counterCollection[chatId] = {
					chatId,
					counter: dialog.counter,
					parentChatId: dialog.parentChatId,
					type: CounterHelper.getCounterTypeByDialogType(dialog.type),
				};
			});

			return counterCollection;
		}

		/**
		 * @param {SyncListResult} syncListResult
		 * @return {Array<number>}
		 */
		#prepareChatIdListToDelete(syncListResult)
		{
			return [
				...Object.values(syncListResult.chatSync.deletedChats),
				...Object.values(syncListResult.chatSync.completeDeletedChats),
			];
		}
	}

	module.exports = { SyncFillerCounter };
});
