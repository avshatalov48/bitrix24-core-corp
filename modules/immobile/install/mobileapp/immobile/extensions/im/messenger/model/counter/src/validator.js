/**
 * @module im/messenger/model/counter/validator
 */
jn.define('im/messenger/model/counter/validator', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @param {Partial<CounterModelState>} fields
	 */
	function validate(fields)
	{
		const result = {};

		if (Type.isNumber(fields.chatId))
		{
			result.chatId = fields.chatId;
		}

		if (Type.isNumber(fields.parentChatId))
		{
			result.parentChatId = fields.parentChatId;
		}

		if (Type.isStringFilled(fields.type))
		{
			result.type = fields.type;
		}

		if (Type.isNumber(fields.counter))
		{
			result.counter = fields.counter;
		}

		if (Type.isBoolean(fields.locked))
		{
			result.locked = fields.locked;
		}

		if (Type.isBoolean(fields.disabled))
		{
			result.disabled = fields.disabled;
		}

		return result;
	}

	module.exports = { validate };
});
