/**
 * @module intranet/simple-list/items/user-redux/user-enum
 */
jn.define('intranet/simple-list/items/user-redux/user-enum', (require, exports, module) => {
	class UserEnum
	{
		static get employeeStatus()
		{
			return {
				INVITED: 1,
				INVITE_AWAITING_APPROVE: 2,
				ACTIVE: 3,
				FIRED: 4,
			};
		}

		static get actions()
		{
			return {
				deleteInvitation: 'delete_invitation_action',
				fire: 'fire_action',
				hire: 'hire_action',
				reinvite: 'reinvite_action',
				confirmUserRequest: 'confirm_user_request',
				declineUserRequest: 'decline_user_request',
				changeDepartment: 'change_department',
			};
		}

		static get requestStatus()
		{
			return {
				Idle: 'Idle',
				Pending: 'Pending',
				Fulfilled: 'Fulfilled',
				Rejected: 'Rejected',
			};
		}
	}
	module.exports = { UserEnum };
});
