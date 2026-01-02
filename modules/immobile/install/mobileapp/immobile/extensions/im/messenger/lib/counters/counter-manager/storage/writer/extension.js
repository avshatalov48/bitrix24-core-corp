/**
 * @module im/messenger/lib/counters/counter-manager/storage/writer
 */
jn.define('im/messenger/lib/counters/counter-manager/storage/writer', (require, exports, module) => {
	const { MemoryStorage } = require('native/memorystore');

	const { Type } = require('type');
	const { AsyncQueue } = require('im/messenger/lib/utils');
	const { STORAGE_NAME, STATE_NAME } = require('im/messenger/lib/counters/counter-manager/const');
	const { CounterCollection } = require('im/messenger/lib/counters/counter-manager/storage/collection');
	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('counters--storage-writer');

	/**
	 * @deprecated
	 * @class CounterStorageWriter
	 */
	class CounterStorageWriter
	{
		static #instance;

		#storage = new MemoryStorage(STORAGE_NAME);
		#queue = new AsyncQueue();
		/** @type {CounterCollection} */
		#collection;

		/**
		 * @return {CounterStorageWriter}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new this();
			}

			return this.#instance;
		}

		get className()
		{
			return this.constructor.name;
		}

		/**
		 * @return {Promise<CounterCollection>}
		 */
		async getCollection()
		{
			if (!this.#collection)
			{
				const collection = await this.getStoredCollection();

				return new CounterCollection(collection);
			}

			return this.#collection;
		}

		/**
		 * @param chatId
		 * @return {?CounterState}
		 */
		async findById(chatId)
		{
			return (await this.getCollection()).findById(chatId);
		}

		/**
		 * @param {CounterState} counterState
		 */
		async set(counterState)
		{
			return this.setCollection({ [counterState.chatId]: counterState });
		}

		/**
		 * @param {Array<number>} chatIdList
		 * @return {Promise<void>}
		 */
		async deleteFromCollection(chatIdList)
		{
			if (!Type.isArrayFilled(chatIdList))
			{
				return;
			}

			void this.#queue.enqueue(
				async () => this.#deleteFromCollection(chatIdList),
				(error) => {
					logger.error(`${this.className}.deleteFromCollection error`, error);
				},
			);
		}

		async clearCounters()
		{
			void this.#queue.enqueue(async () => {
				const collection = await this.getCollection();

				/** @type {Array<CounterState>} */
				this.#collection = collection.map((counterState) => {
					return {
						...counterState,
						counter: 0,
					};
				});

				this.#saveCollection();
			});
		}

		async clearChannelCommentsCounter(channelChatId)
		{
			void this.#queue.enqueue(async () => {
				const collection = await this.getCollection();

				const clearedCommentCollection = collection
					.filter((counterState) => {
						return counterState.parentChatId === channelChatId;
					})
					.map((counterState) => {
						return {
							...counterState,
							counter: 0,
						};
					})
				;

				this.#collection = collection.getMergedCollection(clearedCommentCollection.getValue());

				this.#saveCollection();
			});
		}

		async decreaseCounter(chatId, deductibleCounter)
		{
			logger.log(`${this.className}.decreaseCounter`, chatId, deductibleCounter);
			const counterState = this.#collection.findById(chatId);
			if (!counterState)
			{
				return;
			}

			const newCounter = counterState.counter - deductibleCounter > 0
				? counterState.counter - deductibleCounter
				: 0
			;

			this.set({
				...counterState,
				counter: newCounter,
			});
		}

		async #deleteFromCollection(chatIdList)
		{
			logger.log(`${this.className}.deleteFromCollection`, chatIdList);
			const collection = await this.getCollection();
			this.#collection = collection.filterOutIds(chatIdList);

			await this.#saveCollection();
		}

		/**
		 * @param {CounterStateCollection} counterCollection
		 * @return {Promise<*>}
		 */
		async setCollection(counterCollection)
		{
			void this.#queue.enqueue(
				async () => this.#setCollection(counterCollection),
				(error) => {
					logger.error(`${this.className}.setCollection error`, error);
				},
			);
		}

		/**
		 * @protected
		 * @return {Promise<CounterStateCollection>}
		 */
		async getStoredCollection()
		{
			return await this.#storage.get(STATE_NAME) ?? {};
		}

		/**
		 * @param {CounterStateCollection} counterCollection
		 * @return {Promise<*>}
		 */
		async #setCollection(counterCollection)
		{
			logger.log(`${this.className}.setCollection`, counterCollection);
			const collection = await this.getCollection();

			this.#collection = collection.getMergedCollection(counterCollection);

			await this.#saveCollection();
		}

		#saveCollection()
		{
			logger.log(`${this.className}.saveCollection`, this.#collection);

			return this.#storage.set(STATE_NAME, this.#collection.getValue());
		}
	}

	module.exports = { CounterStorageWriter };
});
