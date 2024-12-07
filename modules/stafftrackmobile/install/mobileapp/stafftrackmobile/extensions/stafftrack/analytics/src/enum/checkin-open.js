/**
 * @module stafftrack/analytics/enum/checkin-open
 */
jn.define('stafftrack/analytics/enum/checkin-open', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class CheckinOpenEnum
	 */
	class CheckinOpenEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static AVA_MENU = new CheckinOpenEnum('AVA_MENU', 'ava_menu');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static CHAT = new CheckinOpenEnum('CHAT', 'chat');
	}

	module.exports = { CheckinOpenEnum };
});