/**
 * @module im/messenger-v2/application/lib/refresher
 */
jn.define('im/messenger-v2/application/lib/refresher', (require, exports, module) => {
	/* global include, InAppNotifier */
	include('InAppNotifier');

	const { Type } = require('type');
	const { Loc } = require('im/messenger/loc');
	const { EntityReady } = require('entity-ready');

	const {
		AppStatus,
		EventType,
		MessengerInitRestMethod,
		WaitingEntity,
		RefreshMode,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Feature } = require('im/messenger/lib/feature');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { SmileManager } = require('im/messenger/lib/smile-manager');
	const { Worker } = require('im/messenger/lib/helper');

	const { MessageQueueRequestManager } = require('im/messenger-v2/application/lib/message-queue-request-manager');

	const REFRESH_AFTER_ERROR_INTERVAL = 10000;

	/**
	 * @class Refresher
	 */
	class Refresher
	{
		static #instance;

		/**
		 * @return {Refresher}
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
			this.logger = getLoggerWithContext('messenger-v2--refresher', this);

			this.isReady = false;
			this.isFirstLoad = true;

			this.core = serviceLocator.get('core');

			this.refreshAfterErrorInterval = REFRESH_AFTER_ERROR_INTERVAL;
			this.initAfterRefreshErrorWorker();

			EntityReady.addCondition(WaitingEntity.chat, () => this.isReady);

			/** @type {JSSharedStorage} */
			this.departmentColleaguesStore = Application.sharedStorage('immobileDepartmentColleagues');
		}

		/**
		 * @type {MessengerInitService|null}
		 */
		get #messengerInitService()
		{
			const messengerInitService = serviceLocator.get('messenger-init-service');
			if (messengerInitService)
			{
				return messengerInitService;
			}

			this.logger.error('messengerInitService is not initialized.');

			return null;
		}

		/**
		 * @type {SyncServiceV2|null}
		 */
		get syncService()
		{
			const syncService = serviceLocator.get('sync-service');
			if (syncService)
			{
				return syncService;
			}

			this.logger.error('syncService is not initialized.');

			return null;
		}

		/**
		 * @return {RecentManager|null}
		 */
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

		initAfterRefreshErrorWorker()
		{
			const notifyRefreshError = () => {
				this.refreshErrorNoticeFlag = true;
				const refreshErrorNotificationTime = this.refreshAfterErrorInterval / 1000 - 2;

				InAppNotifier.showNotification({
					message: Loc.getMessage('IMMOBILE_MESSENGER_APPLICATION_REFRESHER_REFRESH_ERROR'),
					backgroundColor: '#E6000000',
					time: refreshErrorNotificationTime,
				});
			};

			this.refreshErrorWorker = new Worker({
				frequency: 2000,
				callback: notifyRefreshError.bind(this),
			});
		}

		async refreshOnStartup()
		{
			this.logger.log('refreshOnStartup');

			return this.#refresh({
				shortMode: false,
				mode: RefreshMode.startUp,
			});
		}

		async refreshOnResume()
		{
			this.logger.log('refreshOnResume');

			return this.#refresh({
				shortMode: true,
				mode: RefreshMode.resume,
			});
		}

		async refreshOnScrollUp()
		{
			this.logger.log('refreshOnScrollUp');

			return this.#refresh({
				shortMode: false,
				mode: RefreshMode.scrollUp,
			});
		}

		async refreshOnRestoreConnection()
		{
			this.logger.log('refreshOnRestoreConnection');

			return this.#refresh({
				shortMode: false,
				mode: RefreshMode.restoreConnection,
			});
		}

		/**
		 * @private
		 * @param {boolean} shortMode
		 * @param {string} mode
		 * @return {string[]}
		 */
		getInitRestMethods(shortMode, mode)
		{
			return [
				this.#recentManager.getActiveRecent().getRefreshMethod(mode),
				...(shortMode && Feature.isLocalStorageEnabled)
					? this.#getInitRestMethodsForRefresh()
					: this.#getInitRestMethodsForApplicationStartup(),
			].filter(Boolean);
		}

		/**
		 * @return {string[]}
		 */
		#getInitRestMethodsForRefresh()
		{
			return [
				MessengerInitRestMethod.imCounters,
				MessengerInitRestMethod.anchors,
				MessengerInitRestMethod.activeCalls,
			];
		}

		/**
		 * @return {string[]}
		 */
		#getInitRestMethodsForApplicationStartup()
		{
			const methodList = [
				...this.#getInitRestMethodsForRefresh(),
				MessengerInitRestMethod.promotion,
				MessengerInitRestMethod.tariffRestriction,
			];

			const isNeedDepartmentColleagues = !this.departmentColleaguesStore.get('isRequested');
			if (isNeedDepartmentColleagues)
			{
				methodList.push(MessengerInitRestMethod.departmentColleagues);
			}

			return methodList;
		}

		/**
		 * @param {object} params
		 * @param {boolean} params.shortMode
		 * @override
		 */
		async #refresh(params = {})
		{
			const {
				shortMode = false,
				mode,
			} = params;

			this.syncService?.stopBackgroundSync();
			await this.core.setAppStatus(AppStatus.connection, true);
			this.smileManager = SmileManager.getInstance();
			void SmileManager.init();

			await MessageQueueRequestManager.getInstance().callBatch();

			const recentManager = this.#recentManager;
			this.#messengerInitService.onceOnInit(recentManager?.getActiveRecent().getRefreshHandler(mode));
			const methods = this.getInitRestMethods(shortMode, mode);

			return this.#messengerInitService.runAction(methods)
				.then(() => {
					// eslint-disable-next-line promise/no-nesting
					void this.#afterRefresh().catch((error) => this.logger.error('#afterRefresh error:', error));
				})
				.catch((response) => {
					// eslint-disable-next-line promise/no-nesting
					void this.#afterRefreshError(response).catch((error) => this.logger.error('#afterRefreshError error:', error));
				})
				.finally(() => {
					MessengerEmitter.emit(EventType.dialog.external.scrollToFirstUnread);
				});
		}

		async #afterRefresh()
		{
			if (Feature.isLocalStorageEnabled)
			{
				// We're setting the state here to prevent a break between the "connection" and "sync" header text,
				// and to ensure that pull event handlers don't handle events during state changes.
				await this.core.setAppStatus(AppStatus.sync, true);
			}
			await this.core.setAppStatus(AppStatus.connection, false);

			this.refreshErrorNoticeFlag = false;
			this.refreshErrorWorker.stop();

			serviceLocator.get('tab-counters').update();

			const isRequestedDepartmentColleagues = this.departmentColleaguesStore.get('isRequested');
			if (!isRequestedDepartmentColleagues)
			{
				this.departmentColleaguesStore.set('isRequested', true);
			}

			if (!Feature.isLocalStorageEnabled)
			{
				return this.#ready();
			}

			return this.syncService?.startSync()
				.then(() => this.#ready())
				.catch((error) => {
					this.logger.error('#afterRefresh error', error);
				})
				.finally(() => {
					this.syncService?.startBackgroundSync();
				})
			;
		}

		async #afterRefreshError(response)
		{
			this.logger.error('#afterRefreshError', response);
			const errorList = Type.isArray(response) ? response : [response];

			for (const error of errorList)
			{
				if (error?.code === 'REQUEST_CANCELED')
				{
					return;
				}
			}

			const secondsBeforeRefresh = this.refreshAfterErrorInterval / 1000;
			this.logger.error(`refresh error. Try again in ${secondsBeforeRefresh} seconds.`);

			clearTimeout(this.refreshTimeout);

			this.refreshTimeout = setTimeout(() => {
				if (!this.refreshErrorNoticeFlag && !Application.isBackground())
				{
					this.refreshErrorWorker.startOnce();
				}

				this.logger.warn('refresh after error');
				this.#refresh();
			}, this.refreshAfterErrorInterval);
		}

		async #ready()
		{
			this.isReady = true;

			if (this.isFirstLoad)
			{
				this.logger.warn('ready');
				EntityReady.ready(WaitingEntity.chat);
			}

			this.isFirstLoad = false;
			await serviceLocator.get('read-service').readMessagesFromQueue();

			return this.core.setAppStatus(AppStatus.running, true)
				.then(() => {
					MessengerEmitter.emit(EventType.messenger.afterRefreshSuccess);

					this.logger.warn('refresh complete');
				})
				.catch((error) => {
					this.logger.error(error);
				})
			;
		}
	}

	module.exports = {
		Refresher,
	};
});
