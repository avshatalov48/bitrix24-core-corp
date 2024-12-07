/**
 * @module stafftrack/analytics/enum/chat-bool
 */
jn.define('stafftrack/analytics/enum/chat-bool', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class ChatBoolEnum
	 */
	class ChatBoolEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static CHAT_Y = new ChatBoolEnum('CHAT_Y', 'chat_Y');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static CHAT_N = new ChatBoolEnum('CHAT_N', 'chat_N');
	}

	module.exports = { ChatBoolEnum };
});