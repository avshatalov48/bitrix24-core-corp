(() => {
	/**
	 * @param {any} value
	 * @returns {Boolean}
	 */
	function isNil(value)
	{
		return typeof value === 'undefined' || value === null;
	}

	/**
	 * @param {Function|Class} fn
	 * @return {boolean}
	 */
	function isESClass(fn)
	{
		return typeof fn === 'function' && Object.getOwnPropertyDescriptor(fn, 'prototype')?.writable === false;
	}

	/**
	 * @param {any} value
	 * @return {Boolean}
	 */
	function isRegExp(value)
	{
		return value instanceof RegExp;
	}

	/**
	 * @param {any} value
	 * @return {boolean}
	 */
	function isValidDateObject(value)
	{
		return value instanceof Date && !isNaN(value);
	}

	/**
	 * @module utils/type
	 */
	jn.define('utils/type', (require, exports, module) => {
		module.exports = {
			isNil,
			isRegExp,
			isESClass,
			isValidDateObject,
		};
	});
})();
