/**
 * @module stafftrack/model/shift/cancel-reason
 */
jn.define('stafftrack/model/shift/cancel-reason', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class CancelReasonEnum
	 */
	class CancelReasonEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static ILLNESS = new CancelReasonEnum('ILLNESS', 'STAFFTRACK_CANCEL_REASON_ILLNESS');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static SICK_LEAVE = new CancelReasonEnum('SICK_LEAVE', 'STAFFTRACK_CANCEL_REASON_SICK_LEAVE');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static TIME_OFF = new CancelReasonEnum('TIME_OFF', 'STAFFTRACK_CANCEL_REASON_TIME_OFF');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static VACATION = new CancelReasonEnum('VACATION', 'STAFFTRACK_CANCEL_REASON_VACATION');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static CUSTOM = new CancelReasonEnum('CUSTOM', 'STAFFTRACK_CANCEL_REASON_CUSTOM');
	}

	module.exports = { CancelReasonEnum };
});
