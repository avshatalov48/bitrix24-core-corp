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
	 * @function parseAmount
	 * @param {string} amount
	 * @param {string} decimalSeparator
	 * @param {string} thousandsSeparator
	 * @returns {number}
	 */
	function parseAmount(amount, decimalSeparator, thousandsSeparator)
	{
		const parts = String(amount).split(decimalSeparator);
		let thousandPart = parts[0] ?? 0;
		const decimalPart = parts[1] || '';

		if (thousandsSeparator === '&nbsp;')
		{
			thousandPart = thousandPart.replaceAll('\u00A0', '');
		}
		else if (thousandsSeparator !== '')
		{
			thousandPart = thousandPart.replaceAll(thousandsSeparator, '');
		}

		return Number(`${thousandPart}.${decimalPart}`);
	}

	module.exports = {
		inRange,
		toNumber,
		parseAmount,
	};
});
