/**
 * @module intranet/statemanager/redux/slices/employees/extra-reducer
 */
jn.define('intranet/statemanager/redux/slices/employees/extra-reducer', (require, exports, module) => {
	const { Loc } = require('loc');
	const { userListAdapter } = require('intranet/statemanager/redux/slices/employees/meta');
	const { EmployeeStatus, RequestStatus, EmployeeActions } = require('intranet/enum');
	const { showErrorToast, showSafeToast, Position } = require('toast');
	const { check, cross } = require('assets/icons/src/outline');
	const { Actions } = require('intranet/simple-list/items/user-redux/src/actions');

	const deleteInvitationPending = (state, action) => {
		setActionRequestStatus(state, action, RequestStatus.PENDING);
	};

	const deleteInvitationFulfilled = (state, action) => {
		const { userId } = action.meta.arg;

		userListAdapter.removeOne(state, userId);
	};

	const deleteInvitationRejected = (state, action) => {
		const { userId } = action.meta.arg;

		const user = state.entities[userId];
		userListAdapter.upsertOne(state, {
			...user,
			requestStatus: RequestStatus.REJECTED.getValue(),
		});

		setTimeout(() => {
			Actions.list[EmployeeActions.FIRE.getValue()]({ userId }, Loc.getMessage('MOBILE_USERS_USER_ACTIONS_DELETE_INVITATION_REJECTED'));
		}, 300);
	};

	const fireEmployeePending = (state, action) => {
		const { userId } = action.meta.arg;
		const user = state.entities[userId];

		userListAdapter.upsertOne(state, {
			...user,
			employeeStatus: EmployeeStatus.FIRED.getValue(),
			requestStatus: RequestStatus.PENDING.getValue(),
		});
	};

	const fireEmployeeFulfilled = (state, action) => {
		setActionRequestStatus(state, action, RequestStatus.FULFILLED);
	};

	const fireEmployeeRejected = (state, action) => {
		setActionRequestStatus(state, action, RequestStatus.REJECTED);
	};

	const hireEmployeePending = (state, action) => {
		const { userId } = action.meta.arg;
		const user = state.entities[userId];

		userListAdapter.upsertOne(state, {
			...user,
			employeeStatus: EmployeeStatus.ACTIVE.getValue(),
			requestStatus: RequestStatus.PENDING.getValue(),
		});
	};

	const hireEmployeeFulfilled = (state, action) => {
		setActionRequestStatus(state, action, RequestStatus.FULFILLED);
	};

	const hireEmployeeRejected = (state, action) => {
		setActionRequestStatus(state, action, RequestStatus.REJECTED);
	};

	const confirmUserRequestPending = (state, action) => {
		const { isAccept } = action.meta.arg;

		if (isAccept)
		{
			showCustomizedToast(
				Loc.getMessage('MOBILE_USERS_USER_ACTIONS_ACCEPT_REQUEST'),
				check(),
			);
		}
		else
		{
			setActionRequestStatus(state, action, RequestStatus.PENDING);
		}
	};

	const confirmUserRequestFulfilled = (state, action) => {
		const { isAccept, userId } = action.meta.arg;
		const user = state.entities[userId];

		if (isAccept)
		{
			userListAdapter.upsertOne(state, {
				...user,
				employeeStatus: EmployeeStatus.ACTIVE.getValue(),
				requestStatus: RequestStatus.FULFILLED.getValue(),
			});
		}
		else
		{
			userListAdapter.removeOne(state, userId);
		}
	};

	const confirmUserRequestRejected = (state, action) => {
		const { userId, isAccept } = action.meta.arg;

		const message = isAccept
			? Loc.getMessage('MOBILE_USERS_EXTRA_REDUCERS_CONFIRM_INVITATION_REJECTED')
			: Loc.getMessage('MOBILE_USERS_EXTRA_REDUCERS_USER_DELETE_REJECTED');
		const svg = isAccept ? check() : cross();

		showCustomizedToast(message, svg);

		const user = state.entities[userId];
		userListAdapter.upsertOne(state, {
			...user,
			requestStatus: RequestStatus.REJECTED.getValue(),
		});

		if (isAccept)
		{
			return;
		}

		setTimeout(() => {
			Actions.list[EmployeeActions.FIRE.getValue()]({ userId });
		}, 300);
	};

	const reinvitePending = (state, action) => {
		const { userId } = action.meta.arg;
		const user = state.entities[userId];
		if (user)
		{
			userListAdapter.upsertOne(state, {
				...user,
				lastInvitationTimestamp: Date.now(),
			});
		}
	};

	const reinviteRejected = (state, action) => {
		const { userId } = action.meta.arg;
		const { errors } = action.payload;
		if (errors && errors.length > 0)
		{
			showErrorToast(errors[0]);
		}
		const user = state.entities[userId];
		if (user)
		{
			userListAdapter.upsertOne(state, {
				...user,
				lastInvitationTimestamp: null,
			});
		}
	};

	const changeDepartmentPending = (state, action) => {
		const { userId, departments } = action.meta.arg;
		const user = state.entities[userId];

		userListAdapter.upsertOne(state, {
			...user,
			department: departments,
			requestStatus: RequestStatus.FULFILLED.getValue(),
		});
	};

	const setActionRequestStatus = (state, action, requestStatus) => {
		const { userId } = action.meta.arg;

		const user = state.entities[userId];
		userListAdapter.upsertOne(state, {
			...user,
			requestStatus: requestStatus.getValue(),
		});
	};

	const showCustomizedToast = (message, svgContent) => {
		showSafeToast({
			...Actions.getToastParams(),
			message,
			svg: { content: svgContent },
			position: Position.BOTTOM,
			time: 1.5,
			offset: 60,
		}, layout);
	};

	module.exports = {
		deleteInvitationPending,
		deleteInvitationFulfilled,
		deleteInvitationRejected,
		fireEmployeePending,
		fireEmployeeFulfilled,
		fireEmployeeRejected,
		hireEmployeePending,
		hireEmployeeFulfilled,
		hireEmployeeRejected,
		confirmUserRequestPending,
		confirmUserRequestFulfilled,
		confirmUserRequestRejected,
		reinvitePending,
		changeDepartmentPending,
		reinviteRejected,
	};
});
