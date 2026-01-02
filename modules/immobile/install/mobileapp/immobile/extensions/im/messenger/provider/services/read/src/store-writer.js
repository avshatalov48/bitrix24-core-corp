/**
 * @module im/messenger/provider/services/read/store-writer
 */
jn.define('im/messenger/provider/services/read/store-writer', (require, exports, module) => {
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');

	const logger = getLoggerWithContext('counter--store-writer', 'CounterStoreWriter');

	/**
	 * @class CounterStoreWriter
	 */
	class CounterStoreWriter
	{
		/**
		 * @return {MessengerCoreStore}
		 */
		get store()
		{
			return serviceLocator.get('core').getStore();
		}

		/**
		 * @param {number} chatId
		 * @param {number} deductibleCounter
		 * @return {Promise<void>}
		 */
		async decreaseCounter(chatId, deductibleCounter)
		{
			logger.log('decreaseCounter', chatId, deductibleCounter);
			const counterState = this.findById(chatId);
			if (!Type.isPlainObject(counterState))
			{
				return;
			}

			const newCounter = counterState.counter - deductibleCounter > 0
				? counterState.counter - deductibleCounter
				: 0
			;

			this.set({
				...counterState,
				counter: newCounter,
				locked: true,
			})
				.catch((error) => {
					logger.error('decreaseCounter error', error);
				})
			;
		}

		async set(counterState)
		{
			await this.store.dispatch('counterModel/setList', {
				counterList: [counterState],
				ignoreLock: true,
			});
		}

		/**
		 * @param {number} chatId
		 * @return {?CounterModelState}
		 */
		findById(chatId)
		{
			return this.store.getters['counterModel/getByChatId'](chatId)
		}
	}

	module.exports = { CounterStoreWriter };
});
