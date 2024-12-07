/* eslint-disable no-param-reassign */
/**
 * @module im/messenger/model/messages/reactions
 */
jn.define('im/messenger/model/messages/reactions', (require, exports, module) => {
	const { Type } = require('type');
	const { ReactionType } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { clone } = require('utils/object');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--messages-reactions');

	/** @type{ReactionsModelState} */
	const reactionDefaultElement = Object.freeze({
		messageId: 0,
		ownReactions: new Set(),
		reactionCounters: {},
		reactionUsers: new Map(),
	});

	const USERS_TO_SHOW = 3;

	/** @type {ReactionsMessengerModel} */
	const reactionsModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/**
			 * @function messagesModel/reactionsModel/getByMessageId
			 * @param state
			 * @return {ReactionsModelState}
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
			 * @function messagesModel/reactionsModel/setFromPullEvent
			 * @param store
			 * @param {ReactionsModelSetPayload} payload
			 */
			setFromPullEvent: (store, payload) => {
				const reactionList = prepareSetPayload(payload);

				if (reactionList.length === 0)
				{
					return;
				}

				store.commit('set', {
					actionName: 'setFromPullEvent',
					data: {
						reactionList,
					},
				});
			},
			/**
			 * @function messagesModel/reactionsModel/set
			 * @param store
			 * @param {ReactionsModelSetPayload} payload
			 */
			set: (store, payload) => {
				const reactionList = prepareSetPayload(payload);

				if (reactionList.length === 0)
				{
					return;
				}

				store.commit('set', {
					actionName: 'set',
					data: {
						reactionList,
					},
				});
			},
			/**
			 * @function messagesModel/reactionsModel/setFromLocalDatabase
			 * @param store
			 * @param {ReactionsModelSetPayload} payload
			 */
			setFromLocalDatabase: (store, payload) => {
				const reactionList = prepareSetPayload(payload);

				if (reactionList.length === 0)
				{
					return;
				}

				store.commit('set', {
					actionName: 'setFromLocalDatabase',
					data: {
						reactionList,
					},
				});
			},
			/**
			 * @function messagesModel/reactionsModel/setReaction
			 * @param store
			 * @param {ReactionsModelSetReactionPayload} payload
			 */
			setReaction: (store, payload) => {
				if (!ReactionType[payload.reaction])
				{
					return;
				}
				const message = store.rootGetters['messagesModel/getById'](payload.messageId);
				if (!message)
				{
					return;
				}

				if (!store.state.collection[payload.messageId])
				{
					store.commit('add', {
						actionName: 'setReaction',
						data: {
							reaction: prepareAddPayload(payload),
						},
					});

					return;
				}

				store.commit('updateWithId', {
					actionName: 'setReaction',
					data: {
						reaction: prepareUpdatePayload(payload, store.state.collection[payload.messageId]),
					},
				});
			},
			/**
			 * @function messagesModel/reactionsModel/removeReaction
			 * @param store
			 * @param {ReactionsModelRemoveReactionPayload} payload
			 */
			removeReaction: (store, payload) => {
				const { messageId, userId, reaction } = payload;

				const message = store.rootGetters['messagesModel/getById'](messageId);
				if (!message)
				{
					return;
				}

				/** @type {ReactionsModelState} */
				const result = { ...store.state.collection[messageId] };

				if (MessengerParams.getUserId().toString() === userId.toString())
				{
					result.ownReactions.delete(reaction);
				}

				const newUsers = (result.reactionUsers.get(reaction) ?? [])
					.filter((removingUserId) => removingUserId.toString() !== userId.toString())
				;
				result.reactionUsers.set(reaction, newUsers);

				result.reactionCounters[reaction]--;
				if (result.reactionCounters[reaction] <= 0)
				{
					delete result.reactionCounters[reaction];
					result.reactionUsers.delete(reaction);
				}

				store.commit('updateWithId', {
					actionName: 'removeReaction',
					data: {
						reaction: result,
					},
				});
			},

			/**
			 * @function messagesModel/reactionsModel/removeReaction
			 * @param store
			 * @param {ReactionsDeleteByChatIdPayload} payload
			 */
			deleteByChatId: (store, payload) => {
				const { chatId } = payload;

				const messageIdList = store.rootGetters['messagesModel/getByChatId'](chatId)
					.map((message) => message.id)
				;

				if (messageIdList.length === 0)
				{
					return;
				}

				store.commit('deleteByChatId', {
					actionName: 'deleteByChatId',
					data: {
						messageIdList,
					},
				});
			},

		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<ReactionsStoreData>} payload
			 */
			store: (state, payload) => {
				logger.log('reactionsModel: store mutation', payload);

				const {
					reactionList,
				} = payload.data;

				reactionList.forEach((reaction) => {
					state.collection[reaction.messageId] = reaction;
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<ReactionsSetData, ReactionsSetActions>} payload
			 */
			set: (state, payload) => {
				logger.log('reactionsModel: set mutation', payload);

				const {
					reactionList,
				} = payload.data;

				reactionList.forEach((item) => {
					const newItem = {
						reactionCounters: item.reactionCounters,
						reactionUsers: item.reactionUsers,
						messageId: item.messageId,
					};

					/** @type {ReactionsModelState} */
					const currentItem = state.collection[item.messageId];
					const newOwnReaction = Boolean(item.ownReactions);
					if (newOwnReaction)
					{
						newItem.ownReactions = item.ownReactions;
					}
					else
					{
						newItem.ownReactions = currentItem ? currentItem.ownReactions : new Set();
					}

					state.collection[item.messageId] = newItem;
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<ReactionsAddData, ReactionsAddActions>} payload
			 */
			add: (state, payload) => {
				logger.log('reactionsModel: add mutation', payload);

				const {
					messageId,
				} = payload.data.reaction;

				state.collection[messageId] = payload.data.reaction;
			},

			/**
			 * @param state
			 * @param {MutationPayload<ReactionsUpdateWithIdData, ReactionsUpdateWithIdActions>} payload
			 */
			updateWithId: (state, payload) => {
				logger.log('reactionsModel: updateWithId mutation', payload);

				const {
					messageId,
				} = payload.data.reaction;

				state.collection[messageId] = payload.data.reaction;
			},

			/**
			 * @param state
			 * @param {MutationPayload<ReactionsDeleteByChatIdData, ReactionsDeleteByChatIdActions>} payload
			 */
			deleteByChatId: (state, payload) => {
				const { messageIdList } = payload.data;

				for (const messageId of messageIdList)
				{
					delete state.collection[messageId];
				}
			},
		},
	};

	/**
	 * @param {ReactionsModelSetPayload} payload
	 * @return {ReactionsModelState[]}
	 */
	function prepareSetPayload(payload)
	{
		return payload.reactions.map((reactionPayload) => {
			/** @type {ReactionsModelState} */
			const result = {
				messageId: reactionPayload.messageId,
				reactionCounters: Array.isArray(reactionPayload.reactionCounters) ? {} : reactionPayload.reactionCounters,
				reactionUsers: new Map(),
			};

			if (Type.isArray(reactionPayload.ownReactions) && reactionPayload.ownReactions.length > 0)
			{
				result.ownReactions = new Set(reactionPayload.ownReactions);
			}

			if (reactionPayload.reactionUsers instanceof Map)
			{
				reactionPayload.reactionUsers = Object.fromEntries(reactionPayload.reactionUsers);
			}

			Object.entries(reactionPayload.reactionUsers).forEach(([reactionType, users]) => {
				const reactionUsers = users.map((userId) => {

					if (userId === MessengerParams.getUserId())
					{
						if (!result.ownReactions)
						{
							result.ownReactions = new Set();
						}
						result.ownReactions.add(reactionType);
					}

					return userId;
				});

				result.reactionUsers.set(reactionType, reactionUsers);
			});

			return result;
		});
	}

	/**
	 *
	 * @param {ReactionsModelSetReactionPayload} payload
	 * @return {ReactionsModelState}
	 */
	function prepareAddPayload(payload)
	{
		const result = clone(reactionDefaultElement);
		result.messageId = payload.messageId;
		result.ownReactions.add(payload.reaction);
		result.reactionCounters[payload.reaction] = 1;
		result.reactionUsers.set(payload.reaction, [payload.userId]);

		return result;
	}

	/**
	 *
	 * @param {ReactionsModelSetReactionPayload} payload
	 * @param {ReactionsModelState} elementState
	 * @return {ReactionsModelState}
	 */
	function prepareUpdatePayload(payload, elementState)
	{
		const { reaction, userId } = payload;
		const result = clone(elementState);
		if (userId.toString() === MessengerParams.getUserId().toString())
		{
			removeAllCurrentUserReactions(result);
			result.ownReactions.add(reaction);
		}

		if (!result.reactionCounters[reaction])
		{
			result.reactionCounters = {
				...result.reactionCounters,
				[reaction]: 0,
			};
		}

		const currentCounter = result.reactionCounters[reaction];
		if (currentCounter + 1 <= USERS_TO_SHOW)
		{
			if (!result.reactionUsers.has(reaction))
			{
				result.reactionUsers.set(reaction, []);
			}
			result.reactionUsers.get(reaction).push(userId);
		}

		result.reactionCounters[reaction]++;

		return result;
	}

	/**
	 *
	 * @param {ReactionsModelState} reactions
	 */
	function removeAllCurrentUserReactions(reactions)
	{
		reactions.ownReactions.forEach((reaction) => {
			if (!reactions.reactionUsers.has(reaction))
			{
				return;
			}

			const newUsers = reactions.reactionUsers.get(reaction)
				.filter((userId) => userId.toString() !== MessengerParams.getUserId().toString())
			;

			reactions.reactionUsers.set(reaction, newUsers);

			if (newUsers.length === 0)
			{
				reactions.reactionUsers.delete(reaction);
			}

			reactions.reactionCounters[reaction]--;
			if (reactions.reactionCounters[reaction] === 0)
			{
				delete reactions.reactionCounters[reaction];
			}
		});

		reactions.ownReactions = new Set();
	}

	module.exports = { reactionsModel, reactionDefaultElement };
});
