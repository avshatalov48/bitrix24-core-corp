/**
 * @module im/messenger/model/validators/comment
 */
jn.define('im/messenger/model/validators/comment', (require, exports, module) => {
	const { Type } = require('type');

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

		if (Type.isNumber(fields.messageCount))
		{
			result.messageCount = fields.messageCount;
		}

		if (Type.isNumber(fields.messageId))
		{
			result.messageId = fields.messageId;
		}

		if (Type.isArray(fields.lastUserIds))
		{
			result.lastUserIds = fields.lastUserIds;
		}

		if (Type.isNumber(fields.newUserId) && !Type.isArray(fields.lastUserIds))
		{
			result.lastUserIds = [fields.newUserId];
		}

		if (Type.isBoolean(fields.isUserSubscribed))
		{
			result.isUserSubscribed = fields.isUserSubscribed;
		}

		return result;
	}

	module.exports = { validate };
});
