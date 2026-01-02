/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/counter/model
 */
jn.define('im/messenger/model/counter/model', (require, exports, module) => {
	const { Type } = require('type');
	const { unique } = require('utils/array');
	const { CounterType } = require('im/messenger/const');
	const { counterDefaultElement } = require('im/messenger/model/counter/default-element');
	const { validate } = require('im/messenger/model/counter/validator');

	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const logger = getLoggerWithContext('model--counter', 'CounterModel');

	/**
	 * @type {CounterMessengerModel}
	 */
	const counterModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/**
			 * @function counterModel/getCollection
			 * @return {Record<number, CounterModelState>}
			 */
			getCollection: (state) => () => {
				return state.collection;
			},
			/**
			 * @function counterModel/getList
			 * @return {Array<CounterModelState>}
			 */
			getList: (state) => () => {
				return Object.values(state.collection);
			},
			/**
			 * @function counterModel/getByChatId
			 * @return {?CounterModelState}
			 */
			getByChatId: (state) => (chatId) => {
				return state.collection[chatId];
			},
			/**
			 * @function counterModel/getByParentChatId
			 * @return {Array<CounterModelState>}
			 */
			getByParentChatId: (state) => (chatId) => {
				return Object.values(state.collection)
					.filter((counterState) => {
						return counterState.parentChatId === chatId && counterState.counter > 0;
					})
				;
			},

			/**
			 * @function counterModel/getCounterByChatId
			 * @return {number}
			 */
			getCounterByChatId: (state) => (chatId) => {
				return state.collection[chatId]?.counter ?? 0;
			},

			/**
			 * @function counterModel/getCounterByCounterType
			 * @return {number}
			 */
			getCounterByCounterType: (state) => ({ type, withDisabled = false }) => {

				let counterStateList = Object.values(state.collection)
					.filter((counterState) => {
						return counterState.type === type;
					})
				;

				if (!withDisabled)
				{
					counterStateList = counterStateList
						.filter((counterState) => counterState.disabled === false)
					;
				}

				return counterStateList
					.reduce((counter, counterState) => counter + counterState.counter, 0)
				;
			},
			/**
			 * @function counterModel/getCounterByCounterTypeList
			 * @return {number}
			 */
			getCounterByCounterTypeList: (state) => ({ typeList, withDisabled = false }) => {
				if (!Type.isArrayFilled(typeList))
				{
					logger.error('getCounterWithoutCounterTypes: typeList is not filled array');

					return 0;
				}
				let counterStateList = Object.values(state.collection)
					.filter((counterState) => {
						return typeList.includes(counterState.type);
					})
				;

				if (!withDisabled)
				{
					counterStateList = counterStateList
						.filter((counterState) => counterState.disabled === false)
					;
				}

				return counterStateList
					.reduce((counter, counterState) => counter + counterState.counter, 0)
				;
			},
			/**
			 * @function counterModel/getNumberChildCounters
			 * @return {number}
			 */
			getNumberChildCounters: (state) => (parentChatId) => {
				return Object.values(state.collection)
					.filter((counterState) => {
						return counterState.parentChatId === parentChatId;
					})
					.reduce((counter, counterState) => {
						return counter + (counterState.counter > 0 ? 1 : 0);
					}, 0)
				;
			},
		},
		actions: {
			/** @function counterModel/setList */
			setList: async (store, payload) => {
				const {
					/** @type {Array<CounterModelState>} */
					counterList,
					ignoreLock = false,
					clearCollection = false,
				} = payload;

				if (clearCollection)
				{
					await store.dispatch('clear');
				}

				const preparedCounterStateList = [];
				for (const counterState of counterList)
				{
					const chatId = counterState.chatId;
					if (!Type.isNumber(chatId))
					{
						continue;
					}

					if (
						Type.isPlainObject(store.state.collection[chatId])
						&& store.state.collection[chatId].locked === true
						&& ignoreLock === false
					)
					{
						logger.log(`action setList. counter state for chatId ${chatId} is locked. skip`, counterState);

						continue;
					}

					const modelCounter = {
						chatId: Number(chatId),
						...counterState,
					};

					preparedCounterStateList.push({
						...counterDefaultElement,
						...store.state.collection[chatId],
						...validate(modelCounter),
					});
				}

				store.commit('set', {
					actionName: 'set',
					data: {
						counterList: preparedCounterStateList,
					},
				});
			},

			/** @function counterModel/lockChatCounter */
			lockChatCounter: (store, payload) => {
				const { chatId } = payload;

				if (!Type.isPlainObject(store.state.collection[chatId]))
				{
					return;
				}

				store.commit('setLock', {
					actionName: 'blockChatCounter',
					data: {
						chatId,
						locked: true,
					},
				});
			},

			/** @function counterModel/unlockChatCounter */
			unlockChatCounter: (store, payload) => {
				const { chatId } = payload;

				if (!Type.isPlainObject(store.state.collection[chatId]))
				{
					return;
				}

				store.commit('setLock', {
					actionName: 'unlockChatCounter',
					data: {
						chatId,
						locked: false,
					},
				});
			},

			/** @function counterModel/disableChatCounter */
			disableChatCounter: (store, payload) => {
				const { chatId } = payload;

				if (!Type.isPlainObject(store.state.collection[chatId]))
				{
					return;
				}

				store.commit('setDisable', {
					actionName: 'disableChatCounter',
					data: {
						chatId,
						disabled: true,
					},
				});
			},

			/** @function counterModel/enableChatCounter */
			enableChatCounter: (store, payload) => {
				const { chatId } = payload;

				if (!Type.isPlainObject(store.state.collection[chatId]))
				{
					return;
				}

				store.commit('setDisable', {
					actionName: 'enableChatCounter',
					data: {
						chatId,
						disabled: false,
					},
				});
			},

			/** @function counterModel/setDisableChatCounter */
			setDisableChatCounter: (store, payload) => {
				const { chatId, disabled } = payload;

				if (!Type.isPlainObject(store.state.collection[chatId]))
				{
					return;
				}

				store.commit('setDisable', {
					actionName: 'setDisableChatCounter',
					data: {
						chatId,
						disabled: Boolean(disabled),
					},
				});
			},

			/** @function counterModel/delete */
			delete: (store, payload) => {
				const { chatIdList } = payload;

				if (!Type.isArrayFilled(chatIdList))
				{
					return;
				}

				store.commit('delete', {
					actionName: 'delete',
					data: {
						chatIdList,
					},
				});
			},

			/** @function counterModel/readChildChatsCounters */
			readChildChatsCounters: (store, payload) => {
				const { parentChatId } = payload;

				/** @type {Array<CounterModelState>} */
				const counterStateList = [];
				Object.values(store.state.collection).forEach((counterState) => {
					if (counterState.parentChatId === parentChatId)
					{
						counterStateList.push({
							...counterState,
							counter: 0,
						});
					}
				});

				store.commit('set', {
					actionName: 'readChildChatsCounters',
					data: {
						counterList: counterStateList,
					},
				});
			},

			/** @function counterModel/clear */
			clear: (store) => {
				const chatIdList = [];
				Object.values(store.state.collection)
					.forEach((counterState) => {
						if (counterState.type === CounterType.openline)
						{
							return;
						}

						if (counterState.counter > 0)
						{
							chatIdList.push(counterState.chatId);

							if (counterState.parentChatId > 0) // need to update parent chat in recent
							{
								chatIdList.push(counterState.parentChatId);
							}
						}
					})
				;

				store.commit('delete', {
					actionName: 'clear',
					data: {
						chatIdList: unique(chatIdList),
					},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<CounterSetData, CounterSetActions>} payload
			 */
			set: (state, payload) => {
				logger.log('set mutation', payload);

				payload.data.counterList.forEach((counter) => {
					let newCounter = counterDefaultElement;
					if (state.collection[counter.chatId])
					{
						newCounter = {
							...newCounter,
							...state.collection[counter.chatId],
							...counter,
						};
					}
					else
					{
						newCounter = {
							...newCounter,
							...counter,
						};
					}

					state.collection[counter.chatId] = newCounter;
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<CounterSetLockData, CounterSetLockActions>} payload
			 */
			setLock: (state, payload) => {
				logger.log('setLock mutation', payload);
				const { chatId, locked } = payload.data;

				state.collection[chatId] = {
					...state.collection[chatId],
					locked: Boolean(locked),
				};
			},

			/**
			 * @param state
			 * @param {MutationPayload<CounterSetDisableData, CounterSetDisableActions>} payload
			 */
			setDisable: (state, payload) => {
				logger.log('setDisable mutation', payload);
				const { chatId, disabled } = payload.data;

				state.collection[chatId] = {
					...state.collection[chatId],
					disabled: Boolean(disabled),
				};
			},

			/**
			 * @param state
			 * @param {MutationPayload<CounterDeleteData, CounterDeleteActions>} payload
			 */
			delete: (state, payload) => {
				logger.log('counterModel delete mutation', payload);
				const { chatIdList } = payload.data;

				for (const chatId of chatIdList)
				{
					if (state.collection[chatId])
					{
						state.collection[chatId].counter = 0;
					}
				}
			},
		},
	};

	module.exports = {
		counterModel,
	};
});
