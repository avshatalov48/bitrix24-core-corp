/**
 * @module intranet/statemanager/redux/slices/department
 */
jn.define('intranet/statemanager/redux/slices/department', (require, exports, module) => {
	const { sliceName, departmentListAdapter } = require('intranet/statemanager/redux/slices/department/meta');
	const { StateCache } = require('statemanager/redux/state-cache');
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');
	const { DepartmentDataConverter } = require('intranet/statemanager/redux/slices/department/model/department');
	const { fetchParentDepartmentsThunk } = require('intranet/statemanager/redux/slices/department/thunk');
	const { Type } = require('type');

	const initialState = StateCache.getReducerState(sliceName, departmentListAdapter.getInitialState());

	const departmentListSlice = createSlice({
		name: sliceName,
		initialState,
		reducers: {
			departmentsUpserted: {
				reducer: departmentListAdapter.upsertMany,
			},
			departmentsAdded: {
				reducer: departmentListAdapter.addMany,
			},
		},
		extraReducers: (builder) => {
			builder
				.addCase(fetchParentDepartmentsThunk.fulfilled, (state, action) => {
					const {
						isSuccess,
						departments,
					} = action.payload;
					if (isSuccess && Type.isArrayFilled(departments))
					{
						departmentListAdapter.upsertMany(state, departments);
					}
				});
		},
	});

	const { reducer: departmentListReducer, actions } = departmentListSlice;
	const {
		departmentsUpserted,
		departmentsAdded,
	} = actions;

	const {
		selectById,
		selectEntities,
		selectAll,
	} = departmentListAdapter.getSelectors((state) => state[sliceName]);

	ReducerRegistry.register(sliceName, departmentListReducer);

	module.exports = {
		departmentsUpserted,
		departmentsAdded,

		selectById,
		selectEntities,
		selectAll,

		DepartmentDataConverter,
		fetchParentDepartmentsThunk,
	};
});
