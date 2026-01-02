/**
 * @module im/messenger/model/sidebar/src/files/validator
 */

jn.define('im/messenger/model/sidebar/src/files/validator', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @param {SidebarFile} fields
	 */
	function validate(fields)
	{
		const result = {};

		if (Type.isNumber(fields.id))
		{
			result.id = fields.id;
		}

		if (Type.isNumber(fields.messageId))
		{
			result.messageId = fields.messageId;
		}

		if (Type.isNumber(fields.chatId))
		{
			result.chatId = fields.chatId;
		}

		if (Type.isNumber(fields.authorId))
		{
			result.authorId = fields.authorId;
		}

		if (Type.isDate(fields.dateCreate) || Type.isString(fields.dateCreate))
		{
			result.dateCreate = fields.dateCreate;
		}

		if (Type.isNumber(fields.fileId))
		{
			result.fileId = fields.fileId;
		}

		return result;
	}

	module.exports = { validate };
});
