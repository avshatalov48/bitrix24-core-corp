/**
 * @module crm/state-storage/model/activity-counters
 */
jn.define('crm/state-storage/model/activity-counters', (require, exports, module) => {
	const { merge } = require('utils/object');

	const activityCountersModel = {
		namespaced: true,
		state()
		{
			return getDefaultState();
		},
		getters: {
			getCounters: (state) => state.counters,
		},
		actions: {
			setCounters: (store, { counters }) => {
				store.commit('setCounters', { counters });
			},
			clear: (store) => store.commit('clear'),
		},
		mutations: {
			setCounters: (state, { counters }) => {
				merge(state.counters, counters);
			},
			clear: (state) => state = getDefaultState(),
		},
	};

	const getDefaultState = () => {
		return {
			counters: {},
		};
	};

	module.exports = { activityCountersModel };
});
