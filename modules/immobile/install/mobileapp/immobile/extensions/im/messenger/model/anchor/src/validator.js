/**
 * @module im/messenger/model/anchor/validator
 */
jn.define('im/messenger/model/anchor/validator', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @param fields
	 */
	function validate(fields)
	{
		const result = {};

		if (Type.isNumber(fields.chatId))
		{
			result.chatId = fields.chatId;
		}

		if (Type.isNumber(fields.dialogId) || Type.isString(fields.dialogId))
		{
			result.dialogId = fields.dialogId;
		}

		if (Type.isNumber(fields.fromUserId))
		{
			result.fromUserId = fields.fromUserId;
		}

		if (Type.isNumber(fields.messageId))
		{
			result.messageId = fields.messageId;
		}

		if (Type.isNumber(fields.parentChatId))
		{
			result.parentChatId = fields.parentChatId;
		}

		if (Type.isNumber(fields.parentMessageId))
		{
			result.parentMessageId = fields.parentMessageId;
		}

		if (Type.isNumber(fields.userId))
		{
			result.userId = fields.userId;
		}

		if (Type.isStringFilled(fields.type))
		{
			result.type = fields.type;
		}

		if (Type.isStringFilled(fields.subType))
		{
			result.subType = fields.subType;
		}

		return result;
	}

	module.exports = { validate };
});
