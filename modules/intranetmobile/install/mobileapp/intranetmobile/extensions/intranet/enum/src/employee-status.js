/**
 * @module intranet/enum/employee-status
 */
jn.define('intranet/enum/employee-status', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class EmployeeStatus
	 */
	class EmployeeStatus extends BaseEnum
	{
		static NOT_REGISTERED = new EmployeeStatus('NOT_REGISTERED', 0);
		static INVITED = new EmployeeStatus('INVITED', 1);
		static INVITE_AWAITING_APPROVE = new EmployeeStatus('INVITE_AWAITING_APPROVE', 2);
		static ACTIVE = new EmployeeStatus('ACTIVE', 3);
		static FIRED = new EmployeeStatus('FIRED', 4);
	}

	module.exports = { EmployeeStatus };
});
