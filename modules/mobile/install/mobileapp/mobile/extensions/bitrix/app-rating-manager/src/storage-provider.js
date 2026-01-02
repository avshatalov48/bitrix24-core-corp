/**
 * @module app-rating-manager/src/storage-provider
 */
jn.define('app-rating-manager/src/storage-provider', (require, exports, module) => {
	/**
	 * @interface StorageProvider
	 */
	class EventStorageProvider
	{
		/**
		 * @returns {Promise<void>}
		 */
		async init()
		{
			throw new Error('Method "init" must be implemented');
		}

		/**
		 * @param {string} event
		 * @param {Object} [options]
		 * @returns {Promise<void>}
		 */
		async save(event, options = {})
		{
			throw new Error('Method "remember" must be implemented');
		}

		/**
		 * @param {string} event
		 * @returns {number|null}
		 */
		getLastTime(event)
		{
			throw new Error('Method "getLastTime" must be implemented');
		}

		/**
		 * @param {string} event
		 * @returns {number}
		 */
		getNumberOfTimes(event)
		{
			throw new Error('Method "getNumberOfTimes" must be implemented');
		}

		/**
		 * @param {string} event
		 * @returns {Object|null}
		 */
		getContext(event)
		{
			throw new Error('Method "getContext" must be implemented');
		}
	}

	module.exports = {
		EventStorageProvider,
	};
});
