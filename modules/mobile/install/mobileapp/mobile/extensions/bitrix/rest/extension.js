(() => {
	const require = jn.require;
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { isValidAnalyticsData } = require('analytics/validator');

	/**
	 * @class RequestExecutor
	 * @alias RestExecutor
	 */
	class RequestExecutor
	{
		constructor(method, options)
		{
			this.method = method;
			this.options = options || {};
			this.currentAnswer = null;
			this.handler = null;
			/**
			 *
			 * @type {function}
			 */
			this.onCacheFetched = null;
			this.onRequestStart = null;
			this.cacheId = null;
		}

		call(useCache = false)
		{
			return new Promise((resolve, reject) => {
				this.abortCurrentRequest();
				this.currentAnswer = null;

				let cacheExists = false;

				if (this.onCacheFetched && useCache)
				{
					if (!this.cacheId)
					{
						this.cacheId = `${Object.toMD5(this.options)}/${this.method}`;
					}

					const cache = Application.storage.getObject(this.cacheId, null);
					if (cache !== null)
					{
						cacheExists = true;
						this.onCacheFetched(cache);
					}
				}

				if (this.onRequestStart)
				{
					this.onRequestStart(cacheExists);
				}

				BX.rest.callMethod(this.method, this.options, null, this.onRequestCreate.bind(this))
					.then((res) => {
						const result = res.answer.result;

						if (this.cacheId && !res.answer.error)
						{
							Application.storage.setObject(this.cacheId, result);
						}

						return this.__internalHandler(res, false, resolve);
					})
					.catch((res) => {
						this.__internalHandler(res, false, reject);
					});
			});
		}

		callNext()
		{
			return new Promise((resolve, reject) => {
				if (this.hasNext())
				{
					this.abortCurrentRequest();
					this.currentAnswer.next()
						.then((res) => {
							this.__internalHandler(res, true, resolve);
						})
						.catch((res) => {
							this.__internalHandler(res, true, reject);
						});
				}
			});
		}

		abortCurrentRequest()
		{
			if (this.currentAjaxObject != null)
			{
				this.currentAjaxObject.abort();
			}
		}

		onRequestCreate(ajax)
		{
			this.currentAjaxObject = ajax;
		}

		hasNext()
		{
			return (this.currentAnswer != null && typeof this.currentAnswer.answer.next !== 'undefined');
		}

		getNextCount()
		{
			if (this.hasNext())
			{
				const countLeft = this.currentAnswer.answer.total - this.currentAnswer.answer.next;

				return countLeft > 50 ? 50 : countLeft;
			}

			return null;
		}

		getNext()
		{
			if (this.hasNext())
			{
				return this.currentAnswer.answer.next;
			}

			return null;
		}

		/**
		 * @param ajaxAnswer
		 * @param loadMore
		 * @param promiseCallback
		 * @private
		 */
		__internalHandler(ajaxAnswer, loadMore, promiseCallback)
		{
			const result = ajaxAnswer.answer.result;
			let error;
			if (ajaxAnswer.answer.hasOwnProperty('error'))
			{
				error = { code: ajaxAnswer.answer.error, description: ajaxAnswer.answer.error_description };
			}
			this.currentAnswer = ajaxAnswer;

			if (typeof this.handler === 'function')
			{
				this.handler(result, loadMore, error);
			}

			if (promiseCallback)
			{
				promiseCallback({ result, loadMore, error });
			}
		}

		/**
		 * @param {function<object>} func
		 * @returns {RequestExecutor}
		 */
		setHandler(func)
		{
			this.handler = func;

			return this;
		}

		/**
		 * @param {function<object>} func
		 * @returns {RequestExecutor}
		 */
		setCacheHandler(func)
		{
			this.onCacheFetched = func;

			return this;
		}

		/**
		 * @param {function<object>} func
		 * @returns {RequestExecutor}
		 */
		setStartRequestHandler(func)
		{
			this.onRequestStart = func;

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

		setOptions(options = {})
		{
			this.options = options;

			return this;
		}
	}

	/**
	 * Class for delayed rest request
	 * @class DelayedRestRequest
	 */
	class DelayedRestRequest extends RequestExecutor
	{
		/**
		 * @param method
		 * @param options
		 */
		constructor(method, options = {})
		{
			super(method, options);
			this.timeoutId = 0;
			this.delay = 500;
		}

		setDelay(delay)
		{
			if (typeof delay !== 'undefined')
			{
				this.delay = Number(delay);
			}

			return this;
		}

		abortCurrentRequest()
		{
			clearTimeout(this.timeoutId);
			super.abortCurrentRequest();
		}

		call()
		{
			this.abortCurrentRequest();

			return new Promise((resolve, reject) => {
				this.timeoutId = setTimeout(() => super.call().then().catch(reject), this.delay);
			});
		}
	}

	const ActionPromiseWrapper = (promise) => {
		return new Promise((resolve, reject) => {
			promise.then((result) => {
				if (result.status === 'error')
				{
					console.error(result);
					reject(result);
				}
				else
				{
					resolve(result);
				}
			})
				.catch((result) => {
					if (result && result.status && Object.prototype.hasOwnProperty.call(result, 'data'))
					{
						console.error(result);
						reject(result);
					}
					else
					{
						const networkErrorResult = {
							status: 'error',
							data: {
								ajaxRejectData: result,
							},
							errors: [
								{
									code: 'NETWORK_ERROR',
									message: 'Network error',
								},
							],
						};
						console.error(networkErrorResult);
						reject(networkErrorResult);
					}
				});
		});
	};

	/**
	 *
	 * @param actionName
	 * @param params
	 * @return {Promise}
	 */

	/**
	 *
	 * @param {string} action
	 * @param {Object} config
	 * @param {?string|?Object} [config.analyticsLabel]
	 * @param {string} [config.method='POST']
	 * @param {Object} [config.data]
	 * @param {Object} [config.json]
	 * @param {?Object} [config.getParameters]
	 * @param {?Object} [config.headers]
	 * @param {?Object} [config.timeout]
	 * @param {Object} [config.navigation]
	 * @param {number} [config.navigation.page]
	 * @param {function} [config.onCreate]
	 */
	BX.ajax.runAction = (action, config = {}) => {
		const getParameters = prepareAjaxGetParameters(config);
		getParameters.action = action;

		const onCreate = typeof config.onCreate === 'function' ? config.onCreate : () => {};
		const url = `/bitrix/services/main/ajax.php?${BX.ajax.prepareData(getParameters, null, false)}`;
		let prepareData = true;

		if (typeof config.prepareData !== 'undefined')
		{
			prepareData = Boolean(config.prepareData);
		}

		if (config.json)
		{
			prepareData = false;
			config.data = JSON.stringify(config.json);
			config.headers = config.headers || {};
			config.headers['Content-Type'] = 'application/json';
		}

		config = {
			url,
			method: 'POST',
			uploadBinary: Boolean(config.binary),
			dataType: 'json',
			data: config.data,
			headers: config.headers,
			onUploadProgress: config.onprogressupload || function() {},
			prepareData,
		};

		const ajaxPromise = BX.ajax(config);
		onCreate(config.xhr);

		return ActionPromiseWrapper(ajaxPromise);
	};

	/**
	 *
	 * @param {string} component
	 * @param {string} action
	 * @param {Object} config
	 * @param {?string|?Object} [config.analyticsLabel]
	 * @param {string} [config.method='POST']
	 * @param {Object} [config.data]
	 * @param {?Object} [config.getParameters]
	 * @param {?Object} [config.headers]
	 * @param {?Object} [config.timeout]
	 * @param {Object} [config.navigation]
	 * @param {number} [config.navigation.page]
	 * @param {function} [config.onCreate]
	 */

	BX.ajax.runComponentAction = (component, action, config = {}) => {
		const getParameters = prepareAjaxGetParameters(config);
		getParameters.action = action;
		getParameters.c = component;
		const onCreate = typeof config.onCreate === 'function' ? config.onCreate : () => {};
		const url = `/bitrix/services/main/ajax.php?${BX.ajax.prepareData(
			getParameters,
			null,
			/**
			 * We need to avoid double encoding causing by mobile implementation of underlying XMLHttpRequest.open
			 * It encodes URL when it is passed without domain
			 *
			 * Until we are passing URL here without domain here we are safe to not encode parameters
			 */
			false,
		)}`;
		config = {
			url,
			method: 'POST',
			dataType: 'json',
			data: config.data,
		};

		const ajaxPromise = BX.ajax(config);
		onCreate(config.xhr);

		return ActionPromiseWrapper(ajaxPromise);
	};

	BX.ajax.prepareData = (originalData, prefix, encode = true) => {
		let data = '';
		if (originalData !== null)
		{
			for (const paramName in originalData)
			{
				if (originalData.hasOwnProperty(paramName))
				{
					if (data.length > 0)
					{
						data += '&';
					}
					let name = encode ? encodeURIComponent(paramName) : paramName;
					if (prefix)
					{
						name = `${prefix}[${name}]`;
					}

					if (typeof originalData[paramName] === 'object')
					{
						data += BX.ajax.prepareData(originalData[paramName], name, encode);
					}
					else
					{
						data += `${name}=${encode ? encodeURIComponent(originalData[paramName]) : originalData[paramName]}`;
					}
				}
			}
		}

		return data;
	};

	const prepareAjaxGetParameters = function(config) {
		const getParameters = config.getParameters || {};

		if (typeof config.analyticsLabel === 'string' || typeof config.analyticsLabel === 'object')
		{
			getParameters.analyticsLabel = config.analyticsLabel;
		}

		if (typeof config.analytics === 'object')
		{
			if (config.analyticsLabel)
			{
				delete getParameters.analyticsLabel;

				console.error(
					'BX.ajax: Only {analytics} or {analyticsLabel} should be used. If both are present, {analyticsLabel} will be ignored.',
				);
			}

			if (isValidAnalyticsData(config.analytics))
			{
				getParameters.st = config.analytics;
			}
			else
			{
				console.error('BX.ajax: {analytics} is invalid and will be ignored.');
			}
		}

		if (typeof config.mode !== 'undefined')
		{
			getParameters.mode = config.mode || 'ajax';
		}

		if (config.navigation)
		{
			if (config.navigation.page)
			{
				getParameters.nav = `page-${config.navigation.page}`;
			}

			if (config.navigation.size > 0)
			{
				if (getParameters.nav)
				{
					getParameters.nav += '-';
				}
				else
				{
					getParameters.nav = '';
				}
				getParameters.nav += `size-${config.navigation.size}`;
			}
		}

		return getParameters;
	};

	/**
	 * @class RunActionDelayedExecutor
	 */
	class RunActionDelayedExecutor extends RunActionExecutor
	{
		constructor(action, options)
		{
			super(action, options);
			this.timeoutId = null;
			this.timeout = 300;
		}

		abortCurrentRequest()
		{
			clearTimeout(this.timeoutId);
			super.abortCurrentRequest();
		}

		call()
		{
			clearTimeout(this.timeoutId);
			this.timeoutId = setTimeout(() => super.call(), this.timeout);
		}
	}

	jnexport(
		RunActionDelayedExecutor,
		RequestExecutor,
		RunActionExecutor,
		DelayedRestRequest,
		[RequestExecutor, 'RestExecutor'],
	);
})();

/**
 * @module rest
 */
jn.define('rest', (require, exports, module) => {
	module.exports = {
		RequestExecutor: this.RequestExecutor,
	};
});
