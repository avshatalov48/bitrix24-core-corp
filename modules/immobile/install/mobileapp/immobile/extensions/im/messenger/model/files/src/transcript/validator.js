/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/files/transcript/validator
 */
jn.define('im/messenger/model/files/transcript/validator', (require, exports, module) => {
	const { Type } = require('type');
	const { TranscriptStatus } = require('im/messenger/const');

	/**
	 * @param store
	 * @param fields
	 * @returns {TranscriptModelState}
	 */
	function validate(store, fields)
	{
		const result = {};

		if (Type.isNumber(fields.fileId) || Type.isString(fields.fileId))
		{
			result.fileId = Number(fields.fileId);
		}

		if (Type.isNumber(fields.messageId) || Type.isString(fields.messageId))
		{
			result.messageId = fields.messageId;
		}

		if (Type.isNumber(fields.chatId) || Type.isString(fields.chatId))
		{
			result.chatId = Number(fields.chatId);
		}

		if (Type.isString(fields.text))
		{
			result.text = fields.text.trim();
		}

		if (Type.isString(fields.status) && Object.values(TranscriptStatus).includes(fields.status))
		{
			result.status = fields.status;
		}

		return result;
	}

	module.exports = { validate };
});
