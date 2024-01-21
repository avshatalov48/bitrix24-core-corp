/**
 * @module rest/run-action-executor/cache
 */
jn.define('rest/run-action-executor/cache', (require, exports, module) => {
	const { debounce } = require('utils/function');

	/**
	 * @class RunActionCache
	 */
	class RunActionCache
	{
		constructor(options)
		{
			/**
			 * @type {String|null}
			 * @private
			 * @private
			 */
			this.id = options.id ?? '';
			/**
			 * @type {Number}
			 * @private
			 */
			this.ttl = options.ttl ?? 0;

			this.saveDataDebounced = debounce(this.setCacheToStorage, 100, this, true);
		}

		/**
		 * @public
		 * @returns {string|null}
		 */
		getName()
		{
			if (!this.isEnabled())
			{
				return null;
			}

			if (this.ttl >= 0)
			{
				return `${this.id}/${this.ttl}`;
			}

			return this.id;
		}

		isEnabled()
		{
			return this.id !== '';
		}

		/**
		 * @public
		 */
		getData()
		{
			if (!this.isEnabled())
			{
				return null;
			}

			if (this.isExpired())
			{
				return null;
			}

			const cache = this.getCacheFromStorage();

			return this.prepareCacheToReturn(cache);
		}

		prepareCacheToReturn(cache)
		{
			if (!cache)
			{
				return null;
			}

			if (this.ttl > 0)
			{
				return cache.response;
			}

			return cache;
		}

		isExpired()
		{
			const cache = this.getCacheFromStorage();
			if (!cache)
			{
				return true;
			}

			if (this.ttl <= 0)
			{
				return false;
			}

			if (!cache.saveTimestamp)
			{
				return true;
			}

			return cache.saveTimestamp + this.ttl * 1000 < Date.now();
		}

		/**
		 * @private
		 * @returns {Object}
		 */
		getCacheFromStorage()
		{
			if (!this.isEnabled())
			{
				return null;
			}

			return Application.storage.getObject(this.getName(), null);
		}

		/**
		 * @public
		 * @param {Object} cache
		 */
		saveData(cache)
		{
			if (!this.isEnabled())
			{
				return;
			}

			const preparedCache = this.prepareCacheToSave(cache);

			this.saveDataDebounced(preparedCache);
		}

		/**
		 * @private
		 * @param {Object} response
		 * @returns {{response, ttl: number}|*}
		 */
		prepareCacheToSave(response)
		{
			if (this.ttl > 0)
			{
				return {
					saveTimestamp: Date.now(),
					response,
				};
			}

			return response;
		}

		/**
		 * @private
		 * @param {Object} cache
		 */
		setCacheToStorage(cache)
		{
			if (!this.isEnabled())
			{
				return;
			}

			Application.storage.setObject(this.getName(), cache);
		}
	}

	module.exports = { RunActionCache };
});
