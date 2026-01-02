/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/recent/search/validator
 */

jn.define('im/messenger/model/recent/search/validator', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('im/messenger/lib/helper');

	/**
	 * @param fields
	 * @return {RecentSearchModelState}
	 */
	function validate(fields)
	{
		const result = {};

		if (Type.isStringFilled(fields.dialogId))
		{
			fields.id = fields.dialogId;
		}

		if (Type.isStringFilled(fields.id))
		{
			result.id = fields.id;
		}

		if (Type.isStringFilled(fields.dateMessage))
		{
			result.dateMessage = DateHelper.cast(fields.dateMessage, null);
		}

		return result;
	}

	module.exports = {
		validate,
	};
});
