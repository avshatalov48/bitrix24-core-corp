'use strict';

(() => {
	console.log('Navigation is loaded.');
	const require = jn.require;

	const { EntityReady } = require('entity-ready');
	const { AnalyticsEvent } = require('analytics');
	const { Analytics, EventType, ComponentCode, NavigationTab } = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Feature } = require('im/messenger/lib/feature');

	const tabIdCollection = {
		[ComponentCode.imMessenger]: NavigationTab.imMessenger,
		[ComponentCode.imChannelMessenger]: NavigationTab.imChannelMessenger,
		[ComponentCode.imCopilotMessenger]: NavigationTab.imCopilotMessenger,
		[ComponentCode.imCollabMessenger]: NavigationTab.imCollabMessenger,
		[ComponentCode.imNotify]: NavigationTab.imNotify,
		[ComponentCode.imOpenlinesRecent]: NavigationTab.imOpenlinesRecent,
	};

	class NavigationManager
	{
		constructor()
		{
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

			this.currentTab = BX.componentParameters.get('firstTabId', 'chats');
			this.previousTab = 'none';

			this.tabMapping = {
				chats: ComponentCode.imMessenger,
				channel: ComponentCode.imChannelMessenger,
				copilot: ComponentCode.imCopilotMessenger,
				collab: ComponentCode.imCollabMessenger,
				notifications: ComponentCode.imNotify,
				openlines: ComponentCode.imOpenlinesRecent,
			};
			this.componentMapping = null;

			// navigation
			tabs.on('onTabSelected', this.onTabSelected.bind(this));
			BX.addCustomEvent('onTabChange', this.onTabChange.bind(this));

			// counters
			BX.addCustomEvent('ImRecent::counter::list', this.onUpdateCounters.bind(this));
			BX.addCustomEvent('onUpdateCounters', this.onUpdateCounters.bind(this));
			BX.addCustomEvent(EventType.navigation.broadCastEventWithTabChange, this.onBroadcastEvent.bind(this));
			BX.addCustomEvent(EventType.navigation.changeTab, this.changeTabHandler.bind(this));
			BX.addCustomEvent(EventType.app.active, this.onAppActive.bind(this));
			BX.postComponentEvent('requestCounters', [{ component: 'im.navigation' }], 'communication');
			BX.addCustomEvent('onTabsSelected', this.onRootTabsSelected.bind(this));

			EntityReady.ready('im.navigation');

			if (PageManager.getNavigator().isActiveTab())
			{
				this.sendAnalyticsOpenRootTabChat();
				this.sendAnalyticsChangeTab();
			}

			this.isReady = true;
		}

		onAppActive()
		{
			if (PageManager.getNavigator().isActiveTab())
			{
				this.sendAnalyticsOpenRootTabChat();
			}
		}

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

		changeTabHandler(componentCode)
		{
			if (!tabIdCollection[componentCode])
			{
				BX.postComponentEvent(EventType.navigation.changeTabResult, [{
					componentCode,
					errorText: `im.navigation: Error changing tab, tab ${componentCode} does not exist.`,
				}]);

				return;
			}

			if (
				componentCode === ComponentCode.imCopilotMessenger
				&& !BX.componentParameters.get('IS_COPILOT_AVAILABLE', false)
			)
			{
				BX.postComponentEvent(EventType.navigation.changeTabResult, [{
					componentCode,
					errorText: `im.navigation: Error changing tab, tab ${componentCode} is disabled.`,
				}]);

				return;
			}

			if (
				componentCode === ComponentCode.imCollabMessenger
				&& !Feature.isCollabSupported
			)
			{
				this.handleCollabsAreNotSupported();

				BX.postComponentEvent(EventType.navigation.changeTabResult, [{
					componentCode,
					errorText: `im.navigation: Error changing tab, tab ${componentCode} is disabled.`,
				}]);

				return;
			}

			tabs.setActiveItem(tabIdCollection[componentCode]);

			PageManager.getNavigator().makeTabActive();

			BX.postComponentEvent(EventType.navigation.changeTabResult, [{
				componentCode,
			}]);
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

			if (
				item.id === NavigationTab.imCollabMessenger
				&& !Feature.isCollabSupported
			)
			{
				this.handleCollabsAreNotSupported();

				return;
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
			this.sendAnalyticsChangeTab();
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
				this.sendAnalyticsOpenRootTabChat();
				this.sendAnalyticsChangeTab();
			}
		}

		sendAnalyticsChangeTab()
		{
			if (this.currentTab === 'copilot') // TODO delete this, when will be universal event like below
			{
				const analytics = new AnalyticsEvent()
					.setTool(Analytics.Tool.ai)
					.setCategory(Analytics.Category.chatOperations)
					.setEvent(Analytics.Event.openTab)
					.setSection(Analytics.Section.copilotTab);

				analytics.send();
			}

			const type = this.currentTab === 'chats' ? Analytics.Type.chat : Analytics.Type[this.currentTab];
			const analytics = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.messenger)
				.setEvent(Analytics.Event.openTab)
				.setType(type);

			analytics.send();
		}

		sendAnalyticsOpenRootTabChat()
		{
			const analytics = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.messenger)
				.setEvent(Analytics.Event.openMessenger);

			analytics.send();
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
				const counter = this.counters[tab] ? this.counters[tab] : 0;
				tabs.updateItem(tab, {
					counter,
					label: counter ? counter.toString() : '',
				});
			});
		}

		/**
		 *
		 * @param {{
		 * 	broadCastEvent: string,
		 * 	toTab: string,
		 * 	data: Object,
		 * }} params
		 */
		onBroadcastEvent(params)
		{
			if (!tabIdCollection[params.toTab])
			{
				return;
			}
			console.info(`${this.constructor.name}.onBroadcastEvent params:`, params);

			if (
				params.toTab === ComponentCode.imCollabMessenger
				&& !Feature.isCollabSupported
			)
			{
				this.handleCollabsAreNotSupported();
			}
			else
			{
				tabs.setActiveItem(tabIdCollection[params.toTab]);
			}

			MessengerEmitter.emit(params.broadCastEvent, params.data, params.toTab);
		}

		handleCollabsAreNotSupported()
		{
			console.log(`${this.constructor.name}.handleCollabsAreNotSupported`);

			tabs.setActiveItem(tabIdCollection[ComponentCode.imMessenger]);
			Feature.showUnsupportedWidget();
			this.currentTab = NavigationTab.imMessenger;
		}
	}

	window.Navigation = new NavigationManager();
})();
