/**
 * @module stafftrack/analytics/enum/helpdesk
 */
jn.define('stafftrack/analytics/enum/helpdesk', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class HelpdeskEnum
	 */
	class HelpdeskEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static AVA_MENU = new HelpdeskEnum('AVA_MENU', 'ava_menu');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static CHAT = new HelpdeskEnum('CHAT', 'chat');
	}

	module.exports = { HelpdeskEnum };
});