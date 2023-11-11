/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/messages
 */
jn.define('im/messenger/model/messages', (require, exports, module) => {
	const { Type } = require('type');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { Logger } = require('im/messenger/lib/logger');
	const { Uuid } = require('utils/uuid');
	const { get } = require('utils/object');
	const { reactionsModel } = require('im/messenger/model/messages/reactions');

	const TEMPORARY_MESSAGE_PREFIX = 'temporary';

	const messageState = {
		id: 0,
		templateId: '',
		chatId: 0,
		authorId: 0,
		date: new Date(),
		text: '',
		loadText: '',
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
			pinnedMessages: {},
		}),
		modules: {
			reactionsModel,
		},
		getters: {
			/**
			 * @function messagesModel/getByChatId
			 * @return {MessagesModelState[]}
			*/
			getByChatId: (state, getters, rootState, rootGetters) => (chatId) => {
				if (!state.chatCollection[chatId])
				{
					return [];
				}

				return [...state.chatCollection[chatId]].map((messageId) => {
					return {
						...state.collection[messageId],
						reactions: rootGetters['messagesModel/reactionsModel/getByMessageId'](messageId),
					};
				}).sort((a, b) => {
					if (Type.isNumber(a.id) && Type.isNumber(b.id))
					{
						return a.id - b.id;
					}

					if (Uuid.isV4(a.id) || Uuid.isV4(b.id))
					{
						return a.date - b.date;
					}

					return 0;
				});
			},

			/**
			 * @function messagesModel/getById
			 * @return {MessagesModelState}
			 */
			getById: (state, getters, rootState, rootGetters) => (messageId) => {
				if (!Type.isNumber(messageId) && !Type.isStringFilled(messageId))
				{
					return {};
				}
				const message = state.collection[messageId.toString()];
				if (!message)
				{
					return {};
				}

				return {
					...message,
					reactions: rootGetters['messagesModel/reactionsModel/getByMessageId'](messageId),
				};
			},

			/**
			 * @function messagesModel/getByTemplateId
			 * @return {MessagesModelState|null}
			 */
			getByTemplateId: (state) => (messageId) => {
				if (Type.isNumber(messageId))
				{
					return null;
				}

				return state.collection[messageId] || null;
			},

			/**
			 * @function messagesModel/getMessageFiles
			 * @return {FilesModelState[]}
			 */
			getMessageFiles: (state, getters, rootState, rootGetters) => (messageId) => {
				if (!state.collection[messageId])
				{
					return [];
				}

				return state.collection[messageId].files.map((fileId) => {
					return rootGetters['filesModel/getById'](fileId);
				});
			},

			isInChatCollection: (state) => (payload) => {
				const { messageId } = payload;
				const message = state.collection[messageId];
				if (!message)
				{
					return false;
				}

				const { chatId } = message;

				return state.chatCollection[chatId]?.has(messageId);
			},

			/**
			 * @function messagesModel/getFirstId
			 * @return number|null|undefined
			 */
			getFirstId: (state) => (chatId) => {
				if (!state.chatCollection[chatId])
				{
					return false;
				}

				let firstId = null;
				const messages = [...state.chatCollection[chatId]];
				for (const message of messages)
				{
					const element = state.collection[message];
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
			getLastId: (state) => (chatId) => {
				if (!state.chatCollection[chatId])
				{
					return false;
				}

				let lastId = 0;
				const messages = [...state.chatCollection[chatId]];
				for (const message of messages)
				{
					const element = state.collection[message];
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
		},
		actions: {
			/** @function messagesModel/forceUpdateByChatId */
			forceUpdateByChatId: (store, { chatId }) => {
				const messages = store.getters.getByChatId(chatId);

				store.commit('store', {
					actionName: 'forceUpdateByChatId',
					data: {
						messageList: messages,
					},
				});
				store.commit('setChatCollection', {
					actionName: 'forceUpdateByChatId',
					data: {
						messageList: messages,
					},
				});
			},

			/** @function messagesModel/setChatCollection */
			setChatCollection: (store, { messages, clearCollection }) => {
				clearCollection = clearCollection || false;
				if (!Array.isArray(messages) && Type.isPlainObject(messages))
				{
					messages = [messages];
				}

				messages = messages.map((message) => {
					return { ...messageState, ...validate(message) };
				});

				const chatId = messages[0]?.chatId;
				if (chatId && clearCollection)
				{
					store.commit('clearCollection', {
						actionName: 'setChatCollection',
						data: {
							chatId,
						},
					});
				}

				store.commit('store', {
					actionName: 'setChatCollection',
					data: {
						messageList: messages,
					},
				});
				store.commit('setChatCollection', {
					actionName: 'setChatCollection',
					data: {
						messageList: messages,
					},
				});
			},

			/** @function messagesModel/store */
			store: (store, messages) => {
				if (!Array.isArray(messages) && Type.isPlainObject(messages))
				{
					messages = [messages];
				}

				messages = messages.map((message) => {
					return { ...messageState, ...validate(message) };
				});

				if (messages.length === 0)
				{
					return;
				}

				store.commit('store', {
					actionName: 'store',
					data: {
						messageList: messages,
					},
				});
			},

			/** @function messagesModel/add */
			add: (store, payload) => {
				const message = {
					...messageState,
					...validate(payload),
				};
				store.commit('store', {
					actionName: 'add',
					data: {
						messageList: [message],
					},
				});
				store.commit('setChatCollection', {
					actionName: 'add',
					data: {
						messageList: [message],
					},
				});
			},

			/** @function messagesModel/setPinned */
			setPinned: (store, { chatId, pinnedMessages }) => {
				if (pinnedMessages.length === 0)
				{
					return;
				}

				store.commit('setPinned', {
					actionName: 'setPinned',
					data: {
						chatId,
						pinnedMessageIds: pinnedMessages,
					},
				});
			},

			/** @function messagesModel/updateWithId */
			updateWithId: (store, payload) => {
				const { id, fields } = payload;
				if (!store.state.collection[id])
				{
					return;
				}

				store.commit('updateWithId', {
					actionName: 'updateWithId',
					data: {
						id,
						fields: validate(fields),
					},
				});
			},

			/** @function messagesModel/update */
			update: (store, { id, fields }) => {
				if (!store.state.collection[id])
				{
					return;
				}

				store.commit('update', {
					actionName: 'update',
					data: {
						id,
						fields: validate(fields),
					},
				});
			},

			/** @function messagesModel/delete */
			delete: (store, payload) => {
				const { id } = payload;
				if (!store.state.collection[id])
				{
					return false;
				}

				store.commit('delete', {
					actionName: 'delete',
					data: {
						id,
					},
				});

				return true;
			},

			/** @function messagesModel/readMessages */
			readMessages: (store, { chatId, messageIds }) => {
				if (!store.state.chatCollection[chatId])
				{
					return 0;
				}

				const chatMessages = [...store.state.chatCollection[chatId]].map((messageId) => {
					return store.state.collection[messageId];
				});

				let messagesToReadCount = 0;

				let maxMessageId = 0;
				messageIds.forEach((messageId) => {
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

				messageIdsToRead.forEach((messageId) => {
					store.commit('update', {
						actionName: 'readMessages',
						data: {
							id: messageId,
							fields: {
								unread: false,
							},
						},
					});
				});

				messageIdsToView.forEach((messageId) => {
					store.commit('update', {
						actionName: 'readMessages',
						data: {
							id: messageId,
							fields: {
								viewed: true,
							},
						},
					});
				});

				return messagesToReadCount;
			},

			/** @function messagesModel/setViewedByOthers */
			setViewedByOthers: (store, { messageIds }) => {
				messageIds.forEach((id) => {
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
						actionName: 'setViewedByOthers',
						data: {
							id,
							fields: {
								viewedByOthers: true,
							},
						},
					});
				});
			},

			/** @function messagesModel/updateLoadTextProgress */
			updateLoadTextProgress(store, payload) {
				const message = store.state.collection[payload.id];
				if (!message)
				{
					return;
				}

				const { loadText: currentLoadText } = message;
				if (currentLoadText === payload.loadText)
				{
					return;
				}

				store.commit('update', {
					actionName: 'updateLoadTextProgress',
					data: {
						id: payload.id,
						fields: { loadText: payload.loadText },
					},
				});
			},

			/** @function messagesModel/setAudioState */
			setAudioState(store, payload) {
				const message = store.state.collection[payload.id];
				if (!message)
				{
					return;
				}

				const fieldsToUpdate = {};
				if (!Type.isUndefined(payload.audioPlaying))
				{
					fieldsToUpdate.audioPlaying = payload.audioPlaying;
				}

				if (!Type.isUndefined(payload.playingTime))
				{
					fieldsToUpdate.playingTime = payload.playingTime;
				}

				store.commit('update', {
					actionName: 'setAudioState',
					data: {
						id: payload.id,
						fields: fieldsToUpdate,
					},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			setChatCollection: (state, payload) => {
				Logger.warn('messagesModel: setChatCollection mutation', payload);

				const {
					messageList,
				} = payload.data;

				messageList.forEach((message) => {
					if (!state.chatCollection[message.chatId])
					{
						state.chatCollection[message.chatId] = new Set();
					}
					state.chatCollection[message.chatId].add(message.id);
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			store: (state, payload) => {
				Logger.warn('messagesModel: store mutation', payload);

				const {
					messageList,
				} = payload.data;

				messageList.forEach((message) => {
					message.params = { ...messageState.params, ...message.params };

					state.collection[message.id] = message;
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			setPinned: (state, payload) => {
				Logger.warn('messagesModel: setPinned mutation', payload);

				const {
					chatId,
					pinnedMessageIds,
				} = payload.data;

				if (!state.pinnedMessages[chatId])
				{
					state.pinnedMessages[chatId] = new Set();
				}

				pinnedMessageIds.forEach((pinnedMessageId) => {
					state.pinnedMessages[chatId].add(pinnedMessageId);
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			updateWithId: (state, payload) => {
				Logger.warn('messagesModel: updateWithId mutation', payload);

				const {
					id,
					fields,
				} = payload.data;
				const currentMessage = { ...state.collection[id] };

				delete state.collection[id];
				state.collection[fields.id] = { ...currentMessage, ...fields, sending: false };

				if (state.chatCollection[currentMessage.chatId].has(id))
				{
					state.chatCollection[currentMessage.chatId].delete(id);
					state.chatCollection[currentMessage.chatId].add(fields.id);
				}
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			update: (state, payload) => {
				Logger.warn('messagesModel: update mutation', payload);

				const {
					id,
					fields,
				} = payload.data;

				state.collection[id] = {
					...state.collection[id],
					...fields,
				};
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			delete: (state, payload) => {
				Logger.warn('messagesModel: delete mutation', payload);
				const {
					id,
				} = payload.data;
				const { chatId } = state.collection[id];

				state.chatCollection[chatId].delete(id);
				delete state.collection[id];
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			clearCollection: (state, payload) => {
				Logger.warn('messagesModel: clear collection mutation', payload);
				const {
					chatId,
				} = payload.data;

				state.chatCollection[chatId] = new Set();
			},
		},
	};

	function validate(fields)
	{
		const result = {};

		if (Type.isNumber(fields.id))
		{
			result.id = fields.id;
		}
		else if (Uuid.isV4(fields.templateId))
		{
			result.id = fields.templateId;
			result.templateId = fields.templateId;
		}
		else if (Uuid.isV4(fields.id))
		{
			result.id = fields.id;
			result.templateId = fields.id;
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

		if (Type.isNumber(fields.loadText) || Type.isStringFilled(fields.loadText))
		{
			result.loadText = fields.loadText.toString();
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
			const { params, fileIds } = validateParams(fields.params);
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
				params.componentId = value;
			}
			else if (key === 'LIKE' && Type.isArray(value))
			{
				params.REACTION = { like: value.map((element) => Number.parseInt(element, 10)) };
			}
			else if (key === 'FILE_ID' && Type.isArray(value))
			{
				fileIds = value;
			}
			else if (key === 'REPLY_ID')
			{
				params.replyId = Type.isString(value) ? parseInt(value, 10) : value;
			}
			else
			{
				params[key] = value;
			}
		});

		return { params, fileIds };
	}

	module.exports = {
		messagesModel,
	};
});
