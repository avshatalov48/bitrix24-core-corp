/**
 * @module intranet/enum/employee-actions
 */
jn.define('intranet/enum/employee-actions', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class EmployeeActions
	 */
	class EmployeeActions extends BaseEnum
	{
		static DELETE_INVITATION = new EmployeeActions('DELETE_INVITATION', 'delete_invitation_action');
		static FIRE = new EmployeeActions('FIRE', 'fire_action');
		static HIRE = new EmployeeActions('HIRE', 'hire_action');
		static REINVITE = new EmployeeActions('REINVITE', 'reinvite_action');
		static REINVITE_WITH_CHANGE_CONTACT = new EmployeeActions('REINVITE_WITH_CHANGE_CONTACT', 'reinvite_with_change_contact_action');
		static CHANGE_PHONE = new EmployeeActions('CHANGE_PHONE', 'change_phone_action');
		static CHANGE_EMAIL = new EmployeeActions('CHANGE_EMAIL', 'change_email_action');
		static CONFIRM_USER_REQUEST = new EmployeeActions('CONFIRM_USER_REQUEST', 'confirm_user_request');
		static DECLINE_USER_REQUEST = new EmployeeActions('DECLINE_USER_REQUEST', 'decline_user_request');
		static CHANGE_DEPARTMENT = new EmployeeActions('CHANGE_DEPARTMENT', 'change_department');
	}

	module.exports = { EmployeeActions };
});
