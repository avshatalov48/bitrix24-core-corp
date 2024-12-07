/**
 * @module intranet/enum
 */
jn.define('intranet/enum', (require, exports, module) => {
	const { EmployeeActions } = require('intranet/enum/employee-actions');
	const { EmployeeStatus } = require('intranet/enum/employee-status');
	const { RequestStatus } = require('intranet/enum/request-status');

	module.exports = { EmployeeActions, EmployeeStatus, RequestStatus };
});
