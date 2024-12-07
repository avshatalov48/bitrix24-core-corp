/**
 * @module im/messenger/model/comment
 */
jn.define('im/messenger/model/comment', (require, exports, module) => {
	const { Type } = require('type');
	const { validate } = require('im/messenger/model/validators/comment');
	const { clone } = require('utils/object');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('model--comment');

	const LAST_USERS_TO_SHOW = 3;

	const commentDefaultElement = Object.freeze({
		chatId: 0,
		dialogId: 0,
		lastUserIds: [],
		messageCount: 0,
		messageId: 0,
		isUserSubscribed: false,
		showLoader: false,
	});

	/**
	 *
	 * @type {CommentMessengerModel}
	 */
	const commentModel = {
		namespaced: true,
		state: () => ({
			commentCollection: {},
			countersCollection: {},
		}),

		getters: {
			/**
			 * @function commentModel/getByMessageId
			 * @return {CommentInfoModelState || undefined}
			 */
			getByMessageId: (state) => (messageId) => {
				return clone(state.commentCollection[messageId]);
			},

			/** @function commentModel/getCommentCounter */
			getCommentCounter: (state) => (payload) => {
				const { channelId, commentChatId } = payload;
				if (!state.countersCollection[channelId])
				{
					return 0;
				}

				return state.countersCollection[channelId][commentChatId] ?? 0;
			},

			/**
			 * @function commentModel/getCommentInfoByCommentChatId
			 * @return {CommentInfoModelState | undefined}
			 */
			getCommentInfoByCommentChatId: (state) => (commentChatId) => {
				return Object.values(state.commentCollection).find((comment) => {
					return comment.chatId === commentChatId;
				});
			},

			/**
			 * @function commentModel/getAllCounters
			 * @param state
			 * @return {number}
			 */
			getAllCounters: (state) => () => {
				let result = 0;

				Object.values(state.countersCollection).forEach((counters) => {
					Object.values(counters).forEach((counter) => {
						result += counter;
					});
				});

				return result;
			},

			/**
			 * @function commentModel/getChannelCounters
			 * @param state
			 * @return {number}
			 */
			getChannelCounters: (state) => (channelId) => {
				let result = 0;
				if (!state.countersCollection[channelId])
				{
					return 0;
				}

				Object.values(state.countersCollection[channelId]).forEach((counters) => {
					result += counters;
				});

				return result;
			},

			/**
			 * @function commentModel/getUnreadPostsCount
			 * @param state
			 * @return {number}
			 */
			getUnreadPostsCount: (state) => (channelId) => {
				let result = 0;
				if (!state.countersCollection[channelId])
				{
					return 0;
				}

				Object.values(state.countersCollection[channelId]).forEach((counters) => {
					if (counters > 0)
					{
						result += 1;
					}
				});

				return result;
			},

			/**
			 * @function commentModel/getChannelCounterCollection
			 * @return {Record<number, number>}
			 */
			getChannelCounterCollection: (state) => (channelId) => {
				if (!state.countersCollection[channelId])
				{
					return {};
				}

				return state.countersCollection[channelId];
			},
		},
		actions: {
			/** @function commentModel/setComments */
			setComments: (store, payload) => {
				if (!Type.isArray(payload))
				{
					payload = [payload];
				}

				const commentList = payload.map((comment) => validate(comment));
				if (commentList.length === 0)
				{
					return;
				}

				store.commit('setComments', {
					actionName: 'setComments',
					data: {
						commentList,
					},
				});
			},

			/** @function commentModel/updateComment */
			updateComment: (store, payload) => {
				if (!store.state.commentCollection[payload.messageId])
				{
					return;
				}

				const commentInfo = clone(store.state.commentCollection[payload.messageId]);
				if (payload.messageCount)
				{
					commentInfo.messageCount = payload.messageCount;
				}

				if (payload.isUserSubscribed)
				{
					commentInfo.isUserSubscribed = payload.isUserSubscribed;
				}

				store.commit('setComments', {
					actionName: 'updateComment',
					data: {
						commentList: [commentInfo],
					},
				});
			},

			/** @function commentModel/setCommentWithCounter */
			setCommentWithCounter: (store, payload) => {
				const comment = validate(payload);

				const { chatCounterMap } = payload;

				if (!store.state.commentCollection[comment.messageId])
				{
					store.commit('setCommentsWithCounters', {
						actionName: 'setCommentWithCounter',
						data: {
							commentList: [comment],
							chatCounterMap,
						},
					});

					return;
				}

				const { newUserId } = payload;
				let currentUsers = [];
				if (Type.isPlainObject(store.state.commentCollection[comment.messageId]))
				{
					currentUsers = clone(store.state.commentCollection[comment.messageId])
						.lastUserIds
					;
				}

				comment.lastUserIds = getNewLastUsers(newUserId, currentUsers);

				store.commit('setCommentsWithCounters', {
					actionName: 'setCommentWithCounter',
					data: {
						commentList: [comment],
						chatCounterMap,
					},
				});
			},

			/** @function commentModel/setComment */
			setComment: (store, payload) => {
				/** @type {CommentInfoModelState} */
				const comment = validate(payload);

				const { newUserId } = payload;

				let currentUsers = [];
				if (Type.isPlainObject(store.state.commentCollection[comment.messageId]))
				{
					currentUsers = clone(store.state.commentCollection[comment.messageId])
						.lastUserIds
					;
				}

				comment.lastUserIds = getNewLastUsers(newUserId, currentUsers);

				store.commit('setComments', {
					actionName: 'setComment',
					data: {
						commentList: [comment],
					},
				});
			},

			/** @function commentModel/setCounters */
			setCounters: (store, payload) => {
				if (!Type.isPlainObject(payload))
				{
					return;
				}

				store.commit('setCounters', {
					actionName: 'setCounters',
					data: {
						chatCounterMap: payload,
					},
				});
			},

			/** @function commentModel/deleteComments */
			deleteComments: (store, payload) => {

				store.commit('deleteComments', {
					actionName: 'deleteComments',
					data: {},
				});
			},

			/** @function commentModel/deleteChannelCounters */
			deleteChannelCounters: (store, payload) => {
				const { channelId } = payload;
				if (
					store.state.countersCollection[channelId]
					&& store.getters.getChannelCounters(channelId) === 0
				)
				{
					return;
				}
				const commentChatIdList = Object.keys(store.state.countersCollection[channelId])
					.map((id) => Number(id))
				;

				store.commit('deleteChannelCounters', {
					actionName: 'deleteChannelCounters',
					data: {
						channelId,
						commentChatIdList, // using in mutation handler
					},
				});
			},

			/** @function commentModel/deleteCommentByMessageId */
			deleteCommentByMessageId: (store, payload) => {
				const { messageId, channelChatId } = payload;

				const commentInfo = store.state.commentCollection[messageId]
				if (!commentInfo)
				{
					return;
				}

				store.commit('setCounters', {
					actionName: 'deleteCommentByMessageId',
					data: {
						chatCounterMap: {
							[channelChatId]: {
								[commentInfo.chatId]: 0,
							},
						},
					},
				});

				store.commit('deleteComment', {
					actionName: 'deleteCommentByMessageId',
					data: {
						messageId,
						channelChatId,
						commentDialogId: commentInfo.dialogId,
						commentChatId: commentInfo.chatId,
					},
				});
			},

			/** @function commentModel/subscribe */
			subscribe: (store, payload) => {
				const messageId = payload.messageId;
				const commentInfo = {
					...commentDefaultElement,
					...store.state.commentCollection[messageId],
					isUserSubscribed: true,
					messageId,
				};

				store.commit('setComments', {
					actionName: 'subscribe',
					data: {
						commentList: [commentInfo],
					},
				});
			},

			/** @function commentModel/unsubscribe */
			unsubscribe: (store, payload) => {
				const messageId = payload.messageId;
				const commentInfo = {
					...commentDefaultElement,
					...store.state.commentCollection[messageId],
					isUserSubscribed: false,
					messageId,
				};

				store.commit('setComments', {
					actionName: 'subscribe',
					data: {
						commentList: [commentInfo],
					},
				});
			},
			/** @function commentModel/showLoader */
			showLoader: (store, payload) => {
				const messageId = payload.messageId;
				const commentInfo = {
					...commentDefaultElement,
					...store.state.commentCollection[messageId],
					showLoader: true,
					messageId,
				};

				store.commit('setComments', {
					actionName: 'showLoader',
					data: {
						commentList: [commentInfo],
					},
				});
			},
			/** @function commentModel/hideLoader */
			hideLoader: (store, payload) => {
				const messageId = payload.messageId;
				const commentInfo = {
					...commentDefaultElement,
					...store.state.commentCollection[messageId],
					showLoader: false,
					messageId,
				};

				store.commit('setComments', {
					actionName: 'hideLoader',
					data: {
						commentList: [commentInfo],
					},
				});
			},
		},
		mutations: {
			/**
			 *
			 * @param state
			 * @param {MutationPayload<CommentsSetCommentsData, CommentsSetCommentsActions>} payload
			 */
			setComments: (state, payload) => {
				logger.log('commentModel setComments mutation', payload);

				payload.data.commentList.forEach((comment) => {
					state.commentCollection[comment.messageId] = {
						...commentDefaultElement,
						...state.commentCollection[comment.messageId],
						...comment,
					};
				});
			},

			/**
			 *
			 * @param state
			 * @param {MutationPayload<CommentsSetCountersData, CommentsSetCountersActions>} payload
			 */
			setCounters: (state, payload) => {
				logger.log('commentModel setCounters mutation', payload);

				Object.entries(payload.data.chatCounterMap).forEach(([channelChatId, countersMap]) => {
					if (!state.countersCollection[channelChatId])
					{
						state.countersCollection[channelChatId] = {};
					}

					const channelMap = state.countersCollection[channelChatId];
					Object.entries(countersMap).forEach(([commentChatId, counter]) => {
						if (counter === 0)
						{
							delete channelMap[commentChatId];

							return;
						}

						channelMap[commentChatId] = counter;
					});
				});
			},

			setCommentsWithCounters: (state, payload) => {
				logger.log('commentModel setCommentsWithCounters mutation', payload);
				payload.data.commentList.forEach((comment) => {
					state.commentCollection[comment.messageId] = {
						...commentDefaultElement,
						...state.commentCollection[comment.messageId],
						...comment,
					};
				});

				Object.entries(payload.data.chatCounterMap).forEach(([channelChatId, countersMap]) => {
					if (!state.countersCollection[channelChatId])
					{
						state.countersCollection[channelChatId] = {};
					}

					const channelMap = state.countersCollection[channelChatId];
					Object.entries(countersMap).forEach(([commentChatId, counter]) => {
						if (counter === 0)
						{
							delete channelMap[commentChatId];

							return;
						}

						channelMap[commentChatId] = counter;
					});
				});
			},

			deleteComments: (state, payload) => {
				logger.log('commentModel deleteComments mutation', payload);

				state.commentCollection = {};
			},

			/**
			 * @param state
			 * @param {MutationPayload<CommentsDeleteChannelCountersData, CommentsDeleteChannelCountersActions>} payload
			 */
			deleteChannelCounters: (state, payload) => {
				logger.log('commentModel deleteChannelCounters mutation', payload);
				const { channelId } = payload.data;

				state.countersCollection[channelId] = {};
			},

			deleteComment: (state, payload) => {
				logger.log('commentModel deleteComment mutation', payload);
				const { messageId } = payload.data;

				delete state.commentCollection[messageId];
			},
		},
	};

	function getNewLastUsers(newUserId, currentUsers)
	{
		if (currentUsers.includes(newUserId))
		{
			return currentUsers;
		}

		if (currentUsers.length < LAST_USERS_TO_SHOW)
		{
			currentUsers.unshift(newUserId);

			return currentUsers;
		}

		currentUsers.pop();
		currentUsers.unshift(newUserId);

		return currentUsers;
	}

	module.exports = { commentModel, commentDefaultElement };
});
