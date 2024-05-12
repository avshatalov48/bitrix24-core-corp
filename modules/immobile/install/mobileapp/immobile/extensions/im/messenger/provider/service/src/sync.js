/**
 * @module im/messenger/provider/service/sync
 */
jn.define('im/messenger/provider/service/sync', (require, exports, module) => {
	const { Type } = require('type');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { AppStatus, ComponentCode, EventType } = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { DateService } = require('im/messenger/provider/service/classes/sync/date');
	const { LoadService } = require('im/messenger/provider/service/classes/sync/load');
	const { PullEventQueue } = require('im/messenger/provider/service/classes/sync/pull-event-queue');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sync-service');

	const LAST_SYNC_ID_OPTION = 'SYNC_SERVICE_LAST_ID';
	const LAST_SYNC_SERVER_DATE_OPTION = 'SYNC_SERVICE_LAST_SERVER_DATE';

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
			if (!Feature.isLocalStorageEnabled)
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
			this.setAppStatus(true);
			this.postComponentAppStatus(true);

			this.syncStartDate = Date.now();
			this.syncInProgress = true;

			const lastSyncId = await serviceLocator.get('core').getRepository().option.get(LAST_SYNC_ID_OPTION);
			const lastSyncServerDate = await serviceLocator.get('core').getRepository().option.get(LAST_SYNC_SERVER_DATE_OPTION);
			const lastSyncDate = await this.dateService.getLastSyncDate();

			const changeLogOption = {
				fromId: Type.isNumber(lastSyncId) || Type.isStringFilled(lastSyncId) ? Number(lastSyncId) : null,
				fromDate: lastSyncDate,
				fromServerDate: lastSyncServerDate,
			};

			logger.log('SyncService: init synchronization by', changeLogOption);
			await this.loadChangelog(changeLogOption);

			return this.syncPromise;
		}

		/**
		 * @param {Boolean} value
		 */
		setAppStatus(value)
		{
			serviceLocator.get('core').setAppStatus(AppStatus.sync, value);
		}

		/**
		 * @param {Boolean} value
		 */
		postComponentAppStatus(value)
		{
			BX.postComponentEvent(
				EventType.app.changeStatus,
				[{ name: AppStatus.sync, value }],
				ComponentCode.imCopilotMessenger,
			);
		}

		checkPullEventNeedsIntercept(params, extra, command)
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return false;
			}

			return (this.isSyncInProgress && !extra.fromSyncService) || serviceLocator.get('core').getAppStatus() === AppStatus.connection;
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
		async loadChangelog({ fromDate, fromId, fromServerDate })
		{
			const result = await this.loadService.loadPage({
				fromDate,
				fromId,
				fromServerDate,
			});

			const lastSyncId = result.lastId;
			const lastServerDate = result.lastServerDate;

			const hasMore = result.hasMore === true && Type.isNumber(lastSyncId);

			if (Type.isNumber(lastSyncId))
			{
				logger.log('SyncService.loadChangelog: save last sync id', lastSyncId);
				await serviceLocator.get('core').getRepository().option.set(LAST_SYNC_ID_OPTION, lastSyncId);
			}

			if (Type.isStringFilled(lastServerDate))
			{
				logger.log('SyncService.loadChangelog: save last server sync date', lastServerDate);
				await serviceLocator.get('core').getRepository().option.set(LAST_SYNC_SERVER_DATE_OPTION, lastServerDate);
			}

			if (hasMore === true)
			{
				await this.loadChangelog({
					fromId: lastSyncId,
					fromServerDate: lastServerDate,
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
			this.setAppStatus(false);
			this.postComponentAppStatus(false);

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
