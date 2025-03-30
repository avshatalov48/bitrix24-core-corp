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
		static ILLNESS = new CancelReasonEnum('ILLNESS', 'STAFFTRACK_CANCEL_REASON_ILLNESS');
		static SICK_LEAVE = new CancelReasonEnum('SICK_LEAVE', 'STAFFTRACK_CANCEL_REASON_SICK_LEAVE');
		static TIME_OFF = new CancelReasonEnum('TIME_OFF', 'STAFFTRACK_CANCEL_REASON_TIME_OFF');
		static VACATION = new CancelReasonEnum('VACATION', 'STAFFTRACK_CANCEL_REASON_VACATION');
		static CUSTOM = new CancelReasonEnum('CUSTOM', 'STAFFTRACK_CANCEL_REASON_CUSTOM');
	}

	module.exports = { CancelReasonEnum };
});
