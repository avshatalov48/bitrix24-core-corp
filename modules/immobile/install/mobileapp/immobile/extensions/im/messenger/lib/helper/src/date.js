/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/helper/date
 */
jn.define('im/messenger/lib/helper/date', (require, exports, module) => {

	const { Type } = require('type');

	/**
	 * @class DateHelper
	 */
	class DateHelper
	{
		cast(date, defaultValue = new Date())
		{
			let result = defaultValue;

			if (Type.isDate(date))
			{
				result = date;
			}
			else if (Type.isString(date))
			{
				result = new Date(date);
			}
			else if (Type.isNumber(date))
			{
				result = new Date(date * 1000);
			}

			if (
				Type.isDate(result)
				&& Number.isNaN(result.getTime())
			)
			{
				result = defaultValue;
			}

			return result;
		}
	}

	module.exports = {
		DateHelper,
	};
});
