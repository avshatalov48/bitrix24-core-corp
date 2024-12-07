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

	const { sliceName, initialState } = require('tasks/statemanager/redux/slices/kanban-settings/meta');

	const {
		fetchStagesPending,
		fetchStagesFulfilled,
		fetchStagesRejected,
		updateStagesOrderFulfilled,
		updateStagesOrderRejected,
		addStageFulfilled,
		deleteStageFulfilled,
		updateTaskFulfilled,
	} = require('tasks/statemanager/redux/slices/kanban-settings/src/extra-reducer');

	const { setKanbanSettingsActionName } = require('tasks/statemanager/redux/slices/kanban-settings/action');
	const { setKanbanSettings: setKanbanSettingsReducer } = require('tasks/statemanager/redux/slices/kanban-settings/reducer');
	const { update } = require('tasks/statemanager/redux/slices/tasks/thunk');

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
				.addCase(deleteStage.fulfilled, deleteStageFulfilled)
				.addCase(setKanbanSettingsActionName, setKanbanSettingsReducer)
				.addCase(update.fulfilled, updateTaskFulfilled);
		};
	}

	const slice = createSlice({
		name: sliceName,
		initialState,
		extraReducers: getExtraReducers(),
	});

	ReducerRegistry.register(sliceName, slice.reducer);

	module.exports = {
		slice,
	};
});
