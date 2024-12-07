/**
 * @module statemanager/redux/state-cache/memory-storage
 */
jn.define('statemanager/redux/state-cache/memory-storage', (require, exports, module) => {
	const { BaseStorage } = require('statemanager/redux/state-cache/base-storage');
	const { Logger, LogType } = require('utils/logger');

	const logger = new Logger([
		// LogType.INFO,
		LogType.ERROR,
	]);

	const STORE_KEY = 'cache_data';

	/**
	 * @class MemoryStorage
	 */
	class MemoryStorage extends BaseStorage
	{
		constructor()
		{
			super();

			const { MemoryStorage: MemoryStorageEngine } = require('native/memorystore');

			this.store = new MemoryStorageEngine('statemanager/redux/memory-state-cache');
		}

		load()
		{
			const cache = this.store.getSync(STORE_KEY) ?? {};

			logger.info('StateCache/MemoryStorage: load cache storage', cache);

			return cache;
		}

		async save(cache)
		{
			logger.info('StateCache/MemoryStorage: save state to storage', cache);

			await this.store.set(STORE_KEY, cache);
		}
	}

	module.exports = {
		MemoryStorage,
	};
});
