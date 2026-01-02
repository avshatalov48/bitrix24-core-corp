/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/sidebar/src/common-chats/model
 */
jn.define('im/messenger/model/sidebar/src/common-chats/model', (require, exports, module) => {
	const { Type } = require('type');
	const { validate } = require('im/messenger/model/sidebar/src/common-chats/validator');
	const { commonChatItem } = require(
		'im/messenger/model/sidebar/src/common-chats/default-element',
	);
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--sidebar-generalChats');

	/** @type {SidebarCommonChatsModel} */
	const sidebarCommonChatsModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/**
			 * @function sidebarModel/sidebarCommonChatsModel/get
			 * @param state
			 * @return {function({chatId: number}): SidebarCommonChatItem}
			 */
			get: (state) => (chatId) => {
				if (!state.collection[chatId])
				{
					return {};
				}

				return state.collection[chatId];
			},

			/**
			 * @function sidebarModel/sidebarCommonChatsModel/hasNextPage
			 * @param state
			 * @return {function({chatId: number}): boolean}
			 */
			hasNextPage: (state) => (chatId) => {
				return Boolean(state.collection[chatId]?.hasNextPage);
			},
		},
		actions: {
			/**
			 * @function sidebarModel/sidebarCommonChatsModel/set
			 */
			set: (store, payload) => {
				const { chatId, chats } = payload;

				if (!Type.isArrayFilled(chats) || !Type.isNumber(chatId))
				{
					return;
				}

				store.commit('set', {
					actionName: 'set',
					data: {
						chatId,
						chats: prepareChats({ chats }),
					},
				});

				store.dispatch('dialoguesModel/set', chats, { root: true });
			},

			/**
			 * @function sidebarModel/sidebarCommonChatsModel/setFromPagination
			 */
			setFromPagination: (store, payload) => {
				const { chatId, chats, hasNextPage } = payload;

				if (!Type.isArray(chats) || !Type.isNumber(chatId))
				{
					return;
				}

				store.commit('set', {
					actionName: 'setFromPagination',
					data: {
						chatId,
						chats: prepareChats({ chats }),
						hasNextPage,
					},
				});

				store.dispatch('dialoguesModel/set', chats, { root: true });
			},

			/** @function sidebarModel/sidebarCommonChatsModel/delete */
			delete: (store, payload) => {
				const { chatId, id } = payload;
				const isValidParams = Type.isNumber(id) && Type.isNumber(chatId);
				const hasCollection = store.state.collection[chatId];

				if (!isValidParams || !hasCollection)
				{
					return;
				}

				store.commit('delete', {
					actionName: 'delete',
					data: {
						chatId,
						id,
					},
				});
			},

			/** @function sidebarModel/sidebarCommonChatsModel/deleteByChatId */
			deleteByChatId: (store, payload) => {
				const { chatId } = payload;

				store.commit('deleteByChatId', {
					actionName: 'deleteByChatId',
					data: {
						chatId,
					},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<SidebarCommonChatsSetData, sidebarCommonChatsModelActions>} payload
			 */
			set: (state, payload) => {
				logger.log('SidebarCommonChatsModel: set mutation', payload);
				const { chatId, chats } = payload.data;

				if (!Type.isNumber(chatId))
				{
					return;
				}

				if (!state.collection[chatId])
				{
					state.collection[chatId] = {
						hasNextPage: true,
						chats: new Map(),
					};
				}

				chats.forEach((value, key, map) => {
					state.collection[chatId].chats.set(key, value);
				});
			},
			/**
			 * @param state
			 * @param {MutationPayload<SidebarCommonChatsDeleteData, SidebarCommonChatsDeleteActions>} payload
			 */
			delete: (state, payload) => {
				logger.log('SidebarChatsModel: delete mutation', payload);
				const { chatId, id } = payload.data;

				if (state.collection[chatId] && state.collection[chatId].chats.has(id))
				{
					state.collection[chatId].chats.delete(id);
				}
			},
			/**
			 * @param state
			 * @param {MutationPayload<SidebarCommonChatsDeleteByChatIdData, SidebarCommonChatsDeleteByChatIdActions>} payload
			 */
			deleteByChatId: (state, payload) => {
				logger.log('SidebarChatsModel: deleteByChatId mutation', payload);
				const { chatId } = payload.data;

				if (state.collection[chatId])
				{
					delete state.collection[chatId];
				}
			},
		},
	};

	/**
	 * @param {Array<SidebarCommonChatMap>} chats
	 * @returns {SidebarCommonChatMap}
	 */
	function prepareChats({ chats })
	{
		const chatsMap = new Map();

		chats.forEach((chat) => {
			const chatId = chat?.id || chat?.chatId;
			if (chatId)
			{
				chatsMap.set(chatId, { ...commonChatItem, ...validate(chat) });
			}
		});

		return chatsMap;
	}

	module.exports = { sidebarCommonChatsModel };
});
