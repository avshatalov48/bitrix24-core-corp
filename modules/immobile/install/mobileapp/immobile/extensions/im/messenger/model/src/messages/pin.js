/* eslint-disable no-param-reassign */
/**
 * @module im/messenger/model/messages/pin
 */
jn.define('im/messenger/model/messages/pin', (require, exports, module) => {
	const { Type } = require('type');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { validate: validateMessage } = require('im/messenger/model/validators/message');
	const { validate: validatePin } = require('im/messenger/model/validators/pin');
	const { merge } = require('utils/object');

	const logger = LoggerManager.getInstance().getLogger('model--messages-pin');

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

	/**
	 * @type {PinMessengerModel}
	 */
	const pinModel = {
		namespaced: true,
		state: () => ({
			collection: {},
			messageCollection: {},
		}),
		getters: {
			/**
			 * @function messagesModel/pinModel/getListByChatId
			 * @param state
			 * @return {Array<PinModelState>}
			 */
			getListByChatId: (state) => (chatId) => {
				if (!state.collection[chatId])
				{
					return [];
				}

				return state.collection[chatId].map((pin) => {
					const message = state.messageCollection[pin.messageId];

					return {
						...pin,
						message,
					};
				});
			},

			/**
			 * @function messagesModel/pinModel/getPin
			 * @param state
			 * @return {PinModelState || null}
			 */
			getPin: (state) => (chatId, messageId) => {
				if (!(chatId in state.collection) || !(messageId in state.messageCollection))
				{
					return null;
				}

				const messagePin = state.collection[chatId]
					.find((pin) => pin.messageId === messageId)
				;

				if (!messagePin)
				{
					return null;
				}

				return {
					...messagePin,
					message: state.messageCollection[messageId],
				};
			},

			/**
			 * @function messagesModel/pinModel/isPinned
			 * @param state
			 * @return {boolean}
			 */
			isPinned: (state) => (messageId) => {
				return messageId in state.messageCollection;
			},
		},
		actions: {
			/**
			 * @function messagesModel/pinModel/setChatCollection
			 * @param {PinSetChatCollectionPayload} payload
			 */
			setChatCollection: (store, payload) => {
				if (!payload.pins || !payload.messages)
				{
					return;
				}

				const messages = payload.messages.map((message) => {
					return {
						...messageDefaultElement,
						...validateMessage(message),
					};
				});
				const pins = payload.pins.map((pin) => validatePin(pin));

				if (messages.length === 0 || pins.length === 0)
				{
					return;
				}

				const chatId = pins[0].chatId;
				store.commit('setChatCollection', {
					actionName: 'setChatCollection',
					data: {
						chatId,
						pins,
						messages,
					},
				});
			},

			/**
			 *
			 * @param store
			 * @param {PinSetChatCollectionPayload} payload
			 */
			setFromLocalDatabase: (store, payload) => {
				if (!payload.pins || !payload.messages)
				{
					return;
				}

				const messages = payload.messages.map((message) => {
					return {
						...messageDefaultElement,
						...validateMessage(message),
					};
				});
				const pins = payload.pins.map((pin) => validatePin(pin));

				if (messages.length === 0 || pins.length === 0)
				{
					return;
				}

				const chatId = pins[0].chatId;
				store.commit('setChatCollection', {
					actionName: 'setFromLocalDatabase',
					data: {
						chatId,
						pins,
						messages,
					},
				});
			},

			/**
			 * @function messagesModel/pinModel/set
			 * @param {PinSetPayload} payload
			 */
			set: (store, payload) => {
				const pin = validatePin(payload.pin);
				const pinnedMessage = payload.messages
					.map((rawMessage) => {
						return {
							...messageDefaultElement,
							...validateMessage(rawMessage),
						};
					})
					.find((message) => message.id === pin.messageId)
				;

				/** @type {Pin | undefined} */
				const storedPin = store.state.collection[pin.chatId]
					?.find((findingPin) => findingPin.messageId === pin.messageId);

				if (!storedPin)
				{
					store.commit('add', {
						actionName: 'add',
						data: {
							chatId: pin.chatId,
							pin,
							message: pinnedMessage,
						},
					});

					return;
				}

				if (!Type.isNumber(storedPin.id))
				{
					// local pin with id === null. need update

					store.commit('updatePin', {
						actionName: 'set',
						data: {
							chatId: pin.chatId,
							pin,
						},
					});
				}
			},

			/**
			 * @function messagesModel/pinModel/setList
			 * @param store
			 * @param {PinSetListPayload} payload
			 * @return {Promise<void>}
			 */
			setList: async (store, payload) => {
				const setDispatchList = [];

				if (!Type.isArrayFilled(payload.pins))
				{
					return;
				}

				payload.pins.forEach((pin) => {
					const setDispatch = store.dispatch('set', {
						pin,
						messages: payload.messages,
					});

					setDispatchList.push(setDispatch);
				});

				await Promise.all(setDispatchList);
			},

			/**
			 * @function messagesModel/pinModel/updateMessage
			 */
			updateMessage: (store, payload) => {
				const { id, fields } = payload;

				const messageModel = store.rootGetters['messagesModel/getById'](id);

				if (fields?.params?.IS_PINNED === 'N')
				{
					store.commit('deleteMessagesByIdList', {
						actionName: 'updateMessage',
						data: {
							idList: [id],
						},
					});

					return;
				}

				store.commit('updateMessage', {
					actionName: 'updateMessage',
					data: {
						id,
						chatId: messageModel.chatId,
						fields,
					},
				});
			},

			/**
			 * @function messagesModel/pinModel/delete
			 * @param store
			 * @param {PinDeletePayload} payload
			 */
			delete: (store, payload) => {
				const { chatId, messageId } = payload;

				store.commit('delete', {
					actionName: 'delete',
					data: {
						chatId,
						messageId,
					},
				});
			},

			deleteByIdList: (store, payload) => {
				const { idList } = payload;

				store.commit('deleteByIdList', {
					actionName: 'deleteByIdList',
					data: {
						idList,
					},
				});
			},

			/**
			 * @function messagesModel/pinModel/deleteMessagesByChatId
			 * @param store
			 * @param payload
			 */
			deleteMessagesByChatId: (store, payload) => {
				const { chatId } = payload;

				store.commit('deleteByChatId', {
					actionName: 'deleteMessagesByChatId',
					data: {
						chatId,
					},
				});
			},

			/**
			 * @function messagesModel/pinModel/deleteMessagesByIdList
			 * @param store
			 * @param payload
			 */
			deleteMessagesByIdList: (store, payload) => {
				const { idList } = payload;

				store.commit('deleteMessagesByIdList', {
					actionName: 'deleteMessagesByIdList',
					data: {
						idList,
					},
				});
			},

			/**
			 * @function messagesModel/pinModel/deleteMessage
			 * @param store
			 * @param payload
			 */
			deleteMessage: (store, payload) => {
				const { id } = payload;

				store.commit('deleteMessagesByIdList', {
					actionName: 'deleteMessage',
					data: {
						idList: [id],
					},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<PinSetChatCollectionData, PinSetChatCollectionActions>} payload
			 */
			setChatCollection: (state, payload) => {
				logger.log('pinModel.setChatCollection', payload);

				state.collection[payload.data.chatId] = payload.data.pins;

				for (const message of payload.data.messages)
				{
					state.messageCollection[message.id] = message;
				}
			},

			/**
			 * @param state
			 * @param {MutationPayload<PinAddData, PinAddActions>} payload
			 */
			add: (state, payload) => {
				logger.log('pinModel.add', payload);
				if (!Type.isArray(state.collection[payload.data.chatId]))
				{
					state.collection[payload.data.chatId] = [];
				}

				state.collection[payload.data.chatId].push(payload.data.pin);
				state.messageCollection[payload.data.message.id] = payload.data.message;
			},

			/**
			 *
 			 * @param state
			 * @param {MutationPayload<PinUpdatePinData, PinUpdatePinActions>} payload
			 */
			updatePin: (state, payload) => {
				logger.log('pinModel.updatePin', payload);
				const pinIndex = state.collection[payload.data.chatId]
					.findIndex((pin) => pin.messageId === payload.data.pin.messageId)
				;

				if (pinIndex === -1)
				{
					logger.error('pinModel.updatePin error: pin index for update not found');

					return;
				}

				state.collection[payload.data.chatId][pinIndex] = payload.data.pin;
			},

			/**
			 *
			 * @param state
			 * @param {MutationPayload<PinDeleteData, PinDeleteActions>} payload
			 */
			delete: (state, payload) => {
				logger.log('pinModel.delete', payload);
				state.collection[payload.data.chatId] = state.collection[payload.data.chatId]
					?.filter((pin) => pin.messageId !== payload.data.messageId)
				;

				delete state.messageCollection[payload.data.messageId];
			},

			/**
			 *
			 * @param state
			 * @param {MutationPayload<PinDeleteByIdListData, PinDeleteByIdListActions>} payload
			 */
			deleteByIdList: (state, payload) => {
				logger.log('pinModel.deleteByIdList', payload);
				const { idList } = payload.data;

				for (const id of idList)
				{
					for (const [chatId, chatWithPin] of Object.entries(state.collection))
					{
						if (!Type.isArrayFilled(chatWithPin))
						{
							continue;
						}

						const foundPin = chatWithPin.find((pin) => pin.id === id);
						if (foundPin)
						{
							delete state.messageCollection[foundPin.messageId];

							state.collection[chatId] = state.collection[chatId]
								.filter((pin) => pin.id !== id)
							;

							if (state.collection[chatId].length === 0)
							{
								delete state.collection[chatId];
							}

							break;
						}
					}
				}
			},

			/**
			 *
			 * @param state
			 * @param {MutationPayload<PinDeleteByChatIdData, PinDeleteByChatIdActions>} payload
			 */
			deleteByChatId: (state, payload) => {
				logger.log('pinModel.deleteByChatId', payload);
				const { chatId } = payload.data;
				if (!(chatId in state.collection))
				{
					return;
				}

				state.collection[chatId].forEach((pin) => {
					delete state.messageCollection[pin.messageId];
				});

				delete state.collection[chatId];
			},

			/**
			 *
			 * @param state
			 * @param {MutationPayload<PinDeleteMessagesByIdListData, PinDeleteMessagesByIdListActions>} payload
			 */
			deleteMessagesByIdList: (state, payload) => {
				logger.log('pinModel.deleteMessagesByIdList', payload);
				const { idList } = payload.data;

				idList?.forEach((id) => {
					const chatId = state.messageCollection[id]?.chatId;
					delete state.messageCollection[id];

					if (!chatId && !state.collection[chatId])
					{
						return;
					}

					state.collection[chatId] = state.collection[chatId]
						.filter((pin) => pin.messageId !== id)
					;

					if (state.collection[chatId].length === 0)
					{
						delete state.collection[chatId];
					}
				});
			},

			/**
			 *
			 * @param state
			 * @param {MutationPayload<PinUpdateMessageData, PinUpdateMessageActions>} payload
			 */
			updateMessage: (state, payload) => {
				logger.log('pinModel.updateMessage', payload);

				const { id, fields } = payload.data;

				if (!(id in state.messageCollection))
				{
					return;
				}

				state.messageCollection[id] = merge(state.messageCollection[id], fields);
			},

		},
	};

	module.exports = { pinModel };
});
