/* eslint-disable no-param-reassign */
/**
 * @module tasks/statemanager/redux/slices/stage-settings/src/extra-reducer
 */
jn.define('tasks/statemanager/redux/slices/stage-settings/src/extra-reducer', (require, exports, module) => {
	const { statusTypes, Views } = require('tasks/statemanager/redux/types');
	const { adapter } = require('tasks/statemanager/redux/slices/stage-settings/meta');
	const { getStagesFromCache } = require('tasks/statemanager/redux/slices/kanban-settings/thunk/src/data-provider');

	const fetchStagesPending = (state, action) => {
		const cache = getStagesFromCache(action.meta.arg);
		if (cache.length > 0)
		{
			const preparedStages = cache.map(
				(stage) => {
					const newStage = {
						...stage,
						view: action.meta.arg.view,
						projectId: action.meta.arg.projectId,
					};

					delete newStage.counters;

					return newStage;
				},
			);

			adapter.addMany(state, preparedStages);
		}
	};

	const fetchStagesFulfilled = (state, action) => {
		const preparedStages = action.payload.data.stages.map(
			(stage) => {
				const newStage = { ...stage, view: action.meta.arg.view, projectId: action.meta.arg.projectId };
				delete newStage.counters;

				return newStage;
			},
		);

		adapter.upsertMany(state, preparedStages);
	};

	const addStagePending = (state, action) => {
		state.status = statusTypes.pending;
	};

	const addStageFulfilled = (state, action) => {
		const stage = {
			id: action.payload.id,
			name: action.payload.name,
			color: action.payload.color,
			sort: action.payload.sort,
			statusId: action.payload.statusId,
			view: action.payload.filterParams.view,
			projectId: action.payload.filterParams.projectId,
			ownerId: action.payload.filterParams.searchParams.ownerId,
		};
		adapter.upsertOne(state, stage);
	};

	const updateStagePending = (state) => {
		state.status = statusTypes.pending;
	};

	const addStageRejected = (state, action) => {
		state.status = statusTypes.failure;
	};

	const updateStageFulfilled = (state, action) => {
		state.status = statusTypes.success;
		const stageInStore = state.entities[action.meta.arg.stageId];
		stageInStore.name = action.meta.arg.name;
		stageInStore.color = action.meta.arg.color;
	};

	const updateStageRejected = (state, action) => {
		state.status = statusTypes.failure;
	};

	const deleteStagePending = (state, action) => {
		state.status = statusTypes.pending;
	};

	const deleteStageFulfilled = (state, action) => {
		state.status = statusTypes.success;
		const { stageId } = action.payload;
		adapter.removeOne(state, stageId);
	};

	const deleteStageRejected = (state, action) => {
		state.status = statusTypes.failure;
	};

	const setKanbanSettings = (state, action) => {
		const { stages, projectId, view } = action.payload;

		if (Array.isArray(stages))
		{
			const preparedStages = stages.map(
				(stage) => {
					const newStage = {
						...stage,
						view,
						projectId,
					};

					delete newStage.counters;

					return newStage;
				},
			);

			adapter.upsertMany(state, preparedStages);
		}
	};

	const updateTaskFulfilled = (state, action) => {
		const { data } = action.payload;

		if (data?.kanban && Array.isArray(data?.kanban?.stages))
		{
			const {
				reduxFields,
			} = action.meta.arg;
			if (Number.isInteger(reduxFields?.groupId) && reduxFields?.groupId > 0)
			{
				const preparedStages = data?.kanban?.stages.map(
					(stage) => {
						const newStage = {
							...stage,
							viewMode: Views.KANBAN,
							projectId: reduxFields?.groupId,
						};

						delete newStage.counters;

						return newStage;
					},
				);

				adapter.upsertMany(state, preparedStages);
			}
		}
	};

	module.exports = {
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
		setKanbanSettings,
		updateTaskFulfilled,
	};
});
