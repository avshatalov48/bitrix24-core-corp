/**
 * @module utils/number
 */
jn.define('utils/number', (require, exports, module) => {

	/**
	 * Checks if n is between start and up to, but not including, end.
	 * If end is not specified, it's set to start with start then set to 0.
	 * @function inRange
	 */
	function inRange(number, start, end)
	{
		start = toNumber(start);
		if (end === undefined)
		{
			end = start;
			start = 0;
		}
		else
		{
			end = toNumber(end);
		}

		number = toNumber(number);

		return number >= Math.min(start, end) && number < Math.max(start, end);
	}

	/**
	 * @function toNumber
	 */
	function toNumber(number)
	{
		const parsedValue = Number.parseFloat(number);

		if (BX.type.isNumber(parsedValue))
		{
			return parsedValue;
		}

		return 0;
	}

	/**
	 * Returns "99+" if the quantity exceeds the maximum.
	 * @param {number} [quantity=0]
	 * @param {number} [max=99]
	 * @returns {string}
	 */
	function formatQuantityWithLimit(quantity = 0, max = 99)
	{
		return quantity > max ? `${max}+` : quantity.toString();
	}

	module.exports = {
		inRange,
		toNumber,
		formatQuantityWithLimit,
	};
});
