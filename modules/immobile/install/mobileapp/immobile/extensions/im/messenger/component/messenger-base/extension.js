/**
 * @module im/messenger/component/messenger-base
 */
jn.define('im/messenger/component/messenger-base', async (require, exports, module) => {
	const { RestManager } = require('im/messenger/lib/rest-manager');
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { Logger } = require('im/messenger/lib/logger');
	const {
		ConnectionService,
		SyncService,
		SendingService,
		QueueService,
	} = require('im/messenger/provider/service');
	const { EntityReady } = require('entity-ready');
	const {
		AppStatus,
		MessengerInitRestMethod,
	} = require('im/messenger/const');

	class MessengerBase
	{
		/**
		 * @class Messenger - mobile messenger entry point
		 *
		 * @property {boolean} isReady - flag that the messenger has finished initialization
		 * @property {boolean} isFirstLoad - flag that the messenger is loading for the first time
		 *
		 * @property {Object} store - vuex store
		 * @property {Object} storeManager - vuex store manager
		 *
		 * @property {Recent} recent - recent chat list controller
		 * @property {Dialog} dialog - chat controller
		 * @property {DialogSelector} dialogSelector - chat search controller
		 * @property {ChatCreator} chatCreator - chat creation dialog
		 * @property {RestManager} restManager - collects requests to initialize the messenger into a batch and executes it
		 */
		constructor()
		{
			this.isReady = false;
			this.isFirstLoad = true;
			this.refreshTimeout = null;
			this.refreshErrorNoticeFlag = false;

			/**
			 * @type {CoreApplication}
			 */
			this.core = null;
			this.repository = null;

			/**
			 * @type {MessengerCoreStore}
			 */
			this.store = null;

			/**
			 * @type {MessengerCoreStoreManager}
			 */
			this.storeManager = null;

			/**
			 * @type {RestManager}
			 */
			this.queueRestManager = new RestManager();

			/**
			 * @type {SyncService}
			 */
			this.syncService = null;

			/**
			 * @type {SendingService}
			 */
			this.sendingService = null;
			/**
			 * @type {QueueService}
			 */
			this.queueService = null;

			this.titleParams = {};
			this.appStatus = '';

			this.recent = null;
			this.dialog = null;
			/** @type {RecentSelector || DialogSelector} */
			this.searchSelector = null;
			this.chatCreator = null;
			/** @type {DialogCreator || null} */
			this.dialogCreator = null;
			this.visibilityManager = VisibilityManager.getInstance();

			this.init();
		}

		init()
		{
			this.initCore();
			this.bindMethods();
			this.preloadAssets();
			this.initRequests();

			BX.onViewLoaded(async () => {
				try
				{
					await this.initComponents();
					this.subscribeEvents();
					this.initPullHandlers();
					this.initServices();
					await this.initCurrentUser();
					await this.initQueueRequests();

					this.connectionService.updateStatus();

					EntityReady.wait('im.navigation')
						.then(() => this.executeStoredPullEvents())
						.catch((error) => Logger.error(error))
					;

					this.checkChatV2Support();
					await this.refresh();
				}
				catch (error)
				{
					Logger.error(`${this.constructor.name} init error:`, error);
				}
			});
		}

		initCore()
		{
			Logger.info('MessengerBase.initCore method is not override');
		}

		bindMethods()
		{
			this.onApplicationSetStatus = this.applicationSetStatusHandler.bind(this);
		}

		preloadAssets()
		{
			Logger.info('MessengerBase.preloadAssets method is not override');
		}

		initRequests()
		{
			Logger.info('MessengerBase.initRequests method is not override');
		}

		async initComponents()
		{
			Logger.info('MessengerBase.initComponents method is not override');
		}

		subscribeEvents()
		{
			this.subscribeMessengerEvents();
			this.subscribeExternalEvents();
			this.subscribeStoreEvents();
		}

		subscribeMessengerEvents()
		{
			Logger.info('MessengerBase.subscribeMessengerEvents method is not override');
		}

		subscribeExternalEvents()
		{
			Logger.info('MessengerBase.subscribeExternalEvents method is not override');
		}

		subscribeStoreEvents()
		{
			this.storeManager.on('applicationModel/setStatus', this.onApplicationSetStatus);
		}

		unsubscribeStoreEvents()
		{
			this.storeManager.off('applicationModel/setStatus', this.onApplicationSetStatus);
		}

		unsubscribeExternalEvents()
		{
			Logger.info('MessengerBase.unsubscribeExternalEvents method is not override');
		}

		initPullHandlers()
		{
			Logger.info('MessengerBase.initPullHandlers method is not override');
		}

		initServices()
		{
			this.connectionService = ConnectionService.getInstance();
			this.syncService = SyncService.getInstance();
			this.sendingService = SendingService.getInstance();
			this.queueService = QueueService.getInstance();
			this.initCustomServices();
		}

		/**
		 * @protected
		 */
		initCustomServices()
		{
			Logger.info('MessengerBase.initCustomServices method is not override');
		}

		/**
		 * @abstract
		 */
		async initCurrentUser()
		{
			Logger.info('MessengerBase.initCurrentUser method is not override');
		}

		/**
		 * @abstract
		 */
		async initQueueRequests()
		{
			Logger.info('MessengerBase.initQueueRequests method is not override');
		}

		/**
		 * @abstract
		 */
		executeStoredPullEvents()
		{
			Logger.info('MessengerBase.executeStoredPullEvents method is not override');
		}

		/**
		 * @abstract
		 */
		checkChatV2Support()
		{
			Logger.info('MessengerBase.checkChatV2Support method is not override');
		}

		/**
		 * @abstract
		 */
		async refresh()
		{
			Logger.info('MessengerBase.refresh method is not override');
		}

		applicationSetStatusHandler(mutation)
		{
			const statusKey = mutation.payload.data.status.name;
			const statusValue = mutation.payload.data.status.value;
			const wasAppOffline = this.appStatus === AppStatus.networkWaiting;
			const isAppOnline = (statusKey === AppStatus.networkWaiting && statusValue === false);
			this.buildQueueRequests();

			if (wasAppOffline && isAppOnline)
			{
				Logger.info('Messenger: The device went online from offline.');

				this.refresh();
			}

			this.redrawHeader();
			this.appStatus = this.core.getAppStatus();
		}

		buildQueueRequests()
		{
			const requests = this.store.getters['queueModel/getQueue'];
			if (requests && requests.length > 0)
			{
				const sortedRequests = requests.sort((a, b) => a.priority - b.priority);

				sortedRequests.forEach((req) => {
					this.queueRestManager.once(req.requestName, req.requestData);
				});
			}
		}

		/**
		 * @abstract
		 */
		redrawHeader()
		{
			Logger.info('MessengerBase.redrawHeader method is not override');
		}

		/**
		 * @abstract
		 */
		destructor()
		{
			Logger.info('MessengerBase.destructor method is not override');
		}

		/**
		 * @return {string[]}
		 */
		getBaseInitRestMethods()
		{
			return [
				MessengerInitRestMethod.portalCounters,
				MessengerInitRestMethod.recentList,
				MessengerInitRestMethod.imCounters,
				MessengerInitRestMethod.mobileRevision,
				MessengerInitRestMethod.serverTime,
				MessengerInitRestMethod.desktopStatus,
			];
		}
	}

	module.exports = {
		MessengerBase,
	};
});
