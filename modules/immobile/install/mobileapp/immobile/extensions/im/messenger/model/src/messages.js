/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/messages
 */
jn.define('im/messenger/model/messages', (require, exports, module) => {
	const { Type } = require('type');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Uuid } = require('utils/uuid');
	const { mergeImmutable, clone, isEqual } = require('utils/object');
	const { reactionsModel } = require('im/messenger/model/messages/reactions');
	const { pinModel } = require('im/messenger/model/messages/pin');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { validate } = require('im/messenger/model/validators/message');
	const { MessengerMutationHandlersWaiter } = require('im/messenger/lib/state-manager/vuex-manager/mutation-handlers-waiter');

	const logger = LoggerManager.getInstance().getLogger('model--messages');

	const TEMPORARY_MESSAGE_PREFIX = 'temporary';

	const messageDefaultElement = Object.freeze({
		id: 0,
		templateId: '',
		previousId: 0,
		nextId: 0,
		chatId: 0,
		authorId: 0,
		date: new Date(),
		text: '',
		loadText: '',
		uploadFileId: '',
		params: {},
		replaces: [],
		files: [],
		unread: false,
		viewed: true,
		viewedByOthers: false,
		sending: false,
		error: false,
		errorReason: 0, // code from rest/classes/general/rest.php:25 or main/install/js/main/core/core_ajax.js:1044
		retry: false,
		audioPlaying: false,
		playingTime: 0,
		attach: [],
		keyboard: [],
		richLinkId: null,
		forward: {},
	});

	/** @type {MessagesMessengerModel} */
	const messagesModel = {
		namespaced: true,
		state: () => ({
			collection: {},
			chatCollection: {},
			temporaryMessages: {},
			uploadingMessageCollection: new Set(),
		}),
		modules: {
			reactionsModel,
			pinModel,
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
			 * @return {MessagesModelState | {}}
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
			 * @function messagesModel/getListByIds
			 * @return {Array<MessagesModelState>}
			 */
			getListByIds: (state, getters, rootState, rootGetters) => (messageIds) => {
				if (!Type.isArrayFilled(messageIds))
				{
					return [];
				}

				const messageCollection = [];
				for (const id of messageIds)
				{
					const message = state.collection[id.toString()];
					if (message)
					{
						const fullMessageData = {
							...message,
							reactions: rootGetters['messagesModel/reactionsModel/getByMessageId'](id),
						};

						messageCollection.push(fullMessageData);
					}
				}

				return messageCollection.sort((a, b) => sortCollection(a, b));
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

			/**
			 * @function messagesModel/getBreakMessages
			 * @return {Array<MessagesModelState>}
			 */
			getBreakMessages: (state) => (chatId) => {
				const allCollectionList = clone(state.collection);

				if (!allCollectionList || !Type.isNumber(chatId))
				{
					return [];
				}

				/** @type {Array<MessagesModelState>} */
				const list = [];
				for (const messageId of Object.keys(allCollectionList))
				{
					const message = allCollectionList[messageId];
					if (message.chatId === chatId && message.error && message.sending)
					{
						list.push(message);
					}
				}

				// sort broken messages in the order they are sent
				return list.sort((a, b) => {
					return a.date - b.date;
				});
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
			getFirstUnreadId: (state, getters, rootState, rootGetters) => (chatId) => {
				if (!state.chatCollection[chatId])
				{
					return null;
				}

				/** @type {Array<MessagesModelState>} */
				const messageList = rootGetters['messagesModel/getByChatId'](chatId);
				const dialogModel = rootGetters['dialoguesModel/getByChatId'](chatId);

				if (!dialogModel)
				{
					logger.error('messagesModel.getFirstUnreadId: dialog not found by chat id', chatId);

					return null;
				}

				for (const message of messageList)
				{
					if (message.id.toString().startsWith(TEMPORARY_MESSAGE_PREFIX))
					{
						continue;
					}

					if (dialogModel.lastReadId > message.id)
					{
						continue;
					}

					if (!message.viewed)
					{
						return message.id;
					}
				}

				return null;
			},

			/**
			 * @function messagesModel/getUploadingMessages
			 * @return {Array<MessagesModelState>}
			 */
			getUploadingMessages: (state) => (chatId) => {
				return [...state.uploadingMessageCollection]
					.filter((id) => state.collection[id]?.chatId === chatId)
					.map((id) => state.collection[id])
					.sort((message1, message2) => {
						return message1.date.getTime() - message2.date.getTime();
					})
				;
			},

			/**
			 * @function messagesModel/isUploadingMessage
			 * @return {boolean}
			 */
			isUploadingMessage: (state) => (id) => {
				return state.uploadingMessageCollection.has(id);
			},
		},
		actions: {
			/** @function messagesModel/forceUpdateByChatId */
			forceUpdateByChatId: async (store, { chatId }) => {
				const moduleName = 'messagesModel';
				const actionName = 'forceUpdateByChatId';

				const waiter = new MessengerMutationHandlersWaiter(moduleName, actionName);
				waiter.addMutation('store');
				waiter.addMutation('setChatCollection');
				const handlersComplete = waiter.waitComplete();

				const messages = store.getters.getByChatId(chatId);

				store.commit('store', {
					actionName,
					actionUid: waiter.actionUid,
					data: {
						messageList: messages,
					},
				});

				store.commit('setChatCollection', {
					actionName,
					actionUid: waiter.actionUid,
					data: {
						messageList: messages,
					},
				});

				await handlersComplete;
			},

			/** @function messagesModel/setChatCollection */
			setChatCollection: async (store, { messages, clearCollection }) => {
				const moduleName = 'messagesModel';
				const actionName = 'setChatCollection';
				const waiter = new MessengerMutationHandlersWaiter(moduleName, actionName);

				clearCollection = clearCollection || false;
				if (!Array.isArray(messages) && Type.isPlainObject(messages))
				{
					messages = [messages];
				}

				messages = messages.map((message) => {
					return { ...messageDefaultElement, ...validate(message) };
				});

				const chatId = messages[0]?.chatId;
				if (chatId && clearCollection)
				{
					waiter.addMutation('clearCollection');
				}

				waiter.addMutation('store');
				waiter.addMutation('setChatCollection');
				const handlersComplete = waiter.waitComplete();

				if (chatId && clearCollection)
				{
					store.commit('clearCollection', {
						actionName,
						actionUid: waiter.actionUid,
						data: {
							chatId,
						},
					});
				}

				store.commit('store', {
					actionName,
					actionUid: waiter.actionUid,
					data: {
						messageList: messages,
					},
				});

				store.commit('setChatCollection', {
					actionName,
					actionUid: waiter.actionUid,
					data: {
						messageList: messages,
					},
				});

				await handlersComplete;
			},

			/** @function messagesModel/setFromLocalDatabase */
			setFromLocalDatabase: (store, { messages, clearCollection }) => {
				clearCollection = clearCollection || false;
				if (!Array.isArray(messages) && Type.isPlainObject(messages))
				{
					messages = [messages];
				}

				messages = messages.map((message) => {
					return { ...messageDefaultElement, ...validate(message) };
				});

				const chatId = messages[0]?.chatId;
				if (chatId && clearCollection)
				{
					store.commit('clearCollection', {
						actionName: 'setFromLocalDatabase',
						data: {
							chatId,
						},
					});
				}

				store.commit('store', {
					actionName: 'setFromLocalDatabase',
					data: {
						messageList: messages,
					},
				});
				store.commit('setChatCollection', {
					actionName: 'setFromLocalDatabase',
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
					return { ...messageDefaultElement, ...validate(message) };
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

			/** @function messagesModel/storeToLocalDatabase */
			storeToLocalDatabase: (store, messages) => {
				if (!Array.isArray(messages) && Type.isPlainObject(messages))
				{
					messages = [messages];
				}

				messages = messages.map((message) => {
					return { ...messageDefaultElement, ...validate(message) };
				});

				if (messages.length === 0)
				{
					return;
				}

				store.commit('store', {
					actionName: 'storeToLocalDatabase',
					data: {
						messageList: messages,
					},
				});
			},

			/** @function messagesModel/add */
			add: async (store, payload) => {
				const moduleName = 'messagesModel';
				const actionName = 'add';

				const waiter = new MessengerMutationHandlersWaiter(moduleName, actionName);
				waiter.addMutation('store');
				waiter.addMutation('setChatCollection');
				const handlersComplete = waiter.waitComplete();

				const message = {
					...messageDefaultElement,
					...validate(payload),
				};

				if (message.files.some((fileId) => Uuid.isV4(fileId)))
				{
					store.commit('addToUploadingCollection', {
						actionName: 'add',
						data: {
							id: message.id,
						},
					});
				}

				store.commit('store', {
					actionName,
					actionUid: waiter.actionUid,
					data: {
						messageList: [message],
					},
				});

				store.commit('setChatCollection', {
					actionName,
					actionUid: waiter.actionUid,
					data: {
						messageList: [message],
					},
				});

				await handlersComplete;
			},

			/** @function messagesModel/addToChatCollection */
			addToChatCollection: (store, payload) => {
				if (!store.state.collection[payload.id])
				{
					return;
				}

				const message = {
					...messageDefaultElement,
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
					return { ...messageDefaultElement, ...validate(message) };
				});

				store.commit('setTemporaryMessages', {
					actionName: 'setTemporaryMessages',
					data: {
						messageList: messages,
					},
				});
			},

			/** @function messagesModel/updateWithId */
			updateWithId: (store, payload) => {
				const { id } = payload;
				if (!store.state.collection[id])
				{
					return;
				}

				const fields = validate(payload.fields);

				if (
					store.getters.isUploadingMessage(id)
					&& fields?.files?.every?.((fileId) => Type.isNumber(Number(fileId)))
				)
				{
					store.commit('deleteFromUploadingCollection', {
						actionName: 'updateWithId',
						data: {
							id,
						},
					});
				}

				store.commit('updateWithId', {
					actionName: 'updateWithId',
					data: {
						id,
						fields,
					},
				});
			},

			/** @function messagesModel/update */
			update: (store, { id, fields, skipCheckEquality = false }) => {
				if (!store.state.collection[id])
				{
					return;
				}

				const updateMessageData = {
					id,
					fields: validate(fields),
				};

				if (
					store.getters.isUploadingMessage(id)
					&& updateMessageData.fields?.files?.every?.((fileId) => Type.isNumber(Number(fileId)))
				)
				{
					store.commit('deleteFromUploadingCollection', {
						actionName: 'update',
						data: {
							id,
						},
					});
				}

				const storedMessage = store.state.collection[id];

				if (
					!skipCheckEquality
					&& isEqual(storedMessage, mergeImmutable(storedMessage, updateMessageData.fields))
				)
				{
					return;
				}

				store.dispatch('pinModel/updateMessage', updateMessageData);

				store.commit('update', {
					actionName: 'update',
					data: updateMessageData,
				});
			},

			/** @function messagesModel/updateParams */
			updateParams: async (store, { id, params }) => {
				if (!store.state.collection[id])
				{
					return Promise.resolve(null);
				}

				const { params: formatedParams } = validate({ params });
				const { params: storedParams } = store.state.collection[id];

				if (!Type.isPlainObject(storedParams))
				{
					return store.dispatch('update', {
						id,
						fields: {
							params,
						},
						skipCheckEquality: true,
					});
				}

				let needUpdateMessage = false;
				for (const [paramName, value] of Object.entries(formatedParams))
				{
					if (!isEqual(value, storedParams[paramName]))
					{
						needUpdateMessage = true;
						break;
					}
				}

				if (needUpdateMessage)
				{
					return store.dispatch('update', {
						id,
						fields: {
							params,
						},
						skipCheckEquality: true,
					});
				}
			},

			/** @function messagesModel/deleteByChatId */
			deleteByChatId: (store, payload) => {
				const chatId = parseInt(payload.chatId, 10);
				store.dispatch('pinModel/deleteMessagesByChatId', { chatId });

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

				store.dispatch('pinModel/deleteMessagesByIdList', { idList });

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

				store.dispatch('pinModel/deleteMessage', { id });

				if (store.getters.isUploadingMessage(id))
				{
					store.commit('deleteFromUploadingCollection', {
						actionName: 'delete',
						data: {
							id,
							chatId: store.state.collection[id].chatId,
						},
					});
				}

				store.commit('delete', {
					actionName: 'delete',
					data: {
						id,
					},
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
					if (!store.state.collection[messageId])
					{
						logger.warn('MessageModel.readMessages error: update unread a missing message', messageId, chatId, messageIds);

						return;
					}

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
					if (!store.state.collection[messageId])
					{
						logger.warn('MessageModel.readMessages error: update viewed a missing message', messageId, chatId, messageIds);

						return;
					}

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

				const { loadText: currentLoadText, uploadFileId: currentUploadFileId } = message;
				if (currentLoadText === payload.loadText && currentUploadFileId === payload.uploadFileId)
				{
					return;
				}

				store.commit('update', {
					actionName: 'updateLoadTextProgress',
					data: {
						id: payload.id,
						fields: { loadText: payload.loadText, uploadFileId: payload.uploadFileId },
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

			/** @function messagesModel/disableKeyboardByMessageId */
			disableKeyboardByMessageId: (store, messageId) => {
				/** @type {MessagesModelState} */
				const message = store.state.collection[messageId];
				if (!message)
				{
					return;
				}

				const keyboard = message.keyboard.map((button) => {
					button.disabled = true;

					return button;
				});

				store.commit('update', {
					actionName: 'disableKeyboardByMessageId',
					data: {
						id: messageId,
						fields: {
							keyboard,
						},
					},
				});
			},

			/** @function messagesModel/clearChatCollection */
			clearChatCollection: (store, payload) => {
				const { chatId } = payload;
				if (!Type.isNumber(chatId))
				{
					return;
				}

				store.commit('clearCollection', {
					actionName: 'clearChatCollection',
					data: {
						chatId,
					},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<MessagesSetChatCollectionData, MessagesSetChatCollectionActions>} payload
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
			 * @param {MutationPayload<MessagesStoreData, MessagesStoreActions>} payload
			 */
			store: (state, payload) => {
				logger.log('messagesModel: store mutation', payload);

				const {
					messageList,
				} = payload.data;

				messageList.forEach((message) => {
					message.params = { ...messageDefaultElement.params, ...message.params };

					state.collection[message.id] = message;
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<MessagesSetTemporaryMessagesData, MessagesSetTemporaryMessagesActions>} payload
			 */
			setTemporaryMessages: (state, payload) => {
				logger.log('messagesModel: setTemporaryMessages mutation', payload);

				const {
					messageList,
				} = payload.data;

				messageList.forEach((message) => {
					message.params = { ...messageDefaultElement.params, ...message.params };

					state.temporaryMessages[message.id] = message;
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<MessagesUpdateWithIdData, MessagesUpdateWithIdActions>} payload
			 */
			updateWithId: (state, payload) => {
				logger.log('messagesModel: updateWithId mutation', payload);

				const {
					id,
					fields,
				} = payload.data;
				const currentMessage = { ...state.collection[id] };

				delete state.collection[id];
				state.collection[fields.id] = mergeImmutable(currentMessage, fields, { sending: false });

				if (state.chatCollection[currentMessage.chatId].has(id))
				{
					state.chatCollection[currentMessage.chatId].delete(id);
				}

				state.chatCollection[currentMessage.chatId].add(fields.id);
			},

			/**
			 * @param state
			 * @param {MutationPayload<MessagesUpdateData, MessagesUpdateActions>} payload
			 */
			update: (state, payload) => {
				logger.log('messagesModel: update mutation', payload);

				const {
					id,
					fields,
				} = payload.data;

				state.collection[id] = mergeImmutable(state.collection[id], fields);
			},

			/**
			 * @param state
			 * @param {MutationPayload<MessagesDeleteData, MessagesDeleteActions>} payload
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

				if (Type.isSet(state.chatCollection[chatId]))
				{
					state.chatCollection[chatId].delete(id);
				}

				delete state.collection[id];
			},

			/**
			 * @param state
			 * @param {MutationPayload<MessagesDeleteByChatIdData, MessagesDeleteByChatIdActions>} payload
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
			 * @param {MutationPayload<MessagesDeleteTemporaryMessageData, MessagesDeleteTemporaryMessageActions>} payload
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
			 * @param {MutationPayload<MessagesDeleteTemporaryMessagesData, MessagesDeleteTemporaryMessagesActions>} payload
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
			 * @param {MutationPayload<MessagesClearCollectionData, MessagesClearCollectionActions>} payload
			 */
			clearCollection: (state, payload) => {
				logger.log('messagesModel: clear collection mutation', payload.data.chatId);
				const {
					chatId,
				} = payload.data;

				state.chatCollection[chatId] = new Set();
			},

			/**
			 * @param state
			 * @param {MutationPayload<MessagesAddToUploadingCollectionData, MessagesAddToUploadingCollectionActions>} payload
			 */
			addToUploadingCollection: (state, payload) => {
				logger.log('messagesModel: addToUploadingCollection mutation', payload);

				const { id } = payload.data;

				state.uploadingMessageCollection.add(id);
			},

			/**
			 * @param state
			 * @param {MutationPayload<
			 * MessagesDeleteFromUploadingCollectionData,
			 * MessagesDeleteFromUploadingCollectionActions
			 * >} payload
			 */
			deleteFromUploadingCollection: (state, payload) => {
				logger.log('messagesModel: deleteFromUploadingCollection mutation', payload);

				const { id } = payload.data;

				state.uploadingMessageCollection.delete(id);
			},
		},
	};

	function sortCollection(a, b)
	{
		if (Uuid.isV4(a.id) || Uuid.isV4(b.id))
		{
			return a.date.getTime() - b.date.getTime();
		}

		return a.id - b.id;
	}

	module.exports = {
		messagesModel,
		messageDefaultElement,
	};
});
