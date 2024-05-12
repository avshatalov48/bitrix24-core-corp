/**
 * @module im/messenger/provider/service/classes/sync/load
 */
jn.define('im/messenger/provider/service/classes/sync/load', (require, exports, module) => {
	const { Type } = require('type');
	const { Uuid } = require('utils/uuid');
	const { isEqual } = require('utils/object');
	const { EntityReady } = require('entity-ready');

	const { RestMethod, ComponentCode, EventType } = require('im/messenger/const');
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
		 * @return Promise{{hasMore: boolean, lastId: number, lastServerDate: string}}
		 */
		async handleSyncList(result)
		{
			logger.info('RestMethod.imV2SyncList result: ', result);

			let resolveSyncListPromise;
			let rejectSyncListPromise;
			const syncListPromise = new Promise((resolve, reject) => {
				resolveSyncListPromise = resolve;
				rejectSyncListPromise = reject;
			});

			const messengerRequestResultSavedUuid = `${ComponentCode.imMessenger}-${Uuid.getV4()}`;
			const expectedRequestResultSavedIdList = [
				messengerRequestResultSavedUuid,
			];

			const copilotRequestResultSavedUuid = `${ComponentCode.imCopilotMessenger}-${Uuid.getV4()}`;
			const shouldAwaitCopilot = Feature.isCopilotAvailable && this.isEntityReady('copilot-messenger');
			if (shouldAwaitCopilot)
			{
				expectedRequestResultSavedIdList.push(copilotRequestResultSavedUuid);
			}

			const requestResultSavedIdList = [];
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
					BX.removeCustomEvent(EventType.sync.requestResultSaved, fillCompleteHandler);

					resolveSyncListPromise({
						hasMore: result.hasMore,
						lastId: result.lastId,
						lastServerDate: result.lastServerDate,
					});
				}
			};

			BX.addCustomEvent(EventType.sync.requestResultSaved, fillCompleteHandler);

			MessengerEmitter.emit(EventType.sync.requestResultReceived, {
				uuid: messengerRequestResultSavedUuid,
				result,
			}, ComponentCode.imMessenger);

			if (shouldAwaitCopilot)
			{
				MessengerEmitter.emit(EventType.sync.requestResultReceived, {
					uuid: copilotRequestResultSavedUuid,
					result,
				}, ComponentCode.imCopilotMessenger);
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
	}

	module.exports = {
		LoadService,
	};
});
