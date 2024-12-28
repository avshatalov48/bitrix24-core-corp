/**
 * @module crm/entity-tab
 */
jn.define('crm/entity-tab', (require, exports, module) => {
	const { confirmDestructiveAction } = require('alert');
	const AppTheme = require('apptheme');
	const { AnalyticsEvent } = require('analytics');
	const { magnifierWithMenuAndDot } = require('assets/common');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { PureComponent } = require('layout/pure-component');
	const { ViewMode } = require('layout/ui/simple-list/view-mode');
	const { ImageAfterTypes } = require('layout/ui/context-menu/item');
	const { Loc } = require('loc');
	const { qrauth } = require('qrauth/utils');
	const { Color } = require('tokens');
	const { Notify } = require('notify');
	const { Type: CoreType } = require('type');
	const { clone, get, isEqual } = require('utils/object');
	const { capitalize } = require('utils/string');
	const { UIMenuType } = require('layout/ui/menu');
	const { EntitySvg } = require('crm/assets/entity');
	const { CategorySelectActions } = require('crm/category-list/actions');
	const {
		getActionToCopyEntity,
		getActionToChangePipeline,
		getActionToShare,
		getActionToConversion,
	} = require('crm/entity-actions');
	const { Filter } = require('layout/ui/kanban/filter');
	const { PullManager, TypePull } = require('crm/entity-tab/pull-manager');
	const { TypeSort, ItemsSortManager } = require('crm/entity-tab/sort');
	const { TypeFactory } = require('crm/entity-tab/type');
	const { getEntityMessage } = require('crm/loc');
	const { ActivityCountersStoreManager } = require('crm/state-storage');
	const { Type } = require('crm/type');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { Icon } = require('ui-system/blocks/icon');
	const { NotifyManager } = require('notify-manager');
	const store = require('statemanager/redux/store');
	const {
		fetchCrmKanban,
		getCrmKanbanUniqId,
		selectById: selectKanbanById,
		selectStatus,
		STATUS,
	} = require('crm/statemanager/redux/slices/kanban-settings');

	const {
		selectIdAndStatusByIds,
	} = require('crm/statemanager/redux/slices/stage-settings');

	const {
		fetchStageCounters,
	} = require('crm/statemanager/redux/slices/stage-counters');

	const PULL_MODULE_ID = 'crm';
	const PULL_EVENT_NAME_ITEM_UPDATED = 'ITEMUPDATED';
	// const PULL_EVENT_NAME_ITEM_DELETED = 'ITEMDELETED';
	const PULL_QUEUE_TTL = 500; // @todo set more time
	const ASSIGNED_WITH_OTHER_USERS = 'other-users';
	const MAX_CATEGORY_CHANGE_ATTEMPTS = 5;
	const CATEGORY_CHANGE_DELAY_TIME = 1000;

	/**
	 * @typedef {Object} EntityType
	 * @property {number} id
	 * @property {string} typeName
	 * @property {boolean} active
	 * @property {boolean} selectable
	 * @property {boolean} hasRestrictions
	 * @property {string} title
	 * @property {string} titleInPlural
	 * @property {string} link
	 * @property {string} entityLink
	 * @property {boolean} isCategoriesSupported
	 * @property {boolean} isCategoriesEnabled
	 * @property {boolean} isLastActivityEnabled
	 * @property {Object} permissions
	 * @property {boolean} permissions.add
	 * @property {boolean} permissions.update
	 * @property {boolean} permissions.read
	 * @property {boolean} permissions.delete
	 * @property {Object} data
	 * @property {Object} data.counters
	 * @property {string} data.counters.code
	 * @property {number} data.counters.value
	 */

	/**
	 * @class EntityTab
	 * @abstract
	 */
	class EntityTab extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.entityTypeName = props.entityTypeName;
			/** @type {EntityType[]} */
			this.entityTypes = props.entityTypes;

			const currentCategoryId = this.getCurrentCategoryId();

			this.category = currentCategoryId === null ? null : this.getCategoryFromCategoryStorage(currentCategoryId);
			this.needReloadTab = !this.category;
			this.categoryChangeAttempts = 0;
			this.categoryChangeTimeoutId = null;

			this.eventUids = new Set();

			/** @type {ContextMenu} */
			this.floatingButtonMenu = null;

			this.isEmpty = true;
			this.viewComponent = null;

			this.pullManager = new PullManager();
			this.pullQueue = new Map();
			this.pullQueueTimer = null;

			this.state = {
				categoryId: currentCategoryId,
				isLoading: !this.category && currentCategoryId !== null,
				searchButtonBackgroundColor: null,
				forceRenderSwitcher: false,
				isEmptyAvailableCategories: false,
			};

			/** @type {Filter} */
			this.filter = new Filter(this.getDefaultPresetId());

			this.initItemsSortManager(props);

			this.deleteItemConfirm = this.deleteItemConfirmHandler.bind(this);
			this.onSearchHandler = this.onSearch.bind(this);
			this.onSearchHideHandler = this.onSearchHide.bind(this);
			this.showSearchBarHandler = this.showSearchBar.bind(this);
			this.updateEntityTypesHandler = this.updateEntityTypes.bind(this);
			this.onSetSortTypeHandler = this.onSetSortType.bind(this);
			this.onSelectedCategory = this.onSelectedCategoryHandler.bind(this);
			this.onCategoryDelete = this.onCategoryDeleteHandler.bind(this);
			this.onCategoryClose = this.onCategoryCloseHandler.bind(this);
			this.showCategorySelector = this.showCategorySelector.bind(this);
			this.onNotViewable = this.onNotViewableHandler.bind(this);
			this.getEmptyListComponent = this.getEmptyListComponent.bind(this);
			this.onEmptyScreenRefresh = this.onEmptyScreenRefresh.bind(this);
			this.bindRef = this.bindRef.bind(this);
			this.itemDetailOpenHandler = this.handleItemDetailOpen.bind(this);
			this.onFloatingButtonClickHandler = this.handleFloatingButtonClick.bind(this);
			this.onFloatingButtonLongClickHandler = this.handleFloatingButtonLongClick.bind(this);
			this.onDetailCardUpdateHandler = this.onDetailCardUpdate.bind(this);
			this.onDetailCardCreateHandler = this.onDetailCardCreate.bind(this);
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		getView()
		{}

		componentWillReceiveProps(nextProps)
		{
			this.entityTypes = nextProps.entityTypes;

			this.initItemsSortManager(nextProps);

			const entityType = this.getEntityType(nextProps.entityTypeId);

			if (this.entityTypeName === nextProps.entityTypeName)
			{
				return;
			}

			if (!entityType.needSaveCurrentCategoryId && !entityType.isStagesEnabled)
			{
				this.entityTypeName = nextProps.entityTypeName;
				this.state.categoryId = this.getCurrentCategoryId();

				return;
			}

			this.setIsLoading().then(() => {
				this.filter = new Filter();
				this.needReloadTab = true;
				this.entityTypeName = nextProps.entityTypeName;

				const categoryId = this.getCurrentCategoryId();
				const categoryFromStorage = this.getCategoryFromCategoryStorage(categoryId);

				BX.componentParameters.set('analytics', this.getAnalytics());

				this.setState(
					{
						isLoading: false,
						searchButtonBackgroundColor: null,
						categoryId,
					},
					() => {
						setTimeout(() => {
							this.initAfterCategoryChange(
								categoryFromStorage,
								{ categoryId },
							);
						}, 50);
					},
				);
			}).catch((err) => {
				console.error(err);
				NotifyManager.showDefaultError();
			});
		}

		initItemsSortManager(props)
		{
			/** @type {ItemsSortManager} */
			this.itemsSortManager = ItemsSortManager.createFromEntityTypeObject(
				this.getEntityTypeByName(props.entityTypeName),
				props.actions.setSortType,
			);
		}

		componentDidMount()
		{
			BX.addCustomEvent('UI.SearchBar::onSearch', this.onSearchHandler);
			BX.addCustomEvent('UI.SearchBar::onSearchHide', this.onSearchHideHandler);
			BX.addCustomEvent('Crm.CategoryList::onSelectedCategory', this.onSelectedCategory);
			BX.addCustomEvent('Crm.CategoryDetail::onDeleteCategory', this.onCategoryDelete);
			BX.addCustomEvent('Crm.CategoryDetail::onClose', this.onCategoryClose);

			ActivityCountersStoreManager
				.subscribe('activityCountersModel/setCounters', this.updateEntityTypesHandler);

			this.unsubscribeFromStore = store.subscribe(() => {
				const category = this.getCategoryFromCategoryStorage(this.state.categoryId);

				if (!isEqual(this.category, category))
				{
					this.applyCategoryStorageChanges();
				}
			});

			if (!this.props.permissions.read)
			{
				const currentCategoryId = this.getCurrentCategoryId();
				const category = this.getCategoryFromCategoryStorage(currentCategoryId);
				this.changeCategory(category, true);
			}

			BX.componentParameters.set('analytics', this.getAnalytics());
		}

		componentWillUnmount()
		{
			if (this.unsubscribeFromStore)
			{
				this.unsubscribeFromStore();
			}

			BX.removeCustomEvent('UI.SearchBar::onSearch', this.onSearchHandler);
			BX.removeCustomEvent('UI.SearchBar::onSearchHide', this.onSearchHideHandler);

			BX.removeCustomEvent('Crm.CategoryList::onSelectedCategory', this.onSelectedCategory);
			BX.removeCustomEvent('Crm.CategoryDetail::onDeleteCategory', this.onCategoryDelete);
			BX.removeCustomEvent('Crm.CategoryDetail::onClose', this.onCategoryClose);

			ActivityCountersStoreManager
				.unsubscribe('activityCountersModel/setCounters', this.updateEntityTypesHandler);
		}

		onSetSortType(sortType)
		{
			this.updateEntityTypeData({
				sortType,
			}, () => {
				this.reload({
					skipFillSlides: true,
					skipUseCache: true,
					updateToolbarColumnId: false,
					skipInitCounters: true,
					force: true,
					initMenu: true,
				});
			});
		}

		/**
		 * @param {Object} data
		 * @param {Function|null} callback
		 */
		updateEntityTypeData(data, callback = null)
		{
			if (this.props.updateEntityTypeData)
			{
				this.props.updateEntityTypeData(this.props.entityTypeId, data, callback);
			}
		}

		updateEntityTypes()
		{
			const counters = ActivityCountersStoreManager.getCounters();

			let needChangeSearchIcon = false;
			this.entityTypes.forEach((entityType) => {
				entityType.data.counters.forEach((counter) => {
					if (
						counters[counter.code] >= 0
						&& counters[counter.code] !== counter.value
					)
					{
						if (
							entityType.typeName === this.entityTypeName
							&& (counter.value === 0 || counters[counter.code] === 0)
						)
						{
							needChangeSearchIcon = true;
						}

						counter.value = counters[counter.code];
					}
				});
			});

			if (needChangeSearchIcon)
			{
				this.getCurrentStatefulList().setMenuButtons(this.getMenuButtons());
				this.getCurrentStatefulList().initMenu();
			}
		}

		blinkItemListView(itemId)
		{
			return this.getCurrentStatefulList().blinkItem(itemId);
		}

		deleteRowFromListView(itemId)
		{
			this.getCurrentStatefulList().deleteItem(itemId);
		}

		bindRef(ref)
		{
			if (ref)
			{
				this.viewComponent = ref;
			}
		}

		onSearch(params)
		{
			const { searchBarId, data, isCancel, counter } = params;

			if (searchBarId !== this.props.searchBarId)
			{
				return;
			}

			this.state.searchButtonBackgroundColor = data?.background || null;

			if (isCancel)
			{
				this.filter.unsetWasShown();
			}

			if (counter && counter.code)
			{
				this.setCounterFilter(params);

				return;
			}

			this.setPresetFilter(params);
		}

		onSearchHide({ searchBarId })
		{
			if (searchBarId !== this.props.searchBarId)
			{
				return;
			}

			if (this.getCurrentStatefulList().getViewMode() === ViewMode.empty)
			{
				this.filter.unsetWasShown();
				this.setState({
					forceRenderSwitcher: !this.state.forceRenderSwitcher,
				});
			}
		}

		getCounterLongClickHandler()
		{
			const activityItem = this.getItemActions().find((item) => item.id === 'activity');

			if (activityItem && !activityItem.isDisabled)
			{
				return activityItem.onClickCallback;
			}

			return null;
		}

		setCounterFilter(params)
		{
			const { id, code, excludeUsers } = params.counter;
			const { text } = params;

			if (this.filter.isChecked(code) && this.filter.getSearchString() === text)
			{
				this.filter.clear();
			}

			// @todo remove after creating view mode Activity in the mobile
			else if (code === 'my_pending')
			{
				const assignedById = excludeUsers ? ASSIGNED_WITH_OTHER_USERS : env.userId;

				this.filter.set({
					counterId: code,
					presetId: 'tmp_filter',
					currentFilterId: code,
					tmpFields: {
						ASSIGNED_BY_ID: [assignedById],
						ACTIVITY_COUNTER: ['2'],
					},
					search: text,
				});
			}
			else
			{
				const assignedById = excludeUsers ? ASSIGNED_WITH_OTHER_USERS : env.userId;

				this.filter.set({
					counterId: code,
					presetId: 'tmp_filter',
					currentFilterId: code,
					tmpFields: {
						ASSIGNED_BY_ID: [assignedById],
						ACTIVITY_COUNTER: [id],
					},
					search: text,
				});
			}

			this.reload(this.getReloadParamsByFilter(params));
		}

		setPresetFilter(params)
		{
			const { preset, text } = params;
			const presetId = preset ? preset.id : 'tmp_filter';

			this.filter.set({
				presetId,
				search: text,
				currentFilterId: presetId,
				counterId: null,
				tmpFields: {},
			});

			this.reload(this.getReloadParamsByFilter(params));
		}

		getReloadParamsByFilter({ isCancel = false } = {})
		{
			return {
				skipFillSlides: true,
				menuButtons: this.getMenuButtons(),
				force: true,
				filterCancelled: isCancel,
			};
		}

		reload(params = {})
		{
			this.getViewComponent().reload(params);
		}

		getViewConfig()
		{
			return {
				resizableByKeyboard: true,
				style: {
					backgroundColor: AppTheme.colors.bgNavigation,
					flex: 1,
				},
			};
		}

		getMenuActions()
		{
			const actions = [
				{
					type: UIMenuType.DESKTOP,
					showHint: false,
					data: {
						qrUrl: this.getEntityType(this.props.entityTypeId).link,
						analyticsSection: 'crm',
					},
				},
			];

			const { userInfo } = this.props;
			const entity = TypeFactory.getEntityByType(this.entityTypeName, { userInfo });

			return [
				...actions,
				...entity.getMenuActions(),
			];
		}

		getMenuButtons()
		{
			const buttons = [];

			if (this.canUseChangeCategory())
			{
				buttons.push({
					type: Icon.CRM.getIconName(),
					badgeCode: 'kanban_categories_selector',
					callback: this.showCategorySelector,
				});
			}

			buttons.push({
				type: Icon.SEARCH.getIconName(),
				badgeCode: 'search',
				callback: this.showSearchBarHandler,
				accent: Boolean(this.state.searchButtonBackgroundColor),
			});

			return buttons;
		}

		getSearchButtonSvg()
		{
			const counters = this.getCounters();
			const hasCounter = counters
				.filter((counter) => !counter.excludeUsers)
				.some((counter) => counter.value > 0);

			return {
				content: magnifierWithMenuAndDot(
					AppTheme.colors.base4,
					this.state.searchButtonBackgroundColor,
					hasCounter ? AppTheme.colors.accentMainAlert : null,
				),
			};
		}

		showSearchBar()
		{
			const entityType = this.getCurrentEntityType();

			let presetId = this.filter.getPresetId();
			if (!presetId && !this.filter.isActive())
			{
				presetId = entityType.data.presetId;
			}

			const counterId = this.filter.getCounterId();
			if (counterId)
			{
				presetId = null;
			}

			this.filter.set({
				presetId,
				counterId,
				search: this.filter.getSearchString(),
			}).setWasShown();

			BX.postComponentEvent('UI.SearchBar::show', [
				{
					presetId,
					counterId,
					search: this.filter.getSearchString(),
					searchBarId: this.props.searchBarId,
				},
			]);
		}

		/**
		 * @returns {Object}
		 */
		prepareActionParams()
		{
			const actionParams = clone(this.props.actionParams);
			actionParams.loadItems.extra = actionParams.loadItems.extra || {};
			actionParams.loadItems.extra.filterParams = actionParams.loadItems.extra.filterParams || {};

			const categoryId = this.getCategoryId();
			if (Number.isInteger(categoryId))
			{
				actionParams.loadItems.extra.filterParams.CATEGORY_ID = categoryId;
			}

			const entityType = this.getCurrentEntityType();
			const { presetId } = entityType.data;

			if (this.filter.hasSearchText())
			{
				actionParams.loadItems.extra.search = this.filter.getSearchString();
			}

			if (this.filter.getPresetId())
			{
				actionParams.loadItems.extra.filterParams.FILTER_PRESET_ID = this.filter.getPresetId();
				actionParams.loadItems.extra.filter = this.filter.getData();
			}
			else if (this.filter.isActive())
			{
				actionParams.loadItems.extra.filterParams.FILTER_PRESET_ID = this.filter.getEmptyFilterPresetId();
			}
			else
			{
				actionParams.loadItems.extra.filterParams.FILTER_PRESET_ID = (
					presetId || this.filter.getEmptyFilterPresetId()
				);
			}

			return actionParams;
		}

		/**
		 * @return {boolean}
		 */
		isActiveSearch()
		{
			const { searchRef } = this.props;

			return Boolean(this.filter.isActive()
				|| this.filter.hasSearchText()
				|| this.filter.hasSelectedNotDefaultPreset(searchRef));
		}

		/**
		 * @return {EmptyScreen}
		 */
		getEmptyListComponent()
		{
			return new EmptyScreen({
				...this.getEmptyScreenProps(),
				onRefresh: this.onEmptyScreenRefresh,
			});
		}

		onEmptyScreenRefresh()
		{
			this.getCurrentStatefulList().reloadList();
		}

		/**
		 * @return {EmptyScreen}
		 */
		getEmptyScreenProps()
		{
			let emptyScreenParams = {};
			const model = this.getEntityTypeModel();

			if (this.isActiveSearch())
			{
				emptyScreenParams = model.getEmptySearchScreenConfig();
			}
			else
			{
				emptyScreenParams = this.isAllStagesDisplayed()
					? model.getEmptyEntityScreenConfig()
					: this.getEmptyColumnScreenConfig(model);
			}

			return emptyScreenParams;
		}

		getEmptyColumnScreenConfig(model)
		{
			if (this.getCurrentEntityType().isStagesEnabled)
			{
				return model.getEmptyColumnScreenConfig();
			}

			return model.getEmptyEntityScreenConfig();
		}

		/**
		 * @returns {null|EntityType}
		 */
		getCurrentEntityType()
		{
			if (!this.entityTypes || !this.entityTypeName)
			{
				return null;
			}

			return this.getEntityTypeByName(this.entityTypeName);
		}

		/**
		 * @returns {null|EntityType}
		 */
		getEntityTypeByName(name)
		{
			return this.entityTypes.find((item) => {
				return item.typeName === name;
			});
		}

		getPullConfig()
		{
			return {
				moduleId: PULL_MODULE_ID,
				callback: (data, context) => {
					console.log('handle pull', data, context);

					return new Promise((resolve, reject) => {
						if (this.isNeedProcessPull(data, context))
						{
							const entityTypeId = this.props.entityTypeId;
							const categoryId = this.getCategoryId(entityTypeId);

							store.dispatch(fetchStageCounters({
								entityTypeId,
								categoryId,
								params: {
									filter: this.filter,
								},
								forceFetch: true,
							}));

							const { item, eventName } = data.params;
							const oldItem = this.getCurrentStatefulList().getItemComponent(item.id);

							// update item column
							if (
								eventName === PULL_EVENT_NAME_ITEM_UPDATED
								&& this.hasColumnChangesInItem(item, oldItem)
								&& this.hasItemInCurrentColumn(item.data.id)
								&& !this.isCurrentStage(item.data.columnId)
							)
							{
								let action;

								// update columnId in AllStages column
								if (this.isAllStagesDisplayed())
								{
									action = 'update';
									this.updateItemColumn(item.id, item.data.columnId);
								}
								// or delete item from current (not AllStages) column
								else
								{
									action = 'delete';
									this.getViewComponent().deleteItemFromStatefulList(item.id);
								}

								BX.postComponentEvent('UI.Kanban::onItemMoved', [
									{
										item,
										oldItem: oldItem.props.item,
										resolveParams: { action },
									},
								]);

								reject();

								return;
							}

							const itemId = Number(item.id);
							this.pullQueue.set(itemId, {
								id: itemId,
								columnId: item.data.columnId,
								categoryId: item.data.categoryId,
								eventName,
							});

							this.processPullQueue().then((preparedData) => {
								resolve(preparedData);
							}).catch(
								() => {
									reject();
								},
							);
						}
						else
						{
							reject();
						}
					});
				},
			};
		}

		/**
		 * @param {Object} data
		 * @param {Object} context
		 * @returns {Boolean}
		 */
		isNeedProcessPull(data, context)
		{
			this.abstract();
		}

		hasColumnChangesInItem(item, oldItem)
		{
			this.abstract();
		}

		/**
		 * @param {string} prefix
		 * @returns {string}
		 */
		getPullCommand(prefix)
		{
			const { entityTypeId, entityTypeName } = this.props;
			const entityType = this.getEntityType(entityTypeId);

			const isCategoriesSupported = entityType && entityType.isCategoriesSupported;
			const isContact = entityTypeName && entityTypeName === 'CONTACT';

			return (isCategoriesSupported || isContact)
				? `${prefix}_${entityTypeName}_${this.getCategoryId()}`
				: `${prefix}_${entityTypeName}`;
		}

		/**
		 * @param {Number} id
		 * @returns {Boolean}
		 */
		hasItemInCurrentColumn(id)
		{
			return this.getCurrentStatefulList().hasItem(id);
		}

		getCurrentStatefulList()
		{
			this.abstract();
		}

		isCurrentStage(stageCode)
		{
			this.abstract();
		}

		processPullQueue()
		{
			return new Promise((resolve, reject) => {
				if (this.pullQueueTimer)
				{
					reject();

					return;
				}

				this.pullQueueTimer = setTimeout(() => {
					const statefulList = this.getCurrentStatefulList();
					const queue = clone(this.pullQueue);

					this.pullQueueTimer = null;
					this.pullQueue = new Map();
					const itemIds = [];
					const data = {};

					queue.forEach((item, itemId) => {
						if (item.eventName === PULL_EVENT_NAME_ITEM_UPDATED)
						{
							itemIds.push(itemId);
						}
						else
						{
							item.eventName = this.getPreparedItemEventName(item.eventName);
							if (data[item.eventName] === undefined)
							{
								data[item.eventName] = [];
							}
							data[item.eventName].push(item);
						}
					});

					statefulList
						.loadItemsByIds(itemIds)
						.then((items) => {
							items.forEach((item) => {
								const queueItem = queue.get(item.id);
								queueItem.eventName = this.getPreparedItemEventName(queueItem.eventName);
								if (data[queueItem.eventName] === undefined)
								{
									data[queueItem.eventName] = [];
								}

								data[queueItem.eventName].push({
									...queueItem,
									data: item.data,
									config: this.getPullItemConfig(item),
								});
							});
							resolve({
								isBatchMode: true,
								data,
							});
						})
						.catch(() => {
							reject();
						});
				}, PULL_QUEUE_TTL);
			});
		}

		/**
		 * @param {String} eventName
		 * @returns {String}
		 */
		getPreparedItemEventName(eventName)
		{
			if (eventName.indexOf('ITEM') === 0)
			{
				eventName = eventName.replace('ITEM', '');
			}

			return eventName;
		}

		getPullItemConfig(item)
		{
			if (this.getCurrentTypeSort === TypeSort.Id)
			{
				return {};
			}

			let showReloadListNotification = false;
			const currentItem = this.getCurrentStatefulList().getItem(item.id);

			if (!currentItem)
			{
				showReloadListNotification = true;

				return {
					showReloadListNotification,
				};
			}

			const { data: { counters: { lastActivity: oldLastActivity } } } = currentItem;
			const { data: { counters: { lastActivity: newLastActivity } }, id } = item;

			if (!newLastActivity)
			{
				return {};
			}

			if (newLastActivity > oldLastActivity)
			{
				const position = this.getCurrentStatefulList().getItemPosition(id);
				if (!position || position.index !== 1)
				{
					showReloadListNotification = true;
				}
			}

			return {
				showReloadListNotification,
			};
		}

		getCurrentTypeSort()
		{
			return this.itemsSortManager.getSortType();
		}

		getLayoutOptions()
		{
			return {
				useSearch: false,
				useOnViewLoaded: false,
			};
		}

		getItemLayoutOptions()
		{
			return {
				useConnectsBlock: true,
				useCountersBlock: true,
				useItemMenu: true,
				useStatusBlock: true,
			};
		}

		async handleItemDetailOpen(entityId, item, params = {})
		{
			const entityTypeId = this.props.entityTypeId;
			const categoryId = this.getCategoryId(entityTypeId);

			const titleParams = { text: item.name };
			if (item.subTitleText)
			{
				titleParams.detailText = capitalize(item.subTitleText);
			}

			let tabs = null;
			const counter = get(item, ['counters', 'activityCounterTotal'], null);
			if (counter)
			{
				tabs = { [TabType.TIMELINE]: { label: String(counter) } };
			}

			const permissions = get(params, 'entityPermissions', {});

			const { EntityDetailOpener } = await requireLazy('crm:entity-detail/opener');

			EntityDetailOpener.open({
				payload: { ...params, entityTypeId, entityId, categoryId, tabs, permissions },
				widgetParams: { titleParams },
				analytics: this.getAnalytics().setElement('list_item'),
			});
		}

		getAnalytics()
		{
			return new AnalyticsEvent()
				.setTool('crm')
				.setCategory('entity_operations')
				.setSection(`${this.entityTypeName.toLowerCase()}_section`)
				.setSubSection(this.getView());
		}

		showForbiddenCreateNotification(entityTypeId = null)
		{
			const title = this.getEntityMessage('M_CRM_ENTITY_TAB_FORBIDDEN_CREATE_TITLE', entityTypeId);
			const text = Loc.getMessage('M_CRM_ENTITY_TAB_FORBIDDEN_TEXT');

			Notify.showUniqueMessage(text, title, { time: 3 });
		}

		async handleNewItemOpen(entityTypeId)
		{
			const entityType = this.getEntityType(entityTypeId);
			if (!entityType)
			{
				return;
			}

			if (entityType.selectable)
			{
				if (!entityType.permissions.add)
				{
					this.showForbiddenCreateNotification();

					return;
				}

				const { EntityDetailOpener } = await requireLazy('crm:entity-detail/opener');

				const categoryId = entityType.isCategoriesSupported
					? this.getCategoryId(entityTypeId)
					: null
				;

				EntityDetailOpener.open({
					payload: { entityTypeId, categoryId },
					analytics: this.getAnalytics()
						.setEvent('entity_add_open')
						.setElement('floating_create_button'),
				});
			}
			else if (entityType.hasRestrictions)
			{
				await this.openPlanRestriction({ title: entityType.titleInPlural || entityType.title });
			}
			else if (entityType.link)
			{
				qrauth.open({
					title: entityType.titleInPlural || entityType.title,
					redirectUrl: entityType.link,
					analyticsSection: 'crm',
				});
			}
		}

		/**
		 * @param {Number} entityTypeId
		 * @returns {EntityType|null}
		 */
		getEntityType(entityTypeId)
		{
			return this.entityTypes.find((tab) => tab.id === entityTypeId) || null;
		}

		itemDetailOpen(componentParams, titleParams)
		{
			componentParams = componentParams || {};
			titleParams = titleParams || {};

			ComponentHelper.openLayout({
				name: 'crm:crm.entity.details',
				componentParams: {
					payload: componentParams,
				},
				widgetParams: {
					titleParams,
				},
			});
		}

		handleFloatingButtonClick()
		{
			if (this.entityTypes.length === 1)
			{
				this.handleNewItemOpen(this.entityTypes[0].id);

				return;
			}

			const menu = this.getFloatingButtonMenu();
			if (menu)
			{
				menu.show();
			}
		}

		handleFloatingButtonLongClick()
		{
			this.handleNewItemOpen(this.props.entityTypeId);
		}

		onDetailCardUpdate(params)
		{
			// can be implemented in a child class
		}

		onDetailCardCreate(params)
		{
			const { entityTypeId, changeTab = true, eventUid } = params;

			if (eventUid)
			{
				if (this.eventUids.has(eventUid))
				{
					return;
				}

				this.eventUids.add(eventUid);
			}

			if (this.props.entityTypeId === entityTypeId)
			{
				this.reload();

				return;
			}

			if (changeTab)
			{
				this.props.setActiveTab(entityTypeId, params);
			}
		}

		getFloatingButtonMenu()
		{
			if (!this.floatingButtonMenu)
			{
				this.floatingButtonMenu = this.createFloatingMenu();
			}

			return this.floatingButtonMenu;
		}

		/**
		 * @returns {boolean}
		 */
		isShowFloatingButton()
		{
			return this.entityTypes.some((entityType) => {
				return entityType.permissions && entityType.permissions.add;
			});
		}

		createFloatingMenu()
		{
			const actions = this.entityTypes.map((item) => {
				const entityName = Type.getCamelizedEntityTypeName(item.typeName);
				const svgIcon = EntitySvg[entityName] ? EntitySvg[entityName](AppTheme.colors.base3) : null;
				let svgIconAfter = null;

				if (!item.selectable)
				{
					if (item.hasRestrictions)
					{
						svgIconAfter = { type: ImageAfterTypes.LOCK };
					}
					else if (item.link)
					{
						svgIconAfter = { type: ImageAfterTypes.WEB };
					}
				}

				return {
					id: item.id,
					title: item.name || item.title,
					data: { svgIcon, svgIconAfter },
					onClickCallback: this.handleFloatingMenuClick.bind(this),
					onDisableClick: this.showForbiddenCreateNotification.bind(this),
					isDisabled: item.selectable && !item.permissions.add,
					showArrow: item.selectable && item.permissions.add,
				};
			});

			return new ContextMenu({
				testId: `CRM_ENTITY_TAB_${this.entityTypeName}`,
				actions,
				params: {
					showCancelButton: true,
					showActionLoader: false,
					showPartiallyHidden: true,
					mediumPositionPercent: 60,
					shouldResizeContent: true,
				},
				analyticsLabel: {
					module: 'crm',
					source: 'crm-create-entity',
					entityTypeId: this.props.entityTypeId,
				},
			});
		}

		handleFloatingMenuClick(actionItemId)
		{
			if (this.floatingButtonMenu)
			{
				this.floatingButtonMenu.close(() => {
					this.handleNewItemOpen(actionItemId);
				});
			}

			return Promise.resolve({ closeMenu: false });
		}

		getEntityMessage(messageCode, entityTypeId = null)
		{
			return getEntityMessage(messageCode, entityTypeId || this.props.entityTypeId);
		}

		/**
		 * @returns {Object[]}
		 */
		getItemActions()
		{
			const { entityTypeId, restrictions = {} } = this.props;
			const { permissions } = this.getCurrentEntityType();
			const canAdd = Boolean(permissions.add);
			const canUpdate = Boolean(permissions.update);

			const actions = [
				{
					id: 'open',
					sort: 100,
					title:
						canUpdate
							? Loc.getMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_EDIT_TEXT_MSGVER_1')
							: Loc.getMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_OPEN_TEXT'),
					onClickCallback: (action, itemId, { ensureMenuClosed, parent }) => {
						ensureMenuClosed(() => {
							this.handleItemDetailOpen(itemId, parent.data);
						});
					},
					icon: canUpdate ? Icon.EDIT : Icon.FILE,
				},
			];

			actions.push(...this.getItemActionsByEntityType());

			const {
				id: copyEntityId,
				onAction: copyEntityOnAction,
			} = getActionToCopyEntity(entityTypeId);

			const {
				id: conversionId,
				title: conversionTitle,
				svgIcon: conversionSvg,
				canUseConversion,
				onAction,
			} = getActionToConversion();

			if (canUseConversion(entityTypeId))
			{
				let conversionMenuItem = {
					id: conversionId,
					sort: 300,
					title: conversionTitle,
					showActionLoader: false,
					onClickCallback: (action, entityId, { ensureMenuClosed }) => {
						ensureMenuClosed(async () => {
							const analytics = this.getAnalytics()
								.setEvent('entity_convert')
								.setElement('element_context_menu');

							const conversionAction = await onAction({
								entityId,
								entityTypeId,
								onFinishConverted: () => {
									this.getCurrentStatefulList().updateItems([entityId], true, true, false);

									return Promise.resolve();
								},
								analytics,
							});

							conversionAction();
						});
					},
					icon: conversionSvg,
					onDisableClick: this.showForbiddenActionNotification.bind(this),
					isDisabled: !canUpdate,
				};

				if (!restrictions.conversion)
				{
					conversionMenuItem = {
						...conversionMenuItem,
						showActionLoader: false,
						icon: Icon.LOCK,
						style: {
							icon: {
								color: Color.base0.toHex(),
							},
						},
						onClickCallback: (action, entityId, { ensureMenuClosed }) => {
							ensureMenuClosed(async () => {
								await this.openPlanRestriction({ title: conversionTitle });
							});
						},
					};
				}

				actions.push(conversionMenuItem);
			}

			actions.push({
				id: copyEntityId,
				sort: 900,
				title: Loc.getMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_COPY_TEXT_MSGVER_1'),
				onClickCallback: (action, itemId, { parentWidget, ensureMenuClosed }) => {
					const categoryId = this.getCategoryId(entityTypeId);
					const analytics = this.getAnalytics()
						.setEvent('entity_add_open')
						.setElement('element_context_menu');

					ensureMenuClosed(() => {
						copyEntityOnAction({
							entityTypeId,
							entityId: itemId,
							categoryId,
							analytics,
						});
					});
				},
				onDisableClick: () => this.showForbiddenCreateNotification(entityTypeId),
				isDisabled: !canAdd,
				icon: Icon.COPY,
				sectionCode: 'additional',
			});

			if (this.canUseChangeCategory())
			{
				const {
					id: changePipelineId,
					title: changePipelineTitle,
					onAction: changePipelineAction,
				} = getActionToChangePipeline();

				actions.push({
					id: changePipelineId,
					sort: 400,
					title: changePipelineTitle,
					onClickCallback: (action, itemId, { parentWidget, ensureMenuClosed }) => {
						const categoryId = this.getCategoryId(entityTypeId);
						ensureMenuClosed(async () => {
							const changeParams = { categoryId, itemId, entityTypeId };
							await changePipelineAction(changeParams)
								.then(() => this.blinkItemListView(itemId))
								.then(() => this.deleteRowFromListView(itemId));
						});
					},
					onDisableClick: this.showForbiddenActionNotification.bind(this),
					isDisabled: !canUpdate,
					icon: Icon.CHANGE_FUNNEL,
				});
			}

			actions.push(
				{
					id: 'delete',
					sort: 1000,
					title: Loc.getMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_DELETE_TEXT'),
					onClickCallback: this.deleteItemConfirm,
					onDisableClick: this.showForbiddenDeleteNotification.bind(this),
					isDisabled: !permissions.delete,
					isDestructive: true,
					sectionCode: 'additional',
					icon: Icon.TRASHCAN,
				},
				{
					id: 'showActivityDetailTab',
					sort: 600,
					title: Loc.getMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_ACTIVITIES2_MSGVER_1'),
					onClickCallback: (action, itemId, { parentWidget, ensureMenuClosed, parent }) => {
						const params = {
							activeTab: TabType.TIMELINE,
						};
						ensureMenuClosed(() => {
							this.handleItemDetailOpen(itemId, parent.data, params);
						});
					},
					sectionCode: 'timeline',
					icon: Icon.TIMELINE,
				},
			);

			const linkTemplate = this.getEntityType(entityTypeId).entityLink;
			if (CoreType.isStringFilled(linkTemplate))
			{
				const {
					id: shareId,
					title: shareTitle,
					onAction: onShareAction,
				} = getActionToShare();

				actions.push({
					id: shareId,
					sort: 800,
					title: shareTitle,
					onClickCallback: (action, itemId, { parentWidget, ensureMenuClosed }) => {
						const linkToItem = linkTemplate.replace('#ENTITY_ID#', itemId);

						ensureMenuClosed(() => {
							onShareAction(linkToItem);
						});
					},
					icon: Icon.SHARE,
					sectionCode: 'additional',
				});
			}

			actions.sort(({ sort: sortA = Infinity }, { sort: sortB = Infinity }) => sortA - sortB);

			return actions;
		}

		async openPlanRestriction(options)
		{
			const { PlanRestriction } = await requireLazy('layout/ui/plan-restriction');

			PlanRestriction.open(options);
		}

		canUseChangeCategory()
		{
			const { isCategoriesEnabled, needSaveCurrentCategoryId } = this.getCurrentEntityType();

			return isCategoriesEnabled && needSaveCurrentCategoryId;
		}

		getItemActionsByEntityType()
		{
			const entity = this.getEntityTypeModel();

			return entity ? entity.getItemActions(this.props.permissions) : [];
		}

		/**
		 * @param {Object} [params]
		 * @returns {null|Base}
		 */
		getEntityTypeModel(params = {})
		{
			params.categoryId = this.getCategoryId(this.props.entityTypeId);
			params.categoriesCount = this.getCurrentEntityType().data.categoriesCount || 0;
			params.userInfo = this.props.userInfo;
			params.isChatSupported = this.getCurrentEntityType().isChatSupported;
			params.reminders = this.prepareReminders(
				this.getCurrentEntityType().data.reminders,
				this.props.remindersList,
			);

			return TypeFactory.getEntityByType(this.entityTypeName, params);
		}

		/**
		 * @param {array} selectedValues
		 * @param {array} valuesList
		 * @return {{selectedValues: array, valuesList: array}}
		 */
		prepareReminders(selectedValues = [], valuesList = [])
		{
			return {
				selectedValues,
				valuesList,
			};
		}

		/**
		 * @param {String} action
		 * @param {Number} itemId
		 */
		deleteItemConfirmHandler(action, itemId)
		{
			return new Promise((resolve, reject) => {
				confirmDestructiveAction({
					title: this.getEntityMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_DELETE'),
					description: Loc.getMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_DELETE_CONFIRMATION'),
					onDestruct: () => {
						this.deleteItem(itemId);
						resolve({
							action: 'delete',
							id: itemId,
						});
					},
					onCancel: reject,
				});
			});
		}

		deleteItem()
		{
			this.abstract();
		}

		updateItemColumn(itemId, columnName)
		{
			this.abstract();
		}

		abstract()
		{
			throw new Error('Must be implemented in subclass');
		}

		getViewComponent()
		{
			return this.viewComponent;
		}

		showForbiddenActionNotification()
		{
			const title = Loc.getMessage('M_CRM_ENTITY_TAB_FORBIDDEN_TITLE');
			const text = Loc.getMessage('M_CRM_ENTITY_TAB_FORBIDDEN_TEXT');

			Notify.showUniqueMessage(text, title, { time: 3 });
		}

		showForbiddenDeleteNotification()
		{
			const title = this.getEntityMessage('M_CRM_ENTITY_TAB_FORBIDDEN_DELETE_TITLE');
			const text = Loc.getMessage('M_CRM_ENTITY_TAB_FORBIDDEN_TEXT');

			Notify.showUniqueMessage(text, title, { time: 3 });
		}

		getCounters()
		{
			const entity = this.getCurrentEntityType();

			return entity.data.counters || [];
		}

		setIsLoading()
		{
			if (this.state.isLoading)
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				this.setState({ isLoading: true }, resolve);
			});
		}

		scrollToTop()
		{
			this.getCurrentStatefulList().scrollToTop();
		}

		getDefaultPresetId()
		{
			const entityType = this.getCurrentEntityType();
			if (!entityType)
			{
				return null;
			}

			const defaultFilterId = BX.prop.getString(entityType.data, 'defaultFilterId', null);

			return defaultFilterId || BX.prop.getString(entityType.data, 'presetId', null);
		}

		onSelectedCategoryHandler(category, entityTypeId)
		{
			if (this.state.categoryId !== category.categoryId && this.props.entityTypeId === entityTypeId)
			{
				this.changeCategory(category);
			}
		}

		onCategoryDeleteHandler(categoryId)
		{
			if (this.state.categoryId === categoryId)
			{
				this.changeCategory(null);
			}
		}

		changeCategory(category = null, showNotice = false)
		{
			if (this.categoryChangeTimeoutId)
			{
				clearTimeout(this.categoryChangeTimeoutId);
			}

			const desiredCategoryId = category ? category.categoryId : null;

			const promise = this.category ? new Promise((resolve) => {
				this.setIsLoading().then(resolve);
			}) : Promise.resolve();

			promise
				.then(() => this.trySetCurrentCategory(category))
				.then((data) => this.tryUpdateToNewCategory(data, desiredCategoryId, showNotice))
				.catch(console.error);
		}

		trySetCurrentCategory(category)
		{
			return new Promise((resolve) => {
				const entityType = this.getCurrentEntityType();
				const categoryId = category ? category.categoryId : -1;

				if (entityType.needSaveCurrentCategoryId)
				{
					const { entityTypeId } = this.props;
					BX.ajax.runAction('crmmobile.Category.set', {
						data: {
							entityTypeId,
							categoryId,
						},
					}).then((response) => {
						resolve(response.data);
					}).catch(({ errors }) => {
						console.error(errors);
					});
				}
				else
				{
					resolve({
						categoryId,
					});
				}
			});
		}

		tryUpdateToNewCategory(data, desiredCategoryId, showNotice)
		{
			const { categoryId } = data;

			if (categoryId === null)
			{
				this.setState({
					isEmptyAvailableCategories: true,
					isLoading: false,
				});

				return;
			}

			const newState = {
				isLoading: false,
				searchButtonBackgroundColor: null,
			};

			if (categoryId === this.state.categoryId && this.category)
			{
				if (categoryId !== desiredCategoryId)
				{
					this.showChangeToAvailableCategoryNotice(this.category.name);
				}
				this.setState(newState, () => this.reload());

				return;
			}

			const categoryFromStorage = this.getCategoryFromCategoryStorage(categoryId);

			if (!categoryFromStorage)
			{
				this.state.categoryId = this.getCurrentCategoryId();
				this.clearCurrentCategory();

				console.error(`Category ${categoryId} not found in storage`);

				if (this.categoryChangeAttempts++ < MAX_CATEGORY_CHANGE_ATTEMPTS)
				{
					const repeatTimeout = CATEGORY_CHANGE_DELAY_TIME * this.categoryChangeAttempts;

					console.info(`Category change will be repeated after ${repeatTimeout / 1000} seconds. Attempt: ${this.categoryChangeAttempts}`);

					this.categoryChangeTimeoutId = setTimeout(() => {
						store.dispatch(fetchCrmKanban({
							entityTypeId: this.props.entityTypeId,
							categoryId: this.state.categoryId,
						}));
						this.tryUpdateToNewCategory(data, desiredCategoryId, showNotice);
					}, repeatTimeout);
				}

				return;
			}

			this.categoryChangeAttempts = 0;

			showNotice = showNotice
				|| (
					categoryFromStorage
					&& desiredCategoryId
					&& categoryFromStorage.categoryId !== desiredCategoryId
				);

			if (showNotice)
			{
				this.showChangeToAvailableCategoryNotice(categoryFromStorage.name);
			}

			this.filter = new Filter(this.getDefaultPresetId());

			newState.searchButtonBackgroundColor = null;
			newState.categoryId = categoryId;
			this.setState(newState, () => this.initAfterCategoryChange(categoryFromStorage, data));
		}

		showChangeToAvailableCategoryNotice(categoryName)
		{
			const sectionDesc = Loc.getMessage('M_CRM_ENTITY_TAB_FORBIDDEN_READ_SECTION_DESC2');

			Notify.showUniqueMessage(
				sectionDesc.replace('#SECTION_NAME#', categoryName),
				Loc.getMessage('M_CRM_ENTITY_TAB_FORBIDDEN_READ_SECTION2'),
				{ time: 5 },
			);
		}

		clearCurrentCategory()
		{
			this.category = null;
		}

		initAfterCategoryChange(category, data)
		{
			this.filter = new Filter(this.getDefaultPresetId());
			this.category = category;
			this.floatingButtonMenu = null;
			let needInitMenu = true;

			this.updateEntityTypeData(data, (tabs) => {
				this.entityTypes = tabs;
				const params = {
					menuButtons: this.getMenuButtons(),
				};
				this.needReloadTab = false;
				needInitMenu = false;
				this.reload(params);
			});

			if (needInitMenu)
			{
				this.getCurrentStatefulList().initMenu();
			}

			const entityType = this.getCurrentEntityType();
			const isDynamicType = Type.isDynamicTypeById(entityType.id);

			if (
				this.needReloadTab
				&& (
					!isDynamicType
					|| (isDynamicType && entityType.isStagesEnabled)
				)
			)
			{
				this.needReloadTab = false;
				this.reload();
			}
		}

		isCategoryStagesCountChanged(newCategory)
		{
			const currentCategory = this.category;

			if (!currentCategory && newCategory)
			{
				return true;
			}

			if (currentCategory.id !== newCategory.id)
			{
				return false;
			}

			return (
				currentCategory.processStages.length !== newCategory.processStages.length
				|| currentCategory.successStages.length !== newCategory.successStages.length
				|| currentCategory.failedStages.length !== newCategory.failedStages.length
			);
		}

		onCategoryCloseHandler()
		{
			this.applyCategoryStorageChanges();
		}

		applyCategoryStorageChanges()
		{
			const entityType = this.getCurrentEntityType();
			if (!entityType.needSaveCurrentCategoryId && !entityType.isStagesEnabled)
			{
				return;
			}

			const newCategory = this.getCategoryFromCategoryStorage(this.state.categoryId);
			if (!newCategory)
			{
				return;
			}

			if (this.category === null)
			{
				this.changeCategory(newCategory);

				return;
			}

			if (!isEqual(this.category, newCategory))
			{
				if (this.isCategoryStagesCountChanged(newCategory))
				{
					this.reload();
				}
				else
				{
					this.category = newCategory;
				}
			}
		}

		showCategorySelector()
		{
			const categoryId = this.getCategoryId();
			const entityType = this.getCurrentEntityType();

			if (categoryId === null || !entityType)
			{
				console.error('CategoryId or entityType is empty');

				return;
			}

			void this.openCategoryList(categoryId, entityType);
		}

		async openCategoryList(categoryId, entityType)
		{
			const { CategoryListView } = await requireLazy('crm:category-list-view');

			CategoryListView.open(
				{
					entityTypeId: entityType.id,
					currentCategoryId: categoryId,
					selectAction: CategorySelectActions.SelectCurrentCategory,
					needSaveCurrentCategoryId: entityType.needSaveCurrentCategoryId,
				},
				{},
				this.props.layout,
			);
		}

		onNotViewableHandler()
		{
			const category = this.getCategoryFromCategoryStorage();

			this.changeCategory(category);
		}

		/**
		 * @param {Number|null} categoryId
		 * @returns {Object|null}
		 */
		getCategoryFromCategoryStorage(categoryId = null)
		{
			const preparedCategoryId = categoryId === null ? this.getCurrentCategoryId() : categoryId;
			const crmKanbanUniqId = getCrmKanbanUniqId(this.props.entityTypeId, preparedCategoryId);
			const fullCategory = selectKanbanById(store.getState(), crmKanbanUniqId);

			if (!fullCategory)
			{
				return null;
			}

			const processStages = selectIdAndStatusByIds(store.getState(), fullCategory.processStages);
			const successStages = selectIdAndStatusByIds(store.getState(), fullCategory.successStages);
			const failedStages = selectIdAndStatusByIds(store.getState(), fullCategory.failedStages);

			return {
				id: fullCategory.id,
				categoryId: fullCategory.categoryId,
				categoriesEnabled: fullCategory.categoriesEnabled,
				processStages,
				successStages,
				failedStages,
			};
		}

		/**
		 * @returns {null|number}
		 */
		getCategoryId(entityTypeId = null)
		{
			if (Number.isInteger(entityTypeId) && entityTypeId !== this.props.entityTypeId)
			{
				const entityType = this.getEntityType(entityTypeId);
				if (!entityType || !entityType.data || !entityType.isCategoriesSupported)
				{
					return null;
				}

				return BX.prop.getInteger(entityType.data, 'currentCategoryId', null);
			}

			if (Number.isInteger(this.state.categoryId))
			{
				return this.state.categoryId;
			}

			return this.getCurrentCategoryId();
		}

		/**
		 * @returns {null|number}
		 */
		getCurrentCategoryId()
		{
			const currentEntityType = this.getCurrentEntityType();

			if (!this.hasCurrentCategoryIdInEntityType(currentEntityType))
			{
				return null;
			}

			return Number(currentEntityType.data.currentCategoryId);
		}

		/**
		 * @param {string|null} entityType
		 * @returns {boolean}
		 */
		hasCurrentCategoryIdInEntityType(entityType)
		{
			return (
				entityType
				&& entityType.data
				&& Number.isInteger(entityType.data.currentCategoryId)
			);
		}

		isClientEnabled()
		{
			const { isClientEnabled } = this.getCurrentEntityType();
			const { entityTypeId } = this.props;

			if (!Type.isDynamicTypeById(entityTypeId))
			{
				return true;
			}

			return isClientEnabled;
		}

		getCacheName()
		{
			const categoryId = this.getCategoryId() || -1;

			return `${this.props.cacheName}.${categoryId}`;
		}

		changeCategoryIfViewNotFound()
		{
			const viewComponent = this.getViewComponent();

			if (viewComponent)
			{
				return false;
			}

			console.error('view component not found');

			if (!this.getCategoryFromCategoryStorage() && selectStatus(store.getState()) !== STATUS.loading)
			{
				this.changeCategory(null);
			}

			return true;
		}

		/**
		 * @abstract
		 * @return {boolean}
		 */
		isAllStagesDisplayed()
		{}
	}

	module.exports = { EntityTab, TypePull };
});
