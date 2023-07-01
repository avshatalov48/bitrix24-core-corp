// eslint-disable-next-line no-var
var REVISION = 19; // API revision - sync with im/lib/revision.php

/* region Environment variables */

// use in immobile/install/mobileapp/immobile/extensions/im/chat/messengercommon/extension.js:532
// eslint-disable-next-line bitrix-rules/no-bx-message
BX.message.LIMIT_ONLINE = BX.componentParameters.get('LIMIT_ONLINE', 1380);

/* endregion Environment variables */

/* region Clearing session variables after script reload */

// eslint-disable-next-line bitrix-rules/no-typeof
if (typeof window.messenger !== 'undefined' && typeof window.messenger.destructor !== 'undefined')
{
	window.messenger.destructor();
}

/* endregion Clearing session variables after script reload */

(() => {
	/* region import */
	const require = ext => jn.require(ext); //for IDE hints

	const { Type } = require('type');
	const { Loc } = require('loc');
	const { get } = require('utils/object');
	const { core } = require('im/messenger/core');
	const { restManager } = require('im/messenger/lib/rest-manager');

	const {
		MessagePullHandler,
		DialogPullHandler,
		UserPullHandler,
		DesktopPullHandler,
		NotificationPullHandler,
		OnlinePullHandler,
	} = require('im/messenger/provider/pull');

	const {
		EventType,
		RestMethod,
		FeatureFlag,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Logger } = require('im/messenger/lib/logger');
	const { SoftLoader } = require('im/messenger/lib/helper');

	const { Recent } = require('im/messenger/controller/recent');
	const { RecentView } = require('im/messenger/view/recent');
	const { Dialog } = require('im/messenger/controller/dialog');
	const { DialogSelector } = require('im/messenger/controller/dialog-selector');
	const { ChatCreator } = require('im/messenger/controller/chat-creator');
	const { Counters } = require('im/messenger/lib/counters');
	const { EntityReady } = require('entity-ready');
	const { Communication } = require('im/messenger/lib/integration/mobile/communication');
	const { Promotion } = require('im/messenger/lib/promotion');
	const { PushHandler } = require('im/messenger/provider/push');
	const { SelectorDialogListAdapter } = require('im/chat/selector/adapter/dialog-list');
	const { DialogCreator } = require('im/messenger/controller/dialog-creator');
	/* endregion import */

	class Messenger
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
		 * @property {ChatCreator} chatCreator - chat creation dialog
		 * @property {RestManager} restManager - collects requests to initialize the messenger into a batch and executes it
		 */
		constructor()
		{
			if (FeatureFlag.isBetaVersion)
			{
				Logger.enable('log');
			}

			Logger.enable('info');
			Logger.enable('warn');
			Logger.enable('error');

			this.isReady = false;
			this.isFirstLoad = true;
			this.refreshTimeout = null;
			this.refreshAfterErrorInterval = 10000;
			this.refreshErrorNoticeFlag = false;

			this.store = null;
			this.storeManager = null;

			this.recent = null;
			this.dialog = null;
			this.dialogSelector = null;
			this.chatCreator = null;
			this.dialogCreator = null;
			this.communication = new Communication();
			this.loader = new SoftLoader({
				safeDisplayTime: 500,
				onShow: this.showProgress.bind(this),
				onHide: this.hideProgress.bind(this),
			});

			EntityReady.addCondition('chat', () => this.isReady);

			this.init();
		}

		init()
		{
			this.preloadAssets();

			this.core = core;
			this.core
				.ready()
				.then(() => {
					this.store = this.core.getStore();
					this.storeManager = this.core.getStoreManager();

					this.initRequests();

					BX.onViewLoaded(() => {
						this.initComponents();
						this.subscribeEvents();
						this.initPullHandlers();

						EntityReady.wait('im.navigation')
							.then(() => this.executeStoredPullEvents())
						;

						this.refresh();
					});
				})
			;
		}

		initRequests()
		{
			restManager.on(RestMethod.imRevisionGet, {}, this.checkRevision.bind(this));
		}

		initComponents()
		{
			this.recent = new Recent({
				view: new RecentView({
					ui: dialogList,
				}),
			});

			this.dialogSelector = new DialogSelector({
				view: new SelectorDialogListAdapter(dialogList),
			});

			this.chatCreator = new ChatCreator();

			if (Application.getApiVersion() >= 47)
			{
				this.dialogCreator = new DialogCreator();
			}
		}

		preloadAssets()
		{
			if (FeatureFlag.dialog.nativeSupported)
			{
				//TODO: generalize the approach to background caching
				Dialog.preloadAssets();
			}
		}

		subscribeEvents()
		{
			this.subscribeMessengerEvents();
			this.subscribeExternalEvents();
		}

		subscribeMessengerEvents()
		{
			BX.addCustomEvent(EventType.messenger.openDialog, this.openDialog.bind(this));
			BX.addCustomEvent(EventType.messenger.openLine, this.openLine.bind(this));
			BX.addCustomEvent(EventType.messenger.getOpenDialogParams, this.getOpenDialogParams.bind(this));
			BX.addCustomEvent(EventType.messenger.getOpenLineParams, this.getOpenLineParams.bind(this));
			BX.addCustomEvent(EventType.messenger.showSearch, this.openChatSearch.bind(this));
			BX.addCustomEvent(EventType.messenger.createChat, this.openChatCreate.bind(this));
			BX.addCustomEvent(EventType.messenger.openNotifications, this.openNotifications.bind(this));
			BX.addCustomEvent(EventType.messenger.refresh, this.refresh.bind(this));
			BX.addCustomEvent(EventType.messenger.closeDialog, this.closeDialog.bind(this));
		}

		subscribeExternalEvents()
		{
			BX.addCustomEvent(EventType.chatDialog.initComplete, this.onChatDialogInitComplete.bind(this));
			BX.addCustomEvent(EventType.chatDialog.counterChange, this.onChatDialogCounterChange.bind(this));
			BX.addCustomEvent(EventType.chatDialog.accessError, this.onChatDialogAccessError.bind(this));
			BX.addCustomEvent(EventType.chatDialog.taskStatusSuccess, this.onTaskStatusSuccess.bind(this));

			BX.addCustomEvent(EventType.call.active, this.onCallActive.bind(this));
			BX.addCustomEvent(EventType.call.inactive, this.onCallInactive.bind(this));

			BX.addCustomEvent(EventType.notification.open, this.onNotificationOpen.bind(this));
			BX.addCustomEvent(EventType.notification.reload, this.onNotificationReload.bind(this));

			BX.addCustomEvent(EventType.app.activeBefore, this.onAppActiveBefore.bind(this));
			BX.addCustomEvent(EventType.app.paused, this.onAppPaused.bind(this));
			BX.addCustomEvent(EventType.app.active, this.onAppActive.bind(this));
			BX.addCustomEvent(EventType.app.failRestoreConnection, this.refresh.bind(this));
		}

		initPullHandlers()
		{
			BX.PULL.subscribe(new MessagePullHandler());
			BX.PULL.subscribe(new DialogPullHandler());
			BX.PULL.subscribe(new UserPullHandler());
			BX.PULL.subscribe(new DesktopPullHandler());
			BX.PULL.subscribe(new NotificationPullHandler());
			BX.PULL.subscribe(new OnlinePullHandler());
		}

		executeStoredPullEvents()
		{
			PushHandler.updateList();
			PushHandler.executeAction();
		}

		onAppActiveBefore()
		{
			BX.onViewLoaded(() => {
				PushHandler.updateList();

				this.refresh();
			});
		}

		onAppActive()
		{
			BX.onViewLoaded(() => {
				PushHandler.executeAction();
			});
		}

		onAppPaused()
		{
			PushHandler.clearHistory();
		}

		refresh()
		{
			this.loader.show();

			restManager.callBatch()
				.then((response) => this.afterRefresh(response))
				.catch((response) => this.afterRefreshError(response))
			;
		}

		afterRefresh(response)
		{
			this.loader.hide();

			this.refreshErrorNoticeFlag = false;
			ChatTimer.stop('recent', 'error', true);

			Counters.update();

			this.ready();
		}

		afterRefreshError(response)
		{
			const firstErrorKey = Object.keys(response)[0];
			if (firstErrorKey)
			{
				const firstError = response[firstErrorKey].error();
				if (firstError.ex.error === 'REQUEST_CANCELED')
				{
					Logger.error('Messenger.afterRefreshError', firstError.ex);
					return;
				}
			}

			const secondsBeforeRefresh = this.refreshAfterErrorInterval / 1000;
			Logger.error('Messenger: refresh error. Try again in ' + secondsBeforeRefresh + ' seconds.');

			clearTimeout(this.refreshTimeout);

			this.refreshTimeout = setTimeout(() =>
			{
				if (!this.refreshErrorNoticeFlag && !Application.isBackground())
				{
					const notifyRefreshError = () => {
						this.refreshErrorNoticeFlag = true;

						InAppNotifier.showNotification({
							message: Loc.getMessage('IMMOBILE_MESSENGER_REFRESH_ERROR'),
							backgroundColor: '#E6000000',
							time: this.refreshAfterErrorInterval / 1000 - 2,
						});
					};

					ChatTimer.start('recent', 'error', 2000, notifyRefreshError);
				}

				Logger.warn('Messenger.refresh after error');
				this.refresh();

			}, this.refreshAfterErrorInterval);
		}

		ready()
		{
			this.isReady = true;

			if (this.isFirstLoad)
			{
				Logger.warn('Messenger.ready');
				EntityReady.ready('chat');
			}

			this.isFirstLoad = false;

			MessengerEmitter.emit(EventType.messenger.afterRefreshSuccess);
		}

		/* endregion initiation */


		/* region event handlers */

		openDialog(options = {})
		{
			Logger.info('EventType.messenger.openDialog', options);
			if (options.dialogId)
			{
				options.dialogId = options.dialogId.toString();
			}

			//TODO: transfer the list of calls to the model and transfer the work with calls to the integration class
			if (options.callId && !options.dialogId)
			{
				const call = this.recent.getCallById('call' + options.callId);
				if (!call)
				{
					return;
				}

				const dialogId = get(call, 'params.call.associatedEntity.id', null);
				if (!dialogId)
				{
					return;
				}

				options.dialogId = String(dialogId);
			}

			this.dialog = new Dialog({
				storeManager: this.storeManager,
			});

			this.dialog.open(options);
		}

		closeDialog(dialogId)
		{
			Logger.info('EventType.messenger.closeDialog', dialogId);
		}

		openLine(options)
		{
			Logger.info('EventType.messenger.openLine', options);

			this.dialog = new Dialog({
				storeManager: this.storeManager,
			});

			this.dialog.openLine(options);
		}

		openNotifications()
		{
			if (!PageManager.getNavigator().isActiveTab())
			{
				PageManager.getNavigator().makeTabActive();
			}

			BX.postComponentEvent('onTabChange', ['notifications'], 'im.navigation');
		}

		openChatSearch()
		{
			Logger.log('EventType.messenger.showSearch');

			this.dialogSelector.open();
		}

		openChatCreate()
		{
			Logger.log('EventType.messenger.createChat');
			if (this.dialogCreator !== null)
			{
				this.dialogCreator.open();

				return;
			}

			this.chatCreator.open();
		}


		onNotificationOpen()
		{
			Logger.log('EventType.notification.open');

			Counters.notificationCounter.reset();
			Counters.update();
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

		onCallInactive(callId)
		{
			Logger.log('EventType.call.inactive');

			this.recent.removeCallById(callId);
		}

		onTaskStatusSuccess(taskId, result)
		{
			Logger.log('EventType.chatDialog.taskStatusSuccess', taskId, result);
		}

		getOpenDialogParams(options = {})
		{
			const openDialogParamsResponseEvent = EventType.messenger.openDialogParams + '::' + options.dialogId;

			const params = Dialog.getOpenDialogParams(options);
			BX.postComponentEvent(openDialogParamsResponseEvent, [ params ]);
		}

		getOpenLineParams(options = {})
		{
			const openLineParamsResponseEvent = EventType.messenger.openLineParams + '::' + options.userCode;

			Dialog.getOpenLineParams(options).then(params => {
				BX.postComponentEvent(openLineParamsResponseEvent, [ params ]);
			});
		}

		/* region legacy dialog integration */

		onChatDialogInitComplete(event)
		{
			Logger.log('EventType.chatDialog.initComplete', event);

			Promotion.checkDialog(event.dialogId.toString());
		}

		onChatDialogCounterChange(event)
		{
			Logger.log('EventType.chatDialog.counterChange', event);

			const recentItem = ChatUtils.objectClone(this.store.getters['recentModel/getById'](event.dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.counter = event.counter;

			this.store.dispatch('recentModel/set', [ recentItem ]);
		}

		onChatDialogAccessError()
		{
			Logger.warn('EventType.chatDialog.accessError');

			this.dialog.deleteCurrentDialog();
		}

		/* endregion legacy dialog integration */

		/* endregion event handlers */

		showProgress()
		{
			dialogList.setTitle({
				text: Loc.getMessage('COMPONENT_TITLE'),
				useProgress: true,
				largeMode: true,
			});
		}

		hideProgress()
		{
			dialogList.setTitle({
				text: Loc.getMessage('COMPONENT_TITLE'),
				useProgress: false,
				largeMode: true,
			});
		}

		//TODO: Remove after database manager implementation
		initCache()
		{
			const recentState = RecentCache.get();
			const usersState = UsersCache.get();
			const filesState = FilesCache.get();

			const cachePromiseList = [];

			if (recentState)
			{
				cachePromiseList.push(this.store.dispatch('recentModel/setState', recentState));
			}

			if (usersState)
			{
				cachePromiseList.push(this.store.dispatch('usersModel/setState', usersState));
			}

			if (filesState)
			{
				cachePromiseList.push(this.store.dispatch('filesModel/setState', filesState));
			}

			return Promise.all(cachePromiseList);
		}

		checkRevision(response)
		{
			const error = response.error();
			if (error)
			{
				Logger.error('Messenger.checkRevision', error);

				return;
			}

			const actualRevision = response.data().mobile;
			if (!Type.isNumber(actualRevision) || REVISION >= actualRevision)
			{
				Logger.warn('Messenger.checkRevision: current', REVISION, 'actual', actualRevision);

				return true;
			}

			Logger.warn(
				'Messenger.checkRevision: reload scripts because revision up',
				REVISION,
				' -> ',
				actualRevision
			);

			reloadAllScripts();

			return false;
		}

		destructor()
		{
			BX.listeners = {};

			console.warn('Messenger: Garbage collection after refresh complete');
		}
	}

	window.messenger = new Messenger();
})();