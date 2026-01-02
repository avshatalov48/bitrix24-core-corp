/**
 * @module im/messenger/lib/counters/counter-manager/messenger/handler
 */
jn.define('im/messenger/lib/counters/counter-manager/messenger/handler', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getLogger } = require('im/messenger/lib/logger');

	const { CounterStorageReader } = require('im/messenger/lib/counters/counter-manager/storage/reader');

	const logger = getLogger('counters--messenger-counter-handler');

	/**
	 * @class MessengerCounterHandler
	 */
	class MessengerCounterHandler
	{
		static #instance;
		#reader = new CounterStorageReader();
		#store = serviceLocator.get('core').getStore();

		/**
		 * @return {MessengerCounterHandler}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new this();
			}

			return this.#instance;
		}

		constructor()
		{
			this.#reader.subscribeOnStorageChange(this.#onCounterChanged);
		}

		get tabCounters()
		{
			return serviceLocator.get('tab-counters');
		}

		/**
		 * @param {{ updatingCounters: Array<CounterState>, deletedCounters: Array<number>}} changedCounterResult
		 */
		#onCounterChanged = async (changedCounterResult) => {
			const updatingCountersPromiseList = [];
			logger.log(`${this.constructor.name}.#onCounterChanged: changedCounterResult`, changedCounterResult);

			const { updatingCounters, deletedCounters } = changedCounterResult;

			if (!Type.isArrayFilled(updatingCounters) && !Type.isArrayFilled(deletedCounters))
			{
				return;
			}

			for (const counterState of updatingCounters)
			{
				const {
					chatId,
					parentChatId,
					counter,
					type,
				} = counterState;

				const localCounter = this.#getLocalCounter(chatId, parentChatId, type);

				if (localCounter === counter)
				{
					continue;
				}

				this.tabCounters.updateCounterDetailByCounterState(counterState);
				updatingCountersPromiseList.push(
					this.#updateLocalCounter(chatId, parentChatId, type, counter),
				);
			}

			await Promise.all(updatingCountersPromiseList);

			for (const chatId of deletedCounters)
			{
				this.tabCounters.deleteCounterByChatId(chatId);
			}

			this.tabCounters.update();
		};

		async #updateLocalCounter(chatId, parentChatId, type, counter)
		{
			const dialogId = type === DialogType.comment
				? this.#getDialogIdByChatId(parentChatId)
				: this.#getDialogIdByChatId(chatId)
			;

			if (type === DialogType.comment)
			{
				await this.#store.dispatch('commentModel/setCounters', {
					[parentChatId]: {
						[chatId]: counter,
					},
				});

				// force update channel item in recent list
				await this.#store.dispatch('dialoguesModel/update', {
					dialogId,
					fields: {},
				});

				return;
			}

			await this.#store.dispatch('dialoguesModel/update', {
				dialogId,
				fields: {
					counter,
				},
			});
		}

		/**
		 * @param {number} chatId
		 * @returns {?DialoguesModelState}
		 */
		#getDialogByChatId(chatId)
		{
			return this.#store.getters['dialoguesModel/getByChatId'](chatId);
		}

		#getDialogIdByChatId(chatId)
		{
			return this.#getDialogByChatId(chatId)?.dialogId;
		}

		#getLocalCounter(chatId, parentChatId, type)
		{
			if (type === DialogType.comment)
			{
				return this.#store.getters['commentModel/getCommentCounter']({
					channelId: parentChatId,
					commentChatId: chatId,
				});
			}

			return this.#getDialogByChatId(chatId)?.counter;
		}
	}

	module.exports = { MessengerCounterHandler };
});
