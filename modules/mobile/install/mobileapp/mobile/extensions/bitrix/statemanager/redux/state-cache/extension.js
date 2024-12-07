/**
 * @module statemanager/redux/state-cache
 */
jn.define('statemanager/redux/state-cache', (require, exports, module) => {
	const { Feature } = require('feature');
	const { debounce } = require('utils/function');
	const { isEqual } = require('utils/object');
	const { Logger, LogType } = require('utils/logger');
	const { FileStorage } = require('statemanager/redux/state-cache/file-storage');
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
			this.emitChange = null;

			this.storage = Feature.isMemoryStorageSupported() ? new MemoryStorage() : new FileStorage();
			this.cache = this.storage.load();

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
			return this.cache[reducerName] ?? defaultValue;
		}

		/**
		 * @public
		 * @internal
		 * @param {Object} state
		 */
		setState(state)
		{
			if (this.#isEqualToGivenState(state))
			{
				logger.info('StateCache: state is equal, nothing to do', this.cache, state);

				return;
			}

			for (const [reducerName, reducerState] of Object.entries(state))
			{
				this.cache[reducerName] = reducerState;
			}

			logger.info('StateCache: queued new state for save', this.cache);

			this.debouncedSave();
		}

		#isEqualToGivenState(state)
		{
			for (const [reducerName, reducerState] of Object.entries(state))
			{
				if (!isEqual(this.cache[reducerName], reducerState))
				{
					return false;
				}
			}

			return true;
		}

		async #save()
		{
			logger.info('StateCache: save state to storage', this.cache);

			await this.storage.save(this.cache);
		}
	}

	module.exports = {
		StateCache: new StateCache(),
	};
});
