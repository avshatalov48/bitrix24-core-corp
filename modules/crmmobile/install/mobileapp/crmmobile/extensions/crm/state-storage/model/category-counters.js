/**
 * @module crm/state-storage/model/category-counters
 */
jn.define('crm/state-storage/model/category-counters', (require, exports, module) => {
	const { merge, mergeImmutable, isEqual } = require('utils/object');
	const { CategoryAjax } = require('crm/ajax');

	const countersInfoAsyncTypes = {
		success: 'success',
		failure: 'failure',
		pending: 'pending',
	};

	const categoryCountersModel = {
		namespaced: true,
		state()
		{
			return getDefaultState();
		},
		getters: {
			getStages: (state) => state.stages,
			getStage: (state) => (stageId) => (state.stages.find((item) => item.id === stageId) || null),
			getLoading: (state) => state.loading,
		},
		actions: {
			init: (store, ajaxParams) => {
				store.commit('setLoading', { loading: countersInfoAsyncTypes.pending });
				CategoryAjax
					.fetch('getCounters', ajaxParams)
					.then(
						(response) => store.commit('init', {
							stages: response.data.stages,
							loading: countersInfoAsyncTypes.success,
						}),
						() => store.commit('setLoading', { loading: countersInfoAsyncTypes.failure }),
					)
				;
			},
			updateStage: (store, { stageId, data }) => {
				const stage = store.getters.getStage(stageId);
				const newStage = mergeImmutable(stage, data);
				if (stage && !isEqual(stage, newStage))
				{
					store.commit('updateStage', { stageId, data });
				}
			},
			clear: (store) => store.commit('clear'),
		},
		mutations: {
			init: (state, data) => state.stages = data.stages,
			updateStage: (state, { stageId, data }) => {
				const stage = state.stages.find((item) => item.id === stageId);
				if (stage)
				{
					merge(stage, data);
				}
			},
			setLoading: (state, { loading }) => state.loading = loading,
			clear: (state) => state = getDefaultState(),
		},
	};

	const getDefaultState = () => {
		return {
			stages: [],
			loading: null,
		};
	};

	module.exports = { categoryCountersModel };
});
