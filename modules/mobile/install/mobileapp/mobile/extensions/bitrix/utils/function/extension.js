(() => {

	/**
	 * @class FunctionUtils
	 */
	class FunctionUtils
	{
		/**
		 * Creates a debounced function that delays invoking {fn} until after {timeout} milliseconds have elapsed
		 * since the last time the debounced function was invoked.
		 * @param {function} fn
		 * @param {number} timeout
		 * @param {*} ctx
		 * @param {boolean} immediate
		 * @returns {function}
		 */
		static debounce(fn, timeout, ctx, immediate = false)
		{
			let timer = null;

			const clearTimer = () => {
				return setTimeout(() => {
					timer = null;
				}, timeout);
			};

			return function() {
				if (immediate && timer === null)
				{
					fn.apply(ctx, arguments);
					timer = clearTimer();
				}
				else
				{
					clearTimeout(timer);
					timer = setTimeout(() => {
						fn.apply(ctx, arguments);
						timer = clearTimer();
					}, timeout);
				}
			};
		}

		/**
		 * Creates a throttled function that only invokes {fn} at most once per every {timeout} milliseconds.
		 * @param {function} fn
		 * @param {number} timeout
		 * @returns {function}
		 */
		static throttle(fn, timeout)
		{
			throw new Error('Not implemented yet');
		}

		/**
		 * Creates a function that is restricted to invoking {fn} once.
		 * Repeat calls to the function return the value of the first invocation
		 * @param {function} fn
		 * @returns {function}
		 */
		static once(fn)
		{
			throw new Error('Not implemented yet');
		}
	}

	window.reflectFunction = function (object, funcName, thisObject)
	{
		return function(){
			const context = thisObject || object;
			const targetFunction = StringUtils.camelize(funcName);
			if(object && typeof object[targetFunction] == "function")
			{
				return object[targetFunction].apply(context, arguments);
			}

			return function(){};
		}
	}

	jnexport(FunctionUtils);

})();