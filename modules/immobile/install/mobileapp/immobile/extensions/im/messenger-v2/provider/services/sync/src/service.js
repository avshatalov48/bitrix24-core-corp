/**
 * @module im/messenger-v2/provider/services/sync/service
 */
jn.define('im/messenger-v2/provider/services/sync/service', (require, exports, module) => {
	const { Type } = require('type');

	const { AppStatus } = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DateService } = require('im/messenger/provider/services/sync/service/date');
	const { SyncLoader } = require('im/messenger-v2/provider/services/sync/loader');
	const { SyncFiller } = require('im/messenger-v2/provider/services/sync/filler');

	const BACKGROUND_SYNC_INTERVAL = 120_000;

	/**
	 * @class SyncServiceV2
	 */
	class SyncService
	{
		static instance = null;

		/**
		 * @return {SyncServiceV2}
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
			this.syncInProgress = false;
			this.appStatus = null;
			this.backgroundTimerId = null;
			this.currentSyncPromise = null;

			this.syncStartDate = null;
			this.syncFinishDate = null;
			this.syncDuration = null;

			this.logList = [];

			this.logger = getLoggerWithContext('sync-service', this);

			this.#initServices();
		}

		#initServices()
		{
			this.dateService = DateService.getInstance();
			this.syncLoader = SyncLoader.getInstance();
			this.syncFiller = SyncFiller.getInstance();
		}

		/**
		 * @return {MessageRepository}
		 */
		get #messageRepository()
		{
			return serviceLocator.get('core').getRepository().message;
		}

		/**
		 * @param {AppStatusType} appStatus
		 * @return {Promise<*>}
		 */
		async startSync(appStatus = AppStatus.sync)
		{
			if (!Feature.isLocalStorageEnabled)
			{
				this.#processWarning('local storage is disabled, startSync is stopped');

				return null;
			}

			if (![AppStatus.sync, AppStatus.backgroundSync].includes(appStatus))
			{
				this.#processError('invalid appStatus, startSync is stopped, status:', appStatus);

				return null;
			}

			if (this.syncInProgress)
			{
				this.#processWarning('synchronization is already in progress, startSync is stopped');

				return this.currentSyncPromise;
			}

			this.currentSyncPromise = this.#performSync(appStatus);

			return this.currentSyncPromise;
		}

		/**
		 * @param {AppStatusType} appStatus
		 * @return {Promise<void>}
		 */
		async #performSync(appStatus)
		{
			this.syncInProgress = true;
			this.appStatus = appStatus;
			this.syncStartDate = Date.now();

			try
			{
				this.logger.warn(`performSync: ${this.#isBackgroundSync() ? 'background synchronization' : 'synchronization'} started`);
				await this.#setAppStatus(appStatus, true);

				const lastSyncServerDate = await this.dateService.getLastSyncServerDate();
				const lastSyncDate = await this.dateService.getLastSyncDate();

				await this.#loadChangelog({ fromDate: lastSyncDate, fromServerDate: lastSyncServerDate });

				await this.#syncComplete();
			}
			catch (error)
			{
				this.#processError('performSync catch:', error);
				await this.#setAppStatus(appStatus, false);

				throw error;
			}
			finally
			{
				this.syncInProgress = false;
				this.appStatus = null;
				this.currentSyncPromise = null;

				this.syncStartDate = null;
				this.syncFinishDate = null;
			}
		}

		startBackgroundSync()
		{
			this.stopBackgroundSync();

			const handler = async () => {
				if (!Application.isBackground())
				{
					try
					{
						await this.startSync(AppStatus.backgroundSync);
					}
					catch (error)
					{
						this.#processError('background sync catch:', error);
					}
				}
				this.backgroundTimerId = setTimeout(handler, BACKGROUND_SYNC_INTERVAL);
			};
			this.backgroundTimerId = setTimeout(handler, BACKGROUND_SYNC_INTERVAL);
		}

		stopBackgroundSync()
		{
			clearTimeout(this.backgroundTimerId);
			this.backgroundTimerId = null;
		}

		/**
		 * @desc Handles app pause event: stops background sync timer and promotes any
		 * ongoing background sync to a foreground sync so its results can be used
		 * on app resume without triggering a second sync request.
		 */
		updateStateForAppPausedEvent()
		{
			this.stopBackgroundSync();
			if (!this.#isBackgroundSyncInProgress())
			{
				return;
			}

			this.convertBackgroundSyncToSync()
				.then(() => {
					this.#processWarning('backgroundSync converted to sync, current status -', this.appStatus);
				})
				.catch((error) => this.#processError('convertBackgroundSyncToSync catch:', error));
		}

		convertBackgroundSyncToSync()
		{
			return this.#setAppStatus(this.appStatus, false)
				.then(() => {
					return this.#setAppStatus(AppStatus.sync, true);
				})
				.then(() => {
					this.appStatus = AppStatus.sync;
				});
		}

		/**
		 * @param {string} fromDate
		 * @param {string} fromServerDate
		 * @param {?number} lastId
		 */
		async #loadChangelog({ fromDate, fromServerDate, lastId })
		{
			const result = await this.syncLoader.loadPage({
				lastId,
				fromDate,
				fromServerDate,
			});
			const { navigationData } = result;

			await this.syncFiller.applyData(result, this.#isBackgroundSync());
			await this.#updateServerDate(navigationData.lastServerDate);

			const hasMore = navigationData.hasMore === true
				&& Type.isStringFilled(navigationData.lastServerDate)
				&& Boolean(navigationData.lastId)
			;

			if (hasMore)
			{
				await this.#loadChangelog({
					fromServerDate: navigationData.lastServerDate,
					lastId: navigationData.lastId,
				});
			}
		}

		/**
		 * @param {string} lastServerDate
		 */
		async #updateServerDate(lastServerDate)
		{
			if (Type.isStringFilled(lastServerDate))
			{
				await this.dateService.setLastSyncServerDate(lastServerDate);
			}
		}

		async #syncComplete()
		{
			await this.dateService.updateLastSyncDate();
			await this.#messageRepository.clearPushMessages();

			await this.#setAppStatus(this.appStatus, false);

			this.syncInProgress = false;
			this.appStatus = null;

			this.#logSyncDuration();
		}

		/**
		 * @param {AppStatusType} appStatus
		 * @param {boolean} value
		 */
		async #setAppStatus(appStatus, value)
		{
			return serviceLocator.get('core').setAppStatus(appStatus, value);
		}

		/**
		 * @returns {boolean}
		 */
		checkPullEventNeedsIntercept()
		{
			if (!Feature.isLocalStorageEnabled || this.#isBackgroundSyncInProgress())
			{
				return false;
			}

			return this.syncInProgress || serviceLocator.get('core').getAppStatus() === AppStatus.connection;
		}

		/**
		 * @return {boolean}
		 */
		#isBackgroundSync()
		{
			return this.appStatus === AppStatus.backgroundSync;
		}

		/**
		 * @return {boolean}
		 */
		#isBackgroundSyncInProgress()
		{
			return this.syncInProgress && this.appStatus === AppStatus.backgroundSync;
		}

		/**
		 * @param {string} message
		 * @param {*} [data]
		 */
		#processError(message, data = null)
		{
			this.logger.error(message, data);

			const logEntry = {
				type: 'error',
				message,
			};
			if (!Type.isNil(data))
			{
				logEntry.data = data;
			}
			this.logList.push(logEntry);
		}

		/**
		 * @param {string} message
		 * @param {*} [data]
		 */
		#processWarning(message, data = null)
		{
			this.logger.warn(message, data);

			const logEntry = {
				type: 'warning',
				message,
			};
			if (!Type.isNil(data))
			{
				logEntry.data = data;
			}
			this.logList.push(logEntry);
		}

		#logSyncDuration()
		{
			this.syncFinishDate = Date.now();
			this.syncDuration = this.syncFinishDate - this.syncStartDate;

			this.logger.warn(`syncComplete: synchronization completed in ${this.syncDuration / 1000} seconds.`);

			this.syncStartDate = null;
			this.syncFinishDate = null;
		}

		/**
		 * @desc use in devtools, helpful for debug
		 */
		printAllLogs()
		{
			this.getFormattedLogs().forEach((err) => console.error(err));
		}

		/**
		 * @returns {Array<string>}
		 */
		getFormattedLogs()
		{
			return this.logList.map((entry, idx) => {
				const { type, message, data } = entry;
				let errorMsg = `[${idx}] [${type.toUpperCase()}] ${message}`;

				if (!Type.isUndefined(data))
				{
					if (Type.isString(data))
					{
						errorMsg += ` | ${data}`;
					}
					else if (data instanceof Error)
					{
						errorMsg += ` | ${data.name}: ${data.message}\n${data.stack}`;
					}
					else if (Type.isObject(data))
					{
						if (data && (data.message || data.stack))
						{
							errorMsg += ` | ${data.name || ''}: ${data.message || ''}\n${data.stack || ''}`;
						}
						else
						{
							errorMsg += ` | ${JSON.stringify(data)}`;
						}
					}
					else
					{
						errorMsg += ` | ${String(data)}`;
					}
				}

				return errorMsg;
			});
		}
	}

	module.exports = {
		SyncService,
	};
});
