/**
 * @module im/messenger/provider/service/sync
 */
jn.define('im/messenger/provider/service/sync', (require, exports, module) => {
	const { Type } = require('type');
	const { unique } = require('utils/array');

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
			this.addedMessageIds = new Set();
			this.deletedChatIds = new Set();
			this.deletedMessageIds = new Set();
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

			return (this.isSyncInProgress && !extra.fromSyncService) || serviceLocator.get('core').getAppStatus() === AppStatus.connection;
		}

		/**
		 * @deprecated
		 */
		storePullEvent(params, extra, command)
		{
			return;

			logger.info('SyncService.storePullEvent: ', params, extra, command);

			this.pullEventQueue.enqueue({ params, extra, command });
		}

		/**
		 * @private
		 * @deprecated This queue was needed to eliminate a potential data race with
		 * the last page of the synchronization service.
		 * A large queue of pools caused the entire application to hang, temporarily disabled.
		 */
		async emitStoredPullEvents()
		{
			return;

			logger.log('SyncService.emitStoredPullEvents: pullEventQueue', [...this.pullEventQueue.queue]);

			while (this.pullEventQueue.isEmpty() === false)
			{
				const {
					params,
					/** @type {PullExtraParams} */
					extra,
					command,
				} = this.pullEventQueue.dequeue();

				if (
					this.status === AppStatus.sync
					&& (command === 'messageChat' || command === 'message')
				)
				{
					const messageId = params.message.id;

					if (this.addedMessageIds.has(messageId))
					{
						continue;
					}

					if (this.deletedChatIds.has(params.chatId))
					{
						continue;
					}

					if (this.deletedMessageIds.has(messageId))
					{
						continue;
					}
				}

				extra.fromSyncService = true;

				try
				{
					BX.PULL.emit({
						type: BX.PullClient.SubscriptionType.Server,
						moduleId: 'im',
						data: { params, extra, command },
					});
				}
				catch (error)
				{
					logger.error('SyncService.emitStoredPullEvents error:', error);
				}
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
				isBackgroundSync: this.isBackground,
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

			if (Type.isArrayFilled(result.addedMessageIdList))
			{
				this.addedMessageIds = new Set([...this.addedMessageIds, ...result.addedMessageIdList]);
			}

			if (Type.isArrayFilled(result.deletedChatIdList))
			{
				this.deletedChatIds = new Set([...this.deletedChatIds, ...result.deletedChatIdList]);
			}

			if (Type.isArrayFilled(result.deletedChatIdList))
			{
				this.deletedMessageIds = new Set([...this.deletedMessageIds, ...result.deletedChatIdList]);
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
			this.addedMessageIds = new Set();
			this.deletedChatIds = new Set();
			this.deletedMessageIds = new Set();
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
