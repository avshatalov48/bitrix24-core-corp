/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/counter
 */
jn.define('im/messenger/model/counter', (require, exports, module) => {
	const { Type } = require('type');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('model--counter');

	const counterDefaultElement = Object.freeze({
		chatId: 0,
		parentChatId: 0,
		type: 'CHAT',
		counter: 0,
	});

	const counterModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/**
			 * @function counterModel/getCollection
			 */
			getCollection: (state) => () => {
				return state.collection;
			},
			/**
			 * @function counterModel/getList
			 */
			getList: (state) => () => {
				return Object.values(state.collection);
			},
			/**
			 * @function counterModel/getByChatId
			 */
			getByChatId: (state) => (chatId) => {
				return state.collection[chatId];
			},
			/**
			 * @function counterModel/getByParentChatId
			 */
			getByParentChatId: (state) => (chatId) => {
				return Object.values(state.collection).filter((counter) => {
					return counter.parentChatId === chatId;
				});
			},
		},
		actions: {
			/** @function counterModel/set */
			set: (store, counterCollection) => {
				const counterList = [];
				Object.entries(counterCollection).forEach(([chatId, counter]) => {
					const modelCounter = {
						chatId: Number(chatId),
						...counter,
					};

					counterList.push({
						...counterDefaultElement,
						...validate(modelCounter),
					});
				});

				store.commit('set', {
					actionName: 'set',
					data: {
						counterList,
					},
				});
			},

			/** @function counterModel/clear */
			clear: (store) => {
				store.commit('clear', {
					actionName: 'clear',
					data: {},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param payload
			 */
			set: (state, payload) => {
				logger.log('counterModel set mutation', payload);

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
			 * @param payload
			 */
			clear: (state, payload) => {
				logger.log('counterModel clear mutation', payload);

				state.collection = {};
			},
		},
	};

	function validate(fields)
	{
		const result = {};

		if (Type.isNumber(fields.chatId))
		{
			result.chatId = fields.chatId;
		}

		if (Type.isNumber(fields.parentChatId))
		{
			result.parentChatId = fields.parentChatId;
		}

		if (Type.isStringFilled(fields.type))
		{
			result.type = fields.type;
		}

		if (Type.isNumber(fields.counter))
		{
			result.counter = fields.counter;
		}

		return result;
	}

	module.exports = {
		counterModel,
	};
});
