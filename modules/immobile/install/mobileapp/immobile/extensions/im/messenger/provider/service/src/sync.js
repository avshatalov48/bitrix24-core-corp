/**
 * @module im/messenger/provider/service/sync
 */
jn.define('im/messenger/provider/service/sync', (require, exports, module) => {
	const { Type } = require('type');

	const {
		AppStatus,
	} = require('im/messenger/const');
	const { core } = require('im/messenger/core');
	const { Settings } = require('im/messenger/lib/settings');
	const { DateService } = require('im/messenger/provider/service/classes/sync/date');
	const { LoadService } = require('im/messenger/provider/service/classes/sync/load');
	const { PullEventQueue } = require('im/messenger/provider/service/classes/sync/pull-event-queue');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sync-service');

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
		}

		get isSyncInProgress()
		{
			return this.syncInProgress;
		}

		/**
		 * @return {Promise}
		 */
		async sync()
		{
			if (!Settings.isLocalStorageEnabled)
			{
				return Promise.reject(new Error('SyncService.sync error: local storage is disabled'));
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
			core.setAppStatus(AppStatus.sync, true);

			this.syncStartDate = Date.now();
			this.syncInProgress = true;

			const lastSyncDate = await this.dateService.getLastSyncDate();
			this.loadChangelog({
				fromDate: lastSyncDate,
			});

			return this.syncPromise;
		}

		checkPullEventNeedsIntercept(params, extra, command)
		{
			if (!Settings.isLocalStorageEnabled)
			{
				return false;
			}

			return (this.isSyncInProgress && !extra.fromSyncService) || core.getAppStatus() === AppStatus.connection;
		}

		storePullEvent(params, extra, command)
		{
			logger.info('SyncService.storePullEvent: ', params, extra, command);

			this.pullEventQueue.enqueue({ params, extra, command });
		}

		/**
		 * @private
		 */
		emitStoredPullEvents()
		{
			while (this.pullEventQueue.isEmpty() === false)
			{
				const {
					params,
					extra,
					command,
				} = this.pullEventQueue.dequeue();

				extra.fromSyncService = true;

				BX.PULL.emit({
					type: BX.PullClient.SubscriptionType.Server,
					moduleId: 'im',
					data: { params, extra, command },
				});
			}
		}

		/**
		 * @private
		 */
		async loadChangelog({ fromDate, fromId })
		{
			const result = await this.loadService.loadPage({
				fromDate,
				fromId,
			});

			const lastSyncId = result.lastId;
			const hasMore = result.hasMore === true && Type.isNumber(lastSyncId);
			if (hasMore === true)
			{
				await this.loadChangelog({
					fromId: lastSyncId,
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
			this.emitStoredPullEvents();
			this.syncInProgress = false;

			this.syncFinishDate = Date.now();
			this.lastSyncTime = this.syncFinishDate - this.syncStartDate;
			core.setAppStatus(AppStatus.sync, false);

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
