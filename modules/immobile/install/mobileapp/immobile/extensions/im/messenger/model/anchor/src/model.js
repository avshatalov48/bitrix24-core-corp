/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/anchor/model
 */
jn.define('im/messenger/model/anchor/model', (require, exports, module) => {
	const { Type } = require('type');
	const { validate } = require('im/messenger/model/anchor/validator');
	const { AnchorType } = require('im/messenger/const');

	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('model--anchor');

	/**
	 * @type {AnchorMessengerModel}
	 */
	const anchorModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),

		getters: {
			/**
			 * @function anchorModel/getByChatId
			 * @return {AnchorModelState[]}
			 */
			getByChatId: (state) => (chatId, type = null) => {
				if (!type)
				{
					return state.collection[chatId] ?? [];
				}

				if (!Object.values(AnchorType).includes(type))
				{
					logger.error('anchorModel/getByChatId: invalid type', type);

					return [];
				}

				const anchors = state.collection[chatId] ?? [];

				return anchors.filter((anchor) => anchor.type === type);
			},

			/**
			 * @function anchorModel/getByMessageId
			 * @return {AnchorModelState[]}
			 */
			getByMessageId: (state, getters, rootState, rootGetters) => (chatId, messageId) => {
				const anchors = state.collection[chatId] ?? [];

				return anchors.filter((anchor) => anchor.messageId === messageId);
			},

			/**
			 * @function anchorModel/getByMessageIdList
			 * @return {AnchorModelState[]}
			 */
			getByMessageIdList: (state, getters, rootState, rootGetters) => (chatId, messageIdList) => {
				const anchors = state.collection[chatId] ?? [];

				return anchors.filter((anchor) => {
					return messageIdList.includes(anchor.messageId);
				});
			},

			/**
			 * @function anchorModel/hasAnchorsByType
			 * @return {function(*): boolean}
			 */
			hasAnchorsByType: (state) => (chatId, type) => {
				if (!Object.values(AnchorType).includes(type))
				{
					logger.error('anchorModel/hasAnchorsByType: invalid type', type);

					return false;
				}

				const anchors = state.collection[chatId] ?? [];

				return anchors.some((anchor) => anchor.type === type);
			},

			/**
			 * @function anchorModel/hasAnchor
			 * @return {function(*): boolean}
			 */
			hasAnchor: (state) => (anchor) => {
				const anchors = state.collection[anchor.chatId] ?? [];

				return anchors.some((item) => isEqualsAnchors(item, anchor));
			},
		},
		actions: {
			/** @function anchorModel/setState */
			setState: (store, payload) => {
				const anchors = payload.map((anchor) => validate(anchor));

				store.commit('setState', {
					actionName: 'setState',
					data: {
						anchors,
					},
				});
			},

			/** @function anchorModel/add */
			add: (store, payload) => {
				const anchor = validate(payload);

				const anchorsInStore = store.state.collection[anchor.chatId] ?? [];

				if (anchorsInStore.some(((item) => isEqualsAnchors(item, anchor))))
				{
					return;
				}

				store.commit('add', {
					actionName: 'add',
					data: {
						anchor,
					},
				});
			},

			/** @function anchorModel/delete */
			delete: (store, payload) => {
				const anchor = validate(payload);

				store.commit('delete', {
					actionName: 'delete',
					data: {
						anchor,
					},
				});
			},

			/** @function anchorModel/deleteByChatId */
			deleteByChatId: (store, payload) => {
				const { chatId } = payload;

				const anchors = store.state.collection[chatId] ?? [];
				if (!Type.isArrayFilled(anchors))
				{
					return;
				}

				store.commit('deleteMany', {
					actionName: 'deleteByChatId',
					data: {
						anchorList: anchors,
					},
				});
			},

			/** @function anchorModel/deleteByMessageId */
			deleteByMessageId: (store, payload) => {
				const { messageId } = payload;

				const anchors = store.getters.getByMessageId(messageId);

				anchors.forEach((anchor) => {
					store.commit('delete', {
						actionName: 'deleteByMessageId',
						data: {
							anchor,
						},
					});
				});
			},

			/** @function anchorModel/deleteByMessageIdList */
			deleteByMessageIdList: (store, payload) => {
				const { chatId, messageIdList } = payload;

				if (!Type.isArrayFilled(messageIdList))
				{
					return;
				}

				const anchors = store.state.collection[chatId] ?? [];

				const anchorsToDelete = anchors.filter((anchor) => messageIdList.includes(anchor.messageId));
				if (!Type.isArrayFilled(anchorsToDelete))
				{
					return;
				}

				store.commit('deleteMany', {
					actionName: 'deleteByMessageIdList',
					data: {
						anchorList: anchorsToDelete,
					},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<AnchorSetStateData, AnchorSetStateAction>} payload
			 */
			setState: (state, payload) => {
				logger.log('anchorModel: setState mutation', payload);

				const {
					anchors,
				} = payload.data;

				state.collection = {};

				anchors.forEach((anchor) => {
					if (!state.collection[anchor.chatId])
					{
						state.collection[anchor.chatId] = [];
					}

					state.collection[anchor.chatId].push(anchor);
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<AnchorAddData, AnchorAddAction>} payload
			 */
			add: (state, payload) => {
				logger.log('anchorModel: add mutation', payload);

				const {
					anchor,
				} = payload.data;

				if (!state.collection[anchor.chatId])
				{
					state.collection[anchor.chatId] = [];
				}

				state.collection[anchor.chatId].push(anchor);
			},

			/**
			 * @param state
			 * @param {MutationPayload<AnchorDeleteData, AnchorDeleteAction>} payload
			 */
			delete: (state, payload) => {
				logger.log('anchorModel: delete mutation', payload);

				const {
					anchor,
				} = payload.data;

				if (!state.collection[anchor.chatId])
				{
					return;
				}

				state.collection[anchor.chatId] = state.collection[anchor.chatId].filter((item) => {
					return !isEqualsAnchors(item, anchor);
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<AnchorDeleteManyData, AnchorDeleteManyAction>} payload
			 */
			deleteMany: (state, payload) => {
				const {
					anchorList,
				} = payload.data;

				anchorList.forEach((anchor) => {
					if (!state.collection[anchor.chatId])
					{
						return;
					}

					state.collection[anchor.chatId] = state.collection[anchor.chatId].filter((item) => {
						return !isEqualsAnchors(item, anchor);
					});
				});
			},
		},
	};

	function isEqualsAnchors(a, b)
	{
		return (
			a.messageId === b.messageId
			&& a.type === b.type
			&& a.subType === b.subType
			&& a.fromUserId === b.fromUserId
			&& a.chatId === b.chatId
		);
	}

	module.exports = { anchorModel };
});
