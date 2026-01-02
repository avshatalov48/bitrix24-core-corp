// eslint-disable-next-line no-var
var REVISION = 21; // API revision – sync with im/lib/revision.php

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
	const { get, isEqual, isEmpty, clone } = require('utils/object');
	const { Feature: MobileFeature } = require('feature');
	const { EntityReady } = require('entity-ready');

	const { Logger } = require('im/messenger/lib/logger');
	const { ChatApplication } = require('im/messenger/core/chat');
	const { returnToAudioMessage } = require('im/messenger/controller/dialog/lib/audio-panel');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerInitService } = require('im/messenger/provider/services/messenger-init');

	const {
		AppStatus,
		EventType,
		RestMethod,
		ComponentCode,
		OpenRequest,
		MessengerInitRestMethod,
		NavigationTabByComponent,
		DialogType,
		DialogActionType,
	} = require('im/messenger/const');
	const { AfterScrollMessagePosition } = require('im/messenger/view/dialog');

	EntityReady.ready(`${ComponentCode.imMessenger}::launched`);

	const core = new ChatApplication({
		localStorage: {
			enable: true,
			readOnly: false,
		},
	});
	try
	{
		await core.init();
	}
	catch (error)
	{
		Logger.error('ChatApplication init error: ', error);
	}
	serviceLocator.add('core', core);

	const emitter = new JNEventEmitter();
	serviceLocator.add('emitter', emitter);

	const chatInitService = new MessengerInitService({
		actionName: RestMethod.immobileTabChatLoad,
	});
	serviceLocator.add('messenger-init-service', chatInitService);

	const { showNotificationList } = require('im/messenger/api/notifications-opener');
	const { Feature } = require('im/messenger/lib/feature');

	const {
		ChatApplicationPullHandler,
		ChatMessagePullHandler,
		ChatFilePullHandler,
		ChatDialogPullHandler,
		ChatUserPullHandler,
		ChatRecentPullHandler,
		NotificationPullHandler,
		OnlinePullHandler,
	} = require('im/messenger/provider/pull/chat');
	const { CollabInfoPullHandler } = require('im/messenger/provider/pull/collab');
	const { PlanLimitsPullHandler } = require('im/messenger/provider/pull/plan-limits');
	const { FeaturePullHandler } = require('im/messenger/provider/pull/feature');
	const { SidebarPullHandler } = require('im/messenger/provider/pull/sidebar');
	const { VotePullHandler } = require('im/messenger/provider/pull/vote');

	const { MessengerEmitter } = require('im/messenger/lib/emitter');

	const { ChatRecent } = require('im/messenger/controller/recent/chat');
	const { RecentView } = require('im/messenger/view/recent');
	const { Dialog } = require('im/messenger/controller/dialog/chat');
	const { CopilotDialog } = require('im/messenger/controller/dialog/copilot');
	const { AiAssistantDialog } = require('im/messenger/controller/dialog/ai-assistant');
	const { ChatAssets } = require('im/messenger/controller/dialog/lib/assets');
	const { Communication } = require('im/messenger/lib/integration/mobile/communication');
	const { Promotion } = require('im/messenger/lib/promotion');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { PushHandler } = require('im/messenger/provider/push');
	const { DatabasePushMessageHandler } = require('im/messenger/provider/push/message-handler/database');
	const { ChatPushMessageHandler } = require('im/messenger/provider/push/message-handler/chat');
	const { DialogCreator } = require('im/messenger/controller/dialog-creator');
	const { RecentSelector } = require('im/messenger/controller/search/experimental');
	const { SmileManager } = require('im/messenger/lib/smile-manager');
	const { MessengerBase } = require('im/messenger/component/messenger-base');
	const { SyncFillerChat } = require('im/messenger/provider/services/sync/fillers/chat');
	const { SyncFillerDatabase } = require('im/messenger/provider/services/sync/fillers/database');
	const { ComponentCodeService } = require('im/messenger/provider/services/component-code');

	const { SidebarLazyFactory } = require('im/messenger/controller/sidebar-v2/factory');

	const { VoteResult } = require('im/messenger/controller/vote/result');
	const { clearDatabaseHandler } = require('im/messenger/lib/clear-database-handler');
	/* endregion import */

	class Messenger extends MessengerBase
	{
		/* region initiation */

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
			super();
			this.pushUpdateListPromise = null;

			this.promotion = new Promotion();
			this.communication = new Communication();
			EntityReady.addCondition('chat', () => this.isReady);

			/** @type {JSSharedStorage} */
			this.departmentColleaguesStore = Application.sharedStorage('immobileDepartmentColleagues');
		}

		initCore()
		{
			this.serviceLocator = serviceLocator;

			/**
			 * @type {CoreApplication}
			 */
			this.core = this.serviceLocator.get('core');

			/**
			 * @type {MessengerInitService}
			 */
			this.chatInitService = this.serviceLocator.get('messenger-init-service');

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

		showUnsupportedWidget(props = {}, parentWidget = PageManager)
		{
			MobileFeature.showDefaultUnsupportedWidget(props, parentWidget);
		}

		initRequests()
		{
			this.chatInitService.onInit(this.checkRevision.bind(this));
			this.callManager.subscribeMessengerInitEvent();

			if (this.isNeedRequestPlanLimits())
			{
				this.chatInitService.onInit(this.updatePlanLimitsData.bind(this));
			}
		}

		/**
		 * @return {boolean}
		 */
		isNeedRequestPlanLimits()
		{
			const planLimits = MessengerParams.getPlanLimits();
			if (planLimits?.fullChatHistory?.isAvailable)
			{
				return true;
			}

			return false;
		}

		/**
		 * @override
		 */
		async initComponents()
		{
			this.recent = new ChatRecent({
				view: new RecentView({
					ui: dialogList,
				}),
			});

			await this.recent.init().catch((error) => {
				Logger.error('Recent.init error: ', error);
			});

			this.searchSelector = new RecentSelector(dialogList);

			this.dialogCreator = new DialogCreator();
		}

		bindMethods()
		{
			super.bindMethods();

			this.openLine = this.openLine.bind(this);
			this.getOpenLineParams = this.getOpenLineParams.bind(this);
			this.openNotifications = this.openNotifications.bind(this);
			this.onFailRestoreConnection = this.onFailRestoreConnection.bind(this);
			this.destroyDialog = this.destroyDialog.bind(this);
			this.openVoteResult = this.openVoteResult.bind(this);

			this.onChatDialogInitComplete = this.onChatDialogInitComplete.bind(this);
			this.onChatDialogCounterChange = this.onChatDialogCounterChange.bind(this);
			this.onTaskStatusSuccess = this.onTaskStatusSuccess.bind(this);
			this.onCallActive = this.onCallActive.bind(this);
			this.onCallInactive = this.onCallInactive.bind(this);
			this.onNotificationOpen = this.onNotificationOpen.bind(this);
			this.onNotificationReload = this.onNotificationReload.bind(this);
			this.onAppActiveBefore = this.onAppActiveBefore.bind(this);
			this.onAppPaused = this.onAppPaused.bind(this);
			this.onAppActive = this.onAppActive.bind(this);
			this.openRequestRouter = this.openRequestRouter.bind(this);
			this.onChatSettingChange = this.onChatSettingChange.bind(this);
			this.onReturnToAudioMessage = this.onReturnToAudioMessage.bind(this);
		}

		/**
		 * desc preload assets by support feature flag
		 * @override
		 */
		preloadAssets()
		{
			// TODO: generalize the approach to background caching
			(new ChatAssets()).preloadAssets();
			SidebarLazyFactory.preload();
		}

		subscribeMessengerEvents()
		{
			super.subscribeMessengerEvents();

			BX.addCustomEvent(EventType.messenger.openLine, this.openLine);
			BX.addCustomEvent(EventType.messenger.getOpenLineParams, this.getOpenLineParams);
			BX.addCustomEvent(EventType.messenger.openNotifications, this.openNotifications);
			BX.addCustomEvent(EventType.messenger.destroyDialog, this.destroyDialog);
			BX.addCustomEvent(EventType.messenger.openVoteResult, this.openVoteResult);
			BX.addCustomEvent(EventType.messenger.clearDatabase, clearDatabaseHandler);
		}

		unsubscribeMessengerEvents()
		{
			super.unsubscribeMessengerEvents();

			BX.removeCustomEvent(EventType.messenger.openLine, this.openLine);
			BX.removeCustomEvent(EventType.messenger.getOpenLineParams, this.getOpenLineParams);
			BX.removeCustomEvent(EventType.messenger.openNotifications, this.openNotifications);
			BX.removeCustomEvent(EventType.messenger.destroyDialog, this.destroyDialog);
			BX.removeCustomEvent(EventType.messenger.openVoteResult, this.openVoteResult);
			BX.removeCustomEvent(EventType.messenger.clearDatabase, clearDatabaseHandler);
		}

		/**
		 * @override
		 */
		subscribeExternalEvents()
		{
			super.subscribeExternalEvents();

			BX.addCustomEvent(EventType.chatDialog.initComplete, this.onChatDialogInitComplete);
			BX.addCustomEvent(EventType.chatDialog.counterChange, this.onChatDialogCounterChange);
			BX.addCustomEvent(EventType.chatDialog.taskStatusSuccess, this.onTaskStatusSuccess);
			BX.addCustomEvent(EventType.call.active, this.onCallActive);
			BX.addCustomEvent(EventType.call.inactive, this.onCallInactive);
			BX.addCustomEvent(EventType.notification.open, this.onNotificationOpen);
			BX.addCustomEvent(EventType.notification.reload, this.onNotificationReload);
			BX.addCustomEvent(EventType.app.activeBefore, this.onAppActiveBefore);
			BX.addCustomEvent(EventType.app.paused, this.onAppPaused);
			BX.addCustomEvent(EventType.app.active, this.onAppActive);
			BX.addCustomEvent(EventType.app.failRestoreConnection, this.onFailRestoreConnection);
			BX.addCustomEvent(EventType.setting.chat.change, this.onChatSettingChange);
			BX.addCustomEvent(EventType.audio.audioPanelReturnClick, this.onReturnToAudioMessage);
			jnComponent.on(EventType.jnComponent.openRequest, this.openRequestRouter);
		}

		/**
		 * @override
		 */
		unsubscribeExternalEvents()
		{
			super.unsubscribeExternalEvents();

			BX.removeCustomEvent(EventType.chatDialog.initComplete, this.onChatDialogInitComplete);
			BX.removeCustomEvent(EventType.chatDialog.counterChange, this.onChatDialogCounterChange);
			BX.removeCustomEvent(EventType.chatDialog.taskStatusSuccess, this.onTaskStatusSuccess);
			BX.removeCustomEvent(EventType.call.active, this.onCallActive);
			BX.removeCustomEvent(EventType.call.inactive, this.onCallInactive);
			BX.removeCustomEvent(EventType.notification.open, this.onNotificationOpen);
			BX.removeCustomEvent(EventType.notification.reload, this.onNotificationReload);
			BX.removeCustomEvent(EventType.app.activeBefore, this.onAppActiveBefore);
			BX.removeCustomEvent(EventType.app.paused, this.onAppPaused);
			BX.removeCustomEvent(EventType.app.active, this.onAppActive);
			BX.removeCustomEvent(EventType.app.failRestoreConnection, this.onFailRestoreConnection);
			BX.removeCustomEvent(EventType.setting.chat.change, this.onChatSettingChange);
			BX.removeCustomEvent(EventType.audio.audioPanelReturnClick, this.onReturnToAudioMessage);
			jnComponent.off(EventType.jnComponent.openRequest, this.openRequestRouter);
		}

		/**
		 * @override
		 */
		initCustomServices()
		{
			this.syncFillerService = new SyncFillerChat();
			this.syncDatabaseFillerService = new SyncFillerDatabase();
		}

		/**
		 * @override
		 */
		redrawHeader()
		{
			let headerTitle;
			let useProgress;

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
		initPushMessageHandlers()
		{
			this.databasePushMessageHandler = new DatabasePushMessageHandler();
			this.pushHandler = new PushHandler();
			this.chatPushMessageHandler = new ChatPushMessageHandler();
		}

		/**
		 * @override
		 */
		initPullHandlers()
		{
			super.initPullHandlers();
			BX.PULL.subscribe(new ChatApplicationPullHandler());
			BX.PULL.subscribe(new ChatMessagePullHandler());
			BX.PULL.subscribe(new ChatFilePullHandler());
			BX.PULL.subscribe(new ChatDialogPullHandler());
			BX.PULL.subscribe(new ChatUserPullHandler());
			BX.PULL.subscribe(new ChatRecentPullHandler());
			BX.PULL.subscribe(new NotificationPullHandler());
			BX.PULL.subscribe(new OnlinePullHandler());
			BX.PULL.subscribe(new SidebarPullHandler());
			BX.PULL.subscribe(new PlanLimitsPullHandler());
			BX.PULL.subscribe(new FeaturePullHandler());
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

		/**
		 * @override
		 */
		async initQueueRequests()
		{
			const queueRequests = await this.core.getRepository().queue.getList();
			if (queueRequests.length > 0)
			{
				await this.store.dispatch('queueModel/add', queueRequests);
			}
		}

		/**
		 * @override
		 */
		executeStoredPullEvents()
		{
			if (Feature.isInstantPushEnabled)
			{
				this.pushHandler.updateList(false)
					.then(() => {
						this.pushHandler.executeAction();
						this.pushHandler.clearHistory();
					})
					.catch((error) => {
						Logger.warn('Messenger.executeStoredPullEvents error:', error);
					})
				;

				return;
			}

			this.pushHandler.executeAction();
			this.pushHandler.clearHistory();
		}

		async fillDataBaseFromPush()
		{
			if (Feature.isInstantPushEnabled)
			{
				const eventList = await this.pushHandler.getPushEventList();

				await this.databasePushMessageHandler.handleMessageBatch(eventList);
			}
		}

		onAppActiveBefore()
		{
			BX.onViewLoaded(() => {
				MessengerEmitter.emit(EventType.dialog.external.disableScrollToBottom);

				this.pushUpdateListPromise = this.pushHandler.updateList();

				this.refresh({ shortMode: true });
			});
		}

		onAppActive()
		{
			BX.onViewLoaded(async () => {
				if (this.pushUpdateListPromise instanceof Promise)
				{
					await this.pushUpdateListPromise;
				}

				this.pushHandler.executeAction();
			});
		}

		onAppPaused()
		{
			this.chatInitService.clearCommonActionResultStore();
			this.pushHandler.clearHistory();
		}

		/**
		 * @return {string[]}
		 */
		getInitRestMethodsForRefresh()
		{
			return [
				...super.getInitRestMethodsForRefresh(),
				MessengerInitRestMethod.activeCalls,
			];
		}

		/**
		 * @return {string[]}
		 */
		getInitRestMethodsForApplicationStartup()
		{
			const methodList = [
				...super.getInitRestMethodsForApplicationStartup(),
				MessengerInitRestMethod.activeCalls,
				MessengerInitRestMethod.promotion,
				MessengerInitRestMethod.tariffRestriction,
			];

			const isNeedDepartmentColleagues = !this.departmentColleaguesStore.get('isRequested');
			if (isNeedDepartmentColleagues)
			{
				methodList.push(MessengerInitRestMethod.departmentColleagues);
			}

			return methodList;
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

			this.syncService.clearBackgroundSyncInterval();
			await this.core.setAppStatus(AppStatus.connection, true);
			this.smileManager = SmileManager.getInstance();
			SmileManager.init();

			await this.queueCallBatch();

			return this.chatInitService.runAction(this.getInitRestMethods(shortMode))
				.then(() => {
					this.afterRefresh();
				})
				.catch((response) => {
					this.afterRefreshError(response);
				})
				.finally(() => {
					MessengerEmitter.emit(EventType.dialog.external.scrollToFirstUnread);

					Logger.warn('Messenger.refresh complete');
				});
		}

		queueCallBatch()
		{
			return this.queueRestManager.callBatch()
				.then((response) => this.clearRequestQueue(response, true))
				.catch((error) => this.clearRequestQueue(error, true));
		}

		clearRequestQueue(response, withTemporaryMessage = false)
		{
			return this.queueService.clearRequestByBatchResult(response, withTemporaryMessage);
		}

		async afterRefresh()
		{
			if (Feature.isLocalStorageEnabled)
			{
				await this.core.setAppStatus(AppStatus.sync, true);
			}

			await this.core.setAppStatus(AppStatus.connection, false);
			this.refreshErrorNoticeFlag = false;
			this.notifyRefreshErrorWorker.stop();

			serviceLocator.get('tab-counters').update();

			const isRequestedDepartmentColleagues = this.departmentColleaguesStore.get('isRequested');
			if (!isRequestedDepartmentColleagues)
			{
				this.departmentColleaguesStore.set('isRequested', true);
			}

			if (!Feature.isLocalStorageEnabled)
			{
				return this.ready();
			}

			return this.syncService.sync()
				.then(() => this.ready())
				.catch((error) => {
					Logger.error('Messenger.afterRefresh error', error);
				})
				.finally(() => {
					this.syncService.startBackgroundSyncInterval();
				})
			;
		}

		async afterRefreshError(response)
		{
			Logger.error('Messenger.afterRefreshError', response);
			const errorList = Type.isArray(response) ? response : [response];

			for (const error of errorList)
			{
				if (error?.code === 'REQUEST_CANCELED')
				{
					return;
				}
			}

			const secondsBeforeRefresh = this.refreshAfterErrorInterval / 1000;
			Logger.error(`Messenger: refresh error. Try again in ${secondsBeforeRefresh} seconds.`);

			clearTimeout(this.refreshTimeout);

			this.refreshTimeout = setTimeout(() => {
				if (!this.refreshErrorNoticeFlag && !Application.isBackground())
				{
					this.notifyRefreshErrorWorker.startOnce();
				}

				Logger.warn('Messenger.refresh after error');
				this.refresh();
			}, this.refreshAfterErrorInterval);
		}

		async ready()
		{
			this.isReady = true;

			if (this.isFirstLoad)
			{
				Logger.warn('Messenger.ready');
				EntityReady.ready('chat');
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

		/**
		 * @private
		 * @param {PageManager} parentWidget
		 * @param {*} openRequest
		 * @return {Promise<void>}
		 */
		async openRequestRouter(parentWidget, openRequest = {})
		{
			Logger.info(`${this.constructor.name}.openRequestHandler`, parentWidget, openRequest);
			if (!Type.isObject(openRequest))
			{
				return;
			}

			if (openRequest[OpenRequest.dialog] && Type.isObject(openRequest[OpenRequest.dialog].options))
			{
				/**
				 * @type {DialogOpenOptions}
				 */
				const openDialogOptions = openRequest[OpenRequest.dialog].options;
				this.openDialogByRequest(parentWidget, openDialogOptions).catch((error) => {
					Logger.error(error);
				});
			}
		}

		/**
		 * @param {ReturnAudioData} audioData
		 * @returns {Promise<void>}
		 */
		async onReturnToAudioMessage(audioData)
		{
			void returnToAudioMessage({
				audioData,
				dialog: this.dialog,
				openDialog: this.openDialog,
			});
		}

		/**
		 * @private
		 * @param {PageManager} parentWidget
		 * @param {DialogOpenOptions} openDialogOptions
		 * @return {Promise<void>}
		 */
		async openDialogByRequest(parentWidget, openDialogOptions = {})
		{
			const openOptions = openDialogOptions;
			if (openOptions.dialogId)
			{
				// eslint-disable-next-line no-param-reassign
				openOptions.dialogId = openOptions.dialogId.toString();
			}

			this.dialog = new Dialog();

			return this.dialog.open(openOptions, parentWidget);
		}

		/**
		 * @param {DialogOpenOptions} options
		 * @return {Promise<void>}
		 */
		async openDialog(options = {})
		{
			Logger.info(`${this.constructor.name}.openDialog`, options);
			const openDialogOptions = options;

			if (openDialogOptions.dialogId)
			{
				openDialogOptions.dialogId = openDialogOptions.dialogId.toString();
			}

			// TODO: transfer the list of calls to the model and transfer the work with calls to the integration class
			if (openDialogOptions.callId && !openDialogOptions.dialogId)
			{
				const call = this.recent.getCallById(`call${openDialogOptions.callId}`);
				if (!call)
				{
					return;
				}

				const dialogId = get(call, 'params.call.associatedEntity.id', null);
				if (!dialogId)
				{
					return;
				}

				openDialogOptions.dialogId = String(dialogId);
			}

			let componentCode = ComponentCode.imMessenger;
			const checkComponentCode = openDialogOptions.checkComponentCode ?? true;

			if (checkComponentCode)
			{
				const componentCodeService = new ComponentCodeService();
				try
				{
					componentCode = await componentCodeService.getCodeByDialogId(openDialogOptions.dialogId);
				}
				catch (error)
				{
					Logger.error(`${this.constructor.name}.openDialog: get component code error`, error);

					if (error[0]?.code)
					{
						ComponentCodeService.showToastByErrorCode(error[0]?.code);

						return;
					}

					componentCode = ComponentCode.imMessenger;
				}
				Logger.info(`${this.constructor.name}.openDialog: open in component`, componentCode);
			}

			if (componentCode === ComponentCode.imMessenger)
			{
				PageManager.getNavigator().makeTabActive();
				if (options.changeMessengerTab)
				{
					MessengerEmitter.emit(EventType.navigation.changeTab, componentCode, ComponentCode.imNavigation);
				}

				this.visibilityManager.checkIsDialogVisible({
					dialogId: openDialogOptions.dialogId,
					currentContextOnly: true,
				})
					.then((isVisible) => {
						if (isVisible)
						{
							return;
						}

						const dialogHelper = DialogHelper.createByDialogId(openDialogOptions.dialogId);
						const isCopilot = dialogHelper?.isCopilot || openDialogOptions.chatType === DialogType.copilot;
						if (isCopilot)
						{
							this.dialog = new CopilotDialog();
							this.dialog.open(openDialogOptions);

							return;
						}

						const isAiAssistant = dialogHelper?.isAiAssistant;
						if (isAiAssistant)
						{
							this.dialog = new AiAssistantDialog();
							this.dialog.open(openDialogOptions);

							return;
						}

						this.dialog = new Dialog();
						this.dialog.open(openDialogOptions);
					})
					.catch((error) => {
						Logger.error(error);
					})
				;

				return;
			}

			if (options.changeMessengerTab)
			{
				MessengerEmitter.emit(
					EventType.navigation.broadCastEventWithTabChange,
					{
						broadCastEvent: EventType.messenger.openDialog,
						toTab: NavigationTabByComponent[componentCode],
						data: {
							...options,
							checkComponentCode: false,
							changeMessengerTab: false,
						},
					},
					ComponentCode.imNavigation,
				);

				return;
			}

			console.warn(`Dialog opened in ${componentCode}. Look over there`);

			MessengerEmitter.emit(
				EventType.navigation.broadCastEventCheckTabPreload,
				{
					broadCastEvent: EventType.messenger.openDialog,
					toTab: NavigationTabByComponent[componentCode],
					data: {
						...options,
						checkComponentCode: false,
						changeMessengerTab: false,
					},
				},
				ComponentCode.imNavigation,
			);
		}

		destroyDialog(dialogId)
		{
			Logger.info('EventType.messenger.destroyDialog', dialogId);
			this.dialog.view.ui.back();
		}

		/**
		 * @param {DialogOpenOptions} options
		 */
		openLine(options)
		{
			Logger.info('EventType.messenger.openLine', options);

			this.dialog = new Dialog();
			this.dialog.openLine(options);
		}

		openNotifications()
		{
			if (!PageManager.getNavigator().isActiveTab())
			{
				PageManager.getNavigator().makeTabActive();
			}

			showNotificationList();
		}

		openChatSearch()
		{
			Logger.log('EventType.messenger.showSearch');

			this.searchSelector.open();
		}

		closeChatSearch()
		{
			Logger.log('EventType.messenger.hideSearch');

			this.searchSelector.close();
		}

		openChatCreate()
		{
			Logger.log('EventType.messenger.createChat');
			this.dialogCreator.open();
		}

		onNotificationOpen()
		{
			Logger.log('EventType.notification.open');

			const tabCounters = serviceLocator.get('tab-counters');
			tabCounters.clearNotificationCounters();
			tabCounters.update();
		}

		onNotificationReload()
		{
			Logger.log('EventType.notification.reload');

			BX.postWebEvent('onBeforeNotificationsReload', {});
			Application.refreshNotifications();
		}

		onCallActive(call, callStatus)
		{
			Logger.log('EventType.call.active');

			this.recent.addCall(call, callStatus);
		}

		onCallInactive(call)
		{
			Logger.log('EventType.call.inactive');

			this.recent.removeCall(call);
		}

		onTaskStatusSuccess(taskId, result)
		{
			Logger.log('EventType.chatDialog.taskStatusSuccess', taskId, result);
		}

		getOpenLineParams(options = {})
		{
			const requestId = options.userCode ?? options.sessionId;
			const openLineParamsResponseEvent = `${EventType.messenger.openLineParams}::${requestId}`;

			Dialog.getOpenLineParams(options)
				.then((params) => {
					BX.postComponentEvent(openLineParamsResponseEvent, [params]);
				})
				.catch((error) => {
					Logger.error(error);
				})
			;
		}

		/**
		 * @private
		 * @param {{id: string, value: any}} setting
		 */
		onChatSettingChange(setting)
		{
			Logger.log('Messenger.Setting.Chat.Change:', setting);
		}

		onFailRestoreConnection()
		{
			Logger.log('EventType.app.failRestoreConnection');

			this.refresh();
		}

		openVoteResult(options)
		{
			Logger.info('EventType.messenger.openVoteResult', options);

			VoteResult.open({ signedAttachId: options.signedAttachId });
		}

		/* region legacy dialog integration */

		onChatDialogInitComplete(event)
		{
			Logger.log('EventType.chatDialog.initComplete', event);
		}

		onChatDialogCounterChange(event)
		{
			Logger.log('EventType.chatDialog.counterChange', event);

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
				Logger.log('Messenger.checkRevision: current', REVISION, 'actual', revision);

				return true;
			}

			Logger.warn(
				'Messenger.checkRevision: reload scripts because revision up',
				REVISION,
				' -> ',
				revision,
			);

			Application.relogin();

			return false;
		}

		/**
		 * @param {immobileTabChatLoadResult} data
		 * @return boolean
		 */
		updatePlanLimitsData(data)
		{
			const tariffRestriction = data.tariffRestriction;
			Logger.log(`${this.constructor.name}.updatePlanLimitsData`, tariffRestriction);

			if (Type.isNil(tariffRestriction?.fullChatHistory?.isAvailable))
			{
				Logger.log(`${this.constructor.name}.updatePlanLimitsData not valid tariffRestriction`, tariffRestriction);

				return false;
			}
			MessengerParams.setPlanLimits(tariffRestriction);

			this.sendToCopilotComponent(tariffRestriction);

			return true;
		}

		/**
		 * @param {PlanLimits} planLimits
		 * @return void
		 */
		sendToCopilotComponent(planLimits)
		{
			BX.postComponentEvent(EventType.messenger.updatePlanLimitsData, [planLimits], ComponentCode.imCopilotMessenger);
		}

		destructor()
		{
			// TODO: remove all of this
			this.unsubscribeStoreEvents();
			this.unsubscribeMessengerEvents();
			this.unsubscribeExternalEvents();
			this.databasePushMessageHandler.destructor();
			this.chatPushMessageHandler.destructor();
			this.promotion.destruct();

			if (this.connectionService)
			{
				this.connectionService.destructor();
			}

			BX.listeners = {};

			Logger.warn('Messenger: Garbage collection after refresh complete');
		}
	}

	window.messenger = new Messenger();
	window.messengerDebug.clearDatabaseAndRestart = async () => {
		const { clearDatabaseAndRestart } = await requireLazy('im:messenger/api/cleaning');
		void clearDatabaseAndRestart();
	};
})().catch((error) => {
	console.error('Messenger init error', error);
});
