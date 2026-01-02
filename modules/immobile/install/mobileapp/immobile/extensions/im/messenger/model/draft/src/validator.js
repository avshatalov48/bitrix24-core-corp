/**
 * @module im/messenger/model/draft/src/validator
 */
jn.define('im/messenger/model/draft/src/validator', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('im/messenger/lib/helper');

	/**
	 * @param {DraftModelState} fields
	 * @return {Partial<DraftModelState>}
	 */
	function validate(fields)
	{
		/** @type {DraftModelState} */
		const result = {};

		if (Type.isStringFilled(fields.dialogId) || Type.isNumber(fields.dialogId))
		{
			result.dialogId = fields.dialogId;
		}

		if (Type.isStringFilled(fields.messageId) || Type.isNumber(fields.messageId))
		{
			result.messageId = fields.messageId;
		}

		if (Type.isString(fields.lastActivityDate) || Type.isDate(fields.lastActivityDate))
		{
			result.lastActivityDate = DateHelper.cast(fields.lastActivityDate, null);
		}

		if (Type.isStringFilled(fields.type))
		{
			result.type = fields.type;
		}

		if (Type.isStringFilled(fields.messageType))
		{
			result.messageType = fields.messageType;
		}

		if (Type.isString(fields.text))
		{
			result.text = fields.text;
		}

		if (Type.isArray(fields.message))
		{
			result.message = fields.message;
		}

		if (Type.isStringFilled(fields.userName))
		{
			result.userName = fields.userName;
		}

		if (Type.isPlainObject(fields.video))
		{
			result.video = fields.video;
		}

		if (Type.isPlainObject(fields.image))
		{
			result.image = fields.image;
		}

		return result;
	}

	module.exports = { validate };
});
