/**
 * @module im/messenger-v2/application/messenger
 */
jn.define('im/messenger-v2/application/messenger', (require, exports, module) => {
	const { getLoggerWithContext } = require('im/messenger/lib/logger');

	const { RestMethod } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerInitService } = require('im/messenger/provider/services/messenger-init');
	const { ChatAssets } = require('im/messenger/controller/dialog/lib/assets');
	const { SidebarLazyFactory } = require('im/messenger/controller/sidebar-v2/factory');
	const { QueueService } = require('im/messenger/provider/services/queue');
	const { ConnectionService } = require('im/messenger/provider/services/connection');
	const { SendingService } = require('im/messenger/provider/services/sending');
	const { SyncService } = require('im/messenger-v2/provider/services/sync');
	const { ReadMessageService } = require('im/messenger/provider/services/read');
	const { CallManager } = require('im/messenger/lib/integration/callmobile/call-manager');
	const { Communication } = require('im/messenger/lib/integration/mobile/communication');
	const { Promotion } = require('im/messenger/lib/promotion');
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { Anchors } = require('im/messenger/lib/anchors');

	const { MessengerCore } = require('im/messenger-v2/core/messenger');
	const { MessengerHeaderController } = require('im/messenger-v2/controller/messenger-header');
	const { NavigationController, NavigationApiHandler } = require('im/messenger-v2/controller/navigation');
	const { PullHandlerLauncher } = require('im/messenger-v2/application/lib/pull-handler-launcher');
	const { RevisionChecker } = require('im/messenger-v2/application/lib/revision-checker');
	const { waitViewLoaded } = require('im/messenger-v2/lib/wait-view-loaded');
	const { Refresher } = require('im/messenger-v2/application/lib/refresher');
	const { PlanLimitsUpdater } = require('im/messenger-v2/application/lib/plan-limits-updater');
	const { DialogManager } = require('im/messenger-v2/application/lib/dialog-manager');
	const { PushManager } = require('im/messenger-v2/application/lib/push-manager');
	const { StoreEventHandler } = require('im/messenger-v2/application/lib/event-handler/store');
	const { ExternalEventHandler } = require('im/messenger-v2/application/lib/event-handler/external');
	const { MessengerEventHandler } = require('im/messenger-v2/application/lib/event-handler/messenger');
	const { ChannelPullWatchManager } = require('im/messenger-v2/application/lib/channel-pull-watch-manager');
	const { enableMessengerApi } = require('im/messenger-v2/application/lib/api-enabler');
	const { RecentManager } = require('im/messenger-v2/controller/recent/manager');
	const { DialogCreator } = require('im/messenger/controller/dialog-creator');
	const { MessengerCounters } = require('im/messenger/lib/counters/tab-counters/messenger');
	const { showUpdateAppScreenIfNeeded } = require('im/messenger-v2/application/lib/update-notifier');

	const mobileRevision = 21; // sync with im/lib/revision.php. TODO: move value to some config?

	/**
	 * @class Messenger
	 */
	class Messenger
	{
		constructor()
		{
			this.logger = getLoggerWithContext('messenger-v2--application', this);
			this.logger.log('constructor');
		}

		destructor()
		{
			this.logger.log('destructor');

			this.pullHandlerLauncher?.unsubscribeEvents();
			this.unsubscribeEvents();
			this.pushManager?.destructor();
			this.promotion?.destruct();
			this.connectionService?.destructor();
			BX.listeners = {};

			this.logger.warn('Messenger: Garbage collection after refresh complete');
		}

		async init()
		{
			this.logger.log('init');

			await this.initBeforeViewLoaded();
			await waitViewLoaded();
			await this.initAfterViewLoaded();

			await this.refresher.refreshOnStartup();

			this.logger.log('init complete');
		}

		async initBeforeViewLoaded()
		{
			this.logger.log('initBeforeViewLoaded');

			try
			{
				await this.initCore();
				this.initEmitter();
				this.initPushManager();
				await this.pushManager.fillDatabaseFromPush();
				this.initServices();
				this.initNavigationApiHandler();
				this.initRevisionChecker();
				this.initPlanLimitsUpdater();
				this.initRefresher();
				await this.initCurrentUser();
				await enableMessengerApi();
			}
			catch (error)
			{
				this.logger.error('initBeforeViewLoaded error:', error);
			}

			this.logger.log('initBeforeViewLoaded complete');
		}

		async initAfterViewLoaded()
		{
			this.logger.log('initAfterViewLoaded');

			try
			{
				await this.initComponents();
				this.subscribeEvents();
				this.initPullHandlers();
				this.connectionService.updateStatus();
				void this.pushManager.executeStoredPullEvents();
				this.initManagers();
				this.preloadAssets();
				showUpdateAppScreenIfNeeded();
			}
			catch (error)
			{
				this.logger.error('initAfterViewLoaded error:', error);
			}

			this.logger.log('initAfterViewLoaded complete');
		}

		async initCore()
		{
			this.serviceLocator = serviceLocator;

			/**
			 * @type {CoreApplication}
			 */
			this.core = new MessengerCore({
				localStorage: {
					enable: true,
					readOnly: false,
				},
			});

			try
			{
				await this.core.init();
			}
			catch (error)
			{
				this.logger.error('initCore error: ', error);

				throw error;
			}
			serviceLocator.add('core', this.core);

			this.repository = this.core.getRepository();

			/**
			 * @type {MessengerCoreStore}
			 */
			this.store = this.core.getStore();

			/**
			 * @type {MessengerCoreStoreManager}
			 */
			this.storeManager = this.core.getStoreManager();
		}

		initEmitter()
		{
			const emitter = new JNEventEmitter();
			serviceLocator.add('emitter', emitter);
		}

		initServices()
		{
			this.chatInitService = new MessengerInitService({ actionName: RestMethod.immobileMessengerLoad });
			serviceLocator.add('messenger-init-service', this.chatInitService);

			this.tabCounters = new MessengerCounters();
			serviceLocator.add('tab-counters', this.tabCounters);

			this.connectionService = ConnectionService.getInstance();
			serviceLocator.add('connection-service', this.connectionService);

			this.syncService = SyncService.getInstance();
			serviceLocator.add('sync-service', this.syncService);

			this.sendingService = SendingService.getInstance();
			serviceLocator.add('sending-service', this.sendingService);

			this.queueService = QueueService.getInstance();
			serviceLocator.add('queue-service', this.queueService);

			this.readMessageService = new ReadMessageService();
			serviceLocator.add('read-service', this.readMessageService);

			this.channelPullWatchManager = ChannelPullWatchManager.getInstance();
			serviceLocator.add('channel-pull-watch-manager', this.channelPullWatchManager);

			this.recentManager = RecentManager.getInstance();
			serviceLocator.add('recent-manager', this.recentManager);
		}

		initNavigationApiHandler()
		{
			this.navigationApiHandler = NavigationApiHandler.getInstance();
		}

		initRevisionChecker()
		{
			this.revisionChecker = new RevisionChecker(mobileRevision);
			this.revisionChecker.subscribeInitMessengerEvent();
		}

		initPlanLimitsUpdater()
		{
			this.planLimitsUpdater = new PlanLimitsUpdater();
			this.planLimitsUpdater.subscribeInitMessengerEvent();
		}

		initRefresher()
		{
			this.refresher = Refresher.getInstance();
			serviceLocator.add('refresher', this.refresher);
		}

		initPushManager()
		{
			this.pushManager = new PushManager();

			serviceLocator.add('push-manager', this.pushManager);
		}

		async initCurrentUser()
		{
			const currentUser = await this.core.getRepository().user.userTable.getById(this.core.getUserId());
			if (currentUser)
			{
				await this.store.dispatch('usersModel/setFromLocalDatabase', [currentUser]);
			}
		}

		initManagers()
		{
			this.visibilityManager = VisibilityManager.getInstance();
			this.callManager = CallManager.getInstance();
			this.callManager.subscribeMessengerInitEvent();

			this.promotion = new Promotion();
			this.communication = new Communication();
			this.anchors = new Anchors();

			this.dialogManager = DialogManager.getInstance();
			serviceLocator.add('dialog-manager', this.dialogManager);
		}

		preloadAssets()
		{
			(new ChatAssets()).preloadAssets();
			SidebarLazyFactory.preload();
		}

		async initComponents()
		{
			this.headerController = MessengerHeaderController.getInstance();
			serviceLocator.add('messenger-header-controller', this.headerController);

			this.navigationController = NavigationController.getInstance();
			serviceLocator.add('navigation-controller', this.navigationController);

			this.dialogCreator = new DialogCreator();
			serviceLocator.add('dialog-creator', this.dialogCreator);

			const currentTabId = await this.navigationController.getActiveTab();
			this.headerController.redrawRightButtonsIfNeeded(currentTabId);
		}

		initPullHandlers()
		{
			this.pullHandlerLauncher = PullHandlerLauncher.getInstance();
			this.pullHandlerLauncher.subscribeEvents();
		}

		subscribeEvents()
		{
			this.storeEventHandler = StoreEventHandler.getInstance();
			this.messengerEventHandler = MessengerEventHandler.getInstance();
			this.externalEventHandler = ExternalEventHandler.getInstance();

			this.storeEventHandler.subscribeEvents();
			this.messengerEventHandler.subscribeEvents();
			this.externalEventHandler.subscribeEvents();
		}

		unsubscribeEvents()
		{
			this.storeEventHandler?.unsubscribeEvents();
			this.messengerEventHandler?.unsubscribeEvents();
			this.externalEventHandler?.unsubscribeEvents();
		}

		/**
		 * @description dialog object reference for debugging purposes only.
		 * @private
		 * @return {Dialog|null}
		 */
		get dialog()
		{
			return serviceLocator.get('dialog-manager')?.getLastOpenDialog() ?? null;
		}
	}

	module.exports = { Messenger };
});
