/**
 * @module im/messenger/lib/integration/mobile/communication
 */
jn.define('im/messenger/lib/integration/mobile/communication', (require, exports, module) => {

	const { RestManager } = jn.require('im/messenger/lib/rest-manager');
	const { RestMethod } = jn.require('im/messenger/const');
	const { Logger } = jn.require('im/messenger/lib/logger');

	/**
	 * @class Communication
	 */
	class Communication
	{
		constructor()
		{
			RestManager.on(RestMethod.userCounters, {}, this.handleUserCountersGet.bind(this));
			RestManager.on(RestMethod.serverTime, {}, this.handleServerTime.bind(this));
			RestManager.on(RestMethod.imDesktopStatusGet, {}, this.handleDesktopStatusGet.bind(this));
		}

		handleUserCountersGet(response)
		{
			const error = response.error();
			if (error)
			{
				Logger.error('Counters.handleUserCountersGet', error);

				return;
			}

			Logger.info('Counters.handleUserCountersGet', response.data(), response.time());

			const counters = response.data();
			const time = response.time ? response.time() : null;

			BX.postComponentEvent('onSetUserCounters', [ counters, time ], 'communication');
		}

		handleServerTime(response)
		{
			const error = response.error();
			if (error)
			{
				Logger.error('Communication.handleServerTime', error);

				return;
			}

			Logger.info('Communication.handleServerTime', response.data());

			BX.postComponentEvent('onUpdateServerTime', [response.data()], 'communication');
		}

		handleDesktopStatusGet(response)
		{
			const error = response.error();
			if (error)
			{
				Logger.error('Communication.handleDesktopStatusGet', error);

				return;
			}

			Logger.info('Communication.handleDesktopStatusGet', response.data());

			BX.postComponentEvent('setDesktopStatus', [response.data()], 'communication');
		}
	}

	module.exports = {
		Communication,
	};
});
