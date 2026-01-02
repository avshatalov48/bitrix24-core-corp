/**
 * @module im/messenger/model/sidebar/src/common-chats/validator
 */

jn.define('im/messenger/model/sidebar/src/common-chats/validator', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('im/messenger/lib/helper');

	/**
	 * @param {SidebarCommonChat} fields
	 */
	function validate(fields)
	{
		const result = {};

		if (Type.isStringFilled(fields.avatar))
		{
			result.avatar = fields.avatar;
		}

		if (Type.isStringFilled(fields.color))
		{
			result.color = fields.color;
		}

		if (!Type.isUndefined(fields.dateMessage))
		{
			result.dateMessage = DateHelper.cast(fields.dateMessage);
		}

		if (Type.isStringFilled(fields.description))
		{
			result.description = fields.description;
		}

		if (Type.isNumber(fields.dialogId) || Type.isStringFilled(fields.dialogId))
		{
			result.dialogId = fields.dialogId.toString();
		}

		if (Type.isNumber(fields.diskFolderId))
		{
			result.diskFolderId = fields.diskFolderId;
		}

		if (Type.isStringFilled(fields.entityId))
		{
			result.entityId = fields.entityId;
		}

		if (Type.isStringFilled(fields.entityType))
		{
			result.entityType = fields.entityType;
		}

		if (Type.isBoolean(fields.extranet))
		{
			result.extranet = fields.extranet;
		}

		if (Type.isNumber(fields.id))
		{
			result.id = fields.id;
		}

		if (Type.isBoolean(fields.isNew))
		{
			result.isNew = fields.isNew;
		}

		if (Type.isStringFilled(fields.name))
		{
			result.name = fields.name;
		}

		if (Type.isNumber(fields.owner))
		{
			result.owner = fields.owner;
		}

		if (Type.isNumber(fields.parentChatId))
		{
			result.parentChatId = fields.parentChatId;
		}

		if (Type.isNumber(fields.parentMessageId))
		{
			result.parentMessageId = fields.parentMessageId;
		}

		if (Type.isStringFilled(fields.role))
		{
			result.role = fields.role;
		}

		if (Type.isStringFilled(fields.type))
		{
			result.type = fields.type;
		}

		return result;
	}

	module.exports = { validate };
});
