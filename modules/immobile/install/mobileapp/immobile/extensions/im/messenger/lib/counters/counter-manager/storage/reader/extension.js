/**
 * @module im/messenger/lib/counters/counter-manager/storage/reader
 */
jn.define('im/messenger/lib/counters/counter-manager/storage/reader', (require, exports, module) => {
	const { MemoryStorage } = require('native/memorystore');

	const { Type } = require('type');
	const { CounterCollection } = require('im/messenger/lib/counters/counter-manager/storage/collection');

	const { STORAGE_NAME, STATE_NAME } = require('im/messenger/lib/counters/counter-manager/const');

	/**
	 * @class CounterStorageReader
	 */
	class CounterStorageReader
	{
		#storage = new MemoryStorage(STORAGE_NAME);
		#onChangeHandlerList = [];

		constructor()
		{
			this.#storage.on('changed', this.#onChange);
		}

		/**
		 * @param {(counterStateList: Array<CounterState>) => void | Promise} callback
		 */
		subscribeOnStorageChange(callback)
		{
			this.#onChangeHandlerList.push(callback);
		}

		#onChange = (...args) => {
			const { key, oldValue, newValue } = this.#getArgumentsFromStorage(args);
			if (key !== STATE_NAME)
			{
				return;
			}

			if (!Type.isArrayFilled(this.#onChangeHandlerList))
			{
				return;
			}

			const collection = new CounterCollection(oldValue);

			const changeStoreResult = collection.getChangedElements(newValue);

			for (const handler of this.#onChangeHandlerList)
			{
				const result = handler(changeStoreResult);

				if (result instanceof Promise)
				{
					result.catch((error) => {
						console.error(error);
					});
				}
			}
		};

		/**
		 * @param {Array} args
		 * @return {{key: string, oldValue: any, newValue: any}}
		 */
		#getArgumentsFromStorage(args)
		{
			if (args.length === 3)
			{
				return {
					key: args[0],
					oldValue: args[1],
					newValue: args[2],
				};
			}

			return args[0];
		}
	}

	module.exports = { CounterStorageReader };
});
