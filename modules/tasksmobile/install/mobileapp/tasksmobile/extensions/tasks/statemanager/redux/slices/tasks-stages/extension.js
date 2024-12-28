/**
 * @module tasks/statemanager/redux/slices/tasks-stages
 */
jn.define('tasks/statemanager/redux/slices/tasks-stages', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice, createAction } = require('statemanager/redux/toolkit');

	const {
		sliceName,
		selectId,
		entityAdapter,
		initialState,
	} = require('tasks/statemanager/redux/slices/tasks-stages/meta');
	const { updateTaskStage } = require('tasks/statemanager/redux/slices/tasks-stages/thunk');
	const {
		setTaskStage: setTaskStageReducer,
		updateTaskStagePending,
		onUpdateTaskStageRejected,
		updateTaskStageFulfilled,
		updateDeadlinePending,
		updateTaskDeadlineFulfilled,
		updateTaskPending,
		updateTaskFulfilled,
		updateTaskRejected,
	} = require('tasks/statemanager/redux/slices/tasks-stages/extra-reducers');
	const { updateDeadline, update } = require('tasks/statemanager/redux/slices/tasks/thunk');

	const { selectById } = entityAdapter.getSelectors((state) => state[sliceName]);

	/**
	 * @public
	 * @param {object} state
	 * @param {number} taskId
	 * @param {string} viewMode
	 * @param {number} userId
	 * @return {{ stageId: number, canMoveStage: boolean} | undefined}
	 */
	const selectTaskStage = (state, taskId, viewMode, userId = Number(env.userId)) => {
		const id = selectId({ taskId, viewMode, userId });

		return selectById(state, id);
	};

	const selectTaskStageByTaskIdOrGuid = (state, taskId, taskGuid, viewMode, userId = Number(env.userId)) => {
		return (
			selectTaskStage(state, taskId, viewMode, userId)
			|| selectTaskStage(state, taskGuid, viewMode, userId)
		);
	};

	/**
	 * @public
	 * @param {object} state
	 * @param {number} taskId
	 * @param {string} viewMode
	 * @param {number} userId
	 * @return {number|undefined}
	 */
	const selectTaskStageId = (state, taskId, viewMode, userId = env.userId) => {
		const { stageId } = selectTaskStage(state, taskId, viewMode, userId) || {};

		return stageId;
	};

	const setTaskStage = createAction(`${sliceName}/setTaskStage`);

	const tasksStagesSlice = createSlice({
		name: sliceName,
		initialState,
		reducers: {
			taskStageUpserted: {
				reducer: entityAdapter.upsertMany,
			},
			taskStageAdded: {
				reducer: entityAdapter.addMany,
			},
		},
		extraReducers: (builder) => {
			builder
				.addCase(setTaskStage, setTaskStageReducer)
				.addCase(updateTaskStage.pending, updateTaskStagePending)
				.addCase(updateTaskStage.rejected, onUpdateTaskStageRejected)
				.addCase(updateTaskStage.fulfilled, updateTaskStageFulfilled)
				.addCase(updateDeadline.pending, updateDeadlinePending)
				.addCase(updateDeadline.fulfilled, updateTaskDeadlineFulfilled)
				.addCase(update.pending, updateTaskPending)
				.addCase(update.fulfilled, updateTaskFulfilled)
				.addCase(update.rejected, updateTaskRejected);
		},
	});

	const { taskStageUpserted, taskStageAdded } = tasksStagesSlice.actions;

	ReducerRegistry.register(sliceName, tasksStagesSlice.reducer);

	module.exports = {
		taskStageUpserted,
		taskStageAdded,
		selectTaskStage,
		selectTaskStageByTaskIdOrGuid,
		selectTaskStageId,
		setTaskStage,
		updateTaskStage,
	};
});
