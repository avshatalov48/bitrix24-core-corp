/**
 * @module im/messenger-v2/application/lib/event-handler/store
 */
jn.define('im/messenger-v2/application/lib/event-handler/store', (require, exports, module) => {
	const { AppStatus } = require('im/messenger/const');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class StoreEventHandler
	 */
	class StoreEventHandler
	{
		static #instance;

		/**
		 * @return {StoreEventHandler}
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
			this.logger = getLoggerWithContext('messenger-v2--store-event-handler', this);
			this.isLaunched = false;
			this.appStatus = '';
		}

		/**
		 * @return {CoreApplication}
		 */
		get #core()
		{
			return serviceLocator.get('core');
		}

		/**
		 * @return {MessengerCoreStoreManager}
		 */
		get #storeManager()
		{
			return serviceLocator.get('core').getStoreManager();
		}

		/**
		 * @return {MessengerHeaderController}
		 */
		get #headerController()
		{
			return serviceLocator.get('messenger-header-controller');
		}

		/**
		 * @type {Refresher|null}
		 */
		get #refresher()
		{
			return serviceLocator.get('refresher');
		}

		subscribeEvents()
		{
			if (this.isLaunched)
			{
				return;
			}

			this.#storeManager.on('applicationModel/setStatus', this.applicationSetStatusHandler);

			this.isLaunched = true;
		}

		unsubscribeEvents()
		{
			if (!this.isLaunched)
			{
				return;
			}

			this.#storeManager.off('applicationModel/setStatus', this.applicationSetStatusHandler);

			this.isLaunched = false;
		}

		applicationSetStatusHandler = async (mutation) => {
			const statusKey = mutation.payload.data.status.name;
			const statusValue = mutation.payload.data.status.value;
			const wasAppOffline = this.appStatus === AppStatus.networkWaiting;
			const isAppOnline = (statusKey === AppStatus.networkWaiting && statusValue === false);
			if (wasAppOffline && isAppOnline)
			{
				void this.#refresher.refreshOnRestoreConnection();
			}

			this.#headerController.redrawTitleIfNeeded();
			this.appStatus = this.#core.getAppStatus();
		};
	}

	module.exports = { StoreEventHandler };
});
