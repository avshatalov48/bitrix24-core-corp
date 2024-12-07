/**
 * @module tasks/statemanager/redux/slices/stage-settings/src/slice
 */
jn.define('tasks/statemanager/redux/slices/stage-settings/src/slice', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');
	const { sliceName, initialState } = require('tasks/statemanager/redux/slices/stage-settings/meta');

	const {
		fetchStagesPending,
		fetchStagesFulfilled,
		addStagePending,
		addStageFulfilled,
		addStageRejected,
		updateStagePending,
		updateStageFulfilled,
		updateStageRejected,
		deleteStagePending,
		deleteStageFulfilled,
		deleteStageRejected,
		setKanbanSettings: setKanbanSettingsReducer,
		updateTaskFulfilled,
	} = require('tasks/statemanager/redux/slices/stage-settings/src/extra-reducer');
	const { update } = require('tasks/statemanager/redux/slices/tasks/thunk');

	const {
		addStage,
		updateStage,
		deleteStage,
	} = require('tasks/statemanager/redux/slices/stage-settings/thunk');

	const { fetchStages } = require('tasks/statemanager/redux/slices/kanban-settings/thunk');
	const { setKanbanSettingsActionName } = require('tasks/statemanager/redux/slices/kanban-settings/action');

	function getExtraReducers()
	{
		return (builder) => {
			builder.addCase(fetchStages.pending, fetchStagesPending);
			builder.addCase(fetchStages.fulfilled, fetchStagesFulfilled)
				.addCase(addStage.pending, addStagePending)
				.addCase(addStage.fulfilled, addStageFulfilled)
				.addCase(addStage.rejected, addStageRejected)
				.addCase(updateStage.pending, updateStagePending)
				.addCase(updateStage.fulfilled, updateStageFulfilled)
				.addCase(updateStage.rejected, updateStageRejected)
				.addCase(deleteStage.pending, deleteStagePending)
				.addCase(deleteStage.fulfilled, deleteStageFulfilled)
				.addCase(deleteStage.rejected, deleteStageRejected)
				.addCase(setKanbanSettingsActionName, setKanbanSettingsReducer)
				.addCase(update.fulfilled, updateTaskFulfilled);
		};
	}

	const slice = createSlice({
		name: sliceName,
		initialState,
		reducers: {},
		extraReducers: getExtraReducers(),
	});
	ReducerRegistry.register(sliceName, slice.reducer);

	module.exports = {
		slice,
	};
});
