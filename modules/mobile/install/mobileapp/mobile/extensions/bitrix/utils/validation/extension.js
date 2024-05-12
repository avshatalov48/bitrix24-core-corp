/**
 * @module utils/validation
 */
jn.define('utils/validation', (require, exports, module) => {
	const PropType = function() {};

	PropType.isRequired = function() {};

	/** @type {PropTypesProtocol} */
	const PropTypesPolyfill = {
		any: PropType,
		array: PropType,
		bigint: PropType,
		bool: PropType,
		func: PropType,
		number: PropType,
		object: PropType,
		string: PropType,
		symbol: PropType,
		instanceOf: () => PropType,
		oneOf: () => PropType,
		oneOfType: () => PropType,
		arrayOf: () => PropType,
		objectOf: () => PropType,
		shape: () => PropType,
		exact: () => PropType,
		validate: () => {},
	};

	/**
	 * @public
	 * @param {any} val
	 * @param {string} message
	 * @return {void}
	 */
	function assertDefined(val, message)
	{
		if (typeof val === 'undefined')
		{
			throw new TypeError(message);
		}
	}

	/**
	 * @public
	 * @param {any} val
	 * @param {string} message
	 * @return {void}
	 */
	function assertFunction(val, message)
	{
		if (typeof val !== 'function')
		{
			throw new TypeError(message);
		}
	}

	/**
	 * Only suitable for simple single-dimensional arrays containing primitives,
	 * e.g. set of some ids.
	 *
	 * @public
	 * @param {number[]|string[]} values
	 * @param {string} message
	 * @return {void}
	 */
	function assertUnique(values, message)
	{
		const uniqueValues = new Set(values);
		if (uniqueValues.size !== values.length)
		{
			throw new TypeError(message);
		}
	}

	module.exports = {
		// for testing purposes only
		PropTypesPolyfill,
		PropTypes: typeof PropTypes === 'undefined' ? PropTypesPolyfill : PropTypes,
		assertDefined,
		assertFunction,
		assertUnique,
	};
});
