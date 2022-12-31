(() => {
	const { merge, mergeImmutable, isEqual, clone } = jn.require('utils/object');
	const { Haptics } = jn.require('haptics');
	const { Alert } = jn.require('alert');
	const { EventEmitter } = jn.require('event-emitter');
	const { NotifyManager } = jn.require('notify-manager');
	const { ToolbarPadding } = jn.require('layout/ui/detail-card/toolbar/toolbar-padding');
	const { TabFactory } = jn.require('layout/ui/detail-card/tabs/factory');
	const { FocusManager } = jn.require('layout/ui/fields/focus-manager');
	const { debounce } = jn.require('utils/function');

	const CACHE_ID = 'DETAIL_CARD';
	const TAB_HEADER_HEIGHT = 44;
	const TOP_TOOLBAR_HEIGHT = 60;
	const MAIN_TAB = 'main';
	const MAX_TAB_COUNTER_VALUE = 99;
	const DURATION = 200;

	/**
	 * @class DetailCardComponent
	 */
	class DetailCardComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.mounted = false;
			this.isClosing = false;

			this.menuActionsProvider = null;
			this.setAdditionalProvider = null;

			this.itemActions = [];
			this.componentParams = null;

			this.layout = null;
			this.menu = null;

			this.leftButtons = null;
			this.rightButtons = null;
			this.header = null;

			this.sliderRef = null;
			this.tabViewRef = null;

			/** @type {ToolbarPadding} */
			this.bottomPaddingRef = null;
			/** @type {ActionsPanel} */
			this.actionsPanelRef = null;

			/** @type {DetailToolbarFactory} */
			this.topToolbarFactory = null;
			/** @type {ToolbarPadding} */
			this.topPaddingRef = null;

			/** @type {ToolbarPanelWrapper} */
			this.topToolbarRef = null;

			this.readOnly = true;
			this.entityModel = null;

			this.isChanged = false;
			this.isEditing = false;
			this.isLoading = false;
			this.isSaving = false;

			this.anyTabWasLoaded = false;
			this.activeTab = null;
			this.availableTabs = [];
			/** @type {Map<string,Tab>} */
			this.tabRefMap = new Map();

			this.loadedTabsData = null;

			if (this.isDynamicTabsEnabled())
			{
				this.state.tabsInfo = this.getTabsInfoFromCache();
				this.loadTabs();
			}
			else
			{
				this.state.tabsInfo = props.tabs;
			}

			/** @type {(string, function(DetailCardComponent, ...*): void)[][]} */
			this.globalEvents = [];
			/** @type {(string, function(DetailCardComponent, ...*): void)[][]} */
			this.customEvents = [];
			this.uid = this.createUid();
			/** @type {EventEmitter} */
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			/** @type {Function|null} */
			this.ajaxErrorHandler = null;
			/** @type {Function|null} */
			this.headerProcessor = null;
			/** @type {Function|null} */
			this.onEntityModelReadyHandler = null;
			/** @type {Function|null} */
			this.onTabContentLoadedHandler = null;

			this.handleTabClick = this.handleTabClick.bind(this);
			this.handleTabChange = this.handleTabChange.bind(this);
			this.handleTabEdit = this.handleTabEdit.bind(this);
			this.handleTabScroll = this.handleTabScroll.bind(this);
			this.handleTabPreloadRequest = this.handleTabPreloadRequest.bind(this);
			this.handleOnSaveLock = this.setLoading.bind(this);
			this.handleClose = this.close.bind(this);
			this.handleEntityModelReady = this.handleEntityModelReady.bind(this);
			this.handleEntityModelChange = this.handleEntityModelChange.bind(this);
			this.handleEntityEditorInit = this.handleEntityEditorInit.bind(this);

			this.handleSave = this.handleSave.bind(this);
			this.handleCancel = this.handleCancel.bind(this);
			this.handleExitFromEntity = this.handleExitFromEntity.bind(this);
			this.showMenu = this.showMenu.bind(this);
			this.setTabCounter = this.setTabCounter.bind(this);

			/** @type {Function} */
			this.checkToolbarPanelDebounced = debounce(this.checkToolbarPanel, 50, this);
		}

		componentDidMount()
		{
			this.mounted = true;

			this.bindEvents();
			this.checkToolbarPanel();

			this.showTab(this.activeTab);
		}

		componentWillUnmount()
		{
			this.customEventEmitter
				.off('DetailCard::onTabClick', this.handleTabClick)
				.off('DetailCard::onTabChange', this.handleTabChange)
				.off('DetailCard::onTabEdit', this.handleTabEdit)
				.off('DetailCard::onTabPreloadRequest', this.handleTabPreloadRequest)
				.off('DetailCard::onTabCounterChange', this.setTabCounter)
				.off('DetailCard::onSaveLock', this.handleOnSaveLock)
				.off('DetailCard::close', this.handleClose)
				.off('UI.EntityEditor.Model::onReady', this.handleEntityModelReady)
				.off('UI.EntityEditor.Model::onChange', this.handleEntityModelChange)
				.off('UI.EntityEditor::onInit', this.handleEntityEditorInit)
			;

			this.customEventEmitter.emit('DetailCard::onBeforeUnmount');
		}

		bindEvents()
		{
			this.customEventEmitter
				.on('DetailCard::onTabClick', this.handleTabClick)
				.on('DetailCard::onTabChange', this.handleTabChange)
				.on('DetailCard::onTabEdit', this.handleTabEdit)
				.on('DetailCard::onTabPreloadRequest', this.handleTabPreloadRequest)
				.on('DetailCard::onTabCounterChange', this.setTabCounter)
				.on('DetailCard::onShowTopToolbar', this.showTopToolbar.bind(this))
				.on('DetailCard::onSaveLock', this.handleOnSaveLock)
				.on('DetailCard::close', this.handleClose)
				.on('UI.EntityEditor.Model::onReady', this.handleEntityModelReady)
				.on('UI.EntityEditor.Model::onChange', this.handleEntityModelChange)
				.on('UI.EntityEditor::onInit', this.handleEntityEditorInit)
			;

			this.globalEvents.forEach(([event, handler]) => {
				BX.addCustomEvent(event, (...args) => handler(this, ...args));
			});

			this.customEvents.forEach(([event, handler]) => {
				this.customEventEmitter.on(event, (...args) => handler(this, ...args));
			});
		}

		/**
		 * @public
		 * @param {(string, function(DetailCardComponent, ...*): void)[][]} events
		 * @returns {DetailCardComponent}
		 */
		setGlobalEvents(events)
		{
			this.globalEvents = events;

			return this;
		}

		/**
		 * @public
		 * @param {(string, function(DetailCardComponent, ...*): void)[][]} events
		 * @returns {DetailCardComponent}
		 */
		setCustomEvents(events)
		{
			this.customEvents = events;

			return this;
		}

		getUid()
		{
			return this.uid;
		}

		createUid()
		{
			const { uid } = this.getComponentParams();
			return uid || Random.getString();
		}

		isDynamicTabsEnabled()
		{
			const { dynamicTabOptions } = this.props;

			return Array.isArray(dynamicTabOptions) && dynamicTabOptions.length;
		}

		checkToolbarPanel()
		{
			const leftButtons = this.getLeftButtons();
			if (!isEqual(this.leftButtons, leftButtons))
			{
				this.leftButtons = leftButtons;
				this.layout.setLeftButtons(this.leftButtons);
			}

			const rightButtons = this.getRightButtons();
			if (!isEqual(this.rightButtons, rightButtons))
			{
				this.rightButtons = rightButtons;
				this.layout.setRightButtons(this.rightButtons);
			}

			if (!this.readOnly && this.isActionsPanelVisible())
			{
				this.showActionsPanel();
			}
			else
			{
				this.hideToolbars();
			}
		}

		getLeftButtons()
		{
			return [
				{
					// type: 'cross',
					svg: {
						content: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
					},
					callback: this.handleExitFromEntity,
				},
			];
		}

		getRightButtons()
		{
			let buttons = [];

			if (this.hasEntityModel())
			{
				if (this.isToolPanelVisible())
				{
					buttons.push({
						id: 'save-entity',
						name: this.getSaveButtonTitle(),
						type: 'text',
						badgeCode: 'save_entity',
						color: this.isLoading ? '#8ebad4' : '#0065a3',
						callback: this.handleSave,
					});
				}
				else
				{
					buttons.push({
						id: 'more-menu',
						type: 'more',
						callback: this.showMenu,
					});
				}
			}

			if (this.rightButtonsProvider)
			{
				buttons = this.rightButtonsProvider(buttons, this);
			}

			return buttons;
		}

		getSaveButtonTitle()
		{
			if (this.isNewEntity())
			{
				return this.isSaving ? BX.message('DETAIL_CARD_CREATING') : BX.message('DETAIL_CARD_CREATE');
			}

			return this.isSaving ? BX.message('DETAIL_CARD_SAVING') : BX.message('DETAIL_CARD_SAVE');
		}

		getAdditionalElements()
		{
			return this.setAdditionalProvider ? this.setAdditionalProvider(this) : [];
		}

		showActionsPanel()
		{
			if (this.actionsPanelRef)
			{
				this.actionsPanelRef.setModel(this.entityModel);
				this.actionsPanelRef.show();
			}

			if (this.bottomPaddingRef)
			{
				this.bottomPaddingRef.show(false);
			}
		}

		showTopToolbar(model, actionParams)
		{
			if (!this.topPaddingRef || !this.topToolbarRef)
			{
				return Promise.resolve();
			}

			return this.topToolbarRef.show(model, actionParams);
		}

		hideToolbars()
		{
			if (this.actionsPanelRef)
			{
				this.actionsPanelRef.hide();
			}

			if (this.bottomPaddingRef)
			{
				this.bottomPaddingRef.hide(false);
			}
		}

		getTabCacheId()
		{
			return this.props.endpoint + '-' + Object.toMD5(this.getTabParams());
		}

		getTabParams()
		{
			const options = this.props.dynamicTabOptions || [];
			if (!Array.isArray(options))
			{
				return {};
			}

			const params = this.getComponentParams();

			return options.reduce((obj, option) => {
				obj[option] = params[option] || null;
				return obj;
			}, {});
		}

		getTabsInfoFromCache()
		{
			return Application.sharedStorage(CACHE_ID).get(this.getTabCacheId()) || [];
		}

		setTabsInfoToCache(value)
		{
			Application.sharedStorage(CACHE_ID).set(this.getTabCacheId(), value);
		}

		loadTabs()
		{
			new RunActionExecutor(`${this.props.endpoint}.loadTabConfig`, { ...this.getTabParams() })
				.setCacheId(this.getTabCacheId())
				.setCacheHandler((response) => this.setTabs(response))
				.setHandler((response) => this.setTabs(response))
				.enableJson()
				.call(true);
		}

		setTabs(response)
		{
			if (!response || !response.data)
			{
				return;
			}

			const tabsInfo = response.data.tabs;
			if (!isEqual(this.state.tabsInfo, tabsInfo))
			{
				this.setTabsInfoToCache(tabsInfo);
				this.setState({ tabsInfo });
			}
		}

		initializeTabs()
		{
			this.initializeActiveTab();
			this.availableTabs = this.getAvailableTabs();
		}

		initializeActiveTab()
		{
			if (this.activeTab === null)
			{
				const { activeTab } = this.getComponentParams();
				if (activeTab)
				{
					this.activeTab = activeTab;
				}
				else
				{
					this.activeTab = MAIN_TAB;
				}
			}
		}

		getAvailableTabs()
		{
			let tabs = this.state.tabsInfo;

			if (this.availableTabsHandler)
			{
				tabs = this.availableTabsHandler(clone(tabs), this);
			}

			return tabs.filter((tab) => BX.prop.getBoolean(tab, 'available', true));
		}

		preloadTab(tabId, extraPayload = {})
		{
			const tabRef = this.tabRefMap.get(tabId);
			if (tabRef)
			{
				tabRef.fetch(extraPayload);
			}
		}

		showTab(activeTabId)
		{
			this.activeTab = activeTabId;

			this.preloadTab(activeTabId);
			this.dismissKeyboard();

			if (this.sliderRef)
			{
				const tabPosition = this.availableTabs.findIndex((tab) => tab.id === activeTabId);
				if (tabPosition >= 0)
				{
					this.sliderRef.scrollToPage(tabPosition);
				}
			}
		}

		renderTabHeader()
		{
			return TabView({
				style: {
					height: TAB_HEADER_HEIGHT,
					backgroundColor: '#f5f7f8',
				},
				params: {
					styles: {
						tabTitle: {
							underlineColor: '#2985e2',
						},
					},
					items: this.availableTabs.map((tab) => this.prepareTabViewItem(tab)),
				},
				onTabSelected: (tab, changed) => this.handleTabClick(tab, changed),
				ref: (ref) => this.tabViewRef = ref,
			});
		}

		prepareTabViewItem(tab)
		{
			let preparedTab = {
				id: tab.id,
				title: tab.title,
				selectable: tab.selectable,
				active: tab.id === this.activeTab,
				label: tab.label || '',
			};

			if (!this.mounted)
			{
				const { tabs } = this.getComponentParams();
				if (tabs && tabs[tab.id])
				{
					preparedTab = { ...preparedTab, ...tabs[tab.id] };
				}
			}

			const { label } = preparedTab;
			if (label)
			{
				let counter = Number(label);

				if (counter > MAX_TAB_COUNTER_VALUE)
				{
					counter = `${MAX_TAB_COUNTER_VALUE}+`;
				}
				else if (counter > 0)
				{
					counter = String(counter);
				}
				else
				{
					counter = '';
				}

				preparedTab.label = counter;
			}

			return preparedTab;
		}

		setTopToolbarFactory(topToolbarFactory)
		{
			this.topToolbarFactory = topToolbarFactory;

			return this;
		}

		setRightButtonsProvider(rightButtonsProvider)
		{
			this.rightButtonsProvider = rightButtonsProvider;

			return this;
		}

		isReadonly()
		{
			return this.readOnly;
		}

		getEntityTypeId()
		{
			const { entityTypeId } = this.getComponentParams();

			return entityTypeId;
		}

		hasEntityModel()
		{
			return this.entityModel !== null;
		}

		isNewEntity()
		{
			const entityId = this.getEntityId();

			return !BX.type.isNumber(Number(entityId)) || Number(entityId) <= 0;
		}

		getEntityId()
		{
			if (this.hasEntityModel())
			{
				return this.getEntityIdFromModel();
			}

			return this.getEntityIdFromParams();
		}

		getFieldFromModel(name, defaultValue = null)
		{
			if (this.hasEntityModel())
			{
				return this.entityModel.hasOwnProperty(name) ? this.entityModel[name] : defaultValue;
			}

			return defaultValue;
		}

		getEntityIdFromModel()
		{
			if (this.hasEntityModel())
			{
				return this.getFieldFromModel('ID');
			}

			return null;
		}

		getEntityIdFromParams()
		{
			const { entityId } = this.getComponentParams();

			return entityId;
		}

		isEnabledTopToolbar()
		{
			return (
				this.topToolbarFactory
				&& this.topToolbarFactory.has({
					typeId: this.getEntityTypeId(),
					activeTab: this.activeTab,
				})
			);
		}

		renderTopToolbar(duration)
		{
			if (this.isEnabledTopToolbar())
			{
				return this.topToolbarFactory.create(
					{
						typeId: this.getEntityTypeId(),
						activeTab: this.activeTab,
					},
					{
						...this.getComponentParams(),
						ref: (ref) => this.topToolbarRef = ref,
						detailCard: this,
						animation: {
							duration,
						},
						height: TOP_TOOLBAR_HEIGHT,
					});
			}

			return null;
		}

		renderTabSlider()
		{
			if (this.loadedTabsData)
			{
				// this hack is needed to make the slider rerender correctly with new refs
				return View(
					{
						style: {
							flex: 1,
						},
					},
					this.renderSlider(),
				);
			}

			return this.renderSlider();
		}

		renderSlider()
		{
			return Slider(
				{
					bounces: true,
					style: {
						flex: 1,
					},
					ref: (ref) => this.sliderRef = ref,
					onPageWillChange: this.handleSliderPageWillChange.bind(this),
					onPageChange: this.handleSliderPageChange.bind(this),
				},
				...this.getEachTabContent(),
			);
		}

		renderTopPadding()
		{
			return new ToolbarPadding({
				ref: (ref) => this.topPaddingRef = ref,
				height: TOP_TOOLBAR_HEIGHT - 1,
				animation: {
					duration: DURATION,
				},
				content: View(
					{
						style: {
							width: '100%',
							height: 80,
						},
					},
				),
			});
		}

		renderTopContent()
		{
			return View(
				{
					style: {
						position: 'absolute',
						top: 44,
						left: 0,
						width: '100%',
						height: 80,
					},
					clickable: false,
				},
				this.renderTopToolbar(DURATION),
			);
		}

		renderBottomPadding()
		{
			return new ToolbarPadding({
				ref: (ref) => this.bottomPaddingRef = ref,
			});
		}

		renderActionsPanel()
		{
			return new ActionsPanel({
				ref: (ref) => this.actionsPanelRef = ref,
				actions: this.itemActions,
				onActionStart: this.handleOnActionStart.bind(this),
				onActionSuccess: this.handleActionSuccess.bind(this),
				onActionFailure: this.handleActionFailure.bind(this),
			});
		}

		handleOnActionStart(action)
		{
			NotifyManager.showLoadingIndicator();

			if (action.id)
			{
				AnalyticsLabel.send({
					...this.getEntityAnalyticsData(),
					event: action.id,
				});
			}
		}

		handleActionSuccess(action, data)
		{
			this
				.reload(data.load)
				.then(() => {
					NotifyManager.hideLoadingIndicator(true);
					this.emitEntityUpdate();

					if (action.id)
					{
						AnalyticsLabel.send({
							...this.getEntityAnalyticsData(),
							event: action.id + '-success',
						});
					}
				})
				.catch((error) => {
					NotifyManager.hideLoadingIndicator(false);
					console.error(error, action, data);
				})
			;
		}

		handleActionFailure(action, { errors = [], showErrors = true })
		{
			if (showErrors)
			{
				NotifyManager.showErrors(errors);
			}
			else
			{
				NotifyManager.hideLoadingIndicator(false);
			}

			console.error('DetailCard::handleActionFailure', args);
		}

		isActionsPanelVisible()
		{
			if (this.isSaving)
			{
				return false;
			}

			return (
				this.hasEntityModel()
				&& !this.isNewEntity()
				&& !this.isToolPanelVisible()
				&& this.itemActions.filter((action) => action.onActiveCallback(this.entityModel)).length > 0
			);
		}

		isToolPanelVisible()
		{
			if (this.readOnly)
			{
				return false;
			}

			return this.isNewEntity() || this.isChanged;
		}

		reload(tabsData)
		{
			this.entityModel = null;

			if (!Array.isArray(tabsData))
			{
				return Promise.resolve();
			}

			const newTabs = this.getAvailableTabs();
			if (!isEqual(this.availableTabs, newTabs))
			{
				return this.rerenderToApplyTabChanges(tabsData);
			}

			const results = [];
			for (const tabData of tabsData)
			{
				const tabRef = this.tabRefMap.get(tabData.id);
				if (tabRef)
				{
					results.push(tabRef.setResult(tabData.result));
				}
			}

			return Promise.all(results);
		}

		rerenderToApplyTabChanges(tabsData)
		{
			// save loaded tabs data to forward via props in future tab render
			this.loadedTabsData = tabsData;

			return new Promise((resolve) => this.setState({}, resolve));
		}

		getEachTabContent()
		{
			const tabsData = this.loadedTabsData;
			this.loadedTabsData = null;

			const hasTabsData = tabsData !== null && Array.isArray(tabsData);
			const tabsExternalData = this.getTabsExternalData();

			return this.availableTabs
				.map((tab) => {
					if (!tab.selectable)
					{
						return null;
					}

					const tabId = tab.id;
					const tabData = hasTabsData && tabsData.find((tabData) => tabData.id === tabId) || false;

					return TabFactory.create(tab.type, {
						id: tabId,
						uid: this.getUid(),
						ref: (ref) => {
							if (ref)
							{
								this.tabRefMap.set(tabId, ref);
							}
						},
						detailCardRef: this,
						endpoint: `${this.props.endpoint}.load`,
						payload: {
							tabId,
							...tab.payload,
							...this.getComponentParams(),
						},
						result: hasTabsData ? tabData && tabData.result : undefined,
						externalData: tabsExternalData && tabsExternalData[tabId],
						onErrorHandler: this.ajaxErrorHandler,
						onContentLoaded: this.handleTabContentLoaded.bind(this, tabId),
						onScroll: (scrollParams) => this.handleTabScroll(scrollParams, tabId),
					});
				});
		}

		getTabsExternalData()
		{
			const { tabsExternalData = {} } = this.getComponentParams();

			return tabsExternalData;
		}

		clearTabsExternalData()
		{
			this.setComponentParams({ tabsExternalData: null });
		}

		handleSliderPageWillChange(tabPosition, direction)
		{
			if (direction === 'right')
			{
				tabPosition++;
			}
			else
			{
				tabPosition--;
			}

			const selectedTab = this.availableTabs[tabPosition];
			if (selectedTab && selectedTab.selectable)
			{
				this.preloadTab(selectedTab.id);
			}
		}

		handleSliderPageChange(tabPosition)
		{
			const selectedTab = this.availableTabs[tabPosition];
			if (selectedTab && selectedTab.selectable)
			{
				this.setActiveTab(selectedTab.id);

				this.tabRefMap.forEach((tabRef) => tabRef.setActive(tabRef.id === selectedTab.id));
			}
		}

		setActiveTab(tabId)
		{
			if (this.tabViewRef)
			{
				const currentTab = this.tabViewRef.getCurrentItem();

				if (!currentTab || currentTab.id !== tabId)
				{
					this.tabViewRef.setActiveItem(tabId);
				}
			}
		}

		handleTabClick(tab, changed)
		{
			if (changed)
			{
				this.showTab(tab.id);
			}
			else if (tab.selectable === false)
			{
				this.showTab(this.activeTab);
				this.openBackDropDemo(tab.id);
			}
			else
			{
				this.dismissKeyboard();
				this.scrollTabToTop(tab.id);
			}
		}

		scrollTabToTop(tabId)
		{
			const tabRef = this.tabRefMap.get(tabId);
			if (tabRef)
			{
				tabRef.scrollTop();
			}
		}

		handleTabChange()
		{
			if (!this.isChanged)
			{
				this.markChanged();
			}
		}

		markChanged()
		{
			this.isChanged = true;
			this.checkToolbarPanel();
		}

		handleTabEdit(tabId, isEditing)
		{
			if (this.isEditing !== isEditing)
			{
				this.isEditing = isEditing;

				if (this.isEditing)
				{
					this.checkToolbarPanel();
				}
				else
				{
					// delay toolbar render when just changed focus from one field to another
					this.checkToolbarPanelDebounced();
				}
			}
		}

		handleEntityModelReady(entityModel)
		{
			if (this.hasEntityModel() && isEqual(this.entityModel, entityModel))
			{
				return;
			}

			this.entityModel = entityModel;

			if (this.getEntityIdFromParams() && !this.getEntityIdFromModel())
			{
				this.emitEntityUpdate();
				this.close();

				setTimeout(() => {
					Alert.alert(
						'',
						BX.message('DETAIL_CARD_RECORD_NOT_FOUND_2'),
						null,
						BX.message('DETAIL_CARD_DEFAULT_ALERT_CONFIRM'),
					);
				}, 300);

				return;
			}

			this.loadTabCounters();

			if (this.isNewEntity())
			{
				AnalyticsLabel.send({
					...this.getEntityAnalyticsData(),
					event: 'tryingToCreate',
				});
			}

			if (this.onEntityModelReadyHandler)
			{
				this.onEntityModelReadyHandler(this);
			}
		}

		handleEntityModelChange(entityModel)
		{
			this.entityModel = entityModel;

			if (!this.isChanged)
			{
				this.markChanged();
			}
		}

		handleEntityEditorInit({ readOnly })
		{
			this.readOnly = readOnly;

			this.checkToolbarPanel();
		}

		handleTabScroll(scrollParams, tabId)
		{
			this.customEventEmitter.emit('DetailCard::onScroll', [scrollParams, tabId]);
		}

		handleSave(additionalData = {})
		{
			if (this.isLoading)
			{
				return;
			}

			const isNewEntity = this.isNewEntity();

			this.isSaving = true;
			this.setLoading(true);
			this.dismissKeyboard();

			return (
				this
					.dismissKeyboard()
					.then(() => this.validate())
					.then(() => this.hideToolbars())
					.then(() => NotifyManager.showLoadingIndicator())
					.then(() => this.getData())
					.then((payload) => this.runSave(payload, additionalData))
					.then((response) => this.processSave(response))
					.then(() => this.showTabAndScrollActions(isNewEntity))
					.then(() => this.emitSaveEvents(isNewEntity))
					.then(() => NotifyManager.hideLoadingIndicator(true))
					.catch((errors) => {
						this.isSaving = false;
						this.setLoading(false);

						// validation returns false
						if (Array.isArray(errors))
						{
							if (errors.length)
							{
								NotifyManager.showErrors(errors);
							}
							else
							{
								NotifyManager.hideLoadingIndicator(false);
							}
						}
						else
						{
							console.error(errors);
							NotifyManager.hideLoadingIndicatorWithoutFallback();
						}

						return Promise.reject();
					})
			);
		}

		handleTabPreloadRequest(tabId, extraPayload = {})
		{
			const tabRef = this.tabRefMap.get(tabId);
			if (tabRef)
			{
				this.preloadTab(tabId, extraPayload);
			}
		}

		handleTabContentLoaded(tabId, response)
		{
			if (!this.anyTabWasLoaded)
			{
				this.anyTabWasLoaded = true;

				if (this.activeTab === tabId)
				{
					this.showTab(this.activeTab);
				}
			}

			this.customEventEmitter.emit('DetailCard::onTabContentLoaded', [tabId]);

			if (response.header)
			{
				this.setHeader(response.header);
			}

			if (response.params)
			{
				this.setComponentParams(response.params);
			}

			if (this.onTabContentLoadedHandler)
			{
				this.onTabContentLoadedHandler(tabId, response, this);
			}

			if (this.activeTab !== MAIN_TAB)
			{
				this.preloadTab(MAIN_TAB);
			}
		}

		setLoading(isLoading)
		{
			this.isLoading = isLoading;
			this.checkToolbarPanel();
		}

		showConfirmDiscardChanges(onSuccess, onFailed)
		{
			Alert.confirm(
				BX.message('DETAIL_CARD_DISCARD_CHANGES_ALERT_TITLE'),
				BX.message('DETAIL_CARD_DISCARD_CHANGES_ALERT_TEXT'),
				[
					{
						text: BX.message('DETAIL_CARD_DISCARD_CHANGES_ALERT_OK'),
						type: 'destructive',
						onPress: onSuccess,
					},
					{
						text: BX.message('DETAIL_CARD_DISCARD_CHANGES_ALERT_CANCEL'),
						type: 'cancel',
						onPress: onFailed,
					},
				],
			);
		}

		handleCancelNewEntity()
		{
			return new Promise((resolve) => {
				if (!this.isChanged)
				{
					this.close();
					resolve();
				}
				else
				{
					this.showConfirmDiscardChanges(() => {
						this.close();
						resolve();
					});
				}
			});
		}

		handleCancelExistingEntity()
		{
			return new Promise((resolve) => {
				if (!this.isChanged)
				{
					this
						.dismissKeyboard()
						.then(() => {
							this.isEditing = false;
							this.checkToolbarPanel();
							this.customEventEmitter.emit('UI.EntityEditor::switchToViewMode');
							resolve();
						})
					;
				}
				else
				{
					this.showConfirmDiscardChanges(
						() => {
							NotifyManager.showLoadingIndicator();

							this
								.dismissKeyboard()
								.then(() => this.refreshDetailCard())
								.then(() => {
									NotifyManager.hideLoadingIndicatorWithoutFallback();
									resolve();
								})
								.catch(() => NotifyManager.hideLoadingIndicator(false))
							;
						},
						resolve,
					);
				}
			});
		}

		handleExitFromEntity()
		{
			if (this.isClosing)
			{
				return Promise.resolve();
			}

			let promise = Promise.resolve();

			if (this.isChanged)
			{
				promise = promise.then(() => new Promise((resolve, reject) => {
					this.showConfirmExitEntity(
						() => this.handleSave().then(resolve).catch(reject),
						resolve,
					);
				}));
			}

			if (this.onCloseHandler)
			{
				promise = promise.then(() => this.onCloseHandler());
			}

			return promise.then(() => this.close());
		}

		showConfirmExitEntity(onSave, onDiscard)
		{
			Haptics.impactLight();

			Alert.confirm(
				BX.message('DETAIL_CARD_EXIT_ENTITY_ALERT_TITLE'),
				BX.message('DETAIL_CARD_EXIT_ENTITY_ALERT_TEXT2'),
				[
					{
						text: BX.message('DETAIL_CARD_EXIT_ENTITY_ALERT_SAVE'),
						type: 'default',
						onPress: onSave,
					},
					{
						text: BX.message('DETAIL_CARD_EXIT_ENTITY_ALERT_DISCARD'),
						type: 'destructive',
						onPress: onDiscard,
					},
					{
						text: BX.message('DETAIL_CARD_DISCARD_CHANGES_ALERT_CANCEL'),
						type: 'cancel',
					},
				],
			);
		}

		refreshDetailCard()
		{
			if (!this.isChanged && !this.isEditing)
			{
				return Promise.resolve();
			}

			return Promise.all([
				new Promise((resolve) => {
					this.isChanged = false;
					this.isEditing = false;
					this.checkToolbarPanel();
					resolve();
				}),
				...[...this.tabRefMap.values()].map((tabRef) => tabRef.refreshResult()),
			]);
		}

		handleCancel()
		{
			if (this.isNewEntity())
			{
				return this.handleCancelNewEntity();
			}

			return this.handleCancelExistingEntity();
		}

		validate()
		{
			return (
				Promise
					.all(this.getTabs().map((tab) => tab.validate()))
					.then((validationResults) => {
						let showTabWithErrors = null;
						const errors = [];

						validationResults.forEach((validationResult, index) => {
							if (showTabWithErrors !== null)
							{
								return;
							}

							const hasErrors = (
								validationResult === false
								|| (Array.isArray(validationResult) && validationResult.length)
							);

							if (!hasErrors)
							{
								return;
							}

							if (Array.isArray(validationResult))
							{
								errors.push(...validationResult);
							}

							if (showTabWithErrors === null)
							{
								showTabWithErrors = index;
							}
						});

						if (showTabWithErrors !== null)
						{
							Haptics.notifyWarning();
							this.showTab(this.availableTabs[showTabWithErrors].id);

							if (errors.length)
							{
								NotifyManager.showErrors(errors);
							}

							return Promise.reject(false);
						}

						return Promise.resolve();
					})
			);
		}

		/**
		 * @returns {Tab[]}
		 */
		getTabs()
		{
			return (
				this.availableTabs
					.map(({ id }) => this.tabRefMap.get(id))
					.filter(Boolean)
			);
		}

		/**
		 * @public
		 * @param {string} tabId
		 * @return {Tab|undefined}
		 */
		getTab(tabId)
		{
			return this.tabRefMap.get(tabId);
		}

		getData()
		{
			return (
				Promise
					.all(this.getTabs().map((tab) => tab.getData()))
					.then((getDataResults) => {
						let payload = {};

						for (const getDataResult of getDataResults)
						{
							payload = { ...payload, ...getDataResult };
						}

						return payload;
					})
					.catch(() => Promise.reject([]))
			);
		}

		runSave(payload, additionalData = {}, sendAnalytics = true)
		{
			payload = mergeImmutable(payload, additionalData);

			return (
				BX.ajax
					.runAction(this.getSaveEndpoint(), {
						json: {
							...this.getComponentParams(),
							data: payload,
							loadedTabs: this.getLoadedTabs(),
						},
						analyticsLabel: sendAnalytics && {
							...this.getEntityAnalyticsData(),
							event: 'save',
						},
					})
					.then((response) => this.processSaveErrors(response, payload))
					.catch((response) => this.processSaveErrors(response, payload))
			);
		}

		getSaveEndpoint()
		{
			const action = this.isNewEntity() ? 'add' : 'update';

			return this.getActionEndpoint(action);
		}

		/**
		 * @public
		 * @param {string} action
		 * @returns {*}
		 */
		getActionEndpoint(action)
		{
			return `${this.props.endpoint}.${action}`;
		}

		getLoadedTabs()
		{
			return (
				[...this.tabRefMap]
					.map(([tabId, tabRef]) => {
						if (!tabRef.inInitialStatus())
						{
							return tabId;
						}

						return null;
					})
					.filter(Boolean)
			);
		}

		processSaveErrors(response, payload)
		{
			if (this.ajaxErrorHandler)
			{
				return this.ajaxErrorHandler(response, payload);
			}

			return new Promise((resolve, reject) => {
				const { errors } = response;
				if (errors && errors.length)
				{
					if (this.areSaveErrorsCritical(errors))
					{
						reject(errors);
					}
					else
					{
						NotifyManager.showErrors([
							BX.message('DETAIL_CARD_RECORD_SAVE_SUCCESS_WITH_ERRORS'),
							...errors,
						]);
						resolve(response);
					}
				}
				else
				{
					resolve(response);
				}
			});
		}

		processSave(response)
		{
			this.isChanged = false;
			this.isEditing = false;
			this.isSaving = false;

			this.setLoading(false);
			this.clearTabsExternalData();

			const { entityId, title, header, params, load } = response.data;

			if (header)
			{
				this.setHeader(header);
			}
			else
			{
				this.layout.setTitle({ text: title }, true);
			}

			if (params)
			{
				this.setComponentParams(params);
			}

			if (entityId)
			{
				this.setComponentParams({ entityId, title });
				this.entityModel.ID = entityId;
			}

			const { closeOnSave } = this.getComponentParams();
			if (closeOnSave)
			{
				this.close();

				return Promise.resolve();
			}

			return this.reload(load);
		}

		setHeaderProcessor(headerProcessor)
		{
			this.headerProcessor = headerProcessor;

			return this;
		}

		setHeader(header)
		{
			if (this.headerProcessor)
			{
				header = this.headerProcessor(header, this);
			}

			if (!isEqual(this.header, header))
			{
				this.header = header;
				this.layout.setTitle(header, true);
			}
		}

		showTabAndScrollActions(isNewEntity)
		{
			const activeTabId = this.activeTab;

			if (isNewEntity)
			{
				this.setActiveTab(this.availableTabs[0].id);

				this.getTabs().forEach((tab) => {
					tab.scrollTop(this.activeTab === activeTabId);
				});
			}
		}

		openBackDropDemo(tabId)
		{
			const tab = this.availableTabs.find((tab) => tab.id === tabId);
			if (tab && tab.desktopUrl)
			{
				qrauth.open({
					title: tab.title,
					redirectUrl: tab.desktopUrl,
				});
			}
		}

		render()
		{
			this.initializeTabs();

			return View(
				{
					resizableByKeyboard: true,
					style: {
						backgroundColor: '#eef2f4',
					},
					// onPan: () => this.dismissKeyboard(),
				},
				this.renderTabHeader(),
				this.renderTopPadding(),
				this.renderTabSlider(),
				this.renderBottomPadding(),
				this.renderActionsPanel(),
				...this.getAdditionalElements(),
				this.renderTopContent(),
			);
		}

		dismissKeyboard()
		{
			return FocusManager.blurFocusedFieldIfHas();
		}

		emitSaveEvents(isNewEntity)
		{
			if (isNewEntity)
			{
				this.emitEntityCreate();
			}
			else
			{
				this.emitEntityUpdate();
			}
		}

		emitEntityCreate()
		{
			const params = this.getComponentParams();
			params.eventUid = this.createUid();
			params.entityModel = this.entityModel;

			this.customEventEmitter.emit('DetailCard::onCreate', [params]);
		}

		emitEntityUpdate(actionName)
		{
			const params = this.getComponentParams();
			params.actionName = actionName;
			params.eventUid = this.createUid();
			params.entityModel = this.entityModel;

			this.customEventEmitter.emit('DetailCard::onUpdate', [params, this.header]);
		}

		emitDetailClose()
		{
			this.customEventEmitter.emit('DetailCard::onClose', [this.getComponentParams()]);
		}

		renderTo(layout)
		{
			this.layout = layout;

			BX.onViewLoaded(() => {
				this.layout.enableNavigationBarBorder(false);

				this.layout.setListener((eventName) => {
					// onViewWillHidden - iOS, onViewRemoved - Android
					if (eventName === 'onViewWillHidden' || eventName === 'onViewRemoved')
					{
						this.emitDetailClose();
					}
				});

				this.layout.setBackButtonHandler(() => this.handleExitFromEntity() && true);

				this.checkToolbarPanel();

				this.layout.showComponent(this);
			});

			return this;
		}

		getComponentParams()
		{
			if (this.componentParams === null)
			{
				this.componentParams = BX.componentParameters.get('payload', {});
			}

			return this.componentParams;
		}

		setComponentParams(componentParams)
		{
			merge(this.componentParams, componentParams);

			return this;
		}

		showMenu()
		{
			this.createMenu().show();
		}

		createMenu()
		{
			this.menu = new UI.Menu(() => {
				return this.menuActionsProvider(
					this,
					{
						onActionStart: this.handleOnActionStart.bind(this),
						onActionSuccess: this.handleActionSuccess.bind(this),
						onActionFailure: this.handleActionFailure.bind(this),
					},
				);
			});

			return this.menu;
		}

		setMenuActionsProvider(provider)
		{
			this.menuActionsProvider = provider;

			return this;
		}

		setItemActions(actions)
		{
			this.itemActions = actions;

			return this;
		}

		setAdditionalElementsProvider(additionalButtonProvider)
		{
			this.setAdditionalProvider = additionalButtonProvider;

			return this;
		}

		/**
		 * @param {Function} onCloseHandler
		 * @returns {DetailCardComponent}
		 */
		setOnCloseHandler(onCloseHandler)
		{
			this.onCloseHandler = (...args) => onCloseHandler(this, ...args);

			return this;
		}

		/**
		 * @param {Function} ajaxErrorHandler
		 * @returns {DetailCardComponent}
		 */
		setAjaxErrorHandler(ajaxErrorHandler)
		{
			this.ajaxErrorHandler = (...args) => ajaxErrorHandler(this, ...args);

			return this;
		}

		/**
		 * @param {Function} availableTabsHandler
		 * @returns {DetailCardComponent}
		 */
		setAvailableTabsHandler(availableTabsHandler)
		{
			this.availableTabsHandler = availableTabsHandler;

			return this;
		}

		getEntityAnalyticsData()
		{
			if (typeof this.analyticsProvider === 'function')
			{
				return this.analyticsProvider(clone(this.entityModel));
			}

			return {};
		}

		setAnalyticsProvider(provider)
		{
			this.analyticsProvider = provider;

			return this;
		}

		areSaveErrorsCritical(errors)
		{
			for (const error of errors)
			{
				if (
					(
						!error.hasOwnProperty('customData')
						|| error.customData === null
					)
					|| (
						error.customData.hasOwnProperty('NON_CRITICAL')
						&& error.customData['NON_CRITICAL'] !== true
					)
				)
				{
					return true;
				}
			}

			return false;
		}

		getTestId()
		{
			const testIdPrefix = this.testIdPrefix || 'UI_DETAIL_CARD';
			const entityTypeId = this.getEntityTypeId();
			const entityId = this.getEntityId() || 'NEW';

			return `${testIdPrefix}_${entityTypeId}_${entityId}`;
		}

		/**
		 * @param {String} testIdPrefix
		 * @returns {DetailCardComponent}
		 */
		setTestIdPrefix(testIdPrefix)
		{
			this.testIdPrefix = testIdPrefix;

			return this;
		}

		setTabCounter(tabId, value = 0)
		{
			let tab = this.availableTabs.find((tab) => tab.id === tabId);
			if (tab && this.tabViewRef)
			{
				tab = this.prepareTabViewItem({
					...tab,
					label: String(value),
				});

				this.tabViewRef.updateItem(tabId, tab);
			}
		}

		/**
		 * @public
		 */
		loadTabCounters()
		{
			if (!this.props.isCountersLoadSupported || !this.hasEntityModel())
			{
				return;
			}

			const { entityTypeId, entityId, categoryId } = this.getComponentParams();
			const queryConfig = { json: { entityTypeId, entityId, categoryId } };

			BX.ajax
				.runAction(this.getActionEndpoint('loadTabCounters'), queryConfig)
				.then(response => {
					response.data.forEach(({ id, counter }) => {
						this.setTabCounter(id, counter);
					});
				})
				.catch(response => {
					console.warn('Unable to load tab counters', response);
				})
			;
		}

		close()
		{
			this.isClosing = true;

			if (this.layout)
			{
				this.layout.back();
				this.layout.close();
			}
		}

		/**
		 * @param {Function|null} handler
		 * @return {DetailCardComponent}
		 */
		onEntityModelReady(handler)
		{
			this.onEntityModelReadyHandler = handler;

			return this;
		}

		/**
		 * @param {Function|null} handler
		 * @return {DetailCardComponent}
		 */
		onTabContentLoaded(handler)
		{
			this.onTabContentLoadedHandler = handler;

			return this;
		}

		static create(result)
		{
			return new DetailCardComponent(result);
		}
	}

	this.UI = this.UI || {};
	this.UI.DetailCardComponent = DetailCardComponent;
})();
