/**
 * @module im/messenger/provider/service/connection
 */
jn.define('im/messenger/provider/service/connection', (require, exports, module) => {
	const { Type } = require('type');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Logger } = require('im/messenger/lib/logger');
	const {
		AppStatus,
		ConnectionStatus,
	} = require('im/messenger/const');

	const isGetConnectionStatusSupported = Type.isFunction(device.getConnectionStatus);

	/**
	 * @class ConnectionService
	 */
	class ConnectionService
	{
		/**
		 * @return {ConnectionService}
		 */
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new this();
			}

			return this.instance;
		}

		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
			this.onStatusChanged = this.statusChangedHandler.bind(this);

			if (isGetConnectionStatusSupported)
			{
				device.on('connectionStatusChanged', this.onStatusChanged);
			}
		}

		destructor()
		{
			this.instance = undefined;

			if (isGetConnectionStatusSupported)
			{
				device.off('connectionStatusChanged', this.onStatusChanged);
			}
		}

		/**
		 * @return {ConnectionStatus.online|ConnectionStatus.offline}
		 */
		getStatus()
		{
			if (isGetConnectionStatusSupported)
			{
				return device.getConnectionStatus();
			}

			Logger.warn('ConnectionService: device.getConnectionStatus() is not supported.');

			return ConnectionStatus.online;
		}

		updateStatus()
		{
			const status = this.getStatus();

			this.statusChangedHandler(status);
		}

		/**
		 * @private
		 * @param {ConnectionStatus.online|ConnectionStatus.offline} status
		 */
		statusChangedHandler(status)
		{
			switch (status)
			{
				case ConnectionStatus.online:
					serviceLocator.get('core').setAppStatus(AppStatus.networkWaiting, false);
					break;

				case ConnectionStatus.offline:
					serviceLocator.get('core').setAppStatus(AppStatus.networkWaiting, true);
					break;

				default:
					Logger.error('ConnectionService: unknown connection status: ', status);
					break;
			}
		}
	}

	module.exports = {
		ConnectionService,
	};
});
