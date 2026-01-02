/**
 * @module app-rating-manager/src/tourist-storage-provider
 */
jn.define('app-rating-manager/src/tourist-storage-provider', (require, exports, module) => {
	const { Tourist } = require('tourist');
	const { EventStorageProvider } = require('app-rating-manager/src/storage-provider');

	/**
	 * @class TouristEventStorageProvider
	 */
	class TouristEventStorageProvider extends EventStorageProvider
	{
		/**
		 * @returns {Promise<void>}
		 */
		async init()
		{
			return Tourist.ready();
		}

		/**
		 * @param {string} event
		 * @param {object} options
		 * @returns {Promise<void>}
		 */
		async save(event, options = {})
		{
			await Tourist.remember(event, options);
		}

		/**
		 * @param {string} event
		 * @returns {Date|null}
		 */
		getLastTime(event)
		{
			return Tourist.lastTime(event) ?? null;
		}

		/**
		 * @param {string} event
		 * @returns {number}
		 */
		getNumberOfTimes(event)
		{
			return Tourist.numberOfTimes(event);
		}

		/**
		 * @param {string} event
		 * @returns {Object|null}
		 */
		getContext(event)
		{
			return Tourist.getContext(event);
		}
	}

	module.exports = {
		TouristEventStorageProvider,
	};
});
