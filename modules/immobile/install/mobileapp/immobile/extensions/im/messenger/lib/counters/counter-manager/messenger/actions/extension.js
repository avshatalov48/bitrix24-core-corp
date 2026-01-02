/**
 * @module im/messenger/lib/counters/counter-manager/messenger/actions
 */
jn.define('im/messenger/lib/counters/counter-manager/messenger/actions', (require, exports, module) => {
	const { MessengerCounterSender } = require('im/messenger/lib/counters/counter-manager/messenger/sender');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ChatDataProvider } = require('im/messenger/provider/data');

	async function readAllCountersOnClient()
	{
		const chatDataProvider = new ChatDataProvider();

		await chatDataProvider.clearCounters();

		MessengerCounterSender.getInstance().sendReadAll();
		serviceLocator.get('tab-counters').clearAll();
	}

	module.exports = {
		readAllCountersOnClient,
	};
});
