/**
 * @module tasks/statemanager/redux/slices/stage-counters/src/reducer
 */
jn.define('tasks/statemanager/redux/slices/stage-counters/src/reducer', (require, exports, module) => {
	const { adapter, allStagesId } = require('tasks/statemanager/redux/slices/stage-counters/meta');

	const updateCounter = (state, action) => {
		adapter.upsertOne(state, action.payload);
	};

	const stageCounterIncreased = (state, action) => {
		const { stageId, projectId, ownerId, view, flowId } = action.payload;
		const counters = state.entities;
		for (const value of Object.values(counters))
		{
			if ((value.filter.stageId === stageId || value.filter.stageId === allStagesId)
					&& value.filter.view === view
					&& value.filter.projectId === projectId
					&& value.filter.searchParams.ownerId === ownerId
					&& value.filter.searchParams.flowId === flowId)
			{
				value.count++;
			}
		}
	};

	const stageCounterDecreased = (state, action) => {
		const { stageId, projectId, ownerId, view, flowId } = action.payload;
		const counters = state.entities;
		for (const value of Object.values(counters))
		{
			if ((value.filter.stageId === stageId || value.filter.stageId === allStagesId)
				&& value.filter.view === view
				&& value.filter.projectId === projectId
				&& value.filter.searchParams.ownerId === ownerId
				&& value.filter.searchParams.flowId === flowId)
			{
				value.count--;
			}
		}
	};

	module.exports = {
		updateCounter,
		stageCounterIncreased,
		stageCounterDecreased,
	};
});
