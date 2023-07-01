(() => {
	const require = (ext) => jn.require(ext);

	const { PureComponent } = require('layout/pure-component');
	const { PlanRestriction } = require('layout/ui/plan-restriction');
	const {
		clone,
		get,
		isEqual,
		merge,
		mergeImmutable,
	} = require('utils/object');
	const { throttle } = require('utils/function');
	const { KanbanTab } = require('crm/entity-tab/kanban');
	const { ListTab } = require('crm/entity-tab/list');
	const { Search } = require('crm/entity-tab/search');
	const { ActivityCountersStoreManager } = require('crm/state-storage');
	const { Type } = require('crm/type');
	const { LoadingProgressBar } = require('crm/ui/loading-progress');

	const TAB_BIG_LABEL = '99+';

	/**
	 * @class CrmTabs
	 */
	class CrmTabs extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.kanbanTabRef = null;
			this.listTabRef = null;
			this.searchRef = null;
			this.tabsCacheName = `crm:crm.tabs.list.${env.userId}.v2`;
			this.tabViewRef = null;
			this.pullUnsubscribe = null;

			const data = this.getTabsFromCache();
			const {
				tabs = [],
				connectors = {},
				permissions = {},
				restrictions = {},
			} = data;

			this.prepareTabs(props, tabs);
			const activeTab = tabs.find((tab) => tab.active);

			this.onPanBySearch = this.onPanBySearchHandler.bind(this);

			this.state = {
				tabs,
				activeTabTypeName: activeTab && activeTab.typeName,
				activeTabId: activeTab && activeTab.id,
				permissions,
				connectors,
				restrictions,
				isProgress: false,
			};

			this.currentMoneyFormats = Money.formats;
			this.rightButtonsIsSetted = false;

			this.userInfo = null;

			this.loadTabs();

			this.onTabsReSelected = this.onTabsReSelected.bind(this);
			this.onMoneyLoad = this.onMoneyLoad.bind(this);
			this.loadTabs = this.loadTabs.bind(this);
			this.reloadKanbanTab = this.reloadKanbanTab.bind(this);
			this.updateCounters = this.updateCounters.bind(this);
			this.setActiveTab = this.setActiveTab.bind(this);
			this.updateEntityTypeData = this.updateEntityTypeData.bind(this);
			this.onLoadingProgress = this.onLoadingProgress.bind(this);

			this.scrollOnTop = throttle(this.scrollOnTop, 500, this);
		}

		componentDidMount()
		{
			this.pullUnsubscribe = BX.PULL.subscribe({
				moduleId: 'main',
				callback: (data) => {
					if (data.command === 'user_counter')
					{
						this.updateTabCounters(data.params);
					}
				},
			});

			this.bindEvents();
		}

		updateTabCounters(data)
		{
			const counters = data[env.siteId] || null;
			if (!counters)
			{
				return;
			}

			const preparedCounters = {};
			for (const counter in counters)
			{
				if (counters[counter] >= 0)
				{
					preparedCounters[counter] = counters[counter];
				}
			}

			if (Object.keys(preparedCounters).length > 0)
			{
				ActivityCountersStoreManager.setCounters(preparedCounters);
			}
		}

		bindEvents()
		{
			BX.addCustomEvent('onTabsReSelected', this.onTabsReSelected);
			BX.addCustomEvent('Money::onLoad', this.onMoneyLoad);
			BX.addCustomEvent('CrmTabs::loadTabs', this.loadTabs);
			BX.addCustomEvent('CrmTabs::reloadKanbanTab', this.reloadKanbanTab);
			BX.addCustomEvent('CrmTabs::onLoadingProgress', this.onLoadingProgress);

			ActivityCountersStoreManager
				.subscribe('activityCountersModel/setCounters', this.updateCounters)
			;
		}

		componentWillUnmount()
		{
			if (this.pullUnsubscribe)
			{
				this.pullUnsubscribe();
			}

			this.unbindEvents();
		}

		unbindEvents()
		{
			BX.removeCustomEvent('onTabsReSelected', this.onTabsReSelected);
			BX.removeCustomEvent('Money::onLoad', this.onMoneyLoad);
			BX.removeCustomEvent('CrmTabs::loadTabs', this.loadTabs);
			BX.removeCustomEvent('CrmTabs::reloadKanbanTab', this.reloadKanbanTab);
			BX.removeCustomEvent('CrmTabs::onLoadingProgress', this.onLoadingProgress);

			ActivityCountersStoreManager
				.unsubscribe('activityCountersModel/setCounters', this.updateCounters)
			;
		}

		onTabsReSelected(tabName)
		{
			if (tabName === 'crm')
			{
				this.scrollOnTop();
			}
		}

		onMoneyLoad()
		{
			if (!isEqual(this.currentMoneyFormats, Money.formats))
			{
				this.currentMoneyFormats = Money.formats;
				this.setState({});
			}
		}

		updateCounters()
		{
			const counters = ActivityCountersStoreManager.getCounters();
			this.updateTabs(counters);

			if (this.searchRef)
			{
				this.searchRef.updateCounters(counters);
			}
		}

		updateTabs(counters)
		{
			this.state.tabs.forEach((tab) => {
				const code = `crm_${tab.typeName.toLowerCase()}_all`;

				if (counters.hasOwnProperty(code) && counters[code] >= 0)
				{
					const currentValue = BX.prop.getNumber(tab, 'label', 0);
					if (
						counters[code] !== currentValue
						&& (counters[code] <= 99 || currentValue <= 99)
					)
					{
						const item = this.getTabByTypeName(tab.typeName);
						item.label = this.getPreparedLabel(counters[code]);
						const preparedItem = this.getPreparedTabItem(item);
						this.updateTabItem(preparedItem);
					}
				}
			});
		}

		updateTabItem(item)
		{
			this.tabViewRef.updateItem(item.id, item);
		}

		setActiveTab(entityTypeId)
		{
			if (Type.existsById(entityTypeId))
			{
				this.tabViewRef.setActiveItem(Type.resolveNameById(entityTypeId));
			}
		}

		getTabsFromCache()
		{
			const cache = Application.storage.getObject(this.tabsCacheName, {});

			return get(cache, 'data', []);
		}

		modifyCache(params, tabId = null)
		{
			const cacheName = this.tabsCacheName;
			const cache = Application.storage.getObject(cacheName, null);

			if (!(cache && cache.data && cache.data.tabs))
			{
				return null;
			}

			if (tabId === null)
			{
				tabId = this.state.activeTabId;
			}

			const currentTab = cache.data.tabs.find((tab) => tab.id === tabId);
			if (currentTab === null)
			{
				return;
			}

			merge(currentTab, params);
			Application.storage.setObject(cacheName, cache);
		}

		onLoadingProgress(params)
		{
			const { isProgress: stateIsProgress } = this.state;
			const { isProgress, progress } = params;

			if (isProgress !== stateIsProgress)
			{
				this.setState({
					isProgress,
					progressParams: progress,
				});
			}
		}

		getProgressLayout()
		{
			const { progressParams, isProgress } = this.state;

			if (!isProgress)
			{
				return null;
			}

			return new LoadingProgressBar(progressParams);
		}

		render()
		{
			const { isProgress, activeTabTypeName, tabs } = this.state;
			const activeTab = this.getActiveTab();
			if (!activeTab && tabs[0])
			{
				const { hasRestrictions, title, typeName } = tabs[0];

				if (hasRestrictions)
				{
					PlanRestriction.open({ title });
				}
				else
				{
					qrauth.open({
						title,
						redirectUrl: this.getDesktopPageLink(typeName),
					});
				}

				return null;
			}

			return View(
				{
					resizableByKeyboard: true,
				},
				TabView({
					style: {
						height: 44,
						backgroundColor: '#f5f7f8',
					},
					ref: (ref) => this.tabViewRef = ref,
					params: {
						styles: {
							tabTitle: {
								underlineColor: '#207ede',
							},
						},
						items: this.getTabItems(),
					},
					onTabSelected: (tab, changed) => this.handleTabSelected(tab, changed),
				}),
				activeTabTypeName && activeTab && this.renderTab(),
				activeTabTypeName && activeTab && new Search({
					entityTypeName: activeTab.typeName,
					categoryId: activeTab.data.currentCategoryId,
					getSearchDataAction: result.actions.getSearchData,
					link: activeTab.link,
					restrictions: BX.prop.getObject(activeTab.restrictions || {}, 'search', {}),
					layout,
					ref: (ref) => {
						if (ref)
						{
							this.searchRef = ref;
						}
					},
				}),
				isProgress && this.getProgressLayout(),
			);
		}

		getActiveTab()
		{
			return this.getTabById(this.state.activeTabId);
		}

		getTabById(entityTypeId)
		{
			return this.state.tabs.find(({ id }) => id === entityTypeId);
		}

		getTabByTypeName(entityTypeName)
		{
			return this.state.tabs.find(({ typeName }) => typeName === entityTypeName);
		}

		onPanBySearchHandler()
		{
			if (this.searchRef && this.searchRef.isVisible())
			{
				this.searchRef.fadeOut().then(() => {
					layout.search.close();
					this.searchRef.onHide();
				});
			}
		}

		handleTabSelected(tab, changed)
		{
			if (changed)
			{
				this.rightButtonsIsSetted = false;
				const typeId = this.getTabByTypeName(tab.id).id;

				let promise;
				let cancelReload = false;

				const { activeTabTypeName } = this.state;
				// skip rerender at first load when data is from cache
				if (activeTabTypeName === tab.id)
				{
					promise = Promise.resolve();
				}
				else
				{
					if (
						this.getTabByTypeName(activeTabTypeName).isStagesEnabled
						&& this.getTabByTypeName(tab.id).isStagesEnabled
					)
					{
						cancelReload = true;
					}

					promise = new Promise((resolve) => {
						this.setState({
							activeTabTypeName: tab.id,
							activeTabId: typeId,
						}, resolve);
					});
				}

				if (cancelReload)
				{
					return;
				}

				promise.then(() => {
					if (this.searchRef)
					{
						this.searchRef.setState(this.searchRef.getInitialState());
					}

					this.reloadKanbanTab();

					if (this.listTabRef)
					{
						this.listTabRef.reload(this.getLoadItemsParams());
					}
				});
			}
			else if (tab.selectable === false)
			{
				const desiredTab = this.getTabByTypeName(tab.id);

				if (desiredTab.hasRestrictions)
				{
					PlanRestriction.open({ title: tab.title });
				}
				else
				{
					qrauth.open({
						title: tab.title,
						redirectUrl: this.getDesktopPageLink(tab.typeName),
					});
				}
			}
			else
			{
				this.scrollOnTop();
			}
		}

		reloadKanbanTab()
		{
			if (this.kanbanTabRef)
			{
				this.kanbanTabRef.reload(this.getLoadItemsParams());
			}
		}

		getLoadItemsParams()
		{
			return {
				clearFilter: true,
				initMenu: true,

				// @todo may be needed when changing an entityType
				// skipFillSlides: false,
				// force: true,
			};
		}

		scrollOnTop()
		{
			if (this.kanbanTabRef)
			{
				this.kanbanTabRef.scrollToTop();
			}
			else if (this.listTabRef)
			{
				this.listTabRef.scrollToTop();
			}
		}

		loadTabs(params = {})
		{
			new RunActionExecutor(result.actions.loadTabs)
				.setCacheId(this.tabsCacheName)
				.setCacheHandler((response) => this.setTabs({ ...response, ...params }))
				.setHandler((response) => this.setTabs({ ...response, ...params }))
				.call(true);
		}

		setTabs(response)
		{
			const { tabs, permissions, isProgress: stateIsProgress, connectors } = this.state;
			const { data, isProgress = false } = response;
			const isChangeViewProgress = isProgress !== stateIsProgress;

			if ((data.tabs && !isEqual(data.tabs, tabs)) || isChangeViewProgress)
			{
				this.prepareTabs(this.props, data.tabs);
				const tab = data.tabs.find((item) => item.active);

				this.setState({
					isProgress,
					tabs: data.tabs,
					activeTabTypeName: tab.typeName,
					activeTabId: tab.id,
					permissions: data.permissions,
					connectors: data.connectors,
					restrictions: data.restrictions,
				});
			}
			else if (data.permissions && !isEqual(data.permissions, permissions))
			{
				this.setState({
					isProgress,
					permissions: data.permissions,
				});
			}
			else if (data.connectors && !isEqual(data.connectors, connectors))
			{
				this.setState({
					connectors: data.connectors,
				});
			}

			const { user } = data;

			this.userInfo = user;
		}

		prepareTabs(props, tabs)
		{
			if (props.activeTabName)
			{
				tabs.forEach((tab) => tab.active = (tab.typeName === props.activeTabName));
			}
		}

		getDesktopPageLink(tabName)
		{
			const tab = this.getTabByTypeName(tabName);
			if (tab)
			{
				return tab.link;
			}

			return null;
		}

		renderTab()
		{
			return (
				this.isUseColumns()
					? this.createKanban()
					: this.createList()
			);
		}

		isUseColumns()
		{
			const { isStagesEnabled } = this.getActiveTab();

			return isStagesEnabled;
		}

		createKanban()
		{
			this.listTabRef = null;

			return new KanbanTab(this.getEntityTabConfig((ref) => this.kanbanTabRef = ref));
		}

		createList()
		{
			this.kanbanTabRef = null;

			return new ListTab(this.getEntityTabConfig((ref) => this.listTabRef = ref));
		}

		getTabItems()
		{
			return this.state.tabs.map((tab) => this.getPreparedTabItem(tab));
		}

		getPreparedTabItem(tab)
		{
			return {
				id: tab.typeName,
				title: tab.titleInPlural,
				active: tab.hasOwnProperty('active') ? tab.active : undefined,
				selectable: tab.hasOwnProperty('selectable') ? tab.selectable : undefined,
				label: this.getPreparedLabel(tab.label, tab.id),
			};
		}

		getPreparedLabel(label)
		{
			if (!label)
			{
				return '';
			}

			const counter = Number(label);
			if (Number.isNaN(counter))
			{
				return String(label);
			}

			return (counter > 99 ? TAB_BIG_LABEL : String(counter));
		}

		getEntityTypes()
		{
			return this.state.tabs.map((tab) => {
				return {
					...tab,
					name: tab.title,
				};
			});
		}

		getEntityTabConfig(ref)
		{
			const rightButtonsIsSetted = this.rightButtonsIsSetted;
			this.rightButtonsIsSetted = true;
			const { activeTabId, activeTabTypeName } = this.state;

			return {
				entityTypeName: activeTabTypeName,
				entityTypeId: activeTabId,
				entityTypes: this.getEntityTypes(),
				updateEntityTypeData: this.updateEntityTypeData,
				setActiveTab: this.setActiveTab,
				actions: result.actions || {},
				actionParams: {
					loadItems: {
						entityType: activeTabTypeName,
					},
				},
				permissions: this.getPermissions(),
				restrictions: this.getRestrictions(),
				itemParams: this.getItemParams(),
				cacheName: `crm:crm.kanban.${env.userId}.${activeTabTypeName}`,
				layout,
				needInitMenu: !rightButtonsIsSetted,
				onPanList: this.onPanBySearch,
				searchRef: this.searchRef,
				userInfo: this.userInfo,
				ref,
			};
		}

		getPermissions()
		{
			const { permissions, activeTabTypeName } = this.state;
			const { permissions: tabPermissions } = this.getTabByTypeName(activeTabTypeName);

			return mergeImmutable(permissions, tabPermissions);
		}

		getRestrictions()
		{
			const { restrictions } = this.state;
			const { restrictions: tabRestrictions } = this.getActiveTab();

			return mergeImmutable(restrictions, tabRestrictions);
		}

		getItemParams()
		{
			const {
				activeTabTypeName: entityTypeName,
				activeTabId: entityTypeId,
				permissions: entityPermissions,
				connectors,
			} = this.state;

			return {
				entityTypeName,
				entityTypeId,
				entityPermissions,
				connectors,
			};
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Object} data
		 * @param {Function|null} callback
		 */
		updateEntityTypeData(entityTypeId, data, callback = null)
		{
			const tabs = clone(this.state.tabs);
			const tab = tabs.find((currentTab) => currentTab.id === entityTypeId);

			let needUpdateState = false;

			const modifyData = {};

			if (data.permissions && !isEqual(tab.permissions, data.permissions))
			{
				const { permissions } = data;
				tab.permissions = permissions;
				needUpdateState = true;

				modifyData.permissions = permissions;
			}

			if (data.link && tab.link !== data.link)
			{
				const { link } = data;
				tab.link = link;
				needUpdateState = true;

				modifyData.link = link;
			}

			if (!Number.isNaN(Number(data.categoryId)) && tab.data.currentCategoryId !== data.categoryId)
			{
				const { categoryId } = data;
				tab.data.currentCategoryId = categoryId;

				modifyData.data = modifyData.data || {};
				modifyData.data.currentCategoryId = categoryId;
			}

			if (data.counters && tab.data.counters !== data.counters)
			{
				const { counters } = data;
				tab.data.counters = counters;

				modifyData.data = modifyData.data || {};
				modifyData.data.counters = counters;
			}

			if (data.sortType && tab.data.sortType !== data.sortType)
			{
				const { sortType } = data;
				tab.data.sortType = sortType;

				modifyData.data = modifyData.data || {};
				modifyData.data.sortType = sortType;

				this.rightButtonsIsSetted = false;
				needUpdateState = true;
			}

			if (data.restrictions && !isEqual(tab.restrictions, data.restrictions))
			{
				const restrictions = mergeImmutable(tab.restrictions, data.restrictions);

				tab.restrictions = restrictions;

				needUpdateState = true;
				modifyData.restrictions = restrictions;
			}

			if (Object.keys(modifyData).length > 0)
			{
				this.modifyCache(modifyData, tab.id);
			}

			if (needUpdateState)
			{
				this.setState({ tabs }, () => {
					if (callback)
					{
						callback(tabs);
					}
				});
			}
		}
	}

	BX.onViewLoaded(() => {
		layout.enableNavigationBarBorder(false);
		layout.showComponent(new CrmTabs({
			activeTabName: BX.componentParameters.get('activeTabName', null),
		}));
	});
})();
