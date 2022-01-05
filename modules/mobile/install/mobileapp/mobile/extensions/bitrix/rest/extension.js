(() =>
{
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
						this.cacheId = Object.toMD5(this.options) + "/" + this.method;
					}
					let cache = Application.storage.getObject(this.cacheId, null);
					if(cache != null)
					{
						cacheExists = true;
						this.onCacheFetched(cache);
					}
				}


				if(this.onRequestStart)
				{
					this.onRequestStart(cacheExists)
				}
				BX.rest.callMethod(this.method, this.options, null, this.onRequestCreate.bind(this))
					.then(res =>
					{
						let result = res.answer.result;
						if (this.cacheId && res.answer.error == null)
						{
							Application.storage.setObject(this.cacheId, result)
						}

						return this.__internalHandler(res, false, resolve);
					})
					.catch(res =>
					{
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
						.then((res) =>
						{
							this.__internalHandler(res, true,resolve);
						})
						.catch((res) =>
						{
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
			return (this.currentAnswer != null && typeof this.currentAnswer.answer.next != "undefined");
		}

		getNextCount()
		{
			if (this.hasNext())
			{
				let countLeft = this.currentAnswer.answer.total - this.currentAnswer.answer.next;
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
		 *
		 * @param ajaxAnswer
		 * @param loadMore
		 * @param promiseCallback
		 * @private
		 */
		__internalHandler(ajaxAnswer, loadMore, promiseCallback)
		{
			let result = ajaxAnswer.answer.result;
			let error;
			if (ajaxAnswer.answer.error)
			{
				error = {code: ajaxAnswer.answer.error, description: ajaxAnswer.answer.error_description};
			}
			this.currentAnswer = ajaxAnswer;

			if (typeof this.handler == "function")
			{
				this.handler(result, loadMore, error)
			}

			if(promiseCallback)
			{
				promiseCallback({result, loadMore, error});
			}
		}

		/**
		 *
		 * @param {function<object>} func
		 * @returns {RequestExecutor}
		 */
		setHandler(func)
		{
			this.handler = func;
			return this;
		}
		/**
		 *
		 * @param {function<object>} func
		 * @returns {RequestExecutor}
		 */
		setCacheHandler(func)
		{
			this.onCacheFetched = func;
			return this;
		}

		/**
		 *
		 * @param {function<object>} func
		 * @returns {RequestExecutor}
		 */
		setStartRequestHandler(func)
		{
			this.onRequestStart = func;
			return this;
		}

		/**
		 *
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
			if(typeof delay !== "undefined")
				this.delay = Number(delay);

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
			return new Promise((resolve, reject)=>{
				this.timeoutId = setTimeout(()=> super.call().then().catch(reject), this.delay);
			})

		}
	}

	let ActionPromiseWrapper = promise => {
		return new Promise((resolve, reject) =>
			promise
				.then(result => {
					if (result["status"] === "error")
					{
						reject(result)
					}
					else
					{
						resolve(result)
					}
				})
				.catch(result => reject.call(null, result)));
	}

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
	 * @param {?Object} [config.getParameters]
	 * @param {?Object} [config.headers]
	 * @param {?Object} [config.timeout]
	 * @param {Object} [config.navigation]
	 * @param {number} [config.navigation.page]
	 * @param {function} [config.onCreate]
	 */
	BX.ajax.runAction = (action, config = {} )=>{
		let getParameters = prepareAjaxGetParameters(config);
		getParameters.action = action;
		let onCreate = (typeof config["onCreate"] == "function"? config["onCreate"]: ()=>{});
		let url = '/bitrix/services/main/ajax.php?' + BX.ajax.prepareData(getParameters);
		let prepareData = true;
		if (config.json) {
			prepareData = false;
			config.data = JSON.stringify(config.json);
		}
		config = {
			url: url,
			method:"POST",
			dataType:"json",
			data: config.data,
			prepareData: prepareData
		};

		let ajaxPromise = BX.ajax(config);
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

	BX.ajax.runComponentAction = (component, action, config = {} )=>{
		config.mode = config.mode || 'ajax';
		let getParameters = prepareAjaxGetParameters(config);
		getParameters.action = action;
		getParameters.c = component;
		let onCreate = (typeof config["onCreate"] == "function"? config["onCreate"]: ()=>{});
		let url = '/bitrix/services/main/ajax.php?' + BX.ajax.prepareData(getParameters);
		config = {
			url: url,
			method:"POST",
			dataType:"json",
			data: config.data,
		};
		let ajaxPromise = BX.ajax(config);
		onCreate(config.xhr);
		return ActionPromiseWrapper(ajaxPromise);
	};

	let prepareAjaxGetParameters = function(config)
	{
		let getParameters = config.getParameters || {};
		if (typeof config.analyticsLabel == "string")
		{
			getParameters.analyticsLabel = config.analyticsLabel;
		}
		else if (typeof config.analyticsLabel == "object")
		{
			getParameters.analyticsLabel = config.analyticsLabel;
		}
		if (typeof config.mode !== 'undefined')
		{
			getParameters.mode = config.mode;
		}
		if (config.navigation)
		{
			if(config.navigation.page)
			{
				getParameters.nav = 'page-' + config.navigation.page;
			}
			if(config.navigation.size)
			{
				if(getParameters.nav)
				{
					getParameters.nav += '-';
				}
				else
				{
					getParameters.nav = '';
				}
				getParameters.nav += 'size-' + config.navigation.size;
			}
		}

		return getParameters;
	};

	/**s
	 * @class RunActionExecutor
	 */
	class RunActionExecutor
	{
		constructor(action, options)
		{
			this.action = action;
			this.options = options || {};
			this.currentAnswer = null;
			this.handler = null;
			/**
			 *
			 * @type {function}
			 */
			this.onCacheFetched = null;
			this.cacheId = null;
		}

		call(useCache = false)
		{
			this.abortCurrentRequest();
			this.currentAnswer = null;
			if (this.onCacheFetched && useCache)
			{
				if (!this.cacheId)
				{
					this.cacheId = Object.toMD5(this.options) + "/" + this.method;
				}
				let cache = Application.storage.getObject(this.cacheId, null);
				if(cache !== null)
				{
					this.onCacheFetched(cache);
				}
			}

			BX.ajax.runAction(this.action, {data:this.options, onCreate:this.onRequestCreate.bind(this)})
				.then(res =>
				{
					if (this.cacheId && res.error.length === 0)
					{
						Application.storage.setObject(this.cacheId, result)
					}
					return this.__internalHandler(res, false);
				})
				.catch(res => this.__internalHandler(res, false));
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

		/**
		 *
		 * @param ajaxAnswer
		 * @private
		 */
		__internalHandler(ajaxAnswer)
		{
			let result = ajaxAnswer.data;
			let errors = ajaxAnswer.errors;
			this.currentAnswer = ajaxAnswer;

			if (typeof this.handler == "function")
			{
				this.handler(result, errors)
			}
		}

		/**
		 *
		 * @param {function<object>} func
		 * @returns {RequestExecutor}
		 */
		setHandler(func)
		{
			this.handler = func;
			return this;
		}

		/**
		 *
		 * @param {function<object>} func
		 * @returns {RequestExecutor}
		 */
		setCacheHandler(func)
		{
			this.onCacheFetched = func;
			return this;
		}

		/**
		 *
		 * @param {String} id
		 */
		setCacheId(id)
		{
			this.cacheId = id;
			return this;
		}

		updateOptions(options = null)
		{
			if(options != null && typeof options == "object")
				this.options = Object.assign(this.options, options);

			return this;
		}
	}

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
			this.timeoutId = setTimeout(() => super.call(), this.timeout)
		}
	}

	jnexport(
		RunActionDelayedExecutor,
		RequestExecutor,
		RunActionExecutor,
		DelayedRestRequest,
		[RequestExecutor, "RestExecutor"]
	)

})();
