/**
 * @module intranet/statemanager/redux/slices/employees/selector
 */
jn.define('intranet/statemanager/redux/slices/employees/selector', (require, exports, module) => {
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');
	const { sliceName, userListAdapter } = require('intranet/statemanager/redux/slices/employees/meta');
	const { selectById: selectMobileUserById } = require('statemanager/redux/slices/users/selector');
	const { EmployeeStatus, EmployeeActions } = require('intranet/enum');
	const { Moment } = require('utils/date');

	const {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,
	} = userListAdapter.getSelectors((state) => state[sliceName]);

	const selectWholeUserById = createDraftSafeSelector(
		(state, userId) => selectById(state, userId),
		selectMobileUserById,
		(intranetUser, mobileUser) => ({ ...mobileUser, ...intranetUser }),
	);

	const selectUserActions = createDraftSafeSelector(
		(user) => user.actions,
	);

	const selectCanUserBeReinvited = createDraftSafeSelector(
		selectById,
		(user) => {
			if (user)
			{
				const { lastInvitationTimestamp } = user;

				return !Moment.createFromTimestamp((lastInvitationTimestamp ?? 0) / 1000).withinHour;
			}

			return false;
		},
	);

	const selectActions = createDraftSafeSelector(
		(state, { currentUserId }) => selectWholeUserById(state, currentUserId),
		(state, { userId }) => selectWholeUserById(state, userId),
		(state, { userId }) => selectCanUserBeReinvited(state, userId),
		(state, { canInvite }) => canInvite,
		(currentUser, user, canUserBeReinvited, canInvite) => {
			const isNotCurrentUser = currentUser.id !== user.id;
			const isAdmin = currentUser.isAdmin;
			const isInvited = user.employeeStatus === EmployeeStatus.INVITED.getValue();
			const isActive = user.employeeStatus === EmployeeStatus.ACTIVE.getValue();
			const isFired = user.employeeStatus === EmployeeStatus.FIRED.getValue();
			const isAwaitingApproval = user.employeeStatus === EmployeeStatus.INVITE_AWAITING_APPROVE.getValue();
			const isIntranetUser = !user.isExtranet && !user.isCollaber;

			const canReinvite = isNotCurrentUser && canUserBeReinvited && canInvite && isInvited;

			return {
				[EmployeeActions.DELETE_INVITATION.getValue()]: isInvited,
				[EmployeeActions.FIRE.getValue()]: isNotCurrentUser && isAdmin && isActive,
				[EmployeeActions.HIRE.getValue()]: isNotCurrentUser && isAdmin && isFired,
				[EmployeeActions.REINVITE.getValue()]: canReinvite,
				[EmployeeActions.REINVITE_WITH_CHANGE_CONTACT.getValue()]: canReinvite,
				[EmployeeActions.CHANGE_PHONE.getValue()]: canReinvite && user.personalMobile,
				[EmployeeActions.CHANGE_EMAIL.getValue()]: canReinvite && user.email,
				[EmployeeActions.CHANGE_DEPARTMENT.getValue()]: isAdmin && (isInvited || isActive) && isIntranetUser,
				[EmployeeActions.CONFIRM_USER_REQUEST.getValue()]: isNotCurrentUser && isAdmin && isAwaitingApproval,
				[EmployeeActions.DECLINE_USER_REQUEST.getValue()]: isNotCurrentUser && isAdmin && isAwaitingApproval,
			};
		},
	);

	module.exports = {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,

		selectWholeUserById,
		selectUserActions,
		selectActions,
		selectCanUserBeReinvited,
	};
});
