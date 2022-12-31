(() => {

	const { hashCode } = jn.require('utils/hash');
	const { camelize, stringify } = jn.require('utils/string');

	/**
	 * Creates a debounced function that delays invoking {fn} until after {timeout} milliseconds have elapsed
	 * since the last time the debounced function was invoked.
	 * @param {function} fn
	 * @param {number} timeout
	 * @param {*} ctx
	 * @param {boolean} immediate
	 * @returns {function}
	 */
	function debounce(fn, timeout, ctx, immediate = false)
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
	 * @param {object} context
	 * @returns {function}
	 */
	function throttle(fn, timeout, context = this)
	{
		let lock;

		return function() {
			if (!lock)
			{
				const result = fn.apply(context, arguments);
				lock = true;
				setTimeout(() => {
					lock = false;
				}, timeout);

				return result;
			}
		};
	}

	/**
	 * Creates a function that is restricted to invoking {fn} once.
	 * Repeat calls to the function return the value of the first invocation
	 * @param {function} fn
	 * @returns {function}
	 */
	function once(fn)
	{
		throw new Error('Not implemented yet');
	}

	/**
	 * Applies {fn} to each element of {ary}. Callback {fn} **MUST** return Promise.
	 * Next iteration will be performed when previous Promise resolves.
	 * @param {array} ary
	 * @param {function():Promise} fn
	 * @return {Promise}
	 */
	function mapPromise(ary, fn)
	{
		return ary.reduce((p, x) => p.then(() => fn(x)), Promise.resolve());
	}

	const hashIdSymbol = Symbol('hashId');

	/**
	 * @param {function} callback
	 * @param {?array} deps
	 * @returns {function}
	 */
	const useCallback = (callback, deps = null) => {
		deps = deps === undefined ? null : deps;

		if (!callback.hasOwnProperty(hashIdSymbol))
		{
			callback[hashIdSymbol] = hashCode(JSON.stringify([stringify(callback), deps]));
		}

		return callback;
	};

	useCallback.hashIdSymbol = hashIdSymbol;

	/**
	 * @class FunctionUtils
	 * @deprecated Please import specific utilities directly, using jn.require()
	 */
	class FunctionUtils
	{
		static debounce(fn, timeout, ctx, immediate = false)
		{
			return debounce(fn, timeout, ctx, immediate);
		}

		static throttle(fn, timeout, context = this)
		{
			return throttle(fn, timeout, context);
		}

		static once(fn)
		{
			return once(fn);
		}
	}

	window.reflectFunction = function (object, funcName, thisObject)
	{
		return function(){
			const context = thisObject || object;
			const targetFunction = camelize(funcName);
			if(object && typeof object[targetFunction] == "function")
			{
				return object[targetFunction].apply(context, arguments);
			}

			return function(){};
		}
	}

	jnexport(FunctionUtils);

	/**
	 * @module utils/function
	 */
	jn.define('utils/function', (require, exports, module) => {

		module.exports = {
			debounce,
			throttle,
			once,
			mapPromise,
			useCallback,
		};

	});

})();