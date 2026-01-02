// eslint-disable-next-line no-var,no-implicit-globals
var REVISION = 21; // API revision - sync with im/lib/revision.php

/* region Environment variables */

// use in immobile/install/mobileapp/immobile/extensions/im/chat/messengercommon/extension.js:532
BX.message.LIMIT_ONLINE = BX.componentParameters.get('LIMIT_ONLINE', 1380);

/* endregion Environment variables */

/* region Clearing session variables after script reload */

if (typeof window.messenger !== 'undefined' && typeof window.messenger.destructor !== 'undefined')
{
	window.messenger.destructor();
}

window.messengerDebug = {};

/* endregion Clearing session variables after script reload */

(async () => {
	/* global dialogList, reloadAllScripts */
	/* region import */
	const require = (ext) => jn.require(ext); // for IDE hints

	/**
	 * @description Import MessengerParams and waiting should be higher than other imports.
	 */
	const { MessengerParams } = require('im/messenger/lib/params');
	await MessengerParams.waitSharedParamsInit();

	const { QuickRecentLoader } = require('im/messenger/lib/quick-recent-load');
	QuickRecentLoader.renderItemsOnViewLoaded(dialogList);

	const { Type } = require('type');
	const { Loc } = require('im/messenger/loc');
	const { isEqual, clone } = require('utils/object');
	const { EntityReady } = require('entity-ready');

	const { Logger } = require('im/messenger/lib/logger');
	const { CollabApplication } = require('im/messenger/core/collab');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const {
		AppStatus,
		EventType,
		RestMethod,
		ComponentCode,
		Analytics,
	} = require('im/messenger/const');

	EntityReady.ready(`${ComponentCode.imCollabMessenger}::launched`);

	const core = new CollabApplication({
		localStorage: {
			enable: true,
			readOnly: true,
		},
	});
	try
	{
		await core.init();
	}
	catch (error)
	{
		Logger.error('CollabApplication init error: ', error);
	}
	serviceLocator.add('core', core);

	const emitter = new JNEventEmitter();
	serviceLocator.add('emitter', emitter);

	const { MessengerInitService } = require('im/messenger/provider/services/messenger-init');
	const collabInitService = new MessengerInitService({
		actionName: RestMethod.immobileTabCollabLoad,
	});
	serviceLocator.add('messenger-init-service', collabInitService);

	const { Feature } = require('im/messenger/lib/feature');

	const {
		CollabMessagePullHandler,
		CollabFilePullHandler,
		CollabDialogPullHandler,
		CollabUserPullHandler,
		CollabInfoPullHandler,
	} = require('im/messenger/provider/pull/collab');
	const { VotePullHandler } = require('im/messenger/provider/pull/vote');

	const { MessengerEmitter } = require('im/messenger/lib/emitter');

	const { CollabRecent } = require('im/messenger/controller/recent/collab');
	const { RecentView } = require('im/messenger/view/recent');
	const { Dialog } = require('im/messenger/controller/dialog/chat');
	const { DialogCreator } = require('im/messenger/controller/dialog-creator');
	const { RecentSelector } = require('im/messenger/controller/search/experimental');
	const { SmileManager } = require('im/messenger/lib/smile-manager');
	const { MessengerBase } = require('im/messenger/component/messenger-base');
	const { SyncFillerCollab } = require('im/messenger/provider/services/sync/fillers/collab');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');
	const { SidebarLazyFactory } = require('im/messenger/controller/sidebar-v2/factory');

	/* endregion import */

	class CollabMessenger extends MessengerBase
	{
		/* region initiation */

		/**
		 * @class CollabMessenger - mobile messenger entry point
		 *
		 * @property {boolean} isReady - flag that the messenger has finished initialization
		 * @property {boolean} isFirstLoad - flag that the messenger is loading for the first time
		 *
		 * @property {Object} store - vuex store
		 * @property {Object} storeManager - vuex store manager
		 *
		 * @property {CollabRecent} recent - recent chat list controller
		 * @property {Dialog} dialog - chat controller
		 * @property {DialogSelector} dialogSelector - chat search controller
		 * @property {RestManager} restManager - collects requests to initialize the messenger into a batch and executes it
		 */
		constructor()
		{
			super();
			EntityReady.addCondition(`${ComponentCode.imCollabMessenger}::ready`, () => this.isReady);
		}

		initCore()
		{
			this.serviceLocator = serviceLocator;

			/**
			 * @type {CoreApplication}
			 */
			this.core = this.serviceLocator.get('core');
			this.repository = this.core.getRepository();

			/**
			 * @type {MessengerCoreStore}
			 */
			this.store = this.core.getStore();

			/**
			 * @type {MessengerInitService}
			 */
			this.collabInitService = this.serviceLocator.get('messenger-init-service');

			/**
			 * @type {MessengerCoreStoreManager}
			 */
			this.storeManager = this.core.getStoreManager();
		}

		initRequests()
		{
			this.collabInitService.onInit(this.checkRevision.bind(this));
		}

		/**
		 * @override
		 */
		async initComponents()
		{
			this.recent = new CollabRecent({
				view: new RecentView({
					ui: dialogList,
					style: {
						showLoader: true,
					},
				}),
			});

			await this.recent.init().catch((error) => {
				Logger.error('CollabRecent.init error: ', error);
			});

			this.searchSelector = new RecentSelector(dialogList);
			this.dialogCreator = new DialogCreator();
		}

		/**
		 * @override
		 */
		bindMethods()
		{
			super.bindMethods();
			this.openDialog = this.openDialog.bind(this);
			this.openChatSearch = this.openChatSearch.bind(this);
			this.closeChatSearch = this.closeChatSearch.bind(this);
			this.openChatCreate = this.openChatCreate.bind(this);
			this.refresh = this.refresh.bind(this);

			this.onChatDialogCounterChange = this.onChatDialogCounterChange.bind(this);
			this.onChatDialogAccessError = this.onChatDialogAccessError.bind(this);
			this.onTaskStatusSuccess = this.onTaskStatusSuccess.bind(this);
			this.onNotificationReload = this.onNotificationReload.bind(this);
			this.onAppActiveBefore = this.onAppActiveBefore.bind(this);
			this.onChatSettingChange = this.onChatSettingChange.bind(this);
			this.onAppStatusChange = this.onAppStatusChange.bind(this);
			this.updatePlanLimitsData = this.updatePlanLimitsData.bind(this);
		}

		/**
		 * @override
		 */
		preloadAssets()
		{
			SidebarLazyFactory.preload();
		}

		/**
		 * @override
		 */
		subscribeMessengerEvents()
		{
			super.subscribeMessengerEvents();

			BX.addCustomEvent(EventType.messenger.updatePlanLimitsData, this.updatePlanLimitsData);
		}

		unsubscribeMessengerEvents()
		{
			super.unsubscribeMessengerEvents();

			BX.removeCustomEvent(EventType.messenger.updatePlanLimitsData, this.updatePlanLimitsData);
		}

		/**
		 * @override
		 */
		subscribeExternalEvents()
		{
			BX.addCustomEvent(EventType.chatDialog.counterChange, this.onChatDialogCounterChange);
			BX.addCustomEvent(EventType.chatDialog.accessError, this.onChatDialogAccessError);
			BX.addCustomEvent(EventType.chatDialog.taskStatusSuccess, this.onTaskStatusSuccess);
			BX.addCustomEvent(EventType.notification.reload, this.onNotificationReload);
			BX.addCustomEvent(EventType.app.activeBefore, this.onAppActiveBefore);
			BX.addCustomEvent(EventType.app.failRestoreConnection, this.refresh);
			BX.addCustomEvent(EventType.setting.chat.change, this.onChatSettingChange);
			BX.addCustomEvent(EventType.app.changeStatus, this.onAppStatusChange);
		}

		/**
		 * @override
		 */
		unsubscribeExternalEvents()
		{
			BX.removeCustomEvent(EventType.chatDialog.counterChange, this.onChatDialogCounterChange);
			BX.removeCustomEvent(EventType.chatDialog.accessError, this.onChatDialogAccessError);
			BX.removeCustomEvent(EventType.chatDialog.taskStatusSuccess, this.onTaskStatusSuccess);
			BX.removeCustomEvent(EventType.notification.reload, this.onNotificationReload);
			BX.removeCustomEvent(EventType.app.activeBefore, this.onAppActiveBefore);
			BX.removeCustomEvent(EventType.app.failRestoreConnection, this.refresh);
			BX.removeCustomEvent(EventType.app.changeStatus, this.onAppStatusChange);
			BX.removeCustomEvent(EventType.setting.chat.change, this.onChatSettingChange);
		}

		/**
		 * @override
		 */
		initCustomServices()
		{
			this.syncFillerService = new SyncFillerCollab();
		}

		/**
		 * @override
		 */
		redrawHeader()
		{
			let headerTitle = '';
			let useProgress = '';

			const appStatus = this.core.getAppStatus();
			switch (appStatus)
			{
				case AppStatus.networkWaiting:
					headerTitle = Loc.getMessage('IMMOBILE_COMMON_MESSENGER_HEADER_NETWORK_WAITING');
					useProgress = true;
					break;

				case AppStatus.connection:
					headerTitle = Loc.getMessage('IMMOBILE_COMMON_MESSENGER_HEADER_CONNECTION');
					useProgress = true;
					break;

				case AppStatus.sync:
					headerTitle = Loc.getMessage('IMMOBILE_COMMON_MESSENGER_HEADER_SYNC');
					useProgress = true;
					break;

				default:
					headerTitle = Loc.getMessage('IMMOBILE_MESSENGER_COMMON_TITLE');
					useProgress = false;
					break;
			}

			const actualTitleParams = {
				text: headerTitle,
				useProgress,
				largeMode: true,
			};

			if (isEqual(this.titleParams, actualTitleParams))
			{
				return;
			}

			this.titleParams = actualTitleParams;
			dialogList.setTitle(this.titleParams);
		}

		/**
		 * @override
		 */
		initPullHandlers()
		{
			super.initPullHandlers();
			BX.PULL.subscribe(new CollabDialogPullHandler());
			BX.PULL.subscribe(new CollabMessagePullHandler());
			BX.PULL.subscribe(new CollabFilePullHandler());
			BX.PULL.subscribe(new CollabUserPullHandler());
			BX.PULL.subscribe(new CollabInfoPullHandler());
			BX.PULL.subscribe(new VotePullHandler());
		}

		/**
		 * @override
		 */
		async initCurrentUser()
		{
			const currentUser = await this.core.getRepository().user.userTable.getById(this.core.getUserId());
			if (currentUser)
			{
				await this.store.dispatch('usersModel/setFromLocalDatabase', [currentUser]);
			}
		}

		onAppActiveBefore()
		{
			BX.onViewLoaded(() => {
				if (!Feature.isLocalStorageEnabled)
				{
					MessengerEmitter.emit(EventType.dialog.external.disableScrollToBottom);
				}

				void this.refresh({ shortMode: true })
					.finally(() => {
						MessengerEmitter.emit(EventType.dialog.external.scrollToFirstUnread);
					})
				;
			});
		}

		/**
		 * @param {object} params
		 * @param {boolean} params.shortMode
		 * @override
		 */
		async refresh(params = {})
		{
			await EntityReady.wait('im.navigation');

			const { shortMode = false } = params;

			await this.core.setAppStatus(AppStatus.connection, true);
			this.smileManager = SmileManager.getInstance();
			await SmileManager.init();

			return this.collabInitService.runAction(this.getInitRestMethods(shortMode))
				.then(() => {
					this.afterRefresh();
				})
				.catch((response) => {
					this.afterRefreshError(response);
				})
			;
		}

		async afterRefresh()
		{
			await this.core.setAppStatus(AppStatus.connection, false);
			this.refreshErrorNoticeFlag = false;
			this.notifyRefreshErrorWorker.stop();

			serviceLocator.get('tab-counters').update();

			return this.ready();
		}

		async afterRefreshError(response)
		{
			const firstErrorKey = Object.keys(response)[0];
			if (firstErrorKey)
			{
				const firstError = response[firstErrorKey].error();
				if (firstError.ex.error === 'REQUEST_CANCELED')
				{
					Logger.error(`${this.constructor.name}.afterRefreshError`, firstError.ex);

					return;
				}
			}

			const secondsBeforeRefresh = this.refreshAfterErrorInterval / 1000;
			Logger.error(`CollabMessenger: refresh error. Try again in ${secondsBeforeRefresh} seconds.`);

			clearTimeout(this.refreshTimeout);

			this.refreshTimeout = setTimeout(() => {
				if (!this.refreshErrorNoticeFlag && !Application.isBackground())
				{
					this.notifyRefreshErrorWorker.startOnce();
				}

				Logger.warn(`${this.constructor.name}.refresh after error`);
				this.refresh();
			}, this.refreshAfterErrorInterval);
		}

		async ready()
		{
			this.isReady = true;

			if (this.isFirstLoad)
			{
				Logger.warn(`${this.constructor.name}.ready`);
				EntityReady.ready(`${ComponentCode.imCollabMessenger}::ready`);
			}

			this.isFirstLoad = false;

			return this.core.setAppStatus(AppStatus.running, true)
				.then(() => {
					MessengerEmitter.emit(EventType.messenger.afterRefreshSuccess);
				})
				.catch((error) => {
					Logger.error(error);
				})
			;
		}

		/* endregion initiation */

		/* region event handlers */

		openDialog(options = {})
		{
			Logger.info(`${this.constructor.name}.openDialog`, options);
			const openDialogOptions = options;
			openDialogOptions.isNew = Type.isBoolean(options.isNew) ? options.isNew : false;
			if (openDialogOptions.dialogId)
			{
				openDialogOptions.dialogId = openDialogOptions.dialogId.toString();
			}

			PageManager.getNavigator().makeTabActive();
			this.visibilityManager.checkIsDialogVisible({
				dialogId: openDialogOptions.dialogId,
				currentContextOnly: true,
			})
				.then((isVisible) => {
					if (isVisible)
					{
						return;
					}

					this.dialog = new Dialog();
					this.dialog.open(openDialogOptions);

					this.recent.view.renderChatCreateButton();

					if (openDialogOptions.backToMessenegerTab)
					{
						MessengerEmitter.emit(
							EventType.navigation.changeTab,
							ComponentCode.imMessenger,
							ComponentCode.imNavigation,
						);
					}
				})
				.catch((error) => {
					Logger.error(error);
				})
			;
		}

		openChatSearch()
		{
			Logger.log(`${this.constructor.name}.showSearch`);

			this.searchSelector.open();
		}

		closeChatSearch()
		{
			Logger.log(`${this.constructor.name}.hideSearch`);

			this.searchSelector.close();
		}

		async openChatCreate()
		{
			Logger.log(`${this.constructor.name}.createChat`);

			if (this.dialogCreator !== null)
			{
				await this.dialogCreator.createCollab();

				AnalyticsService.getInstance().sendStartCreation({
					section: Analytics.Section.collabTab,
					category: Analytics.Category.collab,
					type: Analytics.Type.collab,
				});
			}
		}

		onNotificationReload()
		{
			Logger.log(`${this.constructor.name}.notification.reload`);

			BX.postWebEvent('onBeforeNotificationsReload', {});
			Application.refreshNotifications();
		}

		onTaskStatusSuccess(taskId, result)
		{
			Logger.log(`${this.constructor.name}.chatDialog.taskStatusSuccess`, taskId, result);
		}

		/**
		 * @private
		 * @param {{id: string, value: any}} setting
		 */
		onChatSettingChange(setting)
		{
			Logger.log(`${this.constructor.name}.Setting.Chat.Change:`, setting);
		}

		/* region legacy dialog integration */

		onChatDialogCounterChange(event)
		{
			Logger.log(`${this.constructor.name}.chatDialog.counterChange`, event);

			const recentItem = clone(this.store.getters['recentModel/getById'](event.dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.counter = event.counter;
			const dialogItem = clone(this.store.getters['dialoguesModel/getById'](event.dialogId));
			if (dialogItem)
			{
				this.store.dispatch('dialoguesModel/update', {
					dialogId: event.dialogId,
					fields: {
						counter: event.counter,
					},
				});
			}

			this.store.dispatch('recentModel/set', [recentItem]);
		}

		onChatDialogAccessError()
		{
			Logger.warn(`${this.constructor.name}.chatDialog.accessError`);

			InAppNotifier.showNotification({
				title: Loc.getMessage('IMMOBILE_COMMON_MESSENGER_DIALOG_ACCESS_ERROR_TITLE'),
				message: Loc.getMessage('IMMOBILE_COMMON_MESSENGER_DIALOG_ACCESS_ERROR_TEXT'),
				backgroundColor: '#E6000000',
				time: 3,
			});

			this.dialog.deleteCurrentDialog();
		}

		/**
		 * @param {PlanLimits} planLimits
		 * @void
		 */
		updatePlanLimitsData(planLimits)
		{
			Logger.log(`${this.constructor.name}.updatePlanLimitsData`, planLimits);
			MessengerParams.setPlanLimits(planLimits);
		}

		/* endregion legacy dialog integration */

		/* endregion event handlers */

		/**
		 * @param {immobileTabChatLoadResult} data
		 */
		checkRevision(data)
		{
			const revision = data?.mobileRevision;

			if (!Type.isNumber(revision) || REVISION >= revision)
			{
				Logger.log(`${this.constructor.name}.checkRevision: current`, REVISION, 'actual', revision);

				return true;
			}

			Logger.warn(
				`${this.constructor.name}.checkRevision: reload scripts because revision up`,
				REVISION,
				' -> ',
				revision,
			);

			// reloadAllScripts();

			return false;
		}

		destructor()
		{
			this.unsubscribeStoreEvents();
			this.unsubscribeMessengerEvents();
			this.unsubscribeExternalEvents();

			if (this.connectionService)
			{
				this.connectionService.destructor();
			}

			BX.listeners = {};
			Logger.warn('CollabMessenger: Garbage collection after refresh complete');
		}

		executeStoredPullEvents()
		{}

		/**
		 * @param {{name: string, value: string}} event
		 */
		onAppStatusChange(event)
		{
			core.setAppStatus(event.name, event.value);
		}

		async initQueueRequests()
		{
			return Promise.resolve();
		}
	}

	window.messenger = new CollabMessenger();
})().catch((error) => {
	console.error('Messenger init error', error);
});
