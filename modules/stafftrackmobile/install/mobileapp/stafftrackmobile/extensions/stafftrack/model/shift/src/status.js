/**
 * @module stafftrack/model/shift/status
 */
jn.define('stafftrack/model/shift/status', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class StatusEnum
	 */
	class StatusEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static WORKING = new StatusEnum('WORKING', 1);
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static NOT_WORKING = new StatusEnum('NOT_WORKING', 2);
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static CANCEL_WORKING = new StatusEnum('CANCEL_WORKING', 3);
	}

	module.exports = { StatusEnum };
});
