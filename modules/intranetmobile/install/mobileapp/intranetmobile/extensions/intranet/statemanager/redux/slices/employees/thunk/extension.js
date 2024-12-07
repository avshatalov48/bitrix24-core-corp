/**
 * @module intranet/statemanager/redux/slices/employees/thunk
 */
jn.define('intranet/statemanager/redux/slices/employees/thunk', (require, exports, module) => {
	const store = require('statemanager/redux/store');
	const { sliceName } = require('intranet/statemanager/redux/slices/employees/meta');
	const { selectById } = require('intranet/statemanager/redux/slices/employees/selector');
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { isOnline } = require('device/connection');

	const condition = () => isOnline();

	const runActionPromise = ({ action, options }) => new Promise((resolve) => {
		(new RunActionExecutor(action, options)).setHandler(resolve).call(false);
	});

	const runComponentActionPromise = async ({ component, action, options }) => (
		BX.ajax.runComponentAction(component, action, {
			mode: 'class',
			data: options,
		})
	);

	const reinvite = createAsyncThunk(
		`${sliceName}/reinvite`,
		async ({ userId }, { rejectWithValue }) => {
			try
			{
				const { isExtranetUser } = selectById(store.getState(), userId);

				const response = await runActionPromise({
					action: 'intranetmobile.employees.reinvite',
					options: {
						userId,
						isExtranetUser,
					},
				});

				if (response.status === 'success')
				{
					return { ...response.data };
				}

				console.error(response.errors[0].message);

				return rejectWithValue(response);
			}
			catch (error)
			{
				console.error(error);
			}
		},
		{
			condition,
			getPendingMeta: ({ arg }) => ({ arg }),
		},
	);

	const reinviteWithChangeContact = createAsyncThunk(
		`${sliceName}/reinviteWithChangeContact`,
		async ({ userId, email, phone }, { rejectWithValue }) => {
			try
			{
				const response = await runActionPromise({
					action: 'intranet.invite.reinviteWithChangeContact',
					options: {
						userId,
						newEmail: email || null,
						newPhone: phone || null,
					},
				});

				if (response.status === 'success')
				{
					return response.data;
				}

				console.error(response.errors[0].message);

				return rejectWithValue(response);
			}
			catch (error)
			{
				console.error(error);
			}
		},
		{
			condition,
			getPendingMeta: ({ arg }) => ({ arg }),
		},
	);

	const deleteInvitation = createAsyncThunk(
		`${sliceName}/deleteInvitation`,
		({ userId }) => runComponentActionPromise({
			component: 'bitrix:intranet.user.list',
			action: 'setActivity',
			options: {
				params: {
					userId,
					action: 'delete',
				},
			},
		}),
		{ condition },
	);

	const fireEmployee = createAsyncThunk(
		`${sliceName}/fireEmployee`,
		({ userId }) => runComponentActionPromise({
			component: 'bitrix:intranet.user.list',
			action: 'setActivity',
			options: {
				params: {
					userId,
					action: 'deactivate',
				},
			},
		}),
		{ condition },
	);

	const hireEmployee = createAsyncThunk(
		`${sliceName}/hireEmployee`,
		({ userId }) => runComponentActionPromise({
			component: 'bitrix:intranet.user.list',
			action: 'setActivity',
			options: {
				params: {
					userId,
					action: 'restore',
				},
			},
		}),
		{ condition },
	);

	const confirmUserRequest = createAsyncThunk(
		`${sliceName}/confirmUserRequest`,
		({ userId, isAccept }) => runActionPromise({
			action: 'intranet.controller.invite.confirmUserRequest',
			options: {
				userId,
				isAccept: isAccept ? 'Y' : 'N',
			},
		}),
		{ condition },
	);

	const changeDepartment = createAsyncThunk(
		`${sliceName}/changeDepartment`,
		async ({ userId, departments }) => runActionPromise({
			action: 'intranetmobile.employees.updateDepartment',
			options: {
				userId,
				newDepartmentsIds: Object.keys(departments),
			},
		}),
		{ condition },
	);

	module.exports = {
		reinvite,
		reinviteWithChangeContact,
		deleteInvitation,
		fireEmployee,
		hireEmployee,
		confirmUserRequest,
		changeDepartment,
	};
});
