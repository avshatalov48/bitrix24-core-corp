/* eslint-disable no-param-reassign */
/**
 * @module im/messenger/model/messages/pin/model
 */
jn.define('im/messenger/model/messages/pin/model', (require, exports, module) => {
	const { Type } = require('type');
	const { mergeImmutable } = require('utils/object');

	const { validate: validateMessage } = require('im/messenger/model/messages/validator');
	const { messageDefaultElement } = require('im/messenger/model/messages/default-element');
	const { validate: validatePin } = require('im/messenger/model/messages/pin/validator');

	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('model--messages-pin');

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
			 * @function messagesModel/pinModel/getPinsCounter
			 * @param state
			 * @param getters
			 * @return {number}
			 */
			getPinsCounter: (state, getters) => (chatId) => {
				return getters.getListByChatId(chatId).length;
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
			 * @param {MessengerStore<PinMessengerModel>} store
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
						actionName: payload.actionName || 'add',
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
						actionName: payload.actionName || 'set',
						data: {
							chatId: pin.chatId,
							pin,
						},
					});
				}
			},

			/** @function messagesModel/pinModel/setListFromSync */
			setListFromSync: (store, payload) => {
				return store.dispatch(
					'setList',
					{ ...payload, actionName: 'setListFromSync' },
				);
			},

			/**
			 * @function messagesModel/pinModel/setList
			 * @param {MessengerStore<PinMessengerModel>} store
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
						actionName: payload.actionName,
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

				store.commit('updateMessages', {
					actionName: 'updateMessage',
					data: {
						messageList: [{
							id,
							chatId: messageModel.chatId,
							fields,
						}],
					},
				});
			},

			/**
			 * @function messagesModel/pinModel/updateMessages
			 */
			updateMessages: async (store, payload) => {
				const { messageList } = payload;
				if (!Type.isArrayFilled(messageList))
				{
					return false;
				}

				const messageModel = store.rootGetters['messagesModel/getById'](messageList[0].id);
				const unpinnedMessageIdList = [];
				const updatePinnedMessageList = [];
				messageList.forEach((messageData) => {
					if (messageData.fields?.params?.IS_PINNED === 'N')
					{
						unpinnedMessageIdList.push(messageData.id);
					}

					updatePinnedMessageList.push({
						...messageData,
						chatId: messageModel.chatId,
					});
				});

				if (unpinnedMessageIdList.length > 0)
				{
					await store.commit('deleteMessagesByIdList', {
						actionName: 'updateMessages',
						data: {
							idList: unpinnedMessageIdList,
						},
					});
				}

				return store.commit('updateMessages', {
					actionName: 'updateMessages',
					data: {
						messageList: updatePinnedMessageList,
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

			/** @function messagesModel/pinModel/deleteByIdList */
			deleteByIdList: (store, payload) => {
				const { idList, actionName = 'deleteByIdList' } = payload;

				store.commit('deleteByIdList', {
					actionName,
					data: {
						idList,
					},
				});
			},

			/** @function messagesModel/pinModel/deleteByIdListFromSync */
			deleteByIdListFromSync: (store, payload) => {
				return store.dispatch(
					'deleteByIdList',
					{ ...payload, actionName: 'deleteByIdListFromSync' },
				);
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
			 * @param state
			 * @param {MutationPayload<PinUpdateMessagesData, PinUpdateMessageActions>} payload
			 */
			updateMessages: (state, payload) => {
				logger.log('pinModel.updateMessages', payload);

				if (Type.isArrayFilled(payload.data.messageList))
				{
					payload.data.messageList.forEach((message) => {
						const id = message.id;
						if (!(id in state.messageCollection))
						{
							return;
						}

						state.messageCollection[id] = mergeImmutable(state.messageCollection[id], message.fields);
					});
				}
			},
		},
	};

	module.exports = { pinModel };
});
