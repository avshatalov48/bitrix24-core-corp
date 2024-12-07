/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/sidebar
 */
jn.define('im/messenger/model/sidebar', (require, exports, module) => {
	const { Type } = require('type');
	const { SidebarFileType } = require('im/messenger/const');
	const { sidebarFilesModel } = require('im/messenger/model/sidebar/files/files');
	const { sidebarLinksModel } = require('im/messenger/model/sidebar/links/links');
	const { MessengerParams } = require('im/messenger/lib/params');

	const sidebarDefaultElement = Object.freeze({
		dialogId: '0',
		isMute: false,
		isHistoryLimitExceeded: false,
	});

	/**
	 *
	 * @type {SidebarModel}
	 */
	const sidebarModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		modules: {
			sidebarFilesModel,
			sidebarLinksModel,
		},
		getters: {
			/**
			 * @function sidebarModel/isMute
			 * @param state
			 * @return {boolean}
			 */
			isMute: (state) => (isMute) => {
				return state.collection[isMute];
			},
			/**
			 * @function sidebarModel/isInited
			 * @param state
			 * @return {boolean}
			 */
			isInited: (state, getters, rootState, rootGetters) => (chatId) => {
				const dialog = rootGetters['dialoguesModel/getByChatId'](chatId);

				if (!dialog)
				{
					return false;
				}

				return Boolean(state.collection[dialog.dialogId]);
			},
			/**
			 * @function sidebarModel/isHistoryLimitExceeded
			 * @param state
			 * @param getters
			 * @param rootState
			 * @param rootGetters
			 * @return {boolean}
			 */
			isHistoryLimitExceeded: (state, getters, rootState, rootGetters) => (dialogId) => {
				if (MessengerParams.isFullChatHistoryAvailable())
				{
					return false;
				}

				if (state.collection[dialogId]?.isHistoryLimitExceeded)
				{
					return Boolean(state.collection[dialogId]?.isHistoryLimitExceeded);
				}

				const dialog = rootGetters['dialoguesModel/getById'](dialogId);
				const linksIsHistoryLimitExceeded = rootGetters['sidebarModel/sidebarLinksModel/isHistoryLimitExceeded'](dialog.chatId);
				const filesIsHistoryLimitExceeded = rootGetters['sidebarModel/sidebarFilesModel/isHistoryLimitExceeded'](dialog.chatId, SidebarFileType.document);

				return filesIsHistoryLimitExceeded || linksIsHistoryLimitExceeded;
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
								fields: { ...sidebarDefaultElement, ...element },
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
								fields: { ...sidebarDefaultElement, ...element },
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

			/** @function sidebarModel/removeHistoryLimitExceeded */
			removeHistoryLimitExceeded: (store, chatId) => {
				if (!store.state.collection[chatId])
				{
					return;
				}

				const item = store.state.collection[chatId];
				if (item.isHistoryLimitExceeded) // FIXME if isHistoryLimitExceeded is bool - this code unreachable, why?
				{
					store.commit('setHistoryLimitExceeded', {
						actionName: 'set',
						data: {
							dialogId: item.dialogId,
							isHistoryLimitExceeded: false,
						},
					});

					const dialog = store.rootGetters['dialoguesModel/getById'](item.dialogId);
					const params = {
						chatId: dialog.chatId,
						isHistoryLimitExceeded: false,
					};

					store.dispatch('sidebarModel/sidebarLinksModel/setHistoryLimitExceeded', params, { root: true });
					store.dispatch('sidebarModel/sidebarFilesModel/setHistoryLimitExceeded', {
						...params,
						subType: SidebarFileType.document,
					}, { root: true });
				}
			},

			/** @function sidebarModel/setHistoryLimitExceeded */
			setHistoryLimitExceeded: (store, payload) => {
				const { chatId, isHistoryLimitExceeded } = payload;
				const dialog = store.rootGetters['dialoguesModel/getByChatId'](chatId);
				if (!Type.isBoolean(isHistoryLimitExceeded) || !dialog)
				{
					return;
				}

				store.commit('setHistoryLimitExceeded', {
					actionName: 'set',
					data: {
						dialogId: dialog.dialogId,
						isHistoryLimitExceeded,
					},
				});

				store.dispatch('sidebarModel/sidebarLinksModel/setHistoryLimitExceeded', {
					chatId: dialog.chatId,
					isHistoryLimitExceeded,
				}, { root: true });
				store.dispatch('sidebarModel/sidebarFilesModel/setHistoryLimitExceeded', {
					chatId: dialog.chatId,
					isHistoryLimitExceeded,
					subType: SidebarFileType.document,
				}, { root: true });
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<SidebarSetHistoryLimitExceededData, SidebarSetHistoryLimitExceededActions>} payload
			 */
			setHistoryLimitExceeded: (state, payload) => {
				const { isHistoryLimitExceeded, dialogId } = payload.data;

				if (!Type.isUndefined(dialogId))
				{
					const existingItem = state.collection[dialogId];
					if (existingItem)
					{
						state.collection[dialogId].isHistoryLimitExceeded = isHistoryLimitExceeded;
					}
					else
					{
						state.collection[dialogId] = { ...elementState, isHistoryLimitExceeded };
					}
				}
			},

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

	module.exports = { sidebarModel, sidebarDefaultElement };
});
