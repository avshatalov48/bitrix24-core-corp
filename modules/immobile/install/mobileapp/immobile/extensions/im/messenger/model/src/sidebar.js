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
							dialogId: element.dialogId,
							fields: element,
						});
					}
					else
					{
						store.commit('add', {
							dialogId: element.dialogId,
							fields: { ...elementState, ...element },
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
							dialogId: element.dialogId,
							fields: { ...elementState, ...element },
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

				store.commit('delete', { dialogId: payload.dialogId });

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
					dialogId: payload.dialogId,
					fields: {
						isMute: payload.isMute,
					},
				});

				return true;
			},
		},
		mutations: {
			add: (state, payload) => {
				if (!Type.isUndefined(payload.dialogId))
				{
					state.collection[payload.dialogId] = payload.fields;
				}
			},
			update: (state, payload) => {
				if (!Type.isUndefined(payload.dialogId))
				{
					state.collection[payload.dialogId] = { ...state.collection[payload.dialogId], ...payload.fields };
				}
			},
			delete: (state, payload) => {
				if (!Type.isUndefined(payload.dialogId))
				{
					delete state.collection[payload.dialogId];
				}
			},
		},
	};

	module.exports = { sidebarModel };
});
