/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/sidebar
 */
jn.define('im/messenger/model/sidebar', (require, exports, module) => {
	const { Type } = require('type');

	const elementState = {
		dialogId: '0',
		isMute: false,
	};

	const sidebarModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			isMute: (state) => (isMute) => {
				return state.collection[isMute];
			},
		},
		actions: {
			/** @function sidebarModel/set */
			set: (store, payload) => {
				if (!Array.isArray(payload) && Type.isPlainObject(payload))
				{
					payload = [payload];
				}

				payload.forEach((element) => {
					const existingItem = store.state.collection[element.dialogId];
					if (existingItem)
					{
						store.commit('update', {
							actionName: 'set',
							data: {
								dialogId: element.dialogId,
								fields: element,
							},
						});
					}
					else
					{
						store.commit('add', {
							actionName: 'set',
							data: {
								dialogId: element.dialogId,
								fields: { ...elementState, ...element },
							},
						});
					}
				});
			},

			/** @function sidebarModel/add */
			add: (store, payload) => {
				if (!Array.isArray(payload) && Type.isPlainObject(payload))
				{
					payload = [payload];
				}

				payload.forEach((element) => {
					const existingItem = store.state.collection[element.dialogId];
					if (!existingItem)
					{
						store.commit('add', {
							actionName: 'add',
							data: {
								dialogId: element.dialogId,
								fields: { ...elementState, ...element },
							},
						});
					}
				});
			},

			/** @function sidebarModel/delete */
			delete: (store, payload) => {
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				store.commit('delete', {
					actionName: 'delete',
					data: {
						dialogId: payload.dialogId,
					},
				});

				return true;
			},

			/** @function sidebarModel/update */
			update(store, payload) {
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				store.commit('update', {
					actionName: 'update',
					data: {
						dialogId: payload.dialogId,
						fields: payload.fields,
					},
				});

				return true;
			},

			/** @function sidebarModel/changeMute */
			changeMute(store, payload)
			{
				const existingItem = store.state.collection[payload.dialogId];
				if (!existingItem)
				{
					return false;
				}

				if (existingItem.isMute === payload.isMute)
				{
					return false;
				}

				store.commit('update', {
					actionName: 'changeMute',
					data: {
						dialogId: payload.dialogId,
						fields: {
							isMute: payload.isMute,
						},
					},
				});

				return true;
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<SidebarAddData, SidebarAddActions>} payload
			 */
			add: (state, payload) => {
				const {
					dialogId,
					fields,
				} = payload.data;

				if (!Type.isUndefined(dialogId))
				{
					state.collection[dialogId] = fields;
				}
			},

			/**
			 * @param state
			 * @param {MutationPayload<SidebarUpdateData, SidebarUpdateActions>} payload
			 */
			update: (state, payload) => {
				const {
					dialogId,
					fields,
				} = payload.data;

				if (!Type.isUndefined(dialogId))
				{
					state.collection[dialogId] = { ...state.collection[dialogId], ...fields };
				}
			},

			/**
			 * @param state
			 * @param {MutationPayload<SidebarDeleteData, SidebarDeleteActions>} payload
			 */
			delete: (state, payload) => {
				const {
					dialogId,
				} = payload.data;

				if (!Type.isUndefined(dialogId))
				{
					delete state.collection[dialogId];
				}
			},
		},
	};

	module.exports = { sidebarModel };
});
