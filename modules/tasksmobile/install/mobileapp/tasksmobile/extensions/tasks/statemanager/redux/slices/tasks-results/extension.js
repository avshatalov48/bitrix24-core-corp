/**
 * @module tasks/statemanager/redux/slices/tasks-results
 */
jn.define('tasks/statemanager/redux/slices/tasks-results', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');

	const {
		sliceName,
		tasksResultsAdapter,
		initialState,
	} = require('tasks/statemanager/redux/slices/tasks-results/meta');
	const {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,
		selectByTaskId,
		selectIdsByTaskId,
		selectLastResult,
	} = require('tasks/statemanager/redux/slices/tasks-results/selector');
	const { fetch, create, update, remove } = require('tasks/statemanager/redux/slices/tasks-results/thunk');
	const {
		fetchFulfilled,
		createFulfilled,
		updateFulFilled,
		removeFulfilled,
	} = require('tasks/statemanager/redux/slices/tasks-results/extra-reducer');

	const tasksResultsSlice = createSlice({
		initialState,
		name: sliceName,
		reducers: {
			setFromServer: (state, { payload }) => {
				const { taskId, results } = payload;
				const taskResultsIds = (
					Object.values(state.entities).filter((item) => item.taskId === taskId).map((item) => item.id)
				);
				tasksResultsAdapter.removeMany(state, taskResultsIds);
				tasksResultsAdapter.addMany(state, results);
			},
		},
		extraReducers: (builder) => {
			builder
				.addCase(fetch.fulfilled, fetchFulfilled)
				.addCase(create.fulfilled, createFulfilled)
				.addCase(update.fulfilled, updateFulFilled)
				.addCase(remove.fulfilled, removeFulfilled)
			;
		},
	});

	const { reducer: tasksResultsReducer, actions } = tasksResultsSlice;
	const { setFromServer } = actions;

	ReducerRegistry.register(sliceName, tasksResultsReducer);

	module.exports = {
		tasksResultsReducer,

		setFromServer,

		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,
		selectByTaskId,
		selectIdsByTaskId,
		selectLastResult,

		fetch,
		create,
		update,
		remove,
	};
});
