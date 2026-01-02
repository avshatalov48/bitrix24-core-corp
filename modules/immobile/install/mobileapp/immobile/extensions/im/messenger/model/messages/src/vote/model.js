/* eslint-disable no-param-reassign */
/**
 * @module im/messenger/model/messages/vote/model
 */
jn.define('im/messenger/model/messages/vote/model', (require, exports, module) => {
	const { Type } = require('type');
	const { getLogger } = require('im/messenger/lib/logger');
	const { validate } = require('im/messenger/model/messages/vote/validator');

	const logger = getLogger('model--messages-vote');

	/** @type {VoteMessengerModel} */
	const voteModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/**
			 * @function messagesModel/voteModel/getByMessageId
			 * @param state
			 * @return {VoteModelState}
			 */
			getByMessageId: (state) => (messageId) => {
				if (!Type.isNumber(messageId) && !Type.isStringFilled(messageId))
				{
					return null;
				}

				return state.collection[messageId.toString()] || null;
			},
		},
		actions: {
			/**
			 * @function messagesModel/voteModel/add
			 * @param store
			 * @param {VoteModelSetPayload} payload
			 */
			add: (store, payload) => {
				logger.log('voteModel: add action', payload);

				const voteList = payload.votes.map((vote) => validate(vote));
				if (voteList.length === 0)
				{
					return;
				}

				store.commit('set', {
					actionName: 'add',
					data: {
						voteList,
					},
				});
			},
			/**
			 * @function messagesModel/voteModel/setFromResponse
			 * @param store
			 * @param {VoteModelSetPayload} payload
			 */
			setFromResponse: (store, payload) => {
				logger.log('voteModel: setFromResponse action', payload);

				const voteList = payload.votes.map((vote) => validate(vote));
				if (voteList.length === 0)
				{
					return;
				}

				store.commit('set', {
					actionName: 'setFromResponse',
					data: {
						voteList,
					},
				});
			},
			/**
			 * @function messagesModel/voteModel/setFromPullEvent
			 * @param store
			 * @param {VoteModelSetPayload} payload
			 */
			setFromPullEvent: (store, payload) => {
				logger.log('voteModel: setFromPullEvent action', payload);

				const voteList = payload.votes.map((vote) => validate(vote));
				if (voteList.length === 0)
				{
					return;
				}

				store.commit('set', {
					actionName: 'setFromPullEvent',
					data: {
						voteList,
					},
				});
			},
			/**
			 * @function messagesModel/voteModel/setFromLocalDatabase
			 * @param store
			 * @param {VoteModelSetPayload} payload
			 */
			setFromLocalDatabase: (store, payload) => {
				logger.log('voteModel: setFromLocalDatabase action', payload);

				const voteList = payload.votes.map((vote) => ({
					...store.state.collection[vote.messageId],
					...validate(vote),
				}));
				if (voteList.length === 0)
				{
					return;
				}

				store.commit('set', {
					actionName: 'setFromLocalDatabase',
					data: {
						voteList,
					},
				});
			},
			/**
			 * @function messagesModel/voteModel/update
			 * @param store
			 * @param {VoteModelSetPayload} payload
			 */
			update: (store, payload) => {
				logger.log('voteModel: update action', payload);

				const { vote } = payload;
				const existingVote = store.state.collection[vote.messageId];
				if (!existingVote)
				{
					return;
				}

				store.commit('set', {
					actionName: 'update',
					data: {
						voteList: [validate({ ...existingVote, ...vote })],
					},
				});
			},
			/**
			 * @function messagesModel/voteModel/updateWithId
			 * @param store
			 * @param {VoteModelUpdateWithIdPayload} payload
			 */
			updateWithId: (store, payload) => {
				logger.log('voteModel: updateWithId action', payload);

				const { id, vote } = payload;

				store.commit('updateWithId', {
					actionName: 'updateWithId',
					data: {
						id,
						vote: validate(vote),
					},
				});
			},
			/**
			 * @function messagesModel/voteModel/deleteByChatId
			 * @param store
			 * @param {VoteDeleteByChatIdPayload} payload
			 */
			deleteByChatId: (store, payload) => {
				logger.log('voteModel: deleteByChatId action', payload);

				store.commit('deleteByChatId', {
					actionName: 'deleteByChatId',
					data: {
						chatId: payload.chatId,
					},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<VoteSetData, VoteSetActions>} payload
			 */
			set: (state, payload) => {
				logger.log('voteModel: set mutation', payload);

				const { voteList } = payload.data;

				voteList.forEach((vote) => {
					state.collection[vote.messageId] = vote;
				});
			},
			/**
			 * @param state
			 * @param {MutationPayload<VoteUpdateWithIdData, VoteUpdateWithIdActions>} payload
			 */
			updateWithId: (state, payload) => {
				logger.log('voteModel: updateWithId mutation', payload);

				const { id, vote } = payload.data;

				if (state.collection[id])
				{
					state.collection[vote.messageId] = { ...vote };
					delete state.collection[id];
				}
			},
			/**
			 * @param state
			 * @param {MutationPayload<VoteDeleteByChatIdData, VoteDeleteByChatIdActions>} payload
			 */
			deleteByChatId: (state, payload) => {
				logger.log('voteModel: deleteByChatId mutation', payload);

				const { chatId } = payload.data;

				state.collection = Object.fromEntries(
					Object.entries(state.collection).filter(([, vote]) => vote.chatId !== chatId),
				);
			},
		},
	};

	module.exports = { voteModel };
});
