/**
 * @module statemanager/redux/state-cache
 */
jn.define('statemanager/redux/state-cache', (require, exports, module) => {
	const { debounce } = require('utils/function');
	const { isEqual } = require('utils/object');
	const { Logger, LogType } = require('utils/logger');
	const { MemoryStorage } = require('statemanager/redux/state-cache/memory-storage');

	const logger = new Logger([
		// LogType.INFO,
		LogType.ERROR,
	]);

	/**
	 * @class StateCache
	 */
	class StateCache
	{
		constructor()
		{
			this.storage = new MemoryStorage();

			this.cache = new Map();
			this.pendingSaves = new Set();

			this.debouncedSave = debounce(this.#save, 100, this);
		}

		/**
		 * @public
		 * @param {string} reducerName
		 * @param {*} defaultValue
		 * @return {*|null}
		 */
		getReducerState(reducerName, defaultValue = null)
		{
			return this.#loadReducerStateFromStorage(reducerName) ?? defaultValue;
		}

		/**
		 * @public
		 * @internal
		 * @param {Object} state
		 */
		setState(state)
		{
			for (const [reducerName, reducerState] of Object.entries(state))
			{
				const cachedReducerState = this.#loadReducerStateFromStorage(reducerName);

				if (isEqual(reducerState, cachedReducerState))
				{
					logger.info(
						'StateCache: states are equal, nothing to do',
						reducerName,
						reducerState,
						cachedReducerState,
					);
					continue;
				}

				logger.info('StateCache: queued new state for save', reducerName, reducerState);

				this.cache.set(reducerName, reducerState);
				this.pendingSaves.add(reducerName);

				this.debouncedSave();
			}
		}

		#loadReducerStateFromStorage(reducerName)
		{
			if (!this.cache.has(reducerName))
			{
				const reducerState = this.storage.load(reducerName);
				if (!reducerState)
				{
					return null;
				}

				this.cache.set(reducerName, reducerState);
			}

			return this.cache.get(reducerName);
		}

		async #save()
		{
			logger.info(
				'StateCache: save states',
				[...this.pendingSaves].map((reducerName) => [reducerName, this.cache.get(reducerName)]),
			);

			await Promise.all(
				[...this.pendingSaves].map(async (reducerName) => {
					await this.storage.save(reducerName, this.cache.get(reducerName));
					this.pendingSaves.delete(reducerName);
				}),
			);
		}
	}

	module.exports = {
		StateCache: new StateCache(),
	};
});
