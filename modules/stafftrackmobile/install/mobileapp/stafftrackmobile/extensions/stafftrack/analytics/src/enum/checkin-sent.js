/**
 * @module stafftrack/analytics/enum/checkin-sent
 */
jn.define('stafftrack/analytics/enum/checkin-sent', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class CheckinSentEnum
	 */
	class CheckinSentEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static DONE = new CheckinSentEnum('DONE', 'done');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static CANCELLED = new CheckinSentEnum('CANCELLED', 'cancelled');
	}

	module.exports = { CheckinSentEnum };
});