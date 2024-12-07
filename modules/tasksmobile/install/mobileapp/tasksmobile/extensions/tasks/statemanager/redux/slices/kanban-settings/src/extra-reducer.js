/* eslint-disable no-param-reassign */
/**
 * @module tasks/statemanager/redux/slices/kanban-settings/src/extra-reducer
 */
jn.define('tasks/statemanager/redux/slices/kanban-settings/src/extra-reducer', (require, exports, module) => {
	const { statusTypes, Views } = require('tasks/statemanager/redux/types');
	const { adapter } = require('tasks/statemanager/redux/slices/kanban-settings/meta');
	const { getStagesFromCache } = require('tasks/statemanager/redux/slices/kanban-settings/thunk');
	const { getStageIds, getUniqId } = require('tasks/statemanager/redux/slices/kanban-settings/tools');
	const { stringifyWithKeysSort } = require('tasks/statemanager/redux/utils');
	const { md5 } = require('utils/hash');

	const fetchStagesPending = (state, action) => {
		state.status = statusTypes.pending;

		state.id = getUniqId(
			action.meta.arg.view,
			action.meta.arg.projectId,
			action.meta.arg.searchParams.ownerId,
		);

		const cache = getStagesFromCache(action.meta.arg);
		if (cache.length > 0)
		{
			adapter.addOne(state, {
				id: state.id,
				kanbanSettingId: action.meta.arg.view,
				projectId: action.meta.arg.projectId,
				stages: getStageIds(cache),
			});
		}

		state.lastSearchRequestId = md5(stringifyWithKeysSort(action.meta.arg.searchParams));
	};

	const fetchStagesFulfilled = (state, action) => {
		state.status = statusTypes.success;
		if (action.payload.data.stages.length > 0)
		{
			const preparedData = {
				id: getUniqId(
					action.meta.arg.view,
					action.meta.arg.projectId,
					action.meta.arg.searchParams.ownerId,
				),
				kanbanSettingId: action.meta.arg.view,
				projectId: action.meta.arg.projectId,
				canEdit: action.payload.data.canedit,
				stages: getStageIds(action.payload.data.stages),
				canMoveStage: action.payload.data.canmovestage,
			};

			adapter.upsertOne(state, preparedData);
		}
	};

	const updateStagesOrderFulfilled = (state, action) => {
		state.status = statusTypes.success;

		const preparedData = {
			id: getUniqId(
				action.meta.arg.view,
				action.meta.arg.projectId,
				action.meta.arg.ownerId,
			),
			kanbanSettingId: action.meta.arg.view,
			projectId: action.meta.arg.projectId,
			stages: action.payload.fields.stageIdsBySemantics.processStages,
		};

		adapter.upsertOne(state, preparedData);
	};

	const fetchStagesRejected = (state, action) => {
		state.status = statusTypes.failure;
	};

	const updateStagesOrderRejected = (state, action) => {
		state.status = statusTypes.failure;
	};

	const addStageFulfilled = (state, action) => {
		const kanbanSettingsId = getUniqId(
			action.meta.arg.filterParams.view,
			action.meta.arg.filterParams.projectId,
			action.meta.arg.filterParams.searchParams.ownerId,
		);
		state.entities[kanbanSettingsId].stages.push(action.payload.id);
	};

	const deleteStageFulfilled = (state, action) => {
		const kanbanSettingsId = getUniqId(
			action.meta.arg.view,
			action.meta.arg.projectId,
			action.meta.arg.ownerId,
		);
		const stageIndexInArray = state.entities[kanbanSettingsId].stages.indexOf(action.payload.stageId);
		if (stageIndexInArray !== -1)
		{
			state.entities[kanbanSettingsId].stages.splice(stageIndexInArray, 1);
		}
	};

	const updateTaskFulfilled = (state, action) => {
		const { data } = action.payload;
		const {
			reduxFields,
			userId,
		} = action.meta.arg;

		if (data?.kanban && Number.isInteger(reduxFields?.groupId))
		{
			const kanbanSettingsId = getUniqId(
				Views.KANBAN,
				reduxFields?.groupId,
				userId,
			);

			adapter.upsertOne(state, {
				id: kanbanSettingsId,
				kanbanSettingId: Views.KANBAN,
				projectId: reduxFields?.groupId,
				...data.kanban,
				stages: getStageIds(data.kanban.stages),
			});
		}
	};

	module.exports = {
		fetchStagesPending,
		fetchStagesFulfilled,
		fetchStagesRejected,
		updateStagesOrderFulfilled,
		updateStagesOrderRejected,
		addStageFulfilled,
		deleteStageFulfilled,
		updateTaskFulfilled,
	};
});
