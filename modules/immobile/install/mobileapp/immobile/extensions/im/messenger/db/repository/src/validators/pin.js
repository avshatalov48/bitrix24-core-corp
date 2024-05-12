/**
 * @module im/messenger/db/repository/validators/pin
 */
jn.define('im/messenger/db/repository/validators/pin', (require, exports, module) => {
	const { Type } = require('type');
	const { ObjectUtils } = require('im/messenger/lib/utils');
	const { DateHelper } = require('im/messenger/lib/helper');

	/**
	 *
	 * @param field
	 * @return {Pin}
	 */
	function validate(field)
	{
		field = ObjectUtils.convertKeysToCamelCase(field);

		const result = {};
		if (Type.isNumber(field.id) || Type.isNull(field.id))
		{
			result.id = field.id;
		}

		if (Type.isNumber(field.chatId))
		{
			result.chatId = field.chatId;
		}

		if (Type.isNumber(field.authorId))
		{
			result.authorId = field.authorId;
		}

		if (Type.isStringFilled(field.dateCreate))
		{
			field.dateCreate = DateHelper.cast(field.dateCreate, null);
		}

		if (Type.isDate(field.dateCreate))
		{
			result.dateCreate = field.dateCreate;
		}

		if (Type.isNumber(field.messageId))
		{
			result.messageId = field.messageId;
		}

		return result;
	}

	module.exports = { validate };
});
