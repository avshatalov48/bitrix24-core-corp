/* eslint-disable no-param-reassign */
/**
 * @module tasks/statemanager/redux/slices/tasks-stages/extra-reducers
 */
jn.define('tasks/statemanager/redux/slices/tasks-stages/extra-reducers', (require, exports, module) => {
	const { statusTypes, Views } = require('tasks/statemanager/redux/types');
	const { entityAdapter, selectId } = require('tasks/statemanager/redux/slices/tasks-stages/meta');
	const { getStageByDeadline } = require('tasks/utils/stages');

	const setTaskStage = (state, action) => {
		const { taskId, viewMode, userId, nextStageId: stageId } = action.payload;

		entityAdapter.upsertOne(state, {
			taskId,
			viewMode,
			userId,
			stageId,
		});
	};

	const updateTaskStagePending = (state, action) => {
		state.status = statusTypes.pending;
		const { taskId, view, userId, stageId } = action.meta.arg;

		const id = selectId({
			taskId,
			viewMode: view,
			userId,
		});

		const prevTaskStage = entityAdapter.getSelectors().selectById(state, id);
		if (prevTaskStage)
		{
			state.prevTaskStage = { ...prevTaskStage };

			entityAdapter.upsertOne(state, {
				taskId,
				viewMode: view,
				userId,
				stageId,
				canMoveStage: true,
			});
		}
	};

	const onUpdateTaskStageRejected = (state) => {
		state.status = statusTypes.failure;
		const { prevTaskStage } = state;

		if (prevTaskStage)
		{
			entityAdapter.upsertOne(state, prevTaskStage);
		}

		state.prevTaskStage = null;
	};

	const updateTaskStageFulfilled = (state, action) => {
		const { data } = action.payload;

		if (data === true)
		{
			state.status = statusTypes.success;
		}
		else
		{
			state.status = statusTypes.failure;

			const { prevTaskStage } = state;
			if (prevTaskStage)
			{
				entityAdapter.upsertOne(state, prevTaskStage);
			}
		}

		state.prevTaskStage = null;
	};

	const updateDeadlinePending = (state, action) => {
		const {
			taskId,
			userId,
			deadline,
		} = action.meta.arg;

		const {
			stages,
		} = action.meta;

		const ts = Number.isInteger(deadline) ? deadline : null;
		const nextStage = getStageByDeadline(ts, stages);

		const id = selectId({
			taskId,
			viewMode: Views.DEADLINE,
			userId,
		});

		const prevTaskStage = entityAdapter.getSelectors().selectById(state, id);

		if (prevTaskStage && Number.isInteger(nextStage?.id))
		{
			state.prevTaskStage = { ...prevTaskStage };

			entityAdapter.upsertOne(state, {
				...prevTaskStage,
				stageId: nextStage.id,
			});
		}
	};

	const updateTaskDeadlineFulfilled = (state, action) => {
		const { status } = action.payload;
		const { payload } = action;

		if (status !== statusTypes.success || !payload?.data?.isSuccess)
		{
			const { prevTaskStage } = state;
			if (prevTaskStage)
			{
				entityAdapter.upsertOne(state, prevTaskStage);
			}
		}

		state.prevTaskStage = null;
	};

	const updateTaskPending = (state, action) => {
		const {
			taskId,
			reduxFields,
			userId,
		} = action.meta.arg;

		if (Number.isInteger(reduxFields?.groupId))
		{
			const entity = selectId({
				taskId,
				viewMode: Views.KANBAN,
				userId,
			});
			state.prevTaskStage = state.entities[entity];
			entityAdapter.removeOne(state, entity);
		}
	};

	const updateTaskFulfilled = (state, action) => {
		const { data, status } = action.payload;
		const {
			taskId,
			reduxFields,
			userId,
		} = action.meta.arg;

		if (Number.isInteger(reduxFields?.groupId) && status === statusTypes.success)
		{
			if (Number.isInteger(data?.stageId))
			{
				entityAdapter.upsertOne(state, {
					taskId,
					viewMode: Views.KANBAN,
					userId,
					stageId: data?.stageId,
					canMoveStage: data?.kanban?.canMoveStage,
				});
			}
			else
			{
				const entity = selectId({
					taskId,
					viewMode: Views.KANBAN,
					userId,
				});
				entityAdapter.removeOne(state, entity);
			}
		}
		else
		{
			entityAdapter.upsertOne(state, state.prevTaskStage);
		}

		state.prevTaskStage = null;
	};

	const updateTaskRejected = (state) => {
		state.status = statusTypes.failure;
		if (state.prevTaskStage)
		{
			entityAdapter.upsertOne(state, state.prevTaskStage);
		}

		state.prevTaskStage = null;
	};

	module.exports = {
		setTaskStage,
		onUpdateTaskStageRejected,
		updateTaskStagePending,
		updateTaskStageFulfilled,
		updateDeadlinePending,
		updateTaskDeadlineFulfilled,
		updateTaskPending,
		updateTaskFulfilled,
		updateTaskRejected,
	};
});
