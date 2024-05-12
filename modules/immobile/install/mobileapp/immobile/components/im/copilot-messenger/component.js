// eslint-disable-next-line no-var,no-implicit-globals
var REVISION = 19; // API revision - sync with im/lib/revision.php

/* region Environment variables */

// use in immobile/install/mobileapp/immobile/extensions/im/chat/messengercommon/extension.js:532
BX.message.LIMIT_ONLINE = BX.componentParameters.get('LIMIT_ONLINE', 1380);

/* endregion Environment variables */

/* region Clearing session variables after script reload */

if (typeof window.copilotMessenger !== 'undefined' && typeof window.copilotMessenger.destructor !== 'undefined')
{
	window.copilotMessenger.destructor();
}

/* endregion Clearing session variables after script reload */

(async () => {
	/* global dialogList, ChatTimer, InAppNotifier, ChatUtils, reloadAllScripts */
	/* region import */
	const require = (ext) => jn.require(ext); // for IDE hints

	const DialogList = dialogList;
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { isEqual } = require('utils/object');

	const { Logger } = require('im/messenger/lib/logger');
	const { CopilotApplication } = require('im/messenger/core/copilot');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { EntityReady } = require('entity-ready');

	// Copilot uses the immobile-messenger-store and must be initialized after chat
	await EntityReady.wait('chat');

	const core = new CopilotApplication({
		localStorageEnable: true,
	});
	try
	{
		await core.init();
	}
	catch (error)
	{
		Logger.error('CopilotApplication init error: ', error);
	}
	serviceLocator.add('core', core);

	const { restManager } = require('im/messenger/lib/rest-manager');
	const { Feature } = require('im/messenger/lib/feature');

	const {
		CopilotDialogPullHandler,
		CopilotMessagePullHandler,
		CopilotFilePullHandler,
		CopilotUserPullHandler,
	} = require('im/messenger/provider/pull/copilot');

	const {
		AppStatus,
		EventType,
		RestMethod,
		FeatureFlag,
		ComponentCode,
	} = require('im/messenger/const');

	const { MessengerEmitter } = require('im/messenger/lib/emitter');

	const { CopilotRecent } = require('im/messenger/controller/recent/copilot');
	const { RecentView } = require('im/messenger/view/recent');
	const { CopilotDialog } = require('im/messenger/controller/dialog/copilot');
	const { CopilotAssets } = require('im/messenger/controller/dialog/lib/assets');
	const { ChatCreator } = require('im/messenger/controller/chat-creator');
	const { Counters } = require('im/messenger/lib/counters');
	const { Communication } = require('im/messenger/lib/integration/mobile/communication');
	const { Promotion } = require('im/messenger/lib/promotion');
	const { DialogCreator } = require('im/messenger/controller/dialog-creator');
	const { RecentSelector } = require('im/messenger/controller/search/experimental');
	const { SmileManager } = require('im/messenger/lib/smile-manager');
	const { MessengerBase } = require('im/messenger/component/messenger-base');
	const { SyncFillerCopilot } = require('im/messenger/provider/service');
	const { SidebarController } = require('im/messenger/controller/sidebar/sidebar-controller');
	const AppTheme = require('apptheme');
	const { MessengerParams } = require('im/messenger/lib/params');
	/* endregion import */

	class CopilotMessenger extends MessengerBase
	{
		/* region initiation */

		/**
		 * @class CopilotMessenger - mobile messenger entry point
		 *
		 * @property {boolean} isReady - flag that the messenger has finished initialization
		 * @property {boolean} isFirstLoad - flag that the messenger is loading for the first time
		 *
		 * @property {Object} store - vuex store
		 * @property {Object} storeManager - vuex store manager
		 *
		 * @property {CopilotRecent} recent - recent chat list controller
		 * @property {Dialog} dialog - chat controller
		 * @property {DialogSelector} dialogSelector - chat search controller
		 * @property {ChatCreator} chatCreator - chat creation dialog
		 * @property {RestManager} restManager - collects requests to initialize the messenger into a batch and executes it
		 */
		constructor()
		{
			super();
			this.refreshAfterErrorInterval = 10000;

			this.communication = new Communication();
			EntityReady.addCondition('copilot-messenger', () => this.isReady);
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
			 * @type {MessengerCoreStoreManager}
			 */
			this.storeManager = this.core.getStoreManager();
		}

		/**
		 * @override
		 */
		checkChatV2Support()
		{
			return true;
		}

		initRequests()
		{
			restManager.on(RestMethod.imRevisionGet, {}, this.checkRevision.bind(this));
		}

		/**
		 * @override
		 */
		async initComponents()
		{
			this.recent = new CopilotRecent({
				view: new RecentView({
					ui: DialogList,
					style: {
						chatCreateButtonColor: AppTheme.colors.accentMainCopilot || AppTheme.colors.accentBrandBlue,
						showLoader: true,
					},
				}),
			});
			await this.recent.init();

			this.searchSelector = new RecentSelector(DialogList);
			this.chatCreator = new ChatCreator();

			if (Application.getApiVersion() >= 47)
			{
				this.dialogCreator = new DialogCreator();
			}
		}

		/**
		 * @override
		 */
		bindMethods()
		{
			this.onApplicationSetStatus = this.applicationSetStatusHandler.bind(this);
			this.openDialog = this.openDialog.bind(this);
			this.getOpenDialogParams = this.getOpenDialogParams.bind(this);
			this.openChatSearch = this.openChatSearch.bind(this);
			this.closeChatSearch = this.closeChatSearch.bind(this);
			this.openChatCreate = this.openChatCreate.bind(this);
			this.refresh = this.refresh.bind(this);

			this.onChatDialogInitComplete = this.onChatDialogInitComplete.bind(this);
			this.onChatDialogCounterChange = this.onChatDialogCounterChange.bind(this);
			this.onChatDialogAccessError = this.onChatDialogAccessError.bind(this);
			this.onTaskStatusSuccess = this.onTaskStatusSuccess.bind(this);
			this.onNotificationReload = this.onNotificationReload.bind(this);
			this.onAppActiveBefore = this.onAppActiveBefore.bind(this);
			this.onChatSettingChange = this.onChatSettingChange.bind(this);
			this.onAppStatusChange = this.onAppStatusChange.bind(this);
			this.openSidebar = this.openSidebar.bind(this);
		}

		/**
		 * desc preload assets by support feature flag
		 * @override
		 */
		preloadAssets()
		{
			if (FeatureFlag.dialog.nativeSupported)
			{
				// TODO: generalize the approach to background caching
				(new CopilotAssets()).preloadAssets();
			}
		}

		/**
		 * @override
		 */
		subscribeMessengerEvents()
		{
			BX.addCustomEvent(EventType.messenger.getOpenDialogParams, this.getOpenDialogParams);
			BX.addCustomEvent(EventType.messenger.showSearch, this.openChatSearch);
			BX.addCustomEvent(EventType.messenger.hideSearch, this.closeChatSearch);
			BX.addCustomEvent(EventType.messenger.createChat, this.openChatCreate);
			BX.addCustomEvent(EventType.messenger.refresh, this.refresh);
			BX.addCustomEvent(EventType.messenger.openDialog, this.openDialog);
			BX.addCustomEvent(EventType.messenger.openSidebar, this.openSidebar);
		}

		unsubscribeMessengerEvents()
		{
			BX.removeCustomEvent(EventType.messenger.openDialog, this.openDialog);
			BX.removeCustomEvent(EventType.messenger.getOpenDialogParams, this.getOpenDialogParams);
			BX.removeCustomEvent(EventType.messenger.showSearch, this.openChatSearch);
			BX.removeCustomEvent(EventType.messenger.hideSearch, this.closeChatSearch);
			BX.removeCustomEvent(EventType.messenger.createChat, this.openChatCreate);
			BX.removeCustomEvent(EventType.messenger.refresh, this.refresh);
			BX.removeCustomEvent(EventType.messenger.openSidebar, this.openSidebar);
		}

		/**
		 * @override
		 */
		subscribeExternalEvents()
		{
			BX.addCustomEvent(EventType.chatDialog.initComplete, this.onChatDialogInitComplete);
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
			BX.removeCustomEvent(EventType.chatDialog.initComplete, this.onChatDialogInitComplete);
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
			this.syncFillerService = new SyncFillerCopilot();
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
					headerTitle = Loc.getMessage('IMMOBILE_COMMON_MESSENGER_HEADER');
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
			DialogList.setTitle(this.titleParams);
		}

		/**
		 * @override
		 */
		initPullHandlers()
		{
			BX.PULL.subscribe(new CopilotDialogPullHandler());
			BX.PULL.subscribe(new CopilotMessagePullHandler());
			BX.PULL.subscribe(new CopilotFilePullHandler());
			BX.PULL.subscribe(new CopilotUserPullHandler());
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

		onAppActiveBefore()
		{
			BX.onViewLoaded(() => {
				if (!Feature.isLocalStorageEnabled)
				{
					MessengerEmitter.emit(EventType.dialog.external.disableScrollToBottom);
				}

				this.refresh()
					.finally(() => {
						MessengerEmitter.emit(EventType.dialog.external.scrollToFirstUnread);
					})
				;
			});
		}

		/**
		 * @override
		 */
		async refresh(redrawHeaderTruly)
		{
			this.redrawHeaderTruly = redrawHeaderTruly ?? false;
			await this.core.setAppStatus(AppStatus.connection, true);
			this.smileManager = SmileManager.getInstance();
			await SmileManager.init();

			await this.queueCallBatch();

			return restManager.callBatch()
				.then(() => this.afterRefresh())
				.catch((response) => this.afterRefreshError(response))
			;
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
			await this.core.setAppStatus(AppStatus.connection, false);
			this.refreshErrorNoticeFlag = false;
			ChatTimer.stop('recent', 'error', true);

			Counters.update();

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
					Logger.error('CopilotMessenger.afterRefreshError', firstError.ex);

					return;
				}
			}

			const secondsBeforeRefresh = this.refreshAfterErrorInterval / 1000;
			Logger.error(`CopilotMessenger: refresh error. Try again in ${secondsBeforeRefresh} seconds.`);

			clearTimeout(this.refreshTimeout);

			this.refreshTimeout = setTimeout(() => {
				if (!this.refreshErrorNoticeFlag && !Application.isBackground())
				{
					const notifyRefreshError = () => {
						this.refreshErrorNoticeFlag = true;

						InAppNotifier.showNotification({
							message: Loc.getMessage('IMMOBILE_COMMON_MESSENGER_REFRESH_ERROR'),
							backgroundColor: '#E6000000',
							time: this.refreshAfterErrorInterval / 1000 - 2,
						});
					};

					ChatTimer.start('recent', 'error', 2000, notifyRefreshError);
				}

				Logger.warn('CopilotMessenger.refresh after error');
				this.refresh();
			}, this.refreshAfterErrorInterval);
		}

		async ready()
		{
			this.isReady = true;

			if (this.isFirstLoad)
			{
				Logger.warn('CopilotMessenger.ready');
				EntityReady.ready('copilot-messenger');
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
			Logger.info('CopilotMessenger.openDialog', options);
			const openDialogOptions = options;
			if (openDialogOptions.dialogId)
			{
				openDialogOptions.dialogId = openDialogOptions.dialogId.toString();
			}

			PageManager.getNavigator().makeTabActive();
			this.visibilityManager.checkIsDialogVisible(openDialogOptions.dialogId)
				.then((isVisible) => {
					if (isVisible)
					{
						return;
					}

					this.dialog = new CopilotDialog();
					this.dialog.open(openDialogOptions);

					this.recent.view.initChatCreateButton();
				})
				.catch((error) => {
					Logger.error(error);
				})
			;
		}

		openChatSearch()
		{
			Logger.log('CopilotMessenger.showSearch');

			this.searchSelector.open();
		}

		closeChatSearch()
		{
			Logger.log('CopilotMessenger.hideSearch');

			this.searchSelector.close();
		}

		openChatCreate()
		{
			Logger.log('CopilotMessenger.createChat');
			if (this.dialogCreator !== null)
			{
				this.dialogCreator.createCopilotDialog();
			}
		}

		onNotificationReload()
		{
			Logger.log('CopilotMessenger.notification.reload');

			BX.postWebEvent('onBeforeNotificationsReload', {});
			Application.refreshNotifications();
		}

		onTaskStatusSuccess(taskId, result)
		{
			Logger.log('CopilotMessenger.chatDialog.taskStatusSuccess', taskId, result);
		}

		getOpenDialogParams(options = {})
		{
			const openDialogParamsResponseEvent = `${EventType.messenger.openDialogParams}::${options.dialogId}`;

			const params = CopilotDialog.getOpenDialogParams(options);
			BX.postComponentEvent(openDialogParamsResponseEvent, [params], ComponentCode.imCopilotMessenger);
		}

		/**
		 * @private
		 * @param {{id: string, value: any}} setting
		 */
		onChatSettingChange(setting)
		{
			Logger.log('CopilotMessenger.Setting.Chat.Change:', setting);
		}

		/* region legacy dialog integration */

		onChatDialogInitComplete(event)
		{
			Logger.log('CopilotMessenger.chatDialog.initComplete', event);

			Promotion.checkDialog(event.dialogId.toString());
		}

		onChatDialogCounterChange(event)
		{
			Logger.log('CopilotMessenger.chatDialog.counterChange', event);

			const recentItem = ChatUtils.objectClone(
				this.store.getters['recentModel/getById'](event.dialogId),
			);

			if (!recentItem)
			{
				return;
			}

			recentItem.counter = event.counter;
			const dialogItem = ChatUtils.objectClone(this.store.getters['dialoguesModel/getById'](event.dialogId));
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
			Logger.warn('CopilotMessenger.chatDialog.accessError');

			InAppNotifier.showNotification({
				title: Loc.getMessage('IMMOBILE_COMMON_MESSENGER_DIALOG_ACCESS_ERROR_TITLE'),
				message: Loc.getMessage('IMMOBILE_COMMON_MESSENGER_DIALOG_ACCESS_ERROR_TEXT'),
				backgroundColor: '#E6000000',
				time: 3,
			});

			this.dialog.deleteCurrentDialog();
		}

		/**
		 * desc Handler call open sidebar event
		 * @param {{dialogId: string|number}} params
		 * @override
		 */
		openSidebar(params)
		{
			Logger.info('CopilotMessenger.openSidebar', params);
			const isOpenSidebarEnabled = MessengerParams.isCopilotAddUsersEnabled();
			if (isOpenSidebarEnabled)
			{
				this.sidebar = new SidebarController(params);
				this.sidebar.open();
			}
		}
		/* endregion legacy dialog integration */

		/* endregion event handlers */

		checkRevision(response)
		{
			const error = response.error();
			if (error)
			{
				Logger.error('CopilotMessenger.checkRevision', error);

				return true;
			}

			const actualRevision = response.data().mobile;
			if (!Type.isNumber(actualRevision) || REVISION >= actualRevision)
			{
				Logger.log('CopilotMessenger.checkRevision: current', REVISION, 'actual', actualRevision);

				return true;
			}

			Logger.warn(
				'CopilotMessenger.checkRevision: reload scripts because revision up',
				REVISION,
				' -> ',
				actualRevision,
			);

			reloadAllScripts();

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
			Logger.warn('CopilotMessenger: Garbage collection after refresh complete');
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
	}

	window.copilotMessenger = new CopilotMessenger();
})();
