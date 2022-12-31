"use strict";
(()=>{

	console.log('Navigation is loaded.');

	const { EntityReady } = jn.require('entity-ready');

	class NavigationManager
	{
		constructor()
		{
			this.isReady = false;
			EntityReady.addCondition('im.navigation', () => this.isReady);

			this.firstSetBadge = true;
			this.counters = {};

			this.currentTab = BX.componentParameters.get('firstTabId', 'chats');
			this.previousTab = 'none';

			this.tabMapping = {
				'chats': 'im.messenger',
				'openlines': 'im.openlines.recent',
				'notifications': 'im.notify',
			};
			this.componentMapping = null;

			// navigation
			tabs.on("onTabSelected", this.onTabSelected.bind(this));
			BX.addCustomEvent("onTabChange", this.onTabChange.bind(this));

			// counters
			BX.addCustomEvent("ImRecent::counter::list", this.onUpdateCounters.bind(this));
			BX.addCustomEvent("onUpdateCounters", this.onUpdateCounters.bind(this));
			BX.postComponentEvent("requestCounters", [{component: 'im.navigation'}], "communication");


			EntityReady.ready('im.navigation');
			this.isReady = true;
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

			if (!PageManager.getNavigator().isActiveTab())
			{
				PageManager.getNavigator().makeTabActive();
			}

			BX.onViewLoaded(() =>
			{
				console.log('onTabChange', 'change tab', id);

				const previousTab = this.currentTab;
				tabs.setActiveItem(id);
				this.currentTab = tabs.getCurrentItem();

				if (this.currentTab !== previousTab)
				{
					this.previousTab = previousTab;
				}
			});
		}

		onTabSelected(item, changed)
		{
			if (!changed)
			{
				console.log('onTabSelected', 'select active element', this.currentTab);
				return true;
			}

			this.previousTab = this.currentTab;
			this.currentTab = item.id;

			console.warn('onTabSelected', 'select element', {
				current: this.currentTab,
				previous: this.previousTab,
			});
		}

		onUpdateCounters(counters, delay)
		{
			let needUpdate = false;
			let params = Object.assign({}, counters);

			for (let element in params)
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

			console.info("AppCounters.update: update counters:", this.counters);

			['chats', 'openlines', 'notifications'].forEach(tab =>
			{
				const counter = this.counters[tab]? this.counters[tab]: 0;
				tabs.updateItem(tab, {
					counter: counter,
					label: counter? counter.toString(): '',
				});
			});
		}
	}

	window.Navigation = new NavigationManager();

})();