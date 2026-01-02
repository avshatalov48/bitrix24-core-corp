/**
 * @module app-rating-manager/src/test-storage-provider
 */
jn.define('app-rating-manager/src/test-storage-provider', (require, exports, module) => {
	const { EventStorageProvider } = require('app-rating-manager/src/storage-provider');

	/**
	 * @class TestEventStorageProvider
	 */
	class TestEventStorageProvider extends EventStorageProvider
	{
		/**
		 * @returns {Promise<void>}
		 */
		async init()
		{
			this.storage = new Map();

			return Promise.resolve({});
		}

		/**
		 * @param {string} event
		 * @param {object} [options = {}]
		 * @param {object} [options.context = null]
		 * @param {Date} [options.time = null]
		 * @param {?number} [options.count = null]
		 * @returns {Promise<void>}
		 */
		async save(event, { context = null, time = null, count = null } = {})
		{
			if (this.storage.has(event))
			{
				const data = this.storage.get(event);
				data.cnt = count ?? (data.cnt + 1);
				data.lastTime = time ?? new Date();
				data.context = context;

				this.storage.set(event, data);
			}
			else
			{
				this.storage.set(event, {
					cnt: count ?? 1,
					lastTime: time ?? new Date(),
					context,
				});
			}

			return Promise.resolve({});
		}

		/**
		 * @param {string} event
		 * @returns {Date|null}
		 */
		getLastTime(event)
		{
			return this.storage.get(event)?.lastTime ?? null;
		}

		/**
		 * @param {string} event
		 * @returns {number}
		 */
		getNumberOfTimes(event)
		{
			return this.storage.get(event)?.cnt ?? 0;
		}

		/**
		 * @param {string} event
		 * @returns {Object|null}
		 */
		getContext(event)
		{
			return this.storage.get(event)?.context ?? null;
		}

		clearAll()
		{
			this.storage = new Map();
		}
	}

	module.exports = {
		TestEventStorageProvider,
	};
});
