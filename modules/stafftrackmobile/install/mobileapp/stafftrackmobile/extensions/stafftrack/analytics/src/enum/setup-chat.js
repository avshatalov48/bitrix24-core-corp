/**
 * @module stafftrack/analytics/enum/setup-chat
 */
jn.define('stafftrack/analytics/enum/setup-chat', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class SetupChatEnum
	 */
	class SetupChatEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static TURN_ON = new SetupChatEnum('TURN_ON', 'turn_on');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static TURN_OFF = new SetupChatEnum('TURN_OFF', 'turn_off');
	}

	module.exports = { SetupChatEnum };
});