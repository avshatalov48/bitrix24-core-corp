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
			isValidDateObject,
		};

	});

})();