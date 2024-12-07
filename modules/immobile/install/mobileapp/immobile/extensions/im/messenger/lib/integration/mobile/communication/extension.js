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
			this.messagerInitService = serviceLocator.get('messenger-init-service');
			this.bindMethods();
			this.subscribeInitMessengerEvent();
		}

		bindMethods()
		{
			this.initMessenger = this.initMessenger.bind(this);
			this.handleUserCountersGet = this.handleUserCountersGet.bind(this);
			this.handleServerTime = this.handleServerTime.bind(this);
			this.handleDesktopStatusGet = this.handleDesktopStatusGet.bind(this);
		}

		subscribeInitMessengerEvent()
		{
			this.messagerInitService.onInit(this.initMessenger);
		}

		handleUserCountersGet(portalCounters)
		{
			const counters = portalCounters.result;
			const time = portalCounters.time ? { start: portalCounters.time } : null;

			Logger.info('Counters.handleUserCountersGet', counters, time);
			BX.postComponentEvent('onSetUserCounters', [counters, time], 'communication');
		}

		handleServerTime(serverTime)
		{
			Logger.info('Communication.handleServerTime', serverTime);
			BX.postComponentEvent('onUpdateServerTime', [serverTime], 'communication');
		}

		handleDesktopStatusGet(desktopStatus)
		{
			Logger.info('Communication.handleDesktopStatusGet', desktopStatus);
			BX.postComponentEvent('setDesktopStatus', [desktopStatus], 'communication');
		}

		/**
		 * @param {immobileTabChatLoadResult | immobileTabChannelLoadResult | immobileTabCopilotLoadResult} data
		 */
		initMessenger(data)
		{
			const { desktopStatus, serverTime, portalCounters } = data;

			if (desktopStatus)
			{
				this.handleDesktopStatusGet(desktopStatus);
			}

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
