/**
 * @module im/messenger/lib/counters/copilot-counters
 */
jn.define('im/messenger/lib/counters/copilot-counters', (require, exports, module) => {
	const { Type } = require('type');

	const { BaseCounters } = require('im/messenger/lib/counters/lib/base-counters');
	const { Counter } = require('im/messenger/lib/counters/lib/counter');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('counters--copilot');

	/**
	 * @class ChatCounters
	 */
	class CopilotCounters extends BaseCounters
	{
		constructor()
		{
			super({ logger });
		}

		initCounters()
		{
			this.copilotCounter = new Counter();
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

			Object.keys(counters.copilot).forEach((chatId) => {
				this.copilotCounter.detail[`chat${chatId}`] = counters.copilot[chatId];
			});

			this.copilotCounter.value = counters.type.copilot;
			this.update();
		}

		update()
		{
			this.clearUpdateTimeout();
			this.copilotCounter.reset();

			this.copilotCounter.value = this.store.getters['recentModel/getCollection']()
				.reduce((counter, item) => {
					delete this.copilotCounter.detail[item.id];

					const dialog = this.store.getters['dialoguesModel/getById'](item.id);
					if (!dialog)
					{
						logger.error(`${this.getClassName()}.update: there is no dialog "${item.id}" in model`);

						return counter;
					}

					return counter + this.calculateItemCounter(item, dialog);
				}, 0)
			;

			this.copilotCounter.update();

			const counters = {
				copilot: this.copilotCounter.value,
			};

			logger.log(`${this.getClassName()}.update`, counters);

			BX.postComponentEvent('ImRecent::counter::list', [counters], 'communication');
			BX.postComponentEvent('ImRecent::counter::list', [counters], 'im.navigation');
		}

		clearAll()
		{
			logger.log(`${this.getClassName()}.clearAll`);

			this.copilotCounter.reset();
		}
	}

	module.exports = { CopilotCounters };
});
