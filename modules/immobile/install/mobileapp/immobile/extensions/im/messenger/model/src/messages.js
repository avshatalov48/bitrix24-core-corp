/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/model/messages
 */
jn.define('im/messenger/model/messages', (require, exports, module) => {

	const { Type } = require('type');
	const { MessagesCache } = require('im/messenger/cache');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { Logger } = require('im/messenger/lib/logger');
	const { Uuid } = require('utils/uuid');
	const { get } = require('utils/object');

	const TEMPORARY_MESSAGE_PREFIX = 'temporary';

	const messageState = {
		id: 0,
		uuid: '',
		chatId: 0,
		authorId: 0,
		date: new Date(),
		text: '',
		params: {},
		replaces: [],
		files: [],
		unread: false,
		viewed: true,
		viewedByOthers: false,
		sending: false,
		error: false,
		retry: false,
		audioPlaying: false,
		playingTime: 0,
	};

	const messagesModel = {
		namespaced: true,
		state: () => ({
			collection: {},
			chatCollection: {},
			pinnedMessages: {}
		}),
		getters: {
			/** @function messagesModel/getByChatId */
			getByChatId: (state) => (chatId) =>
			{
				if (!state.chatCollection[chatId])
				{
					return [];
				}

				return [...state.chatCollection[chatId]].map(messageId => {
					return state.collection[messageId];
				}).sort((a, b) => {
					if (Type.isNumber(a.id) && Type.isNumber(b.id))
					{
						return a.id - b.id;
					}

					if (Uuid.isV4(a.id) || Uuid.isV4(b.id))
					{
						return a.date - b.date;
					}
				});
			},

			/** @function messagesModel/getMessageById */
			getMessageById: (state) => (messageId) =>
			{
				return state.collection[messageId.toString()] || {};
			},

			/** @function messagesModel/getMessageFiles */
			getMessageFiles: (state) => (messageId) =>
			{
				if (!state.collection[messageId])
				{
					return [];
				}

				return state.collection[messageId].files.map(fileId => {
					return store.rootGetters['filesModel/getById'](fileId);
				});
			},

			/** @function messagesModel/getFirstId */
			getFirstId: (state) => (chatId) =>
			{
				if (!state.chatCollection[chatId])
				{
					return;
				}

				let firstId = null;
				const messages = [...state.chatCollection[chatId]];
				for (let i = 0; i < messages.length; i++)
				{
					const element = state.collection[messages[i]];
					if (!firstId)
					{
						firstId = element.id;
					}
					if (element.id.toString().startsWith(TEMPORARY_MESSAGE_PREFIX))
					{
						continue;
					}

					if (element.id < firstId)
					{
						firstId = element.id;
					}
				}

				return firstId;
			},

			/** @function messagesModel/getLastId */
			getLastId: (state) => (chatId) =>
			{
				if (!state.chatCollection[chatId])
				{
					return;
				}

				let lastId = 0;
				const messages = [...state.chatCollection[chatId]];
				for (let i = 0; i < messages.length; i++)
				{
					const element = state.collection[messages[i]];
					if (element.id.toString().startsWith(TEMPORARY_MESSAGE_PREFIX))
					{
						continue;
					}

					if (element.id > lastId)
					{
						lastId = element.id;
					}
				}

				return lastId;
			},

			/** @function messagesModel/getMessageReaction */
			getMessageReaction: (state) => (messageId, reactionId = null) =>
			{
				const message = state.collection[messageId.toString()];
				if (!message)
				{
					return;
				}

				if (!reactionId)
				{
					return get(message, 'params.REACTION', {});
				}

				return get(message, `params.REACTION.${reactionId}`, []);
			},
		},
		actions: {
			/** @function messagesModel/forceUpdateByChatId */
			forceUpdateByChatId: (store, { chatId }) =>
			{
				const messages = store.getters['getByChatId'](chatId);

				store.commit('store', { messages });
				store.commit('setChatCollection', { messages });
			},

			/** @function messagesModel/setChatCollection */
			setChatCollection: (store, { messages, clearCollection }) =>
			{
				clearCollection = clearCollection || false;
				if (!Array.isArray(messages) && Type.isPlainObject(messages))
				{
					messages = [messages];
				}

				messages = messages.map(message => {
					return {...messageState, ...validate(message)};
				});

				const chatId = messages[0]?.chatId;
				if (chatId && clearCollection)
				{
					store.commit('clearCollection', {chatId});
				}

				store.commit('store', { messages });
				store.commit('setChatCollection', { messages });
			},

			/** @function messagesModel/store */
			store: (store, messages) =>
			{
				if (!Array.isArray(messages) && Type.isPlainObject(messages))
				{
					messages = [messages];
				}

				messages = messages.map(message => {
					return {...messageState, ...validate(message)};
				});

				if (messages.length === 0)
				{
					return;
				}

				store.commit('store', {
					messages,
				});
			},

			/** @function messagesModel/add */
			add: (store, payload) =>
			{
				//const temporaryId = generateMessageId();
				const message = {
					...messageState,
					...validate(payload),
					//id: temporaryId
				};
				store.commit('store', {
					messages: [message]
				});
				store.commit('setChatCollection', {
					messages: [message]
				});

				//return temporaryId;
			},

			/** @function messagesModel/setPinned */
			setPinned: (store, { chatId, pinnedMessages }) =>
			{
				if (pinnedMessages.length === 0)
				{
					return;
				}

				store.commit('setPinned', {
					chatId,
					pinnedMessageIds: pinnedMessages
				});
			},

			/** @function messagesModel/updateWithId */
			updateWithId: (store, payload) =>
			{
				const {id, fields} = payload;
				if (!store.state.collection[id])
				{
					return;
				}

				store.commit('updateWithId', {
					id,
					fields: validate(fields),
				});
			},

			/** @function messagesModel/update */
			update: (store, { id, fields }) =>
			{
				if (!store.state.collection[id])
				{
					return;
				}

				store.commit('update', {
					id,
					fields: validate(fields),
				});
			},

			/** @function messagesModel/setReaction */
			setReaction: (store, payload) =>
			{
				const {
					messageId,
					reactionId,
					userList,
				} = payload;

				const message = store.rootGetters['messagesModel/getMessageById'](messageId);
				if (!message)
				{
					return;
				}

				const reactionCollection = {};
				reactionCollection[reactionId] = userList;

				if (!message.params.REACTION)
				{
					message.params.REACTION = {};
				}
				message.params.REACTION = reactionCollection;

				store.commit('update', {
					id: messageId,
					fields: message,
				});
			},

			/** @function messagesModel/addReaction */
			addReaction: (store, payload) =>
			{
				const {
					messageId,
					reactionId,
					userList,
				} = payload;

				const message = store.rootGetters['messagesModel/getMessageById'](messageId);
				if (!message)
				{
					return;
				}

				let reactionUserList = get(message, `params.REACTION.${reactionId}`, []);
				reactionUserList = Array.from(new Set(reactionUserList.concat(userList)));

				const reactionCollection = {};
				reactionCollection[reactionId] = reactionUserList;

				if (!message.params.REACTION)
				{
					message.params.REACTION = {};
				}
				message.params.REACTION = reactionCollection;

				store.commit('update', {
					id: messageId,
					fields: message,
				});
			},

			/** @function messagesModel/removeReaction */
			removeReaction: (store, payload) =>
			{
				const {
					messageId,
					reactionId,
					userList,
				} = payload;

				const message = store.rootGetters['messagesModel/getMessageById'](messageId);
				if (!message)
				{
					return;
				}

				const userListIndex = {};
				userList.forEach(userId => {
					userListIndex[userId] = true;
				});

				let reactionUserList = get(message, `params.REACTION.${reactionId}`, []);
				if (reactionUserList.length === 0)
				{
					return;
				}

				reactionUserList = reactionUserList.filter(userId => !userListIndex[userId]);

				const reactionCollection = {};
				reactionCollection[reactionId] = reactionUserList;

				if (!message.params.REACTION)
				{
					message.params.REACTION = {};
				}
				message.params.REACTION = reactionCollection;

				store.commit('update', {
					id: messageId,
					fields: message,
				});
			},

			/** @function messagesModel/readMessages */
			readMessages: (store, { chatId, messageIds }) =>
			{
				if (!store.state.chatCollection[chatId])
				{
					return 0;
				}

				const chatMessages = [...store.state.chatCollection[chatId]].map((messageId) => {
					return store.state.collection[messageId];
				});

				let messagesToReadCount = 0;

				let maxMessageId = 0;
				messageIds.forEach(messageId => {
					if (maxMessageId < messageId)
					{
						maxMessageId = messageId;
					}
				});

				const messageIdsToView = messageIds;
				const messageIdsToRead = [];
				chatMessages.forEach((chatMessage) => {
					if (!chatMessage.unread)
					{
						return;
					}

					if (chatMessage.id <= maxMessageId)
					{
						messagesToReadCount++;
						messageIdsToRead.push(chatMessage.id);
					}
				});

				store.commit('readMessages', {
					messageIdsToRead,
					messageIdsToView
				});

				return messagesToReadCount;
			},

			/** @function messagesModel/setViewedByOthers */
			setViewedByOthers: (store, { messageIds }) =>
			{
				messageIds.forEach(id => {
					const message = store.state.collection[id];
					if (!message)
					{
						return;
					}

					const isOwnMessage = message.authorId === MessengerParams.getUserId();
					if (!isOwnMessage || message.viewedByOthers)
					{
						return;
					}

					store.commit('update', {
						id,
						fields: {
							viewedByOthers: true,
						},
					});
				});
			},
		},
		mutations: {
			setChatCollection: (state, payload) =>
			{
				Logger.warn('Messages model: setChatCollection mutation', payload);
				payload.messages.forEach(message => {
					if (!state.chatCollection[message.chatId])
					{
						state.chatCollection[message.chatId] = new Set();
					}
					state.chatCollection[message.chatId].add(message.id);
				});
			},
			store: (state, payload) =>
			{
				Logger.warn('Messages model: store mutation', payload);
				payload.messages.forEach(message => {
					message.params = {...messageState.params, ...message.params};

					state.collection[message.id] = message;
				});
			},
			setPinned: (state, payload) =>
			{
				Logger.warn('Messages model: setPinned mutation', payload);
				const {chatId, pinnedMessageIds} = payload;
				if (!state.pinnedMessages[chatId])
				{
					state.pinnedMessages[chatId] = new Set();
				}

				pinnedMessageIds.forEach(pinnedMessageId => {
					state.pinnedMessages[chatId].add(pinnedMessageId);
				});
			},
			updateWithId: (state, payload) =>
			{
				Logger.warn('Messages model: updateWithId mutation', payload);
				const {id, fields} = payload;
				const currentMessage = {...state.collection[id]};

				delete state.collection[id];
				state.collection[fields.id] = {...currentMessage, ...fields, sending: false};

				if (state.chatCollection[currentMessage.chatId].has(id))
				{
					state.chatCollection[currentMessage.chatId].delete(id);
					state.chatCollection[currentMessage.chatId].add(fields.id);
				}
			},
			update: (state, payload) =>
			{
				Logger.warn('Messages model: update mutation', payload);
				const {id, fields} = payload;
				state.collection[id] = {
					...state.collection[id],
					...fields,
				};
			},
			clearCollection: (state, payload) =>
			{
				Logger.warn('Messages model: clear collection mutation', payload.chatId);
				state.chatCollection[payload.chatId] = new Set();
			},
			readMessages: (state, { messageIdsToRead, messageIdsToView }) =>
			{
				messageIdsToRead.forEach(messageId => {
					const message = state.collection[messageId];
					if (!message)
					{
						return;
					}

					message.unread = false;
				});
				messageIdsToView.forEach(messageId => {
					const message = state.collection[messageId];
					if (!message)
					{
						return;
					}

					message.viewed = true;
				});
			},
		}
	};

	function validate(fields)
	{
		const result = {};

		if (Type.isNumber(fields.id))
		{
			result.id = fields.id;
		}
		else if (Uuid.isV4(fields.uuid))
		{
			result.id = fields.uuid;
			result.uuid = fields.uuid;
		}
		else if (Uuid.isV4(fields.id))
		{
			result.id = fields.id;
			result.uuid = fields.id;
		}

		if (!Type.isUndefined(fields.chat_id))
		{
			fields.chatId = fields.chat_id;
		}
		if (Type.isNumber(fields.chatId) || Type.isStringFilled(fields.chatId))
		{
			result.chatId = Number.parseInt(fields.chatId, 10);
		}

		if (Type.isStringFilled(fields.date))
		{
			result.date = DateHelper.cast(fields.date);
		}
		else if (Type.isDate(fields.date))
		{
			result.date = fields.date;
		}

		if (Type.isNumber(fields.text) || Type.isStringFilled(fields.text))
		{
			result.text = fields.text.toString();
		}

		if (!Type.isUndefined(fields.senderId))
		{
			fields.authorId = fields.senderId;
		}
		else if (!Type.isUndefined(fields.author_id))
		{
			fields.authorId = fields.author_id;
		}
		if (Type.isNumber(fields.authorId) || Type.isStringFilled(fields.authorId))
		{
			if (
				fields.system === true
				|| fields.system === 'Y'
				|| fields.isSystem === true
			)
			{
				result.authorId = 0;
			}
			else
			{
				result.authorId = Number.parseInt(fields.authorId, 10);
			}
		}

		if (Type.isPlainObject(fields.params))
		{
			const {params, fileIds} = validateParams(fields.params);
			result.params = params;
			result.files = fileIds;
		}

		if (Type.isPlainObject(fields.reactionCollection))
		{
			if (!result.params)
			{
				result.params = {};
			}

			if (!result.params.REACTION)
			{
				result.params.REACTION = {};
			}

			Object.entries(fields.reactionCollection).forEach(([key, value]) => {
				result.params.REACTION[key] = value;
			});
		}

		if (Type.isArray(fields.replaces))
		{
			result.replaces = fields.replaces;
		}

		if (Type.isBoolean(fields.sending))
		{
			result.sending = fields.sending;
		}

		if (Type.isBoolean(fields.unread))
		{
			result.unread = fields.unread;
		}

		if (Type.isBoolean(fields.viewed))
		{
			result.viewed = fields.viewed;
		}

		if (Type.isBoolean(fields.viewedByOthers))
		{
			result.viewedByOthers = fields.viewedByOthers;
		}

		if (Type.isBoolean(fields.error))
		{
			result.error = fields.error;
		}

		if (Type.isBoolean(fields.retry))
		{
			result.retry = fields.retry;
		}

		if (Type.isBoolean(fields.audioPlaying))
		{
			result.audioPlaying = fields.audioPlaying;
		}

		if (Type.isNumber(fields.playingTime))
		{
			result.playingTime = fields.playingTime;
		}

		return result;
	}

	function validateParams(rawParams)
	{
		const params = {};
		let fileIds = [];
		Object.entries(rawParams).forEach(([key, value]) => {
			if (key === 'COMPONENT_ID' && Type.isStringFilled(value))
			{
				params['componentId'] = value;
			}
			else if (key === 'LIKE' && Type.isArray(value))
			{
				params['REACTION'] = {like: value.map(element => Number.parseInt(element, 10))};
			}
			else if (key === 'FILE_ID' && Type.isArray(value))
			{
				fileIds = value.map(fileId => Number(fileId));
			}
			else
			{
				params[key] = value;
			}
		});

		return {params, fileIds};
	}

	module.exports = {
		messagesModel,
	};
});
