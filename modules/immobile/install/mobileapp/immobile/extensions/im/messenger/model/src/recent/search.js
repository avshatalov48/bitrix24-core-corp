/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/recent/search
 */
jn.define('im/messenger/model/recent/search', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--messages-search');

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

	/**
	 * @param {any} fields
	 * @return {RecentSearchModelState}
	 */
	function validate(fields)
	{
		const result = {};

		if (Type.isStringFilled(fields.dialogId))
		{
			fields.id = fields.dialogId;
		}

		if (Type.isStringFilled(fields.id))
		{
			result.id = fields.id;
		}

		if (Type.isStringFilled(fields.dateMessage))
		{
			result.dateMessage = DateHelper.cast(fields.dateMessage, null);
		}

		return result;
	}

	module.exports = { searchModel };
});
