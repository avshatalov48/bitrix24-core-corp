// eslint-disable-next-line no-var,no-implicit-globals
var REVISION = 19; // API revision - sync with im/lib/revision.php

/* region Environment variables */

// use in immobile/install/mobileapp/immobile/extensions/im/chat/messengercommon/extension.js:532
BX.message.LIMIT_ONLINE = BX.componentParameters.get('LIMIT_ONLINE', 1380);

/* endregion Environment variables */

/* region Clearing session variables after script reload */

if (typeof window.messenger !== 'undefined' && typeof window.messenger.destructor !== 'undefined')
{
	window.messenger.destructor();
}

/* endregion Clearing session variables after script reload */

(async () => {
	/* global dialogList, ChatTimer, InAppNotifier, ChatUtils, reloadAllScripts */
	/* region import */
	const require = (ext) => jn.require(ext); // for IDE hints

	const { Logger } = require('im/messenger/lib/logger');
	const { CollabApplication } = require('im/messenger/core/collab');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { EntityReady } = require('entity-ready');

	const core = new CollabApplication({
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
		Logger.error('CollabApplication init error: ', error);
	}
	serviceLocator.add('core', core);

	const { MessengerBase } = require('im/messenger/component/messenger-base');

	/* endregion import */

	class CollabMessenger extends MessengerBase
	{
		/* region initiation */

		/**
		 * @class CollabMessenger - mobile collab messenger tab entry point
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

			EntityReady.addCondition('collab-messenger', () => this.isReady);
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
		{}

		/**
		 * @override
		 */
		async initComponents()
		{}

		/**
		 * @override
		 */
		bindMethods()
		{}

		preloadAssets()
		{}

		/**
		 * @override
		 */
		subscribeMessengerEvents()
		{}

		unsubscribeMessengerEvents()
		{}

		/**
		 * @override
		 */
		subscribeExternalEvents()
		{}

		/**
		 * @override
		 */
		unsubscribeExternalEvents()
		{}

		/**
		 * @override
		 */
		initCustomServices()
		{}

		/**
		 * @override
		 */
		redrawHeader()
		{}

		/**
		 * @override
		 */
		initPullHandlers()
		{}

		/**
		 * @override
		 */
		async initCurrentUser()
		{}

		/**
		 * @override
		 */
		async initQueueRequests()
		{}

		onAppActiveBefore()
		{}

		/**
		 * @override
		 */
		async refresh(redrawHeaderTruly)
		{}

		queueCallBatch()
		{}

		clearRequestQueue(response, withTemporaryMessage = false)
		{}

		async afterRefresh()
		{}

		async afterRefreshError(response)
		{}

		async ready()
		{}

		/* endregion initiation */

		/* region event handlers */

		openDialog(options = {})
		{}

		openChatSearch()
		{}

		closeChatSearch()
		{}

		openChatCreate()
		{}

		onNotificationReload()
		{}

		onTaskStatusSuccess(taskId, result)
		{}

		getOpenDialogParams(options = {})
		{}

		/**
		 * @private
		 * @param {{id: string, value: any}} setting
		 */
		onChatSettingChange(setting)
		{}

		/* region legacy dialog integration */

		onChatDialogInitComplete(event)
		{}

		onChatDialogCounterChange(event)
		{}

		onChatDialogAccessError()
		{}
		/* endregion legacy dialog integration */

		/* endregion event handlers */

		checkRevision(response)
		{}

		destructor()
		{}

		executeStoredPullEvents()
		{}

		/**
		 * @param {{name: string, value: string}} event
		 */
		onAppStatusChange(event)
		{}
	}

	window.messenger = new CollabMessenger();
})().catch((error) => {
	console.error('Messenger init error', error);
});
