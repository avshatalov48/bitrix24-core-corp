/**
 * @module statemanager/redux/state-cache/file-storage
 */
jn.define('statemanager/redux/state-cache/file-storage', (require, exports, module) => {
	const { BaseStorage } = require('statemanager/redux/state-cache/base-storage');
	const { Logger, LogType } = require('utils/logger');

	const logger = new Logger([
		// LogType.INFO,
		LogType.ERROR,
	]);

	/**
	 * @class FileStorage
	 */
	class FileStorage extends BaseStorage
	{
		load()
		{
			const cache = Application.storage.getObject('statemanager/redux/state-cache', {});

			logger.info('StateCache/FileStorage: load cache storage', cache);

			return cache;
		}

		async save(cache)
		{
			logger.info('StateCache/FileStorage: save state to storage', cache);

			await Application.storage.setObject('statemanager/redux/state-cache', cache);
		}
	}

	module.exports = {
		FileStorage,
	};
});
