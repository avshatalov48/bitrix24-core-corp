/**
 * @module im/messenger/lib/counters/tab-counters/copilot
 */
jn.define('im/messenger/lib/counters/tab-counters/copilot', (require, exports, module) => {
	const { Type } = require('type');

	const { EventType, CounterType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const { BaseTabCounters } = require('im/messenger/lib/counters/tab-counters/base');
	const { TabCounterState } = require('im/messenger/lib/counters/tab-counters/state');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('counters--copilot');

	/**
	 * @deprecated
	 * @see MessengerCounters
	 * @class ChatCounters
	 */
	class CopilotCounters extends BaseTabCounters
	{
		constructor()
		{
			super({ logger });
		}

		initCounters()
		{
			this.copilotCounter = new TabCounterState();
		}

		subscribeEvents()
		{
			BX.addCustomEvent(EventType.counters.clearAll, this.#clearAllHandler);
		}

		/**
		 * @param {immobileTabCopilotLoadResult} data
		 */
		handleCountersGet(data)
		{
			const counters = data?.imCounters;

			if (!Type.isPlainObject(counters))
			{
				logger.error(`${this.getClassName()}.handleCountersGet`, counters);

				return;
			}

			logger.log(`${this.getClassName()}.handleCountersGet`, counters);

			this.mutedChatCollection = this.getMutedCollectionByImCounters(counters);

			counters.copilotUnread?.forEach((chatId) => {
				this.copilotCounter.detail[`chat${chatId}`] = 1;
			});

			Object.keys(counters.copilot).forEach((chatId) => {
				this.copilotCounter.detail[`chat${chatId}`] = counters.copilot[chatId];
			});

			this.copilotCounter.value = counters.type.copilot;
			this.update();
			this.sendCountersToCounterService(counters);
		}

		update()
		{
			this.clearUpdateTimeout();
			this.copilotCounter.reset();

			const userId = serviceLocator.get('core').getUserId();

			this.copilotCounter.value = this.store.getters['recentModel/getCollection']()
				.reduce((counter, item) => {
					delete this.copilotCounter.detail[item.id];

					const dialog = this.store.getters['dialoguesModel/getById'](item.id);
					if (!dialog)
					{
						logger.error(`${this.getClassName()}.update: there is no dialog "${item.id}" in model`);

						return counter;
					}

					const isChatMuted = dialog && dialog.muteList.includes(userId);
					if (!isChatMuted)
					{
						return counter + this.calculateChatCounter(item, dialog);
					}

					return counter;
				}, 0)
			;

			this.copilotCounter.update();

			const counters = {
				copilot: this.copilotCounter.value,
			};

			logger.log(`${this.getClassName()}.update`, counters);

			BX.postComponentEvent('ImRecent::counter::list', [counters], 'im.navigation');
		}

		clearAll()
		{
			logger.log(`${this.getClassName()}.clearAll`);

			this.copilotCounter.clearAll();
		}

		#clearAllHandler = () => {
			this.clearAll();
			this.update();
		};

		deleteCounterByChatId(chatId)
		{
			const counterId = this.getIdByChatId(chatId);
			if (!Type.isNil(this.copilotCounter.detail[counterId]))
			{
				delete this.copilotCounter.detail[counterId];
			}
		}

		updateCounterDetailByCounterState(counterState)
		{
			const {
				type,
				counter,
				chatId,
			} = counterState;

			if (this.mutedChatCollection.has(chatId))
			{
				return;
			}

			const counterId = this.getIdByChatId(chatId);

			if (type === CounterType.copilot)
			{
				this.copilotCounter.detail[counterId] = counter;
			}
		}
	}

	module.exports = { CopilotCounters };
});
