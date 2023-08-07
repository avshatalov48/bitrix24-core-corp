/**
 * @module crm/state-storage/model/conversion-wizard
 */
jn.define('crm/state-storage/model/conversion-wizard', (require, exports, module) => {
	const { merge } = require('utils/object');

	const conversionWizardModel = {
		namespaced: true,
		state()
		{
			return {};
		},
		getters: {
			getEntityTypeIds: (state) => (key) => {
				if (state.hasOwnProperty(key))
				{
					return state[key].entityTypeIds;
				}

				return [];
			},
		},
		actions: {
			setEntityTypeIds: (store, { data }) => {
				store.commit('setEntityTypeIds', { data });
			},
		},
		mutations: {
			setEntityTypeIds: (state, { data }) => {
				merge(state, data);
			},
		},
	};

	module.exports = { conversionWizardModel };
});
