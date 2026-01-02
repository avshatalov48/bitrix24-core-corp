/**
 * @module im/messenger-v2/application/lib/event-handler/external
 */
jn.define('im/messenger-v2/application/lib/event-handler/external', (require, exports, module) => {
	const { Type } = require('type');

	const {
		EventType,
		OpenRequest,
		RefreshMode,
	} = require('im/messenger/const');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { Feature } = require('im/messenger/lib/feature');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { waitViewLoaded } = require('im/messenger-v2/lib/wait-view-loaded');
	/**
	 * @class ExternalEventHandler
	 */
	class ExternalEventHandler
	{
		static #instance;

		/**
		 * @return {ExternalEventHandler}
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
			this.isLaunched = false;
			this.logger = getLoggerWithContext('messenger-v2--external-event-handler', this);

			this.#bindMethods();
		}

		/**
		 * @type {Refresher|null}
		 */
		get #refresher()
		{
			const refresher = serviceLocator.get('refresher');
			if (refresher)
			{
				return refresher;
			}

			this.logger.error('refresher is not initialized.');

			return null;
		}

		/**
		 * @type {DialogManager|null}
		 */
		get #dialogManager()
		{
			const dialogManager = serviceLocator.get('dialog-manager');
			if (dialogManager)
			{
				return dialogManager;
			}

			this.logger.error('dialogManager is not initialized.');

			return null;
		}

		get #recentManager()
		{
			const recentManager = serviceLocator.get('recent-manager');
			if (recentManager)
			{
				return recentManager;
			}

			this.logger.error('recentManager is not initialized.');

			return null;
		}

		#bindMethods()
		{
			this.appActiveBeforeHandler = this.appActiveBeforeHandler.bind(this);
			this.appActiveHandler = this.appActiveHandler.bind(this);
			this.failRestoreConnectionHandler = this.failRestoreConnectionHandler.bind(this);
			this.appPausedHandler = this.appPausedHandler.bind(this);

			this.notificationOpenHandler = this.notificationOpenHandler.bind(this);
			this.notificationReloadHandler = this.notificationReloadHandler.bind(this);

			this.openRequestHandler = this.openRequestHandler.bind(this);
		}

		subscribeEvents()
		{
			if (this.isLaunched)
			{
				return;
			}

			BX.addCustomEvent(EventType.app.activeBefore, this.appActiveBeforeHandler);
			BX.addCustomEvent(EventType.app.active, this.appActiveHandler);
			BX.addCustomEvent(EventType.app.failRestoreConnection, this.failRestoreConnectionHandler);
			BX.addCustomEvent(EventType.app.paused, this.appPausedHandler);

			BX.addCustomEvent(EventType.notification.open, this.notificationOpenHandler);
			BX.addCustomEvent(EventType.notification.reload, this.notificationReloadHandler);

			jnComponent.on(EventType.jnComponent.openRequest, this.openRequestHandler);

			this.isLaunched = true;
		}

		unsubscribeEvents()
		{
			if (!this.isLaunched)
			{
				return;
			}

			BX.removeCustomEvent(EventType.app.activeBefore, this.appActiveBeforeHandler);
			BX.removeCustomEvent(EventType.app.active, this.appActiveHandler);
			BX.removeCustomEvent(EventType.app.failRestoreConnection, this.failRestoreConnectionHandler);
			BX.removeCustomEvent(EventType.app.paused, this.appPausedHandler);

			BX.removeCustomEvent(EventType.notification.open, this.notificationOpenHandler);
			BX.removeCustomEvent(EventType.notification.reload, this.notificationReloadHandler);

			jnComponent.off(EventType.jnComponent.openRequest, this.openRequestHandler);

			this.isLaunched = false;
		}

		async appActiveBeforeHandler()
		{
			this.logger.log('appActiveBeforeHandler');
			await serviceLocator.get('push-manager').executeStoredPullEvents();
			serviceLocator.get('read-service').disableReading();

			await waitViewLoaded();

			// this.clearExtendWatchInterval();
			if (!Feature.isLocalStorageEnabled)
			{
				MessengerEmitter.emit(EventType.dialog.external.disableScrollToBottom);
			}

			void this.#refresher?.refreshOnResume()
				.finally(() => {
					MessengerEmitter.emit(EventType.dialog.external.scrollToFirstUnread);
				})
			;
		}

		async appActiveHandler()
		{
			this.logger.log('appActiveHandler');

			await waitViewLoaded();
		}

		appPausedHandler()
		{
			this.logger.log('appPausedHandler');

			serviceLocator.get('sync-service')?.updateStateForAppPausedEvent();

			this.#recentManager.inactiveRecents(RefreshMode.resume);
		}

		async failRestoreConnectionHandler()
		{
			this.logger.log('failRestoreConnectionHandler');

			await waitViewLoaded();
			this.#recentManager.inactiveRecents(RefreshMode.restoreConnection);

			void this.#refresher?.refreshOnRestoreConnection();
		}

		/**
		 * @private
		 * @param {PageManager} parentWidget
		 * @param {*} openRequest
		 * @return {Promise<void>}
		 */
		async openRequestHandler(parentWidget, openRequest = {})
		{
			this.logger.info(`${this.constructor.name}.openRequestHandler`, parentWidget, openRequest);
			if (!Type.isObject(openRequest))
			{
				return;
			}

			if (openRequest[OpenRequest.dialog] && Type.isObject(openRequest[OpenRequest.dialog].options))
			{
				/**
				 * @type {DialogOpenOptions}
				 */
				const openDialogOptions = openRequest[OpenRequest.dialog].options;
				openDialogOptions.makeTabActive = false;

				this.#dialogManager.openDialog(openDialogOptions, parentWidget).catch((error) => {
					this.logger.error(error);
				});
			}
		}

		notificationOpenHandler()
		{
			this.logger.log('notificationOpenHandler');
			const tabCounters = serviceLocator.get('tab-counters');

			tabCounters.clearNotificationCounters();
			tabCounters.update();
		}

		notificationReloadHandler()
		{
			this.logger.log('notificationReloadHandler');

			BX.postWebEvent('onBeforeNotificationsReload', {});
			Application.refreshNotifications();
		}
	}

	module.exports = { ExternalEventHandler };
});
