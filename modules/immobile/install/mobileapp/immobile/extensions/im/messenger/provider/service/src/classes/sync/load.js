/**
 * @module im/messenger/provider/service/classes/sync/load
 */
jn.define('im/messenger/provider/service/classes/sync/load', (require, exports, module) => {
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
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sync-service');

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
			/** @type {AppStatus['sync'] | AppStatus['backgroundSync']} */
			this.syncMode = AppStatus.sync;
		}

		get emitter()
		{
			return serviceLocator.get('emitter');
		}

		/**
		 * @param fromDate
		 * @param fromId
		 * @param fromServerDate
		 * @return {Promise<Partial<SyncLoadServiceLoadPageResult>>}
		 */
		async loadPage({ fromDate, fromId, fromServerDate })
		{
			const syncListOptions = {
				filter: {},
				limit: 500,
			};

			if (Type.isStringFilled(fromServerDate))
			{
				syncListOptions.filter.lastDate = fromServerDate;
			}
			else if (Type.isNumber(fromId))
			{
				syncListOptions.filter.lastId = fromId;
			}
			else
			{
				syncListOptions.filter.lastDate = fromDate;
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

			const requestResultSavedIdList = [];
			const noResponseCheckTimeout = setTimeout(() => {
				if (!(isEqual(expectedRequestResultSavedIdList.sort(), requestResultSavedIdList.sort())))
				{
					const noResponseIdList = expectedRequestResultSavedIdList
						.filter((id) => !requestResultSavedIdList.includes(id))
					;
					logger.warn('SyncService: no response from ', noResponseIdList, 'in 5 seconds');
				}
			}, 5000);
			const fillCompleteHandler = (data) => {
				const {
					uuid,
					error,
				} = data;
				logger.log('SyncService received a response from SyncFiller', uuid, data);

				requestResultSavedIdList.push(uuid);
				if (error)
				{
					rejectSyncListPromise(error);

					return;
				}

				if (isEqual(expectedRequestResultSavedIdList.sort(), requestResultSavedIdList.sort()))
				{
					clearTimeout(noResponseCheckTimeout);
					BX.removeCustomEvent(EventType.sync.requestResultSaved, fillCompleteHandler);

					resolveSyncListPromise({
						hasMore: result.hasMore,
						lastId: result.lastId,
						lastServerDate: result.lastServerDate,
						addedMessageIdList: this.getAddedMessageIdList(result),
						deletedChatIdList: this.getDeletedChatIdList(result),
						deletedMessageIdList: this.getDeletedMessageIdList(result),
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

			logger.log('SyncService waits for a response from SyncFillers', expectedRequestResultSavedIdList);

			return syncListPromise;
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
			};

			options.shouldFillDatabase = Feature.isLocalStorageEnabled;

			options.shouldFillChat = this.syncMode === AppStatus.sync;
			options.shouldFillChannel = (
				this.syncMode === AppStatus.sync
				&& this.isEntityReady('channel-messenger')
			);

			options.shouldFillCopilot = Feature.isCopilotEnabled
				&& this.syncMode === AppStatus.sync
				&& this.isEntityReady('copilot-messenger')
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

		/**
		 * @param {SyncListResult} result
		 * @return {Array<number>}
		 */
		getAddedMessageIdList(result)
		{
			if (!Type.isArrayFilled(result.messages.messages))
			{
				return [];
			}

			return result.messages.messages.map((message) => message.id);
		}

		/**
		 * @param {SyncListResult} result
		 * @return {Array<number>}
		 */
		getDeletedChatIdList(result)
		{
			if (!Type.isPlainObject(result.completeDeletedChats))
			{
				return [];
			}

			return Object.values(result.completeDeletedChats);
		}

		/**
		 * @param {SyncListResult} result
		 * @return {Array<number>}
		 */
		getDeletedMessageIdList(result)
		{
			if (!Type.isPlainObject(result.completeDeletedMessages))
			{
				return [];
			}

			return Object.values(result.completeDeletedMessages);
		}
	}

	module.exports = {
		LoadService,
	};
});
