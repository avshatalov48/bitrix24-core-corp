/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/model/messages
 */
jn.define('im/messenger/model/messages', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { MessagesCache } = jn.require('im/messenger/cache');
	const { DateHelper } = jn.require('im/messenger/lib/helper');

	const messageState = {
		id: 0,
		dialogId: 0,
		authorId: 0,
		date: new Date(),
		text: '',
		textConverted: '',
		isRead: true,
		reactionCollection: {
			likeList: [],
		},
		params: {},
	};

	const messagesModel = {
		namespaced: true,
		state: () => ({
			collection: {},
			saveMessageList: {},
		}),
		getters: {
			getDialogPage: (state) => (dialogId, pageNumber, itemsPerPage) => {
				const messageList = state.collection[dialogId];
				if (messageList)
				{
					return [...messageList].splice((pageNumber - 1) * itemsPerPage, itemsPerPage);
				}

				return [];
			},
			getLastMessageIdByPage: (state) => (dialogId, pageNumber, itemsPerPage) => {
				if (pageNumber === 0 || itemsPerPage === 0)
				{
					return 0;
				}

				const messageList = state.collection[dialogId];
				if (!messageList)
				{
					return 0;
				}

				if (pageNumber === 1)
				{
					return messageList.pop().id;
				}

				const lastMessage = [...messageList].splice(((pageNumber - 1) * itemsPerPage) - 1, 1);
				if (!lastMessage)
				{
					return 0;
				}

				return lastMessage.pop().id;
			},
			get: (state) => (dialogId, messageId) => {
				if (!state.saveMessageList[dialogId])
				{
					return;
				}

				if (!state.saveMessageList[dialogId][messageId])
				{
					return;
				}

				return state.collection[dialogId].find(message => message.id === messageId);
			}
		},
		actions: {
			setState: (store, payload) =>
			{
				store.commit('setState', payload);
			},
			add: (store, payload) =>
			{
				const result = {
					dialogId: payload.dialogId,
					messages: payload.messages.map(message => {
						message.dialogId = payload.dialogId;

						return {
							...messageState,
							...validate(message),
						};
					}),
				};

				store.commit('add', result);
			},
			push: (store, payload) =>
			{
				const message = {
					...messageState,
					...validate(payload.message),
				};

				if (!message.id && message.templateId)
				{
					message.id = message.templateId;
				}

				const result = {
					dialogId: payload.dialogId,
					message,
				};

				if (hasMessage(store, payload))
				{
					store.commit('update', result);
					return;
				}

				store.commit('push', result);
			},
			setLikes: (store, payload) =>
			{
				const message = store.state.collection[payload.dialogId][payload.index];
				if (!message)
				{
					return;
				}

				store.commit('setLikes', payload);
			}
		},
		mutations: {
			setState: (state, payload) => {
				state.host = payload.host;
				state.collection = payload.collection;
				state.saveMessageList = payload.saveMessageList;
			},
			add: (state, payload) => {
				if (!state.collection[payload.dialogId])
				{
					state.collection[payload.dialogId] = [];
				}

				if (!state.saveMessageList[payload.dialogId])
				{
					state.saveMessageList[payload.dialogId] = {};
				}

				payload.messages.forEach((message) => {
					if (state.saveMessageList[payload.dialogId][message.id])
					{
						const index = state.collection[payload.dialogId].findIndex(item => item.id === message.id);
						state.collection[payload.dialogId][index] = message;

						return;
					}

					state.collection[payload.dialogId].push(message);
					state.saveMessageList[payload.dialogId][message.id] = true;
				});

				state.collection[payload.dialogId].sort((a, b) => b.id - a.id);

				MessagesCache.save(state);
			},
			push: (state, payload) => {
				if (!state.collection[payload.dialogId])
				{
					state.collection[payload.dialogId] = [];
				}

				if (!state.saveMessageList[payload.dialogId])
				{
					state.saveMessageList[payload.dialogId] = {};
				}

				state.collection[payload.dialogId].push(payload.message);
				state.saveMessageList[payload.dialogId][payload.message.id] = true;

				state.collection[payload.dialogId].sort((a, b) => b.id - a.id);

				MessagesCache.save(state);
			},
			update: (state, payload) =>
			{
				if (state.saveMessageList[payload.dialogId][payload.message.templateId])
				{
					const index = state.collection[payload.dialogId].findIndex(item => item.id === payload.message.templateId);
					state.collection[payload.dialogId][index] = payload.message;

					delete state.saveMessageList[payload.dialogId][payload.message.templateId];
					state.saveMessageList[payload.dialogId][payload.message.id] = true;
				}
				else if (state.saveMessageList[payload.dialogId][payload.message.id])
				{
					const index = state.collection[payload.dialogId].findIndex(item => item.id === payload.message.id);
					state.collection[payload.dialogId][index] = payload.message;
				}

				state.collection[payload.dialogId].sort((a, b) => b.id - a.id);

				MessagesCache.save(state);
			},
			setLikes: (state, payload) =>
			{
				const message = state.collection[payload.dialogId][payload.index];
				if (!message.params)
				{
					message.params = {};
				}

				message.params.LIKE = payload.likeList;

				MessagesCache.save(state);
			}
		}
	};

	function validate(fields)
	{
		const result = {};

		if (Type.isNumber(fields.id))
		{
			result.id = fields.id;
		}

		if (Type.isString(fields.dialogId))
		{
			result.dialogId = fields.dialogId;
		}

		if (!Type.isUndefined(fields.senderId))
		{
			fields.authorId = fields.senderId;
		}
		else if (!Type.isUndefined(fields.author_id))
		{
			fields.authorId = fields.author_id;
		}
		if (Type.isNumber(fields.authorId) || Type.isString(fields.authorId))
		{
			if (fields.system === true || fields.system === 'Y')
			{
				result.authorId = 0;
			}
			else
			{
				result.authorId = parseInt(fields.authorId);
			}
		}

		if (!Type.isUndefined(fields.date))
		{
			result.date = DateHelper.toDate(fields.date);
		}

		if (Type.isString(fields.text) || Type.isNumber(fields.text))
		{
			result.text = fields.text.toString();
		}

		if (Type.isBoolean(fields.unread))
		{
			result.isRead = !fields.unread;
		}

		result.reactionCollection = {
			likeList: [],
		};
		if (fields.params)
		{
			if (Type.isArrayFilled(fields.params.LIKE))
			{
				result.reactionCollection.likeList = fields.params.LIKE;
			}

			result.params = fields.params;
		}

		return result;
	}

	function hasMessage(store, payload)
	{
		if (!store.state.saveMessageList[payload.dialogId])
		{
			return false;
		}

		return (
			store.state.saveMessageList[payload.dialogId][payload.message.templateId]
			|| store.state.saveMessageList[payload.dialogId][payload.message.id]
		)
	}

	module.exports = { messagesModel };
});
