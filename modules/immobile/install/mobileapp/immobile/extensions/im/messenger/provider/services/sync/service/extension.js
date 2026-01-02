/**
 * @module im/messenger/provider/services/sync/service
 */
jn.define('im/messenger/provider/services/sync/service', (require, exports, module) => {
	const { Type } = require('type');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { AppStatus, ComponentCode, EventType } = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { DateService } = require('im/messenger/provider/services/sync/service/date');
	const { LoadService } = require('im/messenger/provider/services/sync/service/load');
	const { PullEventQueue } = require('im/messenger/provider/services/sync/service/pull-event-queue');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('sync-service');

	const LAST_SYNC_SERVER_DATE_OPTION = 'SYNC_SERVICE_LAST_SERVER_DATE';

	const BACKGROUND_SYNC_INTERVAL = 120_000;

	/**
	 * @class SyncService
	 */
	class SyncService
	{
		/**
		 * @return {SyncService}
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
			this.initServices();

			this.pullEventQueue = new PullEventQueue();
			this.syncInProgress = false;

			this.syncStartDate = null;
			this.syncFinishDate = null;
			this.lastSyncTime = null;
			this.status = AppStatus.sync;
			this.backgroundTimerId = null;
			/**
			 * @private
			 * @type {MessageRepository}
			 */
			this.messageRepository = serviceLocator.get('core').getRepository().message;
		}

		get isSyncInProgress()
		{
			return this.syncInProgress;
		}

		get isBackgroundSyncInProgress()
		{
			return this.syncInProgress && this.isBackground;
		}

		get isBackground()
		{
			return this.status === AppStatus.backgroundSync;
		}

		/**
		 * @param {AppStatus['sync'] || AppStatus['backgroundSync']} status
		 * @return {Promise}
		 */
		async sync(status = AppStatus.sync)
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return Promise.reject(new Error('SyncService.sync error: local storage is disabled'));
			}

			if (![AppStatus.sync, AppStatus.backgroundSync].includes(status))
			{
				return Promise.reject(new Error(`SyncService.sync error: invalid sync status: ${status}`));
			}

			if (this.syncInProgress === true)
			{
				logger.info('SyncService.sync: synchronization is already in progress');

				return this.syncPromise;
			}

			this.syncPromise = new Promise((resolve, reject) => {
				this.resolveSyncPromise = resolve;
				this.rejectSyncPromise = reject;
			});

			logger.warn('SyncService: synchronization has started.');
			this.status = status;
			await this.setAppStatus(status, true);
			this.postComponentAppStatus(true);
			this.loadService.setSyncMode(this.status);

			this.syncStartDate = Date.now();
			this.syncInProgress = true;

			const lastSyncServerDate = await serviceLocator.get('core').getRepository().option.get(LAST_SYNC_SERVER_DATE_OPTION);
			const lastSyncDate = await this.dateService.getLastSyncDate();

			const changeLogOption = {
				fromDate: lastSyncDate,
				fromServerDate: lastSyncServerDate,
			};

			logger.log('SyncService: init synchronization by', changeLogOption);
			await this.loadChangelog(changeLogOption);

			return this.syncPromise;
		}

		startBackgroundSyncInterval()
		{
			const backgroundSyncHandler = async () => {
				if (Application.isBackground())
				{
					return;
				}
				logger.info('SyncService.backgroundSync: start background synchronization');

				try
				{
					await this.sync(AppStatus.backgroundSync);
				}
				catch (error)
				{
					logger.error('SyncService.backgroundSync: error', error);
				}

				this.backgroundTimerId = setTimeout(backgroundSyncHandler, BACKGROUND_SYNC_INTERVAL);
			};

			this.backgroundTimerId = setTimeout(backgroundSyncHandler, BACKGROUND_SYNC_INTERVAL);
		}

		clearBackgroundSyncInterval()
		{
			clearTimeout(this.backgroundTimerId);
			this.backgroundTimerId = null;
		}

		/**
		 * @param {AppStatus['sync'] || AppStatus['backgroundSync']} syncStatus
		 * @param {Boolean} value
		 */
		async setAppStatus(syncStatus, value)
		{
			return serviceLocator.get('core').setAppStatus(syncStatus, value);
		}

		/**
		 * @param {Boolean} value
		 */
		postComponentAppStatus(value)
		{
			[
				ComponentCode.imCopilotMessenger,
				ComponentCode.imChannelMessenger,
				ComponentCode.imCollabMessenger,
				ComponentCode.imNavigation,
			].forEach((componentCode) => {
				BX.postComponentEvent(
					EventType.app.changeStatus,
					[{ name: this.status, value }],
					componentCode,
				);
			});
		}

		checkPullEventNeedsIntercept(params, extra, command)
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return false;
			}

			if (this.isBackgroundSyncInProgress)
			{
				return false;
			}

			return this.isSyncInProgress || serviceLocator.get('core').getAppStatus() === AppStatus.connection;
		}

		/**
		 * @param {string} fromDate
		 * @param {string|null} fromServerDate
		 * @param {?number} lastId
		 * @private
		 */
		async loadChangelog({ fromDate, fromServerDate, lastId })
		{
			const result = await this.loadService.loadPage({
				lastId,
				fromDate,
				fromServerDate,
				isBackgroundSync: this.isBackground,
			});
			const lastServerDate = result.lastServerDate;
			const hasMore = result.hasMore === true && Type.isStringFilled(lastServerDate) && Boolean(result.lastId);

			if (Type.isStringFilled(lastServerDate))
			{
				logger.log('SyncService.loadChangelog: save last server sync date', lastServerDate);
				await serviceLocator.get('core').getRepository().option.set(LAST_SYNC_SERVER_DATE_OPTION, lastServerDate);
			}

			if (hasMore === true)
			{
				await this.loadChangelog({
					fromServerDate: lastServerDate,
					lastId: result.lastId,
				});
			}
			else
			{
				await this.doSyncCompleteActions();
				this.resolveSyncPromise();
			}
		}

		/**
		 * @private
		 */
		async doSyncCompleteActions()
		{
			await this.dateService.updateLastSyncDate();

			await this.messageRepository.clearPushMessages();
			this.syncInProgress = false;

			this.syncFinishDate = Date.now();
			this.lastSyncTime = this.syncFinishDate - this.syncStartDate;
			this.setAppStatus(this.status, false);

			if (this.status === AppStatus.backgroundSync)
			{
				// need to disable sync status enabled in Messenger.afterRefresh method
				/** @see Messenger.afterRefresh */
				this.setAppStatus(AppStatus.sync, false);
			}

			this.postComponentAppStatus(false);
			this.loadService.resetSyncMode();
			this.status = null;

			logger.warn(`SyncService: synchronization completed in ${this.lastSyncTime / 1000} seconds.`);
			this.syncStartDate = null;
			this.syncFinishDate = null;
		}

		/**
		 * @private
		 */
		initServices()
		{
			this.dateService = DateService.getInstance();
			this.loadService = LoadService.getInstance();
		}
	}

	module.exports = {
		SyncService,
	};
});
