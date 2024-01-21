/**
 * @module tasks/statemanager/redux/slices/stage-counters/src/extra-reducer
 */
jn.define('tasks/statemanager/redux/slices/stage-counters/src/extra-reducer', (require, exports, module) => {
	const { statusTypes } = require('tasks/statemanager/redux/types');
	const {
		adapter,
		allStagesId,
	} = require('tasks/statemanager/redux/slices/stage-counters/meta');
	const { stringifyWithKeysSort } = require('tasks/statemanager/redux/utils');
	const { md5 } = require('utils/hash');

	const fetchStagesPending = (state) => {
		state.status = statusTypes.pending;
	};

	const fetchStagesFulfilled = (state, action) => {
		state.status = statusTypes.success;
		const counters = action.payload.data.stages.map((stage) => {
			const filter = {
				...action.meta.arg,
				stageId: stage.id,
			};

			return {
				id: md5(stringifyWithKeysSort(filter)),
				filter,
				count: stage.counters.total,
			};
		});

		const totalCount = action.payload.data.stages.reduce((count, item) => {
			return count + item.counters.total;
		}, 0);
		const totalCounterFilter = {
			...action.meta.arg,
			stageId: allStagesId,
		};
		const totalCounter = {
			id: md5(stringifyWithKeysSort(totalCounterFilter)),
			filter: totalCounterFilter,
			count: totalCount,
		};

		adapter.upsertMany(state, {
			...counters,
			totalCounter,
		});
	};

	const fetchStagesRejected = (state) => {
		state.status = statusTypes.failure;
	};

	const addStageFulfilled = (state, action) => {
		const filter = {
			...action.meta.arg.filterParams,
			stageId: action.payload.id,
		};
		const newCounter = {
			id: md5(stringifyWithKeysSort(filter)),
			filter,
			count: 0,
		};
		adapter.upsertOne(state, newCounter);
	};

	const setTaskStageFulfilled = (state, action) => {
		const { viewMode, userId, nextStageId, prevStageId, projectId } = action.payload;
		const counters = state.entities;
		for (const value of Object.values(counters))
		{
			if (value.filter.view === viewMode
				&& value.filter.projectId === projectId
				&& value.filter.searchParams.ownerId === userId)
			{
				if (value.filter.stageId === prevStageId)
				{
					value.count--;
				}

				if (value.filter.stageId === nextStageId)
				{
					value.count++;
				}
			}
		}
	};

	module.exports = {
		fetchStagesPending,
		fetchStagesFulfilled,
		fetchStagesRejected,
		addStageFulfilled,
		setTaskStageFulfilled,
	};
});
