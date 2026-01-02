/**
 * @module im/messenger/lib/counters/counter-manager/storage/collection
 */
jn.define('im/messenger/lib/counters/counter-manager/storage/collection', (require, exports, module) => {
	const { Type } = require('type');
	const { mergeImmutable, clone } = require('utils/object');

	/**
	 * @class CounterCollection
	 */
	class CounterCollection
	{
		#collection;

		/**
		 * @param {Array<CounterState>} counterStateList
		 * @return {CounterCollection}
		 */
		static createFromArray(counterStateList)
		{
			const collection = {};

			counterStateList.forEach((counterState) => {
				collection[counterState.chatId] = counterState;
			});

			return new this(collection);
		}

		constructor(collection)
		{
			this.#collection = collection;
		}

		/**
		 * @return {boolean}
		 */
		isEmpty()
		{
			return !Type.isArrayFilled(this.getElements());
		}

		/**
		 * @return {CounterStateCollection}
		 */
		getValue()
		{
			return this.#collection;
		}

		/**
		 * @return {Array<CounterState>}
		 */
		getElements()
		{
			return Object.values(this.#collection);
		}

		/**
		 * @param {(counterState: CounterState) => CounterState} callback
		 * @return {CounterCollection}
		 */
		map(callback)
		{
			const changedElements = this.getElements()
				.map((element) => callback(element))
			;

			return CounterCollection.createFromArray(changedElements);
		}

		/**
		 * @param {(counterState: CounterState) => boolean} callback
		 * @return {CounterCollection}
		 */
		filter(callback)
		{
			const changedElements = this.getElements()
				.filter((element) => callback(element))
			;

			return CounterCollection.createFromArray(changedElements);
		}

		/**
		 * @param chatId
		 * @return {?CounterState}
		 */
		findById(chatId)
		{
			return this.#collection[chatId];
		}

		/**
		 * @param {CounterStateCollection} newCollection
		 * @return {CounterCollection}
		 */
		getMergedCollection(newCollection)
		{
			return new CounterCollection(mergeImmutable(this.#collection, newCollection));
		}

		/**
		 * @param {CounterStateCollection} newCollection
		 * @return {{ updatingCounters: Array<CounterState>, deletedCounters: Array<number>}}
		 */
		getChangedElements(newCollection)
		{
			return this.#getChangedElements(this.#collection, newCollection);
		}

		/**
		 * @param {Array<number>} chatIdList
		 * @return {CounterCollection}
		 */
		filterOutIds(chatIdList)
		{
			const result = clone(this.#collection);

			chatIdList.forEach((chatId) => {
				delete result[chatId];
			});

			return new CounterCollection(result);
		}

		/**
		 * @pure
		 * @param {CounterStateCollection} oldValue
		 * @param {CounterStateCollection} newValue
		 * @return {{ updatingCounters: Array<CounterState>, deletedCounters: Array<number>}}
		 */
		#getChangedElements(oldValue, newValue)
		{
			if (Type.isNull(oldValue)) // first set in memory storage
			{
				return {
					updatingCounters: Object.values(newValue),
					deletedCounters: [],
				};
			}
			const oldChatIdList = Object.keys(oldValue);
			const newChatIdList = Object.keys(newValue);
			const clonedNewValue = clone(newValue);

			const changedElementList = [];
			const deletedCounterChatIdList = [];

			// added new chat in storage
			if (oldChatIdList.length !== newChatIdList.length)
			{
				for (const chatId of newChatIdList)
				{
					if (Type.isNil(oldValue[chatId]))
					{
						changedElementList.push(clonedNewValue[chatId]);

						delete clonedNewValue[chatId];
					}
				}

				for (const chatId of oldChatIdList)
				{
					if (Type.isNil(newValue[chatId]))
					{
						deletedCounterChatIdList.push(chatId);
					}
				}
			}

			for (const counterState of Object.values(clonedNewValue))
			{
				if (oldValue[counterState.chatId]?.counter !== counterState.counter)
				{
					changedElementList.push(counterState);
				}
			}

			return {
				updatingCounters: changedElementList,
				deletedCounters: deletedCounterChatIdList,
			};
		}
	}

	module.exports = { CounterCollection };
});
