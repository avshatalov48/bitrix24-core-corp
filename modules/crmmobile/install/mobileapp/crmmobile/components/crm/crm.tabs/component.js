(() => {
	const require = (ext) => jn.require(ext);

	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const {
		clone,
		get,
		isEqual,
		merge,
		mergeImmutable,
	} = require('utils/object');
	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');
	const { throttle } = require('utils/function');
	const { KanbanTab } = require('crm/entity-tab/kanban');
	const { ListTab } = require('crm/entity-tab/list');
	const { ListItemType } = require('crm/simple-list/items');
	const { ActivityCountersStoreManager } = require('crm/state-storage');
	const { Type } = require('crm/type');
	const { LoadingProgressBar } = require('crm/ui/loading-progress');
	const { getEntityMessage } = require('crm/loc');
	const { SearchBar } = require('layout/ui/search-bar');
	const { SkeletonFactory } = require('layout/ui/simple-list/skeleton');
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { Feature } = require('feature');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { CrmNavigator } = require('crm/navigator');
	const { qrauth } = require('qrauth/utils');

	SkeletonFactory.alias('Kanban', ListItemType.CRM_ENTITY);

	const TAB_BIG_LABEL = '99+';

	let featureCounter = 0;
	const customSectionId = BX.componentParameters.get('customSectionId', null);
	const crmNavigator = new CrmNavigator({ customSectionId });
	crmNavigator.unsubscribeFromPushNotifications();
	crmNavigator.subscribeToPushNotifications();

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

			/** @type {SearchBar|null} */
			this.searchRef = null;

			const tabsCacheName = `crm:crm.tabs.list.${env.userId}.v2`;
			this.tabsCacheName = (
				props.customSectionId
					? `${tabsCacheName}.customSection.${props.customSectionId}`
					: tabsCacheName
			);
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
			};

			this.currentMoneyFormats = Money.formats;
			this.rightButtonsIsSet = false;

			this.userInfo = null;

			this.loadTabs();

			this.onTabsReSelected = this.onTabsReSelected.bind(this);
			this.onMoneyLoad = this.onMoneyLoad.bind(this);
			this.loadTabs = this.loadTabs.bind(this);
			this.reloadKanbanTab = this.reloadKanbanTab.bind(this);
			this.updateCounters = this.updateCounters.bind(this);
			this.setActiveTab = this.setActiveTab.bind(this);
			this.updateEntityTypeData = this.updateEntityTypeData.bind(this);
			this.bindKanbanRef = this.bindKanbanRef.bind(this);
			this.bindListRef = this.bindListRef.bind(this);
			this.bindSearchRef = this.bindSearchRef.bind(this);
			this.onMoreButtonClick = this.onMoreButtonClick.bind(this);
			this.onCheckRestrictions = this.onCheckRestrictions.bind(this);
			this.onItemsLoaded = this.onItemsLoaded.bind(this);
			this.close = this.close.bind(this);

			this.scrollOnTop = throttle(this.scrollOnTop, 500, this);
		}

		componentDidMount()
		{
			if (env.extranet)
			{
				this.showAlertAndClose();

				return;
			}

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
			this.preloadCrmMode();
		}

		preloadCrmMode()
		{
			setTimeout(() => {
				requireLazy('crm:crm-mode', false).then(({ CrmMode }) => {
					void CrmMode.getCrmModeConfig();
				})
					.catch((error) => {
						console.error(error);
					});
			}, 2000);
		}

		updateTabCounters(data)
		{
			const counters = data[env.siteId] || null;
			if (!counters)
			{
				return;
			}

			const preparedCounters = {};

			for (const counter of Object.keys(counters))
			{
				if (counters[counter] >= 0)
				{
					preparedCounters[counter] = counters[counter];
				}
				else if (counters[counter] === -1)
				{
					BX.ajax.runAction('crm.counter.list', {
						data: {
							entityTypeId: this.kanbanTabRef.props.entityTypeId,
							extras: data.ex,
							withExcludeUsers: Boolean(this.props.withExcludeUsers),
						},
					});

					break;
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

		render()
		{
			const { activeTabTypeName, tabs } = this.state;
			const activeTab = this.getActiveTab();

			if (!activeTab && tabs[0])
			{
				const { hasRestrictions, title, typeName } = tabs[0];

				if (hasRestrictions)
				{
					void this.showPlanRestriction(title);
				}
				else
				{
					qrauth.open({
						title,
						redirectUrl: this.getDesktopPageLink(typeName),
						analyticsSection: 'crm',
					});
				}

				return null;
			}

			if (!activeTab)
			{
				return null;
			}

			const entityTypeName = activeTab.typeName;
			const categoryId = get(activeTab, 'data.currentCategoryId', null);

			return View(
				{
					resizableByKeyboard: true,
				},
				TabView({
					style: {
						height: Feature.isAirStyleSupported() ? 50 : 44,
						backgroundColor: Feature.isAirStyleSupported()
							? AppTheme.realColors.bgNavigation
							: AppTheme.colors.bgNavigation,
					},
					ref: (ref) => {
						this.tabViewRef = ref;
					},
					params: {
						styles: {
							tabTitle: {
								underlineColor: AppTheme.colors.accentExtraDarkblue,
							},
						},
						items: this.getTabItems(),
					},
					onTabSelected: (tab, changed) => this.handleTabSelected(tab, changed),
				}),
				activeTabTypeName && activeTab && this.renderTab(),
				activeTabTypeName && activeTab && new SearchBar({
					id: `${entityTypeName}_${categoryId}`,
					cacheId: `Crm.SearchBar.${entityTypeName}.${categoryId || 'all'}.${env.userId}`,
					searchDataAction: result.actions.getSearchData,
					searchDataActionParams: {
						entityTypeName,
						categoryId,
					},
					layout,
					ref: this.bindSearchRef,
					onMoreButtonClick: this.onMoreButtonClick,
					onCheckRestrictions: this.onCheckRestrictions,
				}),
				new LoadingProgressBar(),
			);
		}

		showAlertAndClose()
		{
			Alert.alert(
				Loc.getMessage('M_CRM_ENTITY_TAB_ALERT_EXTRANET_ACCESS_DENIED_TITLE'),
				Loc.getMessage('M_CRM_ENTITY_TAB_ALERT_EXTRANET_ACCESS_DENIED_TEXT'),
				this.close,
				Loc.getMessage('M_CRM_ENTITY_TAB_ALERT_CONFIRM'),
			);
		}

		close()
		{
			if (layout)
			{
				layout.back();
				layout.close();
			}
		}

		bindSearchRef(ref)
		{
			if (ref)
			{
				this.searchRef = ref;
			}
		}

		onMoreButtonClick()
		{
			const { typeName, link } = this.getActiveTab() || {};

			this.showFilterSettings(typeName, link);
		}

		onCheckRestrictions()
		{
			const hasRestrictions = get(this.getActiveTab(), 'restrictions.search.isExceeded', false);
			if (hasRestrictions)
			{
				void this.showPlanRestriction(BX.message('M_CRM_ET_SEARCH_PLAN_RESTRICTION_TITLE'));
			}

			return hasRestrictions;
		}

		async showPlanRestriction(title)
		{
			const { PlanRestriction } = await requireLazy('layout/ui/plan-restriction');

			PlanRestriction.open({ title });
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
				void this.searchRef.fadeOut();
			}
		}

		handleTabSelected(tab, changed)
		{
			if (changed)
			{
				featureCounter = 0;

				this.rightButtonsIsSet = false;
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
				})
					.catch(console.error);
			}
			else if (tab.selectable === false)
			{
				const desiredTab = this.getTabByTypeName(tab.id);

				if (desiredTab.hasRestrictions)
				{
					void this.showPlanRestriction(tab.title);
				}
				else
				{
					qrauth.open({
						title: tab.title,
						redirectUrl: this.getDesktopPageLink(tab.typeName),
						analyticsSection: 'crm',
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
			const { customSectionId } = this.props;
			const ajaxParams = { customSectionId };

			new RunActionExecutor(result.actions.loadTabs, ajaxParams)
				.setCacheId(this.tabsCacheName)
				.setCacheHandler((response) => this.setTabs({ ...response, ...params }))
				.setHandler((response) => this.setTabs({ ...response, ...params }))
				.call(true);
		}

		setTabs(response)
		{
			const { tabs, permissions, connectors } = this.state;
			const { data } = response;

			if (data.tabs && !isEqual(data.tabs, tabs))
			{
				this.prepareTabs(this.props, data.tabs);
				const tab = data.tabs.find((item) => item.active);

				this.setState({
					tabs: data.tabs,
					activeTabTypeName: tab.typeName,
					activeTabId: tab.id,
					permissions: data.permissions,
					connectors: data.connectors,
					restrictions: data.restrictions,
					remindersList: data.remindersList,
				});
			}
			else if (data.permissions && !isEqual(data.permissions, permissions))
			{
				this.setState({
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

			return new KanbanTab(this.getEntityTabConfig(this.bindKanbanRef));
		}

		bindKanbanRef(ref)
		{
			this.kanbanTabRef = ref;
		}

		createList()
		{
			this.kanbanTabRef = null;

			return new ListTab(this.getEntityTabConfig(this.bindListRef));
		}

		bindListRef(ref)
		{
			this.listTabRef = ref;
		}

		getTabItems()
		{
			return this.state.tabs.map((tab) => this.getPreparedTabItem(tab));
		}

		getPreparedTabItem(tab)
		{
			let tabItem = {
				id: tab.typeName,
				title: tab.titleInPlural,
				active: Boolean(tab?.active),
				selectable: Boolean(tab.selectable),
				label: this.getPreparedLabel(tab.label, tab.id),
			};
			const { hasRestrictions } = this.getTabByTypeName(tabItem.id);

			if (hasRestrictions)
			{
				tabItem = {
					...tabItem,
					icon: Icon.LOCK.getIconName(),
					style: {
						icon: {
							color: Color.base0.toHex(),
						},
					},
				};
			}

			return tabItem;
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
			const rightButtonsIsSet = this.rightButtonsIsSet;
			this.rightButtonsIsSet = true;
			const { activeTabId, activeTabTypeName, remindersList } = this.state;
			const activeTab = this.getActiveTab() || {};

			const categoryId = get(activeTab, 'data.currentCategoryId', null);

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
					updateItemStage: {
						entityType: activeTabTypeName,
					},
					deleteItem: {
						entityType: activeTabTypeName,
					},
				},
				actionCallbacks: {
					loadItems: this.onItemsLoaded,
				},
				permissions: this.getPermissions(),
				restrictions: this.getRestrictions(),
				itemParams: this.getItemParams(),
				cacheName: `crm:crm.kanban.${env.userId}.${activeTabTypeName}`,
				layout,
				needInitMenu: !rightButtonsIsSet,
				onPanList: this.onPanBySearch,
				searchRef: this.searchRef,
				searchBarId: `${activeTabTypeName}_${categoryId}`,
				userInfo: this.userInfo,
				remindersList,
				ref,
			};
		}

		onItemsLoaded(data)
		{
			if (data?.event === 'refreshPresets')
			{
				this.searchRef?.refreshPresets();
			}
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

				this.rightButtonsIsSet = false;
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

		/**
		 * @private
		 * @param {string} entityTypeName
		 * @param {string} redirectUrl
		 */
		showFilterSettings(entityTypeName, redirectUrl)
		{
			const pathToExtension = `${currentDomain}/bitrix/mobileapp/crmmobile/components/crm/crm.tabs`;
			const imagePath = `${pathToExtension}/images/settings.png`;

			const menu = new ContextMenu({
				banner: {
					featureItems: [
						BX.message('M_CRM_ENTITY_TAB_SEARCH_FILTER_SETTINGS_CREATE_FILTER'),
						BX.message('M_CRM_ENTITY_TAB_SEARCH_FILTER_SETTINGS_MORE_SETTINGS'),
						BX.message('M_CRM_ENTITY_TAB_SEARCH_FILTER_SETTINGS_RESPONSIBLE'),
						getEntityMessage(
							'M_CRM_ENTITY_TAB_SEARCH_FILTER_SETTINGS_CUSTOMIZATION',
							entityTypeName,
						),
					],
					imagePath,
					qrauth: {
						redirectUrl,
						type: 'crm',
						analyticsSection: 'crm',
					},
				},
				params: {
					title: BX.message('M_CRM_ENTITY_TAB_SEARCH_FILTER_SETTINGS_TITLE'),
				},
			});

			void menu.show(PageManager);
		}
	}

	BX.onViewLoaded(() => {
		layout.enableNavigationBarBorder(false);
		layout.showComponent(new CrmTabs({
			activeTabName: BX.componentParameters.get('activeTabName', null),
			customSectionId: BX.componentParameters.get('customSectionId', null),
		}));
	});
})();
