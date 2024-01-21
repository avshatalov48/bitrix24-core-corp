/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/messages
 */
jn.define('im/messenger/model/messages', (require, exports, module) => {
	const { Type } = require('type');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { Uuid } = require('utils/uuid');
	const { get, clone } = require('utils/object');
	const { reactionsModel } = require('im/messenger/model/messages/reactions');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { ObjectUtils } = require('im/messenger/lib/utils');

	const logger = LoggerManager.getInstance().getLogger('model--messages');

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
		errorReason: 0, // code from rest/classes/general/rest.php:25
		retry: false,
		audioPlaying: false,
		playingTime: 0,
		attach: [],
		richLinkId: null,
		forward: {},
	};

	const messagesModel = {
		namespaced: true,
		state: () => ({
			collection: {},
			chatCollection: {},
			pinnedMessages: {},
			temporaryMessages: {},
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
				}).sort((a, b) => sortCollection(a, b));
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

			/**
			 * @function messagesModel/isInChatCollection
			 * @return {Boolean}
			 */
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

			/** @function messagesModel/getBreakMessages */
			getBreakMessages: (state) => (chatId) => {
				const allCollectionList = clone(state.collection);

				if (!allCollectionList || !Type.isNumber(chatId))
				{
					return [];
				}

				const list = [];
				for (const messageId of Object.keys(allCollectionList))
				{
					const message = allCollectionList[messageId];
					if (message.chatId === chatId && message.error && message.sending)
					{
						list.push(message);
					}
				}

				return list;
			},

			/** @function messagesModel/getTemporaryMessagesMessages */
			getTemporaryMessagesMessages: (state) => {
				return clone(state.temporaryMessages);
			},

			/** @function messagesModel/getTemporaryMessageById */
			getTemporaryMessageById: (state) => (messageId) => {
				if (!Type.isNumber(messageId) && !Type.isStringFilled(messageId))
				{
					return null;
				}

				const message = state.temporaryMessages[messageId.toString()];
				if (!message)
				{
					return null;
				}

				return clone(message);
			},

			/**
			 * @function messagesModel/getFirstUnreadId
			 * @return {number || null}
			 */
			getFirstUnreadId: (state) => (chatId) => {
				if (!state.chatCollection[chatId])
				{
					return null;
				}
				const messageIds = [...state.chatCollection[chatId]].sort();

				for (const messageId of messageIds)
				{
					const message = state.collection[messageId];
					if (message.unread)
					{
						return messageId;
					}
				}

				return null;
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

			/** @function messagesModel/addToChatCollection */
			addToChatCollection: (store, payload) => {
				if (!store.state.collection[payload.id])
				{
					return;
				}

				const message = {
					...messageState,
					...validate(payload),
				};

				store.commit('setChatCollection', {
					actionName: 'addToChatCollection',
					data: {
						messageList: [message],
					},
				});
			},

			/** @function messagesModel/setTemporaryMessages */
			setTemporaryMessages: (store, messages) => {
				if (!Array.isArray(messages) && Type.isPlainObject(messages))
				{
					messages = [messages];
				}

				messages = messages.map((message) => {
					return { ...messageState, ...validate(message) };
				});

				store.commit('setTemporaryMessages', {
					actionName: 'setTemporaryMessages',
					data: {
						messageList: messages,
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

			/** @function messagesModel/deleteByChatId */
			deleteByChatId: (store, payload) => {
				const chatId = parseInt(payload.chatId, 10);

				store.commit('deleteByChatId', {
					actionName: 'deleteByChatId',
					data: {
						chatId,
					},
				});
			},

			/** @function messagesModel/deleteByIdList */
			deleteByIdList: (store, payload) => {
				const { idList } = payload;

				idList.forEach((id) => {
					store.commit('delete', {
						actionName: 'deleteByIdList',
						data: {
							id,
						},
					});
				});
			},

			/** @function messagesModel/delete */
			delete: (store, payload) => {
				const { id } = payload;

				store.commit('delete', {
					actionName: 'delete',
					data: {
						id,
					},
				});
			},

			/** @function messagesModel/clearCollectionByDialogId */
			clearCollectionByDialogId: (store, dialogId) => {
				store.commit('clearCollection', { dialogId });
			},

			/** @function messagesModel/setReaction */
			setReaction: (store, payload) => {
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
			addReaction: (store, payload) => {
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
				reactionUserList = [...new Set(reactionUserList.concat(userList))];

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
			removeReaction: (store, payload) => {
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
				userList.forEach((userId) => {
					userListIndex[userId] = true;
				});

				let reactionUserList = get(message, `params.REACTION.${reactionId}`, []);
				if (reactionUserList.length === 0)
				{
					return;
				}

				reactionUserList = reactionUserList.filter((userId) => !userListIndex[userId]);

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
			/** @function messagesModel/deleteAttach */
			deleteAttach: (store, payload) => {
				const { messageId, attachId } = payload;

				/** @type {MessagesModelState} */
				const message = store.state.collection[messageId];

				if (!Type.isArray(message?.attach))
				{
					return;
				}

				const attach = message.attach.filter((attachItem) => {
					return attachItem.id !== attachId;
				});

				store.commit('update', {
					actionName: 'deleteAttach',
					data: {
						id: messageId,
						fields: {
							attach,
							richLinkId: null,
						},
					},
				});
			},

			/** @function messagesModel/deleteTemporaryMessage */
			deleteTemporaryMessage: (store, payload) => {
				const { id } = payload;
				if (!Type.isNumber(id) && !Type.isStringFilled(id))
				{
					return false;
				}

				if (!store.state.temporaryMessages[id])
				{
					return false;
				}

				store.commit('deleteTemporaryMessage', {
					actionName: 'deleteTemporaryMessage',
					data: {
						id,
					},
				});

				return true;
			},

			/** @function messagesModel/deleteTemporaryMessages */
			deleteTemporaryMessages: (store, payload) => {
				const { ids } = payload;
				if (!Type.isArray(ids) && !Type.isArrayFilled(ids))
				{
					return false;
				}

				store.commit('deleteTemporaryMessages', {
					actionName: 'deleteTemporaryMessages',
					data: {
						ids,
					},
				});

				return true;
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			setChatCollection: (state, payload) => {
				logger.log('messagesModel: setChatCollection mutation', payload);

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
				logger.log('messagesModel: store mutation', payload);

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
			setTemporaryMessages: (state, payload) => {
				logger.log('messagesModel: setTemporaryMessages mutation', payload);

				const {
					messageList,
				} = payload.data;

				messageList.forEach((message) => {
					message.params = { ...messageState.params, ...message.params };

					state.temporaryMessages[message.id] = message;
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			setPinned: (state, payload) => {
				logger.log('messagesModel: setPinned mutation', payload);

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
				logger.log('messagesModel: updateWithId mutation', payload);

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
				}

				state.chatCollection[currentMessage.chatId].add(fields.id);
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			update: (state, payload) => {
				logger.log('messagesModel: update mutation', payload);

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
				logger.log('messagesModel: delete mutation', payload);
				const {
					id,
				} = payload.data;

				const message = state.collection[id];
				if (!message)
				{
					return;
				}

				const { chatId } = message;

				state.chatCollection[chatId].delete(id);
				delete state.collection[id];
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			deleteByChatId: (state, payload) => {
				logger.log('messagesModel: deleteByChatId mutation', payload);
				const {
					chatId,
				} = payload.data;

				delete state.chatCollection[chatId];
				Object.entries(state.collection).forEach(([messageId, message]) => {
					if (message.chatId === chatId)
					{
						delete state.collection[messageId];
					}
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			deleteTemporaryMessage: (state, payload) => {
				logger.log('messagesModel: deleteTemporaryMessage mutation', payload);
				const {
					id,
				} = payload.data;

				delete state.temporaryMessages[id.toString()];
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			deleteTemporaryMessages: (state, payload) => {
				logger.log('messagesModel: deleteTemporaryMessages mutation', payload);
				const {
					ids,
				} = payload.data;

				ids.forEach((id) => delete state.temporaryMessages[id.toString()]);
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			clearCollection: (state, payload) => {
				logger.log('messagesModel: clear collection mutation', payload.chatId);
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
			result.templateId = (Uuid.isV4(fields.templateId) || Uuid.isV4(fields.uuid)) ? (fields.templateId || fields.uuid) : '';
		}
		else if (Uuid.isV4(fields.templateId) || Uuid.isV4(fields.uuid))
		{
			result.id = fields.templateId;
			result.templateId = fields.templateId || fields.uuid;
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

		if (Type.isArray(fields.attach))
		{
			result.attach = fields.attach;
		}

		if (Type.isNumber(fields.richLinkId) || Type.isNull(fields.richLinkId))
		{
			result.richLinkId = fields.richLinkId;
		}

		if (Type.isPlainObject(fields.params))
		{
			const { params, fileIds, attach, richLinkId } = validateParams(fields.params);
			result.params = params;
			result.files = fileIds;
			result.richLinkId = richLinkId;

			if (Type.isUndefined(result.attach))
			{
				result.attach = attach;
			}

			if (Type.isUndefined(result.richLinkId))
			{
				result.richLinkId = richLinkId;
			}
		}

		// passed when a file is received from the local database
		if (Type.isArrayFilled(fields.files))
		{
			result.files = fields.files;
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

		if (Type.isNumber(fields.errorReason))
		{
			result.errorReason = fields.errorReason;
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

		if (Type.isObject(fields.forward) && fields.forward.id)
		{
			result.forward = fields.forward;
		}

		return result;
	}

	function validateParams(rawParams)
	{
		const params = {};
		let fileIds = [];
		let attach = [];
		let richLinkId = null;

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
			else if (key === 'ATTACH')
			{
				attach = ObjectUtils.convertKeysToCamelCase(clone(value), true);
				params.ATTACH = value;
			}
			else if (key === 'URL_ID')
			{
				richLinkId = value[0] ? Number(value[0]) : null;
				params.URL_ID = value;
			}
			else
			{
				params[key] = value;
			}
		});

		return { params, fileIds, attach, richLinkId };
	}

	function sortCollection(a, b)
	{
		if (Uuid.isV4(a.id) && !Uuid.isV4(b.id))
		{
			return 1;
		}

		if (!Uuid.isV4(a.id) && Uuid.isV4(b.id))
		{
			return -1;
		}

		if (Uuid.isV4(a.id) && Uuid.isV4(b.id))
		{
			return a.date.getTime() - b.date.getTime();
		}

		return a.id - b.id;
	}

	module.exports = {
		messagesModel,
	};
});
