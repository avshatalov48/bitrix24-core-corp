/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/rest-manager/rest-manager
 */
jn.define('im/messenger/lib/rest-manager/rest-manager', (require, exports, module) => {

	const { Type } = require('type');
	const { Logger } = require('im/messenger/lib/logger');

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

		callBatch()
		{
			return new Promise((resolve, reject) => {
				if (this.methodCollection.size === 0)
				{
					Logger.log('RestManager.callBatch: No registered methods');
					return;
				}

				const batchMethodCollection = {};

				this.methodCollection.forEach((paramCollection, method) => {
					this.methodCollection
						.get(method)
						.forEach((item, paramsKey) => {
							const methodKey = method + '|' + paramsKey;

							batchMethodCollection[methodKey] = {
								method,
								params: item.params,
							};
						})
					;
				});

				Logger.info(
					'RestManager.callBatch: ',
					Object.keys(batchMethodCollection).map(methodKey => batchMethodCollection[methodKey])
				);

				BX.rest.callBatch(batchMethodCollection, (result) => {
					let hasError = false;

					Object.keys(result).forEach((methodKey) => {
						const { method, params } = batchMethodCollection[methodKey];

						this._emit(method, params, result[methodKey]);

						if (result[methodKey].error())
						{
							hasError = true;
						}
					});

					if (hasError)
					{
						Logger.error('RestManager.callBatch error: ', result);
						reject(result);

						return;
					}

					Logger.info('RestManager.callBatch result: ', result);
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
				throw new Error('RestManager: params must be an object');
			}

			if (!Type.isFunction(callback))
			{
				throw new Error('RestManager: callback must be a function');
			}

			this._subscribe(method, params, callback);

			return this;
		}

		/**
		 * Unsubscribe request. If the request remains without subscribers, it passes through the batch.
		 *
		 * @param {string} method
		 * @param {object} params
		 * @param {function} callback
		 */
		off(method, params= {}, callback)
		{
			if (!Type.isStringFilled(method))
			{
				throw new Error('RestManager: method must be a filled string');
			}

			if (!Type.isObject(params))
			{
				throw new Error('RestManager: params must be an object');
			}

			if (!Type.isFunction(callback))
			{
				throw new Error('RestManager: callback must be a function');
			}

			this._unsubscribe(method, params, callback);

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
				throw new Error('RestManager: params must be an object');
			}

			if (!Type.isFunction(callback))
			{
				throw new Error('RestManager: callback must be a function');
			}

			const onceCallback = (response) => {
				callback(response);
				this._unsubscribe(method, params, onceCallback);
			};

			this._subscribe(method, params, onceCallback);

			return this;
		}

		_subscribe(method, params, callback)
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

		_unsubscribe(method, params, callback)
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

			this.methodCollection.get(method).get(paramsKey).handlerList =
				this.methodCollection
					.get(method)
					.get(paramsKey)
					.handlerList
					.filter(handler => handler !== callback)
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

		_emit(method, params, response)
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
				.forEach(handler => handler(response))
			;
		}
	}

	module.exports = {
		RestManager,
	};
});
