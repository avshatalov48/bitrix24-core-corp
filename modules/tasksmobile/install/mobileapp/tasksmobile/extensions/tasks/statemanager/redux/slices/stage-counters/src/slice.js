/**
 * @module tasks/statemanager/redux/slices/stage-counters/src/slice
 */
jn.define('tasks/statemanager/redux/slices/stage-counters/src/slice', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');
	const { sliceName, initialState } = require('tasks/statemanager/redux/slices/stage-counters/meta');
	const { setTaskStage } = require('tasks/statemanager/redux/slices/tasks-stages');
	const {
		fetchStagesPending,
		fetchStagesFulfilled,
		fetchStagesRejected,
		addStageFulfilled,
		setTaskStageFulfilled,
	} = require('tasks/statemanager/redux/slices/stage-counters/src/extra-reducer');

	const {
		stageCounterIncreased,
		stageCounterDecreased,
	} = require('tasks/statemanager/redux/slices/stage-counters/src/reducer');

	const { fetchStages } = require('tasks/statemanager/redux/slices/kanban-settings/thunk');
	const { addStage } = require('tasks/statemanager/redux/slices/stage-settings/thunk');

	function getExtraReducers()
	{
		return (builder) => {
			builder
				.addCase(fetchStages.pending, fetchStagesPending)
				.addCase(fetchStages.fulfilled, fetchStagesFulfilled)
				.addCase(fetchStages.rejected, fetchStagesRejected)
				.addCase(addStage.fulfilled, addStageFulfilled)
				.addCase(setTaskStage, setTaskStageFulfilled);
		};
	}

	const slice = createSlice({
		name: sliceName,
		initialState,
		reducers: {
			stageCounterIncreased,
			stageCounterDecreased,
		},
		extraReducers: getExtraReducers(),
	});
	ReducerRegistry.register(sliceName, slice.reducer);

	module.exports = {
		slice,
	};
});
