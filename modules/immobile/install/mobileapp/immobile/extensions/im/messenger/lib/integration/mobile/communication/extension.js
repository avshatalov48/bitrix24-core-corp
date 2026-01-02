/**
 * @module im/messenger/lib/integration/mobile/communication
 */
jn.define('im/messenger/lib/integration/mobile/communication', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class Communication
	 */
	class Communication
	{
		constructor()
		{
			this.messengerInitService = serviceLocator.get('messenger-init-service');
			this.bindMethods();
			this.subscribeInitMessengerEvent();
		}

		bindMethods()
		{
			this.initMessenger = this.initMessenger.bind(this);
			this.handleUserCountersGet = this.handleUserCountersGet.bind(this);
			this.handleServerTime = this.handleServerTime.bind(this);
		}

		subscribeInitMessengerEvent()
		{
			this.messengerInitService.onInit(this.initMessenger);
		}

		handleUserCountersGet(portalCounters)
		{
			const counters = portalCounters.result;
			const time = portalCounters.time ? { start: portalCounters.time } : null;

			Logger.info('TabCounters.handleUserCountersGet', counters, time);
			BX.postComponentEvent('onSetUserCounters', [counters, time], 'communication');
		}

		handleServerTime(serverTime)
		{
			Logger.info('Communication.handleServerTime', serverTime);
			BX.postComponentEvent('onUpdateServerTime', [serverTime], 'communication');
		}

		/**
		 * @param {immobileTabChatLoadResult | immobileTabChannelLoadResult | immobileTabCopilotLoadResult} data
		 */
		initMessenger(data)
		{
			const { serverTime, portalCounters } = data;

			if (serverTime)
			{
				this.handleServerTime(serverTime);
			}

			if (portalCounters)
			{
				this.handleUserCountersGet(portalCounters);
			}
		}
	}

	module.exports = {
		Communication,
	};
});
