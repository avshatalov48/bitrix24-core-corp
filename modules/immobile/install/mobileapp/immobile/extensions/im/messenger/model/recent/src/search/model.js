/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/recent/search/model
 */
jn.define('im/messenger/model/recent/search/model', (require, exports, module) => {
	const { validate } = require('im/messenger/model/recent/search/validator');

	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('model--messages-search');

	/** @type {RecentSearchModel} */
	const searchModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/**
			 * @function recentModel/searchModel/getCollection
			 * @return {Array<RecentSearchModelState>}
			 */
			getCollection: (state) => () => {
				return Object.values(state.collection);
			},

			/**
			 * @function recentModel/searchModel/getById
			 * @return {?RecentSearchModelState}
			 */
			getById: (state) => (dialogId) => {
				return state.collection[dialogId];
			},
		},
		actions: {
			/**
			 * @function recentModel/searchModel/set
			 * @param store
			 * @param {Array<any>} payload
			 */
			set: (store, payload) => {
				payload.forEach((item) => {
					const recentElement = validate(item);

					store.commit('set', {
						actionName: 'set',
						data: {
							item: {
								id: recentElement.id,
								dateMessage: recentElement.dateMessage,
							},
						},
					});
				});
			},

			/**
			 * @function recentModel/searchModel/clear
			 */
			clear: (store, payload) => {
				store.commit('clear', {
					actionName: 'clear',
					data: {},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<RecentSearchSetData, RecentSearchSetActions>} payload
			 */
			set: (state, payload) => {
				logger.log('searchModel: set mutation', payload);

				const {
					item,
				} = payload.data;

				state.collection[item.id] = item;
			},

			/**
			 * @param state
			 * @param {MutationPayload<RecentSearchClearData, RecentSearchClearActions>} payload
			 */
			clear: (state, payload) => {
				logger.log('searchModel: clear mutation', payload);

				state.collection = {};
			},
		},
	};

	module.exports = { searchModel };
});
