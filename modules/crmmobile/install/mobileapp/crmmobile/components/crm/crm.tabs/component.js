(() => {
	const {
		clone,
		get,
		isEqual,
		merge,
		mergeImmutable,
	} = jn.require('utils/object');
	const { throttle } = jn.require('utils/function');
	const { KanbanTab } = jn.require('crm/entity-tab/kanban');
	const { ListTab } = jn.require('crm/entity-tab/list');
	const { Search } = jn.require('crm/entity-tab/search');
	const { ActivityCountersStoreManager } = jn.require('crm/state-storage');
	const { Type } = jn.require('crm/type');
	const { PureComponent } = jn.require('layout/pure-component');

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
			this.tabsCacheName = 'crm:crm.tabs.list.' + env.userId;
			this.tabViewRef = null;
			this.pullUnsubscribe = null;

			const data = this.getTabsFromCache();
			const tabs = data.tabs || [];

			this.prepareTabs(props, tabs);
			const activeTab = tabs.find((tab) => tab.active);

			const permissions = data.permissions || {};

			this.onPanBySearch = this.onPanBySearchHandler.bind(this);

			this.state = {
				tabs,
				activeTabTypeName: activeTab && activeTab.typeName,
				activeTabId: activeTab && activeTab.id,
				permissions,
			};

			this.currentMoneyFormats = Money.formats;
			this.rightButtonsIsSetted = false;

			this.loadTabs();

			this.onTabsReSelected = this.onTabsReSelected.bind(this);
			this.onMoneyLoad = this.onMoneyLoad.bind(this);
			this.updateCounters = this.updateCounters.bind(this);
			this.setActiveTab = this.setActiveTab.bind(this);
			this.updateEntityTypeData = this.updateEntityTypeData.bind(this);

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

			if (Object.keys(preparedCounters).length)
			{
				ActivityCountersStoreManager.setCounters(preparedCounters);
			}
		}

		bindEvents()
		{
			BX.addCustomEvent('onTabsReSelected', this.onTabsReSelected);
			BX.addCustomEvent('Money::onLoad', this.onMoneyLoad);

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
			this.state.tabs.forEach(tab => {
				const code = 'crm_' + tab.typeName.toLowerCase() + '_all';

				if (counters.hasOwnProperty(code) && counters[code] >= 0)
				{
					const currentValue = BX.prop.getNumber(tab, 'label', 0);
					if (
						counters[code] !== currentValue
						&& (counters[code] <= 99 || currentValue <= 99)
					)
					{
						const item = this.getTab(tab.typeName);
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

			const currentTab = cache.data.tabs.find(tab => tab.id === tabId);
			if (currentTab === null)
			{
				return;
			}

			merge(currentTab, params);
			Application.storage.setObject(cacheName, cache);
		}

		render()
		{
			const activeTab = this.getActiveTab();

			if (!activeTab && this.state.tabs[0])
			{
				qrauth.open({
					title: this.state.tabs[0].title,
					redirectUrl: this.getDesktopPageLink(this.state.tabs[0].typeName),
				});
				return;
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
					ref: ref => this.tabViewRef = ref,
					params: {
						styles: {
							tabTitle: {
								underlineColor: '#2985E2',
							},
						},
						items: this.getTabItems(),
					},
					onTabSelected: (tab, changed) => this.handleTabSelected(tab, changed),
				}),
				this.state.activeTabTypeName && activeTab && this.renderTab(),
				this.state.activeTabTypeName && activeTab && new Search({
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
			);
		}

		getActiveTab()
		{
			return this.getTabById(this.state.activeTabId);
		}

		getTabById(id)
		{
			return this.state.tabs.find(tab => tab.id === id);
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
				const typeId = this.getTab(tab.id).id;

				let promise;

				// skip rerender at first load when data is from cache
				if (this.state.activeTabTypeName !== tab.id)
				{
					promise = new Promise((resolve) => {
						this.setState({
							activeTabTypeName: tab.id,
							activeTabId: typeId,
						}, resolve);
					});
				}
				else
				{
					promise = Promise.resolve();
				}

				promise.then(() => {
					this.searchRef.setState(this.searchRef.getInitialState());

					const loadItemsParams = {
						clearFilter: true,
					};

					this.kanbanTabRef && this.kanbanTabRef.reload(loadItemsParams);
					this.listTabRef && this.listTabRef.reload(loadItemsParams);
				});
			}
			else if (tab.selectable === false)
			{
				const desiredTab = this.getTab(tab.id);

				if (desiredTab.pageUrl)
				{
					PageManager.openPage({
						url: desiredTab.pageUrl,
					});
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

		loadTabs()
		{
			new RunActionExecutor(result.actions.loadTabs)
				.setCacheId(this.tabsCacheName)
				.setCacheHandler(response => this.setTabs(response))
				.setHandler(response => this.setTabs(response))
				.call(true);
		}

		setTabs(response)
		{
			if (response.data.tabs && !isEqual(response.data.tabs, this.state.tabs))
			{
				const { data } = response;

				this.prepareTabs(this.props, data.tabs);
				const tab = data.tabs.find((item) => item.active);

				this.setState({
					tabs: data.tabs,
					activeTabTypeName: tab.typeName,
					activeTabId: tab.id,
					permissions: data.permissions,
				});
			}
			else if (response.data.permissions && !isEqual(response.data.permissions, this.state.permissions))
			{
				this.setState({
					permissions: response.data.permissions,
				});
			}
		}

		prepareTabs(props, tabs)
		{
			if (props.activeTabName)
			{
				tabs.forEach(tab => tab.active = (tab.typeName === props.activeTabName));
			}
		}

		getDesktopPageLink(tabName)
		{
			const tab = this.getTab(tabName);
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
			const { activeTabTypeName } = this.state;
			const activeTab = this.getTab(activeTabTypeName);
			return activeTab.isStagesEnabled;
		}

		getTab(tabName)
		{
			return this.state.tabs.find(tab => tab.typeName === tabName);
		}

		createKanban()
		{
			this.listTabRef = null;
			return new KanbanTab(this.getEntityTabConfig(ref => this.kanbanTabRef = ref));
		}

		createList()
		{
			this.kanbanTabRef = null;
			return new ListTab(this.getEntityTabConfig(ref => this.listTabRef = ref));
		}

		getTabItems()
		{
			return this.state.tabs.map(tab => this.getPreparedTabItem(tab));
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
			if (isNaN(counter))
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

			return {
				entityTypeName: this.state.activeTabTypeName,
				entityTypeId: this.state.activeTabId,
				entityTypes: this.getEntityTypes(),
				updateEntityTypeData: this.updateEntityTypeData,
				setActiveTab: this.setActiveTab,
				actions: result.actions || {},
				actionParams: {
					loadItems: {
						entityType: this.state.activeTabTypeName,
					},
				},
				permissions: this.getPermissions(),
				itemParams: this.getItemParams(),
				cacheName: 'crm:crm.kanban.' + env.userId + '.' + this.state.activeTabTypeName,
				layout: layout,
				needInitMenu: !rightButtonsIsSetted,
				onPanList: this.onPanBySearch,
				searchRef: this.searchRef,
				ref,
			};
		}

		getPermissions()
		{
			return mergeImmutable(this.state.permissions, this.getTab(this.state.activeTabTypeName).permissions);
		}

		getItemParams()
		{
			return {
				entityTypeName: this.state.activeTabTypeName,
				entityTypeId: this.state.activeTabId,
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
			const tab = tabs.find(tab => tab.id === entityTypeId);

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

			if (!isNaN(Number(data.categoryId)) && tab.data.currentCategoryId !== data.categoryId)
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

			if (!isEqual(tab.restrictions, data.restrictions))
			{
				const { restrictions } = data;
				tab.restrictions = restrictions;

				needUpdateState = true;
				modifyData.restrictions = restrictions;
			}

			if (Object.keys(modifyData).length)
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
