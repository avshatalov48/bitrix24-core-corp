/**
 * @module intranet/statemanager/redux/slices/employees
 */
jn.define('intranet/statemanager/redux/slices/employees', (require, exports, module) => {
	const { sliceName, userListAdapter } = require('intranet/statemanager/redux/slices/employees/meta');
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');
	const {
		confirmUserRequest,
		deleteInvitation,
		fireEmployee,
		hireEmployee,
		reinvite,
		reinviteWithChangeContact,
		changeDepartment,
	} = require('intranet/statemanager/redux/slices/employees/thunk');
	const {
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
		reinviteRejected,
		changeDepartmentPending,
	} = require('intranet/statemanager/redux/slices/employees/extra-reducer');
	const { IntranetUserModel } = require('intranet/statemanager/redux/slices/employees/model/user');

	const userListSlice = createSlice({
		name: sliceName,
		initialState: userListAdapter.getInitialState(),
		reducers: {
			usersUpserted: {
				reducer: userListAdapter.upsertMany,
				prepare: (users) => ({
					payload: users.map((user) => IntranetUserModel.prepareReduxUserFromServerUser(user)),
				}),
			},
			usersAdded: {
				reducer: userListAdapter.addMany,
				prepare: (users) => ({
					payload: users.map((user) => IntranetUserModel.prepareReduxUserFromServerUser(user)),
				}),
			},
		},
		extraReducers: (builder) => {
			builder
				.addCase(reinvite.pending, reinvitePending)
				.addCase(reinvite.rejected, reinviteRejected)
				.addCase(reinviteWithChangeContact.pending, reinvitePending)
				.addCase(reinviteWithChangeContact.rejected, reinviteRejected)
				.addCase(deleteInvitation.pending, deleteInvitationPending)
				.addCase(deleteInvitation.fulfilled, deleteInvitationFulfilled)
				.addCase(deleteInvitation.rejected, deleteInvitationRejected)
				.addCase(fireEmployee.pending, fireEmployeePending)
				.addCase(fireEmployee.fulfilled, fireEmployeeFulfilled)
				.addCase(fireEmployee.rejected, fireEmployeeRejected)
				.addCase(hireEmployee.pending, hireEmployeePending)
				.addCase(hireEmployee.fulfilled, hireEmployeeFulfilled)
				.addCase(hireEmployee.rejected, hireEmployeeRejected)
				.addCase(confirmUserRequest.pending, confirmUserRequestPending)
				.addCase(confirmUserRequest.fulfilled, confirmUserRequestFulfilled)
				.addCase(confirmUserRequest.rejected, confirmUserRequestRejected)
				.addCase(changeDepartment.pending, changeDepartmentPending);
		},
	});

	const { reducer: userListReducer, actions } = userListSlice;
	const {
		usersUpserted,
		usersAdded,
	} = actions;

	ReducerRegistry.register(sliceName, userListReducer);

	module.exports = {
		usersUpserted,
		usersAdded,
	};
});
