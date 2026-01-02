'use strict';

/* global tabs */
(async () => {
	console.log('Navigation is loaded.');
	const require = jn.require;

	window.messengerDebug = {};

	/**
	 * @description Import MessengerParams and initSharedParams should be higher than other imports.
	 */
	const { MessengerParams } = require('im/messenger/lib/params');
	await MessengerParams.initSharedParams();

	const { MemoryStorage } = require('native/memorystore');
	const { EntityReady } = require('entity-ready');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { NavigationApplication } = require('im/messenger/core/navigation');

	const core = new NavigationApplication({
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
		console.error('NavigationApplication init error:', error);
	}
	serviceLocator.add('core', core);

	const emitter = new JNEventEmitter();
	serviceLocator.add('emitter', emitter);

	const { NotifyManager } = require('notify-manager');

	const {
		EventType,
		ComponentCode,
		NavigationTab,
		NavigationTabByComponent,
		WaitingEntity,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Feature } = require('im/messenger/lib/feature');

	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { ConnectionService } = require('im/messenger/provider/services/connection');
	const { NavigationCounterHandler } = require('im/messenger/lib/counters/counter-manager/navigation');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');

	// TODO remove after updating freeze debug
	window.syncRequestResultDebugInfo = {
		result: null,
		isViewLoaded: null,
		readyEntitiesCollection: [],
		isSyncFillerReady: null,
	};

	BX.addCustomEvent(EventType.sync.requestResultReceived, (result) => {
		window.syncRequestResultDebugInfo.result = result;
		window.syncRequestResultDebugInfo.isViewLoaded = window.isViewLoaded;
		window.syncRequestResultDebugInfo.readyEntitiesCollection = [...EntityReady.readyEntitiesCollection];
		window.syncRequestResultDebugInfo.isSyncFillerReady = window?.Navigation?.counterHandler?.syncHandler?.isReady;
	});

	class NavigationManager
	{
		#currentTab;

		constructor()
		{
			this.core = core;
			this.isReady = false;
			this.isViewReady = false;
			EntityReady.addCondition('im.navigation', () => this.isReady);
			EntityReady.addCondition('im.navigation::view', () => this.isViewReady);

			BX.onViewLoaded(() => {
				this.isViewReady = true;
				EntityReady.ready('im.navigation::view');
			});

			this.firstSetBadge = true;
			this.counters = {};

			this.tabMapping = {
				chats: ComponentCode.imMessenger,
				channel: ComponentCode.imChannelMessenger,
				copilot: ComponentCode.imCopilotMessenger,
				collab: ComponentCode.imCollabMessenger,
				notifications: ComponentCode.imNotify,
				openlines: ComponentCode.imOpenlinesRecent,
			};
			this.componentMapping = null;

			this.#currentTab = BX.componentParameters.get('firstTabId', 'chats');
			this.previousTab = 'none';

			this.visibilityManager = VisibilityManager.getInstance();
			this.counterHandler = NavigationCounterHandler.getInstance();
			this.connectionService = ConnectionService.getInstance();

			this.init();

			this.isReady = true;
			this.currentTabOptions = {};
		}

		init()
		{
			void this.saveActiveTabInfo();
			this.bindMethods();
			this.subscribeEvents();

			this.enableMessengerApi()
				.catch((error) => {
					console.error('im.navigation: enable external api error', error);
				})
				.finally(() => {
					EntityReady.ready('im.navigation');
				})
			;

			if (PageManager.getNavigator().isActiveTab())
			{
				this.sendAnalyticsChangeTab();
			}
		}

		/**
		 * @override
		 */
		bindMethods()
		{
			this.onTabSelected = this.onTabSelected.bind(this);
			this.onTabChange = this.onTabChange.bind(this);
			this.onUpdateCounters = this.onUpdateCounters.bind(this);
			this.onUpdateCounters = this.onUpdateCounters.bind(this);
			this.onBroadcastEventTabChange = this.onBroadcastEventTabChange.bind(this);
			this.onBroadcastEventCheckTabPreload = this.onBroadcastEventCheckTabPreload.bind(this);
			this.changeTabHandler = this.changeTabHandler.bind(this);
			this.onAppActive = this.onAppActive.bind(this);
			this.onRootTabsSelected = this.onRootTabsSelected.bind(this);
			this.closeAll = this.closeAll.bind(this);
			this.onAppStatusChange = this.onAppStatusChange.bind(this);
		}

		/**
		 * @override
		 */
		subscribeEvents()
		{
			// navigation
			tabs.on('onTabSelected', this.onTabSelected);
			BX.addCustomEvent('onTabChange', this.onTabChange);

			// counters
			BX.addCustomEvent('ImRecent::counter::list', this.onUpdateCounters);
			BX.addCustomEvent('onUpdateCounters', this.onUpdateCounters);
			BX.addCustomEvent(EventType.navigation.broadCastEventWithTabChange, this.onBroadcastEventTabChange);
			BX.addCustomEvent(EventType.navigation.broadCastEventCheckTabPreload, this.onBroadcastEventCheckTabPreload);
			BX.addCustomEvent(EventType.navigation.changeTab, this.changeTabHandler);
			BX.addCustomEvent(EventType.app.active, this.onAppActive);
			BX.postComponentEvent('requestCounters', [{ component: 'im.navigation' }], 'communication');
			BX.addCustomEvent(EventType.navigation.onRootTabsSelected, this.onRootTabsSelected);
			BX.addCustomEvent(EventType.navigation.closeAll, this.closeAll.bind(this));
			BX.addCustomEvent(EventType.app.changeStatus, this.onAppStatusChange);
		}

		set currentTab(tab)
		{
			this.#currentTab = tab;

			void this.saveActiveTabInfo();
		}

		get currentTab()
		{
			return this.#currentTab;
		}

		saveActiveTabInfo()
		{
			return this.visibilityManager.saveActiveTabInfo({
				tabId: this.currentTab,
				componentCode: this.tabMapping[this.currentTab],
			});
		}

		onAppActive()
		{}

		onTabChange(id)
		{
			if (
				id === 'none'
				|| this.currentTab === id
			)
			{
				return;
			}
			console.log(`${this.constructor.name}.onTabChange id:`, id);

			if (!PageManager.getNavigator().isActiveTab())
			{
				PageManager.getNavigator().makeTabActive();
			}

			BX.onViewLoaded(() => {
				setTimeout(() => {
					const previousTab = this.currentTab;

					tabs.setActiveItem(id);
					this.currentTab = tabs.getCurrentItem() ? tabs.getCurrentItem()?.id : 'chats';
					if (this.currentTab !== previousTab)
					{
						this.previousTab = previousTab;
					}
				}, 100); // TODO change when makeTabActive will return a promise
			});
		}

		changeTabHandler(componentCode, options = {})
		{
			if (!NavigationTabByComponent[componentCode])
			{
				BX.postComponentEvent(EventType.navigation.changeTabResult, [{
					componentCode,
					errorText: `im.navigation: Error changing tab, tab ${componentCode} does not exist.`,
				}]);

				return;
			}

			if (
				componentCode === ComponentCode.imCopilotMessenger
				&& !BX.componentParameters.get('IM_FEATURES', {}).copilotActive
			)
			{
				BX.postComponentEvent(EventType.navigation.changeTabResult, [{
					componentCode,
					errorText: `im.navigation: Error changing tab, tab ${componentCode} is disabled.`,
				}]);

				return;
			}

			this.currentTabOptions = options;

			if (Application.getApiVersion() >= 61)
			{
				PageManager.getNavigator().makeTabActive()
					.then(() => {
						tabs.setActiveItem(NavigationTabByComponent[componentCode]);

						BX.postComponentEvent(EventType.navigation.changeTabResult, [{
							componentCode,
						}]);
					})
					.catch((error) => {
						console.error(error);
					});
			}
			else
			{
				tabs.setActiveItem(NavigationTabByComponent[componentCode]);

				PageManager.getNavigator().makeTabActive();

				BX.postComponentEvent(EventType.navigation.changeTabResult, [{
					componentCode,
				}]);
			}
		}

		async closeAll()
		{
			return new Promise((resolve, reject) => {
				PageManager.getNavigator().popTo('im.tabs')
					.then(() => {
						BX.postComponentEvent(EventType.navigation.closeAllComplete, [{ isSuccess: true }]);
						resolve();
					})
					.catch((error) => {
						BX.postComponentEvent(EventType.navigation.closeAllComplete, [{ isSuccess: false }]);

						reject(new Error(`NavigationManager.closeAll error: ${error}`));
					});
			});
		}

		onTabSelected(item, changed)
		{
			if (!changed)
			{
				console.log(`${this.constructor.name}.onTabSelected select active element:`, this.currentTab);

				return true;
			}

			if (this.currentTab === item.id)
			{
				console.log(`${this.constructor.name}.onTabSelected selected tab is equal current, this.currentTab:`, this.currentTab, item.id);

				return true;
			}

			this.previousTab = this.currentTab;
			this.currentTab = item.id;

			console.warn(`${this.constructor.name}.onTabSelected tabs:`, {
				current: this.currentTab,
				previous: this.previousTab,
			}, item, changed);

			BX.postComponentEvent(EventType.navigation.tabChanged, [{
				newTab: this.currentTab,
				previousTab: this.previousTab,
			}]);

			const { analytics = {} } = this.currentTabOptions || {};
			this.sendAnalyticsChangeTab(analytics);

			this.currentTabOptions = {};
		}

		/**
		 * @param {String} id
		 */
		onRootTabsSelected(id)
		{
			console.log(`${this.constructor.name}.onRootTabsSelected id:`, id);

			const rootTabChatName = 'chats';
			if (id === rootTabChatName)
			{
				this.sendAnalyticsChangeTab();
			}
		}

		sendAnalyticsChangeTab(analyticsOptions = {})
		{
			AnalyticsService.getInstance().sendChangeNavigationTab(this.currentTab, analyticsOptions);
		}

		async onUpdateCounters(counters, delay)
		{
			await EntityReady.wait('im.navigation::view');
			let needUpdate = false;
			const params = { ...counters };
			console.info(`${this.constructor.name}.onUpdateCounters params:`, params, delay);

			for (const element in params)
			{
				if (!params.hasOwnProperty(element))
				{
					continue;
				}

				params[element] = Number(params[element]);

				if (Number.isNaN(params[element]))
				{
					continue;
				}

				if (this.counters[element] == params[element])
				{
					continue;
				}

				this.counters[element] = params[element];
				needUpdate = true;
			}

			if (needUpdate)
			{
				this.updateCounters(delay === false);
			}
		}

		getComponentCodeByTab(tabId)
		{
			return this.tabMapping[tabId];
		}

		getTabCodeByComponent(componentId)
		{
			if (this.componentMapping)
			{
				return this.componentMapping[componentId];
			}

			for (const tabId in this.tabMapping)
			{
				if (!this.tabMapping.hasOwnProperty(tabId))
				{
					continue;
				}

				const componentId = this.tabMapping[tabId];
				this.componentMapping[componentId] = tabId;
			}

			return this.componentMapping[componentId];
		}

		updateCounters(delay)
		{
			if (delay && !this.firstSetBadge)
			{
				if (!this.updateCountersTimeout)
				{
					this.updateCountersTimeout = setTimeout(this.update.bind(this), 300);
				}

				return true;
			}

			this.firstSetBadge = true;

			clearTimeout(this.updateCountersTimeout);
			this.updateCountersTimeout = null;

			console.info(`${this.constructor.name}.updateCounters counters:`, this.counters, delay);

			[
				NavigationTab.imMessenger,
				NavigationTab.imOpenlinesRecent,
				NavigationTab.imNotify,
				NavigationTab.imCopilotMessenger,
				NavigationTab.imCollabMessenger,
			].forEach((tab) => {
				const counter = this.counters[tab] ?? 0;
				tabs.updateItem(tab, {
					counter,
					label: counter ? counter.toString() : '',
				});
			});
		}

		/**
		 * @param {onBroadcastEventParams} [params]
		 */
		async onBroadcastEventTabChange(params)
		{
			const componentCodeByTab = this.getComponentCodeByTab(params.toTab);
			if (!componentCodeByTab)
			{
				console.error(`${this.constructor.name}.onBroadcastEventTabChange error - missing componentCode for ${params.toTab}`);

				return;
			}
			console.info(`${this.constructor.name}.onBroadcastEventTabChange params:`, params);

			await this.handleBroadcastEventSetActiveItem(params.toTab, componentCodeByTab);
			MessengerEmitter.emit(params.broadCastEvent, params.data, componentCodeByTab);
		}

		/**
		 * @param {onBroadcastEventParams} [params]
		 */
		async onBroadcastEventCheckTabPreload(params)
		{
			const componentCodeByTab = this.getComponentCodeByTab(params.toTab);
			if (!componentCodeByTab)
			{
				console.error(`${this.constructor.name}.onBroadcastEventCheckTabPreload error - missing componentCode for ${params.toTab}`);

				return;
			}
			console.info(`${this.constructor.name}.onBroadcastEventCheckTabPreload params:`, params);

			if ([NavigationTab.imMessenger, NavigationTab.imOpenlinesRecent, NavigationTab.imNotify].includes(params.toTab))
			{
				MessengerEmitter.emit(params.broadCastEvent, params.data, componentCodeByTab);

				return;
			}

			const emitData = params.data;
			const ready = EntityReady.isReady(`${componentCodeByTab}::launched`);
			if (!ready)
			{
				await this.checkIsOpenDialogByComponentLaunched(componentCodeByTab);

				emitData.backToMessenegerTab = true;
			}

			MessengerEmitter.emit(params.broadCastEvent, params.data, componentCodeByTab);
		}

		/**
		 * @param {NavigationTab} navigationTab
		 * @param {ComponentCode} componentCode
		 */
		handleBroadcastEventSetActiveItem(navigationTab, componentCode)
		{
			console.log(`${this.constructor.name}.handleBroadcastEventSetActiveItem:`, navigationTab);

			if (this.currentTab === navigationTab)
			{
				return true;
			}

			if ([NavigationTab.imMessenger, NavigationTab.imOpenlinesRecent, NavigationTab.imNotify].includes(navigationTab))
			{
				tabs.setActiveItem(navigationTab);

				return true;
			}

			const ready = EntityReady.isReady(`${componentCode}::launched`);
			if (!ready)
			{
				return this.checkIsOpenDialogByComponentLaunched(componentCode);
			}

			tabs.setActiveItem(navigationTab);

			return true;
		}

		/**
		 * @param {ComponentCode} componentCode
		 */
		async checkIsOpenDialogByComponentLaunched(componentCode)
		{
			try
			{
				if (!Feature.isIOSChangeTabInBackgroundAvailable)
				{
					Feature.showUnsupportedWidget();

					return;
				}

				void NotifyManager.showLoadingIndicator();

				tabs.setActiveItem(NavigationTabByComponent[componentCode]);

				await EntityReady.wait(`${componentCode}::ready`);

				NotifyManager.hideLoadingIndicatorWithoutFallback();
			}
			catch (error)
			{
				NotifyManager.hideLoadingIndicatorWithoutFallback();
				console.error(`${this.constructor.name}.checkIsOpenDialogByComponentLaunched catch:`, error);
			}
		}

		/**
		 * @param {{name: string, value: string}} event
		 */
		onAppStatusChange(event)
		{
			core.setAppStatus(event.name, event.value);
		}

		async enableMessengerApi()
		{
			const memoryStorage = new MemoryStorage('immobile::external-api');
			await memoryStorage.set('apiVersion', 1);

			EntityReady.addCondition(WaitingEntity.externalApi, () => true);
			EntityReady.ready(WaitingEntity.externalApi);
		}
	}

	window.showMessengerDeveloperMenu = () => {
		jn.import('im:messenger/lib/dev/menu')
			.then(() => {
				const { showDeveloperMenu } = require('im/messenger/lib/dev/menu');
				showDeveloperMenu();
			})
			.catch((error) => {
				// eslint-disable-next-line no-console
				console.error(error);
			})
		;
	};

	window.openMessengerDeveloperConsole = async (componentCode = MessengerParams.getComponentCode()) => {
		if (componentCode === MessengerParams.getComponentCode())
		{
			const { Console } = await requireLazy('im:messenger/lib/dev/tools');
			Console.open();

			return;
		}

		BX.postComponentEvent(EventType.messenger.dev.openConsole, [], componentCode);
	};

	BX.addCustomEvent(EventType.messenger.dev.openConsole, () => {
		window.openMessengerDeveloperConsole();
	});

	window.Navigation = new NavigationManager();
})().catch((error) => {
	console.error(error);
});
