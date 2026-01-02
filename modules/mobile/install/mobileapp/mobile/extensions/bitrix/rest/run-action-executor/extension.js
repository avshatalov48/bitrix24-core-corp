/**
 * @module rest/run-action-executor
 */
jn.define('rest/run-action-executor', (require, exports, module) => {
	const { RunActionCache } = require('rest/run-action-executor/cache');
	const { RequestManager } = require('rest/run-action-executor/src/request-manager');
	const { Logger, LogType } = require('utils/logger');
	const { toMD5 } = require('utils/object');

	const logger = new Logger([
		LogType.INFO,
		LogType.ERROR,
		LogType.WARN,
	]);

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
			this.timeout = null;
			this.onTimeout = null;
			this.onNetworkError = null;
			this.skipDuplicateRequests = false;
			this.logger = logger;
			this.requestManager = new RequestManager(action, options, logger, navigation);
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

		async call(useCache = false)
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

			if (this.skipDuplicateRequests)
			{
				const sameOngoingRequest = this.requestManager.getSameOngoingRequest();
				if (sameOngoingRequest)
				{
					return this.requestManager.waitOngoingRequestResult(
						this.#executeRequest.bind(this),
						this.#internalHandler.bind(this),
					);
				}

				this.requestManager.startRequest();
			}

			return this.#executeRequest();
		}

		async #executeRequest()
		{
			const dataKey = this.jsonEnabled ? 'json' : 'data';
			const timeout = this.#setupTimeout();

			this.logger.info(`Start request: ${this.action}`);

			try
			{
				const response = await BX.ajax.runAction(this.action, {
					[dataKey]: this.options,
					navigation: this.navigation,
					onCreate: this.onRequestCreate.bind(this),
				});

				this.#handleSuccessResponse({
					timeoutId: timeout.timeoutId,
					response,
				});

				this.logger.info(`Success response received from ${this.action}`, response);

				return response;
			}
			catch (response)
			{
				this.#handleErrorResponse({
					timeoutId: timeout.timeoutId,
					isTimeoutActionTriggered: timeout.isTimeoutActionTriggered,
					response,
				});

				this.logger.error(`Failure response received from ${this.action}`, response);

				return response;
			}
		}

		#handleSuccessResponse(handleParams)
		{
			const { timeoutId, response } = handleParams;

			if (timeoutId)
			{
				clearTimeout(timeoutId);
			}

			if (!response.errors || response.errors.length === 0)
			{
				this.getCache().saveData(response);
			}

			this.#invokeInternalHandler({
				response,
			});
		}

		#handleErrorResponse(handleParams)
		{
			const { timeoutId, isTimeoutActionTriggered, response } = handleParams;

			if (timeoutId)
			{
				clearTimeout(timeoutId);
			}

			if (
				response.errors.some((error) => error.code === 'NETWORK_ERROR')
				&& !isTimeoutActionTriggered
				&& this.onNetworkError
			)
			{
				this.onNetworkError();
			}

			this.#invokeInternalHandler({
				response,
				isPromiseRejected: true,
			});
		}

		#invokeInternalHandler(handleParams)
		{
			const { response, isPromiseRejected = false } = handleParams;

			this.#internalHandler(response);

			if (this.skipDuplicateRequests)
			{
				this.requestManager.setRequestStatus(
					isPromiseRejected ? RequestManager.PROMISE_STATUS.REJECTED : RequestManager.PROMISE_STATUS.FULFILLED,
					response,
				);
			}
		}

		#setupTimeout()
		{
			if (!this.timeout)
			{
				return {
					timeoutId: null,
					isTimeoutActionTriggered: false,
				};
			}

			let isTimeoutActionTriggered = false;
			const timeoutId = setTimeout(() => {
				this.abortCurrentRequest();
				this.onTimeout?.();
				isTimeoutActionTriggered = true;
			}, this.timeout);

			return {
				timeoutId,
				isTimeoutActionTriggered,
			};
		}

		/**
		 * @public
		 * @param {Number} timeout
		 * @param {Function} onTimeout
		 * @returns {RunActionExecutor}
		 */
		setTimeoutHandler(timeout, onTimeout)
		{
			this.timeout = timeout;
			this.onTimeout = onTimeout;

			return this;
		}

		setNetworkErrorHandler(onNetworkError)
		{
			this.onNetworkError = onNetworkError;

			return this;
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

		#internalHandler(ajaxAnswer)
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
				this.uid = `${toMD5(this.options)}/${toMD5(this.navigation)}/${this.action}`;
			}

			return this.uid;
		}

		updateOptions(options = null)
		{
			if (options && typeof options === 'object')
			{
				this.options = Object.assign(this.options, options);
				this.requestManager.updateOptions(this.options);
			}

			return this;
		}

		setSkipRequestIfCacheExists()
		{
			this.skipRequestIfCacheExists = true;

			return this;
		}

		setSkipDuplicateRequests()
		{
			if (Application.getApiVersion() < 61)
			{
				this.skipDuplicateRequests = false;

				return this;
			}

			this.skipDuplicateRequests = true;

			return this;
		}
	}

	// Note: RequestManager export is intended for testing purposes only.
	module.exports = { RunActionExecutor, RequestManager };
});
