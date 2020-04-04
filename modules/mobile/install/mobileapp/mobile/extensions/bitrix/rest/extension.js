(() =>
{
	class RequestExecutor
	{
		constructor(method, options)
		{
			this.method = method;
			this.options = options;
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
				let cache = Application.storage.getObject(this.cacheId, []);
				this.onCacheFetched(cache);
			}

			BX.rest.callMethod(this.method, this.options, null, this.onRequestCreate.bind(this))
				.then(res =>
				{
					let result = res.answer.result;
					if (this.cacheId && res.answer.error == null)
					{
						Application.storage.setObject(this.cacheId, result)
					}
					return this.__internalHandler(res, false);
				})
				.catch(res => this.__internalHandler(res, false));
		}

		callNext()
		{
			if (this.hasNext())
			{
				this.abortCurrentRequest();
				this.currentAnswer.next()
					.then((res) => this.__internalHandler(res, true))
					.catch((res) => this.__internalHandler(res, true));
			}
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
		 * @private
		 */
		__internalHandler(ajaxAnswer, loadMore)
		{
			let result = ajaxAnswer.answer.result;
			let error;
			if (ajaxAnswer.answer.error)
			{
				error = {code: ajaxAnswer.answer.error, description: ajaxAnswer.answer.error_decription};
			}
			this.currentAnswer = ajaxAnswer;

			if (typeof this.handler == "function")
			{
				this.handler(result, loadMore, error)
			}
		}
	}

	/**
	 *  @interface DelayedRestRequestDelegate
	 * */
	/** Result method.
	 * @name  DelayedRestRequestDelegate#onDelayedRequestResult
	 * @param {{success:boolean}} result
	 * @type {Function}
	 * @return {void}
	 */
	/** Method gets params.
	 * @name  DelayedRestRequestDelegate#getParams
	 * @type {Function}
	 * @return {object}
	 */

	/**
	 * Class for delayed rest request
	 * @class DelayedRestRequest
	 */
	class DelayedRestRequest
	{
		/**
		 *
		 * @param method
		 * @param {DelayedRestRequestDelegate, null} delegate
		 */
		constructor(method, delegate = null)
		{
			if(delegate == null || typeof delegate !== "function")
				new Error("Argument (2) must be defined");

			this.method = method;
			this.timeoutId = 0;
			this.delay = 500;
			this._delegate = delegate;
		}

		get delegate()
		{
			return this._delegate;
		}

		send()
		{
			if (this.timeoutId)
			{
				clearTimeout(this.timeoutId);
			}

			this.timeoutId = setTimeout(
				()=>{
					BX.rest.callMethod(this.method, this.delegate.getParams())
						.then(() => this.delegate.onDelayedRequestResult({success: true}))
						.catch(() =>
						{
							return this.delegate.onDelayedRequestResult({success: false});
						})
				},
				this.delay
			);
		}
	}

	window.RequestExecutor = RequestExecutor;
	window.DelayedRestRequest = DelayedRestRequest;

})();
