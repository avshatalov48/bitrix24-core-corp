/* global InAppNotifier */

/**
 * @module im/messenger/component/messenger-base
 */
jn.define('im/messenger/component/messenger-base', async (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');

	const { Worker } = require('im/messenger/lib/helper');
	const { EventType } = require('im/messenger/const');
	const { RestManager } = require('im/messenger/lib/rest-manager');
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { Logger } = require('im/messenger/lib/logger');
	const { QueueService } = require('im/messenger/provider/services/queue');
	const { ConnectionService } = require('im/messenger/provider/services/connection');
	const { SendingService } = require('im/messenger/provider/services/sending');
	const { SyncService } = require('im/messenger/provider/services/sync/service');
	const { EntityReady } = require('entity-ready');
	const {
		AppStatus,
		MessengerInitRestMethod,
	} = require('im/messenger/const');
	const { CallManager } = require('im/messenger/lib/integration/callmobile/call-manager');
	const { AnchorPullHandler } = require('im/messenger/provider/pull/anchor');
	const { Anchors } = require('im/messenger/lib/anchors');
	const { MessengerCounterHandler } = require('im/messenger/lib/counters/counter-manager/messenger/handler');
	const { Feature } = require('im/messenger/lib/feature');
	const { createTabCountersByComponent } = require('im/messenger/lib/counters/tab-counters');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const REFRESH_AFTER_ERROR_INTERVAL = 10000;

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
		 * @property {RestManager} restManager - collects requests to initialize the messenger into a batch and executes it
		 */
		constructor()
		{
			this.isReady = false;
			this.isFirstLoad = true;
			this.refreshTimeout = null;
			this.refreshErrorNoticeFlag = false;
			this.refreshAfterErrorInterval = REFRESH_AFTER_ERROR_INTERVAL;

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
			/** @type {DialogCreator || null} */
			this.dialogCreator = null;
			this.tabCounters = createTabCountersByComponent();
			serviceLocator.add('tab-counters', this.tabCounters);
			this.visibilityManager = VisibilityManager.getInstance();

			this.callManager = CallManager.getInstance();

			this.counterClientHandler = MessengerCounterHandler.getInstance();

			this.initPromise = this.init();

			this.initPromise.catch((error) => {
				Logger.error(`${this.constructor.name} init error`, error);
			});

			this.anchors = new Anchors();
		}

		async init()
		{
			this.initCore();
			this.bindMethods();
			this.preloadAssets();
			this.initRequests();
			this.initPushMessageHandlers();
			this.initNotifyRefreshErrorWorker();
			await this.fillDataBaseFromPush();

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

					await EntityReady.wait('im.navigation');
					this.executeStoredPullEvents();

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
			this.onShortRefresh = this.onShortRefresh.bind(this);
			this.openDialog = this.openDialog.bind(this);
			this.openChatCreate = this.openChatCreate.bind(this);
			this.openChatSearch = this.openChatSearch.bind(this);
			this.closeChatSearch = this.closeChatSearch.bind(this);
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
			BX.addCustomEvent(EventType.messenger.refresh, this.onShortRefresh);
			BX.addCustomEvent(EventType.messenger.openDialog, this.openDialog);
			BX.addCustomEvent(EventType.messenger.createChat, this.openChatCreate);
			BX.addCustomEvent(EventType.messenger.showSearch, this.openChatSearch);
			BX.addCustomEvent(EventType.messenger.hideSearch, this.closeChatSearch);
		}

		unsubscribeMessengerEvents()
		{
			BX.removeCustomEvent(EventType.messenger.refresh, this.onShortRefresh);
			BX.removeCustomEvent(EventType.messenger.openDialog, this.openDialog);
			BX.removeCustomEvent(EventType.messenger.createChat, this.openChatCreate);
			BX.removeCustomEvent(EventType.messenger.showSearch, this.openChatSearch);
			BX.removeCustomEvent(EventType.messenger.hideSearch, this.closeChatSearch);
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

		initPushMessageHandlers()
		{
			Logger.info('MessengerBase.initPushMessageHandlers method is not override');
		}

		initNotifyRefreshErrorWorker()
		{
			const notifyRefreshError = () => {
				this.refreshErrorNoticeFlag = true;
				const refreshErrorNotificationTime = this.refreshAfterErrorInterval / 1000 - 2;

				InAppNotifier.showNotification({
					message: Loc.getMessage('IMMOBILE_COMMON_MESSENGER_REFRESH_ERROR'),
					backgroundColor: '#E6000000',
					time: refreshErrorNotificationTime,
				});
			};

			this.notifyRefreshErrorWorker = new Worker({
				frequency: 2000,
				callback: notifyRefreshError.bind(this),
			});
		}

		initPullHandlers()
		{
			BX.PULL.subscribe(new AnchorPullHandler());
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

		async fillDataBaseFromPush()
		{
			Logger.info('MessengerBase.fillDataBaseFromPush method is not override');
		}

		/**
		 * @abstract
		 * @param {object} params
		 * @param {boolean} params.shortMode
		 */
		async refresh(params = {})
		{
			Logger.info('MessengerBase.refresh method is not override');
		}

		onShortRefresh()
		{
			Logger.log('EventType.messenger.refresh');

			this.refresh({ shortMode: true });
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
		getInitRestMethodsForRefresh()
		{
			if (Feature.isChatBetaEnabled)
			{
				return [
					MessengerInitRestMethod.imCounters,
					MessengerInitRestMethod.anchors,
				];
			}

			return [
				MessengerInitRestMethod.imCounters,
				MessengerInitRestMethod.anchors,
				MessengerInitRestMethod.recentList,
			];
		}

		/**
		 * @return {string[]}
		 */
		getInitRestMethodsForApplicationStartup()
		{
			return [
				MessengerInitRestMethod.imCounters,
				MessengerInitRestMethod.anchors,
				MessengerInitRestMethod.recentList,
			];
		}

		/**
		 * @param {boolean} shortMode
		 * @return {string[]}
		 */
		getInitRestMethods(shortMode)
		{
			if (shortMode && Feature.isLocalStorageEnabled)
			{
				return this.getInitRestMethodsForRefresh();
			}

			return this.getInitRestMethodsForApplicationStartup();
		}
	}

	module.exports = {
		MessengerBase,
	};
});
