/**
 * @module testing/tests/redux/optimistic-ui/playground-slice-users/src/thunk
 */
jn.define('testing/tests/redux/optimistic-ui/playground-slice-users/src/thunk', (require, exports, module) => {
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { sliceName } = require('testing/tests/redux/optimistic-ui/playground-slice-users/meta');
	const { isOnline } = require('device/connection');
	const { createThunkSettings } = require('statemanager/redux/optimistic-ui');

	const condition = () => isOnline();

	const timer = (ms) => new Promise((resolve) => {
		setTimeout(resolve, ms);
	});

	const createOne = createAsyncThunk(
		`${sliceName}/create`,
		async ({
			id,
			user,
			imitateSuccess,
			toAddTestField = true,
		}, { rejectWithValue }) => {
			await timer(100);

			if (imitateSuccess)
			{
				const userFromServer = {
					id,
					changes: {
						...user,
						id: `${id}-from-server`,
					},
				};

				if (toAddTestField)
				{
					userFromServer.changes.testFieldFromServer = 'test';
				}

				return {
					status: 'success',
					entitiesFromServer: [userFromServer],
				};
			}

			return rejectWithValue({
				status: 'error',
				errors: [{ message: 'Some server error' }],
			});
		},
		{
			condition,
			/* getPendingMeta: () => {
				return createThunkSettings({
					toUpdateEntityWithErrors: false,
					disable: false,
				});
			}, */
		},
	);

	const updateOne = createAsyncThunk(
		`${sliceName}/update`,
		async ({ id, changes, imitateSuccess }, { rejectWithValue }) => {
			await timer(100);

			if (imitateSuccess)
			{
				/* const userFromServer = {
					id,
					changes: {
						testFieldFromServer: 'test',
					},
				}; */

				return {
					status: 'success',
					// entitiesFromServer: [userFromServer],
				};
			}

			return rejectWithValue({
				status: 'error',
				errors: [{ message: 'Some server error' }],
			});
		},
		{
			condition,
			getPendingMeta: () => {
				return createThunkSettings({
					toUpdateEntityWithErrors: false,
				});
			},
		},
	);

	const updateMany = createAsyncThunk(
		`${sliceName}/updateMany`,
		async ({ changedItems, imitateSuccess }, { rejectWithValue }) => {
			await timer(100);

			if (imitateSuccess)
			{
				/* const usersFromServer = [];
				changedItems.forEach(({ id, changes }) => {
					usersFromServer.push({
						id,
						changes: {
							...changes,
							testFieldFromServer: 'test',
						},
					});
				}); */

				return {
					status: 'success',
					// entitiesFromServer: usersFromServer,
				};
			}

			return rejectWithValue({
				status: 'error',
				errors: [{ message: 'Some server error' }],
			});
		},
		{
			condition,
			getPendingMeta: () => {
				return createThunkSettings({
					toUpdateEntityWithErrors: false,
				});
			},
		},
	);

	const deleteOne = createAsyncThunk(
		`${sliceName}/delete`,
		async ({
			id,
			imitateSuccess,
		}, { rejectWithValue }) => {
			await timer(100);

			if (imitateSuccess)
			{
				return {
					status: 'success',
				};
			}

			return rejectWithValue({
				status: 'error',
				errors: [{ message: 'Some server error' }],
			});
		},
		{
			condition,
			/* getPendingMeta: () => {
				return createThunkSettings({
					toUpdateEntityWithErrors: false,
					disable: true,
				});
			}, */
		},
	);

	const updateCustomData = createAsyncThunk(
		`${sliceName}/updateCustomData`,
		async ({ id, customData, imitateSuccess }, { rejectWithValue }) => {
			await timer(100);
			if (imitateSuccess)
			{
				return {
					status: 'success',
				};
			}

			return rejectWithValue({
				status: 'error',
				errors: [{ message: 'Some server error' }],
			});
		},
		{
			condition,
		},
	);

	module.exports = {
		createOne,
		updateOne,
		updateMany,
		deleteOne,
		updateCustomData,
	};
});
