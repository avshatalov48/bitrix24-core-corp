/**
 * @module rest/run-action-executor/src/request-manager
 */
jn.define('rest/run-action-executor/src/request-manager', (require, exports, module) => {
	const REQUEST_STORAGE_NAME = 'requestExecutorStore';
	const REQUEST_TIMEOUT_MS = 1100;
	const REQUEST_INTERVAL_MS = 50;
	const PULSE_INTERVAL_MS = 1000;

	/**
	 * @class RequestManager
	 */
	class RequestManager
	{
		/**
		 * @param {string} action
		 * @param {Object} options
		 * @param {Logger} logger
		 * @param {Object} [navigation={}]
		 * @param {Object} [storage=null]
		 */
		constructor(action, options, logger, navigation = {}, storage = null)
		{
			this.action = action;
			this.options = options || {};
			this.navigation = navigation || {};
			this.requestKey = this.#getRequestKey();
			this.pulseInterval = null;
			this.logger = logger;
			this.requestStore = storage || Application.sharedStorage(REQUEST_STORAGE_NAME);
		}

		static PROMISE_STATUS = {
			PENDING: 'pending',
			FULFILLED: 'fulfilled',
			REJECTED: 'rejected',
		};

		/**
		 * @returns {Promise<*|null>}
		 */
		getSameOngoingRequest()
		{
			if (this.requestKey)
			{
				const requestState = this.requestStore.get(this.requestKey);

				return requestState && requestState.status === RequestManager.PROMISE_STATUS.PENDING ? requestState : null;
			}

			return null;
		}

		/**
		 * @param {string} [status=RequestManager.PROMISE_STATUS.PENDING]
		 * @param {*} [result=null] - The result to set (can be any type except a function).
		 * @returns {void}
		 */
		setRequestStatus(status, result = null)
		{
			const timestamp = Date.now();
			this.requestStore.set(this.requestKey, { status, result, timestamp });

			if (status === RequestManager.PROMISE_STATUS.FULFILLED || status === RequestManager.PROMISE_STATUS.REJECTED)
			{
				this.#clearStorage(timestamp);
				this.#stopPulseMechanism();
			}
		}

		#clearStorage(timestamp)
		{
			setTimeout(() => {
				const currentState = this.requestStore.get(this.requestKey);

				if (currentState && currentState.timestamp === timestamp)
				{
					this.requestStore.set(this.requestKey, null);
				}
			}, REQUEST_TIMEOUT_MS);
		}

		/**
		 * @returns {void}
		 */
		startRequest()
		{
			this.#startPulseMechanism();
			this.setRequestStatus(RequestManager.PROMISE_STATUS.PENDING);
		}

		#startPulseMechanism()
		{
			this.pulseInterval = setInterval(() => {
				const requestState = this.requestStore.get(this.requestKey);
				if (requestState?.status === RequestManager.PROMISE_STATUS.PENDING)
				{
					this.#updateOngoingRequestTimestamp(requestState);
				}
				else
				{
					this.#stopPulseMechanism();
				}
			}, PULSE_INTERVAL_MS);
		}

		#updateOngoingRequestTimestamp(requestState)
		{
			this.setRequestStatus(RequestManager.PROMISE_STATUS.PENDING, requestState.result);
		}

		#stopPulseMechanism()
		{
			if (this.pulseInterval)
			{
				clearInterval(this.pulseInterval);
				this.pulseInterval = null;
			}
		}

		/**
		 * @param {Function} executeRequestCallback
		 * @param {Function} internalHandlerCallback
		 * @returns {Promise<*>}
		 */
		waitOngoingRequestResult(executeRequestCallback, internalHandlerCallback)
		{
			this.logger.info(`Action ${this.action} has a duplicate request that is queued`);

			return new Promise((resolve, reject) => {
				const interval = setInterval(() => {
					const request = this.requestStore.get(this.requestKey);

					if (this.#isRequestCompleted(request))
					{
						this.logger.info(`${this.action} duplicate request have received response`, request);

						clearInterval(interval);

						this.#resolveOngoingRequestResult(request, resolve, reject, internalHandlerCallback);
					}
					else
					{
						this.#tryAgain(request, executeRequestCallback, resolve, reject, interval);
					}
				}, REQUEST_INTERVAL_MS);
			});
		}

		/**
		 * @param {Object} options
		 */
		updateOptions(options)
		{
			if (options && typeof options === 'object')
			{
				this.options = Object.assign(this.options, options);
				this.requestKey = this.#getRequestKey();
			}
		}

		#getRequestKey()
		{
			if (!this.requestKey)
			{
				const sortedOptions = this.#sortKeysRecursively(this.options);
				const sortedNavigation = this.#sortKeysRecursively(this.navigation);

				return `${JSON.stringify(sortedOptions)}/${JSON.stringify(sortedNavigation)}/${this.action}`;
			}

			return this.requestKey;
		}

		#sortKeysRecursively(obj)
		{
			if (typeof obj !== 'object' || obj === null)
			{
				return obj;
			}

			if (Array.isArray(obj))
			{
				return obj.map((item) => this.#sortKeysRecursively(item));
			}

			const sortedKeys = Object.keys(obj).sort();
			const sortedObj = {};
			sortedKeys.forEach((key) => {
				sortedObj[key] = this.#sortKeysRecursively(obj[key]);
			});

			return sortedObj;
		}

		#isRequestCompleted(request)
		{
			return request.status === RequestManager.PROMISE_STATUS.FULFILLED
				|| request.status === RequestManager.PROMISE_STATUS.REJECTED;
		}

		#resolveOngoingRequestResult(request, resolve, reject, internalHandlerCallback)
		{
			if (request.status === RequestManager.PROMISE_STATUS.FULFILLED)
			{
				this.#handleFulfilledRequest(request, resolve, internalHandlerCallback);
			}
			else if (request.status === RequestManager.PROMISE_STATUS.REJECTED)
			{
				this.#handleRejectedRequest(request, reject);
			}
		}

		#handleFulfilledRequest(request, resolve, internalHandlerCallback)
		{
			resolve(request.result);
			internalHandlerCallback(request.result);
		}

		#handleRejectedRequest(request, reject)
		{
			reject(request.result);
		}

		#tryAgain(request, executeRequestCallback, resolve, reject, interval)
		{
			const isAlive = this.#isRequestAlive();
			if (!isAlive)
			{
				clearInterval(interval);

				this.setRequestStatus(RequestManager.PROMISE_STATUS.PENDING);

				this.logger.warn(`Main request of ${this.action} is dead. A queued duplicate request is now being sent to the server`);

				try
				{
					const result = executeRequestCallback();
					this.setRequestStatus(RequestManager.PROMISE_STATUS.FULFILLED, result);
					resolve(result);
				}
				catch (error)
				{
					this.setRequestStatus(RequestManager.PROMISE_STATUS.REJECTED, error);
					reject(error);
				}
			}
		}

		#isRequestAlive()
		{
			const requestState = this.requestStore.get(this.requestKey);
			if (requestState)
			{
				const currentTime = Date.now();
				const differenceInMilliseconds = currentTime - requestState.timestamp;

				return differenceInMilliseconds < REQUEST_TIMEOUT_MS;
			}

			return false;
		}
	}

	module.exports = {
		RequestManager,
	};
});
