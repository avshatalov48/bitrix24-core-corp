/**
 * @module tasks/statemanager/redux/slices/kanban-settings/src/slice
 */
jn.define('tasks/statemanager/redux/slices/kanban-settings/src/slice', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');

	const {
		fetchStages,
		updateStagesOrder,
	} = require('tasks/statemanager/redux/slices/kanban-settings/thunk');

	const {
		sliceName,
		adapter,
	} = require('tasks/statemanager/redux/slices/kanban-settings/meta');

	const {
		fetchStagesPending,
		fetchStagesFulfilled,
		fetchStagesRejected,
		updateStagesOrderFulfilled,
		updateStagesOrderRejected,
		addStageFulfilled,
		deleteStageFulfilled,
	} = require('tasks/statemanager/redux/slices/kanban-settings/src/extra-reducer');

	const {
		addStage,
		deleteStage,
	} = require('tasks/statemanager/redux/slices/stage-settings/thunk');

	function getExtraReducers()
	{
		return (builder) => {
			builder
				.addCase(fetchStages.pending, fetchStagesPending)
				.addCase(fetchStages.fulfilled, fetchStagesFulfilled)
				.addCase(fetchStages.rejected, fetchStagesRejected)
				.addCase(updateStagesOrder.fulfilled, updateStagesOrderFulfilled)
				.addCase(updateStagesOrder.rejected, updateStagesOrderRejected)
				.addCase(addStage.fulfilled, addStageFulfilled)
				.addCase(deleteStage.fulfilled, deleteStageFulfilled);
		};
	}

	const initialState = adapter.getInitialState();
	const filledState = adapter.upsertMany(initialState, []);
	const slice = createSlice({
		name: sliceName,
		initialState: filledState,
		reducers: {},
		extraReducers: getExtraReducers(),
	});
	ReducerRegistry.register(sliceName, slice.reducer);

	module.exports = {
		slice,
	};
});
