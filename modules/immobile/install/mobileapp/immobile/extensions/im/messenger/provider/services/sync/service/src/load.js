/**
 * @module im/messenger/provider/services/sync/service/load
 */
jn.define('im/messenger/provider/services/sync/service/load', (require, exports, module) => {
	const { Type } = require('type');
	const { Uuid } = require('utils/uuid');
	const { isEqual } = require('utils/object');
	const { EntityReady } = require('entity-ready');

	const {
		RestMethod,
		ComponentCode,
		EventType,
		AppStatus,
		WaitingEntity,
	} = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Feature } = require('im/messenger/lib/feature');
	const { runAction } = require('im/messenger/lib/rest');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('sync-service');

	const FILLER_COUNTER_EMIT_RETRIES = 5;
	const FILLER_COUNTER_EMIT_RETRY_DELAY = 2000;

	/**
	 * @class LoadService
	 */
	class LoadService
	{
		/**
		 * @return {LoadService}
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
			/** @type {AppStatusType} */
			this.syncMode = AppStatus.sync;
			this.errorList = [];
			this.retryLog = [];
		}

		get emitter()
		{
			return serviceLocator.get('emitter');
		}

		/**
		 * @param {string} fromDate
		 * @param {string|null} fromServerDate
		 * @param {?number} lastId
		 * @return {Promise<Partial<SyncLoadServiceLoadPageResult>>}
		 */
		async loadPage({ fromDate, fromServerDate, lastId })
		{
			const lastDate = Type.isStringFilled(fromServerDate) ? fromServerDate : fromDate;
			const syncListOptions = {
				lastDate,
				limit: 500,
			};

			if (Type.isNumber(lastId))
			{
				syncListOptions.lastId = lastId;
			}

			try
			{
				logger.log('RestMethod.imV2SyncList request data:', syncListOptions);
				const result = await runAction(RestMethod.imV2SyncList, { data: syncListOptions });

				return await this.handleSyncList(result);
			}
			catch (error)
			{
				logger.error('LoadService.loadPage error: ', error);

				return Promise.resolve({
					hasMore: true,
				});
			}
		}

		/**
		 * @param {SyncListResult} result
		 * @return {Promise<SyncLoadServiceLoadPageResult>}
		 */
		async handleSyncList(result)
		{
			logger.info('RestMethod.imV2SyncList result: ', result);

			let resolveSyncListPromise = (data) => {};

			let rejectSyncListPromise = (error) => {};

			const syncListPromise = new Promise((resolve, reject) => {
				resolveSyncListPromise = resolve;
				rejectSyncListPromise = reject;
			});

			const expectedRequestResultSavedIdList = [];

			const databaseRequestResultSavedUuid = `${WaitingEntity.sync.filler.database}-${Uuid.getV4()}`;
			const messengerRequestResultSavedUuid = `${WaitingEntity.sync.filler.chat}-${Uuid.getV4()}`;
			const copilotRequestResultSavedUuid = `${WaitingEntity.sync.filler.copilot}-${Uuid.getV4()}`;
			const channelRequestResultSavedUuid = `${WaitingEntity.sync.filler.channel}-${Uuid.getV4()}`;
			const collabRequestResultSavedUuid = `${WaitingEntity.sync.filler.collab}-${Uuid.getV4()}`;
			const counterRequestResultSavedUuid = `${WaitingEntity.sync.filler.counter}-${Uuid.getV4()}`;

			const fillerOptions = this.getFillerOptions();

			if (fillerOptions.shouldFillDatabase)
			{
				expectedRequestResultSavedIdList.push(databaseRequestResultSavedUuid);
			}

			if (fillerOptions.shouldFillChat)
			{
				expectedRequestResultSavedIdList.push(messengerRequestResultSavedUuid);
			}

			if (fillerOptions.shouldFillCopilot)
			{
				expectedRequestResultSavedIdList.push(copilotRequestResultSavedUuid);
			}

			if (fillerOptions.shouldFillChannel)
			{
				expectedRequestResultSavedIdList.push(channelRequestResultSavedUuid);
			}

			if (fillerOptions.shouldFillCollab)
			{
				expectedRequestResultSavedIdList.push(collabRequestResultSavedUuid);
			}

			if (fillerOptions.shouldFillCounter)
			{
				expectedRequestResultSavedIdList.push(counterRequestResultSavedUuid);
			}

			const requestResultSavedIdList = new Set();
			const noResponseCheckTimeout = setTimeout(() => {
				if (!(isEqual(expectedRequestResultSavedIdList.sort(), [...requestResultSavedIdList].sort())))
				{
					const noResponseIdList = expectedRequestResultSavedIdList
						.filter((id) => !requestResultSavedIdList.has(id))
					;
					const errorMessage = `SyncService: no response from [${noResponseIdList}] in 5 seconds`;

					this.errorList.push(errorMessage);
					logger.error(errorMessage);
				}
			}, 5000);
			const fillCompleteHandler = (data) => {
				const {
					uuid,
					error,
				} = data;
				logger.log('SyncService received a response from SyncFiller', uuid, data);

				requestResultSavedIdList.add(uuid);
				if (error)
				{
					this.errorList.push(error);
					rejectSyncListPromise(error);

					return;
				}

				if (isEqual(expectedRequestResultSavedIdList.sort(), [...requestResultSavedIdList].sort()))
				{
					logger.log('SyncService: All fillers have recorded the data');
					clearTimeout(noResponseCheckTimeout);
					BX.removeCustomEvent(EventType.sync.requestResultSaved, fillCompleteHandler);

					resolveSyncListPromise({
						hasMore: result.navigationData.hasMore,
						lastServerDate: result.navigationData.lastServerDate,
						lastId: result.navigationData.lastId,
					});
				}
			};

			BX.addCustomEvent(EventType.sync.requestResultSaved, fillCompleteHandler);

			if (fillerOptions.shouldFillDatabase)
			{
				this.emitter.emit(EventType.sync.requestResultReceived, [{
					uuid: databaseRequestResultSavedUuid,
					result,
				}]);
			}

			if (fillerOptions.shouldFillChat)
			{
				this.emitter.emit(EventType.sync.requestResultReceived, [{
					uuid: messengerRequestResultSavedUuid,
					result,
				}]);
			}

			if (fillerOptions.shouldFillCopilot)
			{
				MessengerEmitter.emit(EventType.sync.requestResultReceived, {
					uuid: copilotRequestResultSavedUuid,
					result,
				}, ComponentCode.imCopilotMessenger);
			}

			if (fillerOptions.shouldFillChannel)
			{
				MessengerEmitter.emit(EventType.sync.requestResultReceived, {
					uuid: channelRequestResultSavedUuid,
					result,
				}, ComponentCode.imChannelMessenger);
			}

			if (fillerOptions.shouldFillCollab)
			{
				MessengerEmitter.emit(EventType.sync.requestResultReceived, {
					uuid: collabRequestResultSavedUuid,
					result,
				}, ComponentCode.imCollabMessenger);
			}

			if (fillerOptions.shouldFillCounter)
			{
				this.emitCounterFillerWithRetry({
					uuid: counterRequestResultSavedUuid,
					result,
				}, ComponentCode.imNavigation, requestResultSavedIdList);
			}

			logger.log('SyncService waits for a response from SyncFillers', expectedRequestResultSavedIdList);

			return syncListPromise;
		}

		/**
		 * @param {Object} data
		 * @param {string} componentCode
		 * @param {Set} requestResultSavedIdList
		 */
		emitCounterFillerWithRetry(data, componentCode, requestResultSavedIdList)
		{
			let attempt = 0;
			const uuid = data.uuid;
			const tryEmit = () => {
				attempt += 1;
				const logEntry = {
					uuid,
					attempt,
					timestamp: Date.now(),
					message: `Emit counter filler event, attempt ${attempt}`,
				};
				this.retryLog.push(logEntry);
				logger.warn(`[CounterRetry] ${logEntry.message}`, data);

				MessengerEmitter.emit(EventType.sync.requestResultReceived, data, componentCode);
				setTimeout(() => {
					if (requestResultSavedIdList.has(uuid))
					{
						this.retryLog.push({
							uuid,
							attempt,
							timestamp: Date.now(),
							message: 'Counter filler responded successfully',
						});

						return;
					}

					if (attempt < FILLER_COUNTER_EMIT_RETRIES)
					{
						this.retryLog.push({
							uuid,
							attempt,
							timestamp: Date.now(),
							message: 'No response from counter filler, retrying...',
						});
						tryEmit();
					}
					else
					{
						this.retryLog.push({
							uuid,
							attempt,
							timestamp: Date.now(),
							message: 'No response from counter filler after max retries',
						});
						logger.error(`[CounterRetry] No response from counter filler after ${FILLER_COUNTER_EMIT_RETRIES} attempts`, data);
					}
				}, FILLER_COUNTER_EMIT_RETRY_DELAY);
			};

			tryEmit();
		}

		isEntityReady(entityId)
		{
			if (Type.isFunction(EntityReady.isReady))
			{
				return EntityReady.isReady(entityId);
			}

			return EntityReady.readyEntitiesCollection.has(entityId);
		}

		getFillerOptions()
		{
			const options = {
				shouldFillDatabase: false,
				shouldFillChat: false,
				shouldFillCopilot: false,
				shouldFillChannel: false,
				shouldFillCollab: false,
				shouldFillCounter: false,
			};

			options.shouldFillDatabase = Feature.isLocalStorageEnabled;

			options.shouldFillChat = this.syncMode === AppStatus.sync;
			options.shouldFillCounter = this.syncMode === AppStatus.sync;
			options.shouldFillChannel = (
				this.syncMode === AppStatus.sync
				&& this.isEntityReady(`${ComponentCode.imChannelMessenger}::ready`)
			);

			options.shouldFillCopilot = Feature.isCopilotEnabled
				&& this.syncMode === AppStatus.sync
				&& this.isEntityReady(`${ComponentCode.imCopilotMessenger}::ready`)
			;

			options.shouldFillCollab = Feature.isCollabAvailable
				&& this.syncMode === AppStatus.sync
				&& this.isEntityReady(`${ComponentCode.imCollabMessenger}::ready`)
			;

			return options;
		}

		/**
		 * @param {AppStatus['sync'] || AppStatus['backgroundSync']} mode
		 */
		setSyncMode(mode)
		{
			this.syncMode = mode;
		}

		resetSyncMode()
		{
			this.syncMode = AppStatus.sync;
		}
	}

	module.exports = {
		LoadService,
	};
});
