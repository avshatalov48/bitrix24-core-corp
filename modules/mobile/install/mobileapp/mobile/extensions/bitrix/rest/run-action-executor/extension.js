/**
 * @module rest/run-action-executor
 */
jn.define('rest/run-action-executor', (require, exports, module) => {
	const { RunActionCache } = require('rest/run-action-executor/cache');

	/**
	 * @class RunActionExecutor
	 */
	class RunActionExecutor
	{
		constructor(action, options, navigation = {})
		{
			this.action = action;
			this.options = options || {};
			this.navigation = navigation || {};
			this.handler = null;
			/** @type {function} */
			this.onCacheFetched = null;
			/** @type {RunActionCache} */
			this.cache = null;
			/** @type {String|null} */
			this.cacheId = null;
			this.cacheTtl = 0;
			this.uid = null;
			this.jsonEnabled = false;
			this.skipRequestIfCacheExists = false;
		}

		enableJson()
		{
			this.jsonEnabled = true;

			return this;
		}

		/**
		 * @return {RunActionCache}
		 */
		getCache()
		{
			if (this.cache === null)
			{
				if (this.cacheId === null)
				{
					this.cacheId = this.getUid();
				}

				this.cache = new RunActionCache({
					id: this.cacheId,
					ttl: this.cacheTtl,
				});
			}

			return this.cache;
		}

		call(useCache = false)
		{
			this.abortCurrentRequest();

			if (useCache && this.onCacheFetched)
			{
				const cache = this.getCache().getData();
				if (cache)
				{
					this.onCacheFetched(cache, this.getUid());

					if (this.skipRequestIfCacheExists)
					{
						return Promise.resolve(cache);
					}
				}
			}

			const dataKey = this.jsonEnabled ? 'json' : 'data';

			return BX.ajax.runAction(this.action, {
				[dataKey]: this.options,
				navigation: this.navigation,
				onCreate: this.onRequestCreate.bind(this),
			})
				.then((response) => {
					if (!response.errors || response.errors.length === 0)
					{
						this.getCache().saveData(response);
					}

					this.internalHandler(response);

					return response;
				})
				.catch((response) => {
					this.internalHandler(response);

					return response;
				});
		}

		abortCurrentRequest()
		{
			if (this.currentAjaxObject)
			{
				this.currentAjaxObject.abort();
			}
		}

		onRequestCreate(ajax)
		{
			this.currentAjaxObject = ajax;
		}

		/**
		 * @param ajaxAnswer
		 * @private
		 */
		internalHandler(ajaxAnswer)
		{
			if (typeof this.handler === 'function')
			{
				this.handler(ajaxAnswer, this.getUid());
			}
		}

		/**
		 * @param {function<object>} func
		 * @returns {RunActionExecutor}
		 */
		setHandler(func)
		{
			this.handler = func;

			return this;
		}

		/**
		 * @param {function<object>} func
		 * @returns {RunActionExecutor}
		 */
		setCacheHandler(func)
		{
			this.onCacheFetched = func;

			return this;
		}

		/**
		 * @param {String} id
		 */
		setCacheId(id)
		{
			this.cacheId = id;

			return this;
		}

		/**
		 * @param {Number} seconds
		 */
		setCacheTtl(seconds)
		{
			this.cacheTtl = seconds;

			return this;
		}

		getUid()
		{
			if (this.uid === null)
			{
				this.uid = `${Object.toMD5(this.options)}/${Object.toMD5(this.navigation)}/${this.action}`;
			}

			return this.uid;
		}

		updateOptions(options = null)
		{
			if (options && typeof options === 'object')
			{
				this.options = Object.assign(this.options, options);
			}

			return this;
		}

		setSkipRequestIfCacheExists()
		{
			this.skipRequestIfCacheExists = true;

			return this;
		}
	}

	module.exports = { RunActionExecutor };
});
