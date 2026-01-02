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

		load(key)
		{
			const cache = this.store.getSync(key) ?? null;

			logger.info('StateCache/MemoryStorage: load cache from storage', key, cache);

			return cache;
		}

		async save(key, cache)
		{
			logger.info('StateCache/MemoryStorage: save cache to storage', key, cache);

			await this.store.set(key, cache);
		}
	}

	module.exports = {
		MemoryStorage,
	};
});
