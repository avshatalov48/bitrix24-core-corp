/**
 * @module im/messenger/lib/rest-manager/rest-manager
 */
jn.define('im/messenger/lib/rest-manager/rest-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('network--batch');
	/**
	 * @class RestManager
	 *
	 * Designed for delayed BX.rest.callBatch execution and sending events to subscribers when a result is received.
	 *
	 * @property {Map} methodCollection - methods, parameters, and result listeners.
	 * {
	 *     'rest.method': {
	 *         'paramsKey': { //the same method can be called multiple times with different parameters.
	 *             params: {},
	 *             handlerList: [],
	 *         }
	 *     }
	 * }
	 */
	class RestManager
	{
		constructor()
		{
			this.methodCollection = new Map();
		}

		/**
		 * @param {object} options
		 * @param {boolean} options.shouldExtractResponseByMethod
		 * @return {Promise}
		 */
		callBatch(options = {})
		{
			return new Promise((resolve, reject) => {
				if (this.methodCollection.size === 0)
				{
					logger.log('RestManager.callBatch: No registered methods');

					resolve(true);

					return;
				}

				const batchMethodCollection = {};

				this.methodCollection.forEach((paramCollection, method) => {
					this.methodCollection
						.get(method)
						.forEach((item, paramsKey) => {
							const methodKey = `${method}|${paramsKey}`;

							batchMethodCollection[methodKey] = {
								method,
								params: item.params,
							};
						})
					;
				});

				logger.info(
					'RestManager.callBatch: ',
					Object.keys(batchMethodCollection).map((methodKey) => batchMethodCollection[methodKey]),
				);

				BX.rest.callBatch(batchMethodCollection, (result) => {
					let hasError = false;

					Object.keys(result).forEach((methodKey) => {
						const { method, params } = batchMethodCollection[methodKey];

						this.emit(method, params, result[methodKey]);

						if (result[methodKey].error())
						{
							hasError = true;
						}
					});

					if (hasError)
					{
						logger.error('RestManager.callBatch error: ', result);
						reject(result);

						return;
					}

					logger.info('RestManager.callBatch result: ', result);

					if (options.shouldExtractResponseByMethod)
					{
						// eslint-disable-next-line no-param-reassign
						result = this.extractResponseByMethod(result);

						logger.info('RestManager.callBatch result extracted by method: ', result);
					}

					resolve(result);
				});
			});
		}

		/**
		 * Add request to batch and register result handler.
		 *
		 * @param {string} method
		 * @param {object} params
		 * @param {function} callback
		 */
		on(method, params = {}, callback = () => {})
		{
			if (!Type.isStringFilled(method))
			{
				throw new Error('RestManager: method must be a filled string');
			}

			if (!Type.isObject(params))
			{
				throw new TypeError('RestManager: params must be an object');
			}

			if (!Type.isFunction(callback))
			{
				throw new TypeError('RestManager: callback must be a function');
			}

			this.subscribe(method, params, callback);

			return this;
		}

		/**
		 * Unsubscribe request. If the request remains without subscribers, it passes through the batch.
		 *
		 * @param {string} method
		 * @param {object} params
		 * @param {function} callback
		 */
		off(method, params = {}, callback = () => {})
		{
			if (!Type.isStringFilled(method))
			{
				throw new Error('RestManager: method must be a filled string');
			}

			if (!Type.isObject(params))
			{
				throw new TypeError('RestManager: params must be an object');
			}

			if (!Type.isFunction(callback))
			{
				throw new TypeError('RestManager: callback must be a function');
			}

			this.unsubscribe(method, params, callback);

			return this;
		}

		/**
		 * Add request to batch and register one-time result handler.
		 *
		 * @param {string} method
		 * @param {object} params
		 * @param {function} callback
		 */
		once(method, params = {}, callback = () => {})
		{
			if (!Type.isStringFilled(method))
			{
				throw new Error('RestManager: method must be a filled string');
			}

			if (!Type.isObject(params))
			{
				throw new TypeError('RestManager: params must be an object');
			}

			if (!Type.isFunction(callback))
			{
				throw new TypeError('RestManager: callback must be a function');
			}

			const onceCallback = (response) => {
				callback(response);
				this.unsubscribe(method, params, onceCallback);
			};

			this.subscribe(method, params, onceCallback);

			return this;
		}

		/**
		 * @private
		 */
		subscribe(method, params, callback)
		{
			const paramsKey = JSON.stringify(params);

			if (!this.methodCollection.has(method))
			{
				this.methodCollection.set(method, new Map());
			}

			if (!this.methodCollection.get(method).has(paramsKey))
			{
				this.methodCollection.get(method).set(paramsKey, {
					params,
					handlerList: [],
				});
			}

			this.methodCollection.get(method).get(paramsKey).handlerList.push(callback);
		}

		/**
		 * @private
		 */
		unsubscribe(method, params, callback)
		{
			const paramsKey = JSON.stringify(params);

			const hasMethodSubscribers = (
				this.methodCollection.has(method)
				&& this.methodCollection.get(method).has(paramsKey)
				&& this.methodCollection.get(method).get(paramsKey).handlerList
			);

			if (!hasMethodSubscribers)
			{
				return;
			}

			this.methodCollection.get(method).get(paramsKey).handlerList = this.methodCollection
				.get(method)
				.get(paramsKey)
				.handlerList
				.filter((handler) => handler !== callback)
			;

			if (this.methodCollection.get(method).get(paramsKey).handlerList.length === 0)
			{
				this.methodCollection.get(method).delete(paramsKey);
			}

			if (this.methodCollection.get(method).size === 0)
			{
				this.methodCollection.delete(method);
			}
		}

		/**
		 * @private
		 */
		emit(method, params, response)
		{
			const paramsKey = JSON.stringify(params);

			const hasMethodSubscribers = (
				this.methodCollection.has(method)
				&& this.methodCollection.get(method).has(paramsKey)
				&& this.methodCollection.get(method).get(paramsKey).handlerList
			);

			if (!hasMethodSubscribers)
			{
				return;
			}

			this.methodCollection
				.get(method)
				.get(paramsKey)
				.handlerList
				.forEach((handler) => handler(response))
			;
		}

		extractResponseByMethod(response)
		{
			Object.keys(response).forEach((restManagerResponseKey) => {
				const restMethod = restManagerResponseKey.split('|')[0];
				const ajaxResult = response[restManagerResponseKey];

				// eslint-disable-next-line no-param-reassign
				delete response[restManagerResponseKey];
				// eslint-disable-next-line no-param-reassign
				response[restMethod] = ajaxResult.data();
			});

			return response;
		}

		/**
		 * @desc Method call one request by name from the batch collection ( if it is )
		 * @param {string} methodName
		 * @void
		 */
		callByName(methodName)
		{
			let batchMethod = null;
			this.methodCollection.forEach((paramCollection, method) => {
				if (method === methodName)
				{
					this.methodCollection
						.get(method)
						.forEach((item) => {
							batchMethod = {
								method,
								params: item.params,
							};
						});
				}
			});

			if (!batchMethod)
			{
				return;
			}
			BX.rest.callMethod(batchMethod.method, batchMethod.params).then((result) => {
				this.emit(batchMethod.method, batchMethod.params, result);
				logger.info('RestManager.callByName result: ', result);
			}).catch((err) => logger.error(err));
		}
	}

	module.exports = {
		RestManager,
	};
});
