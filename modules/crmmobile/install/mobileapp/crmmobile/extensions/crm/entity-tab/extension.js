/**
 * @module crm/entity-tab
 */
jn.define('crm/entity-tab', (require, exports, module) => {
	const { TypeFactory } = require('crm/entity-tab/type');
	const { Filter } = require('crm/entity-tab/filter');
	const { TypeSort, ItemsSortManager } = require('crm/entity-tab/sort');
	const { Type, TypeId } = require('crm/type');
	const { getEntityMessage } = require('crm/loc');
	const { EntitySvg } = require('crm/assets/entity');
	const { Alert } = require('alert');
	const { EntityDetailOpener } = require('crm/entity-detail/opener');
	const { PullManager } = require('crm/entity-tab/pull-manager');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { PlanRestriction } = require('layout/ui/plan-restriction');
	const { ViewMode } = require('layout/ui/simple-list/view-mode');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { Type: CoreType } = require('type');
	const { clone, get } = require('utils/object');
	const { capitalize } = require('utils/string');
	const { ActivityCountersStoreManager } = require('crm/state-storage');
	const { getActionToCopyEntity, getActionToChangePipeline, getActionToShare } = require('crm/entity-actions');
	const { getActionToConversion } = require('crm/entity-actions/conversion');
	const { PureComponent } = require('layout/pure-component');
	const { magnifierWithMenuAndDot } = require('assets/common');

	const PULL_MODULE_ID = 'crm';
	const PULL_EVENT_NAME_ITEM_UPDATED = 'ITEMUPDATED';
	// const PULL_EVENT_NAME_ITEM_DELETED = 'ITEMDELETED';
	const PULL_QUEUE_TTL = 500; // @todo set more time
	const ASSIGNED_WITH_OTHER_USERS = 'other-users';

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
	 * @property {boolean} isAvailableCrmMode
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

			this.deleteItemConfirm = this.deleteItemConfirmHandler.bind(this);
			this.onSearchHandler = this.onSearch.bind(this);
			this.onSearchHideHandler = this.onSearchHide.bind(this);
			this.checkIsEmptyHandler = this.checkIsEmpty.bind(this);
			this.showSearchBarHandler = this.showSearchBar.bind(this);
			this.updateEntityTypesHandler = this.updateEntityTypes.bind(this);
			this.onSetSortTypeHandler = this.onSetSortType.bind(this);
			this.eventUids = new Set();

			/** @type {ContextMenu} */
			this.floatingButtonMenu = null;

			this.isEmpty = true;
			this.viewComponent = null;

			this.pullManager = new PullManager();
			this.pullQueue = new Map();
			this.pullQueueTimer = null;

			this.entityTypeName = props.entityTypeName;
			/** @type {EntityType[]} */
			this.entityTypes = props.entityTypes;

			this.state = {
				isLoading: false,
				searchButtonBackgroundColor: null,
				forceRenderSwitcher: false,
			};

			/** @type {Filter} */
			this.filter = new Filter(this.getDefaultPresetId());

			this.initItemsSortManager(props);
		}

		componentWillReceiveProps(nextProps)
		{
			this.entityTypes = nextProps.entityTypes;

			this.initItemsSortManager(nextProps);
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
			BX.addCustomEvent('UI.StatefulList::onDrawList', this.checkIsEmptyHandler);
			BX.addCustomEvent('UI.StatefulList::onDrawListFromAjax', this.checkIsEmptyHandler);
			BX.addCustomEvent('Crm.EntityTab::onSearch', this.onSearchHandler);
			BX.addCustomEvent('Crm.EntityTab::onSearchHide', this.onSearchHideHandler);

			ActivityCountersStoreManager
				.subscribe('activityCountersModel/setCounters', this.updateEntityTypesHandler)
			;
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent('UI.StatefulList::onDrawList', this.checkIsEmptyHandler);
			BX.removeCustomEvent('UI.StatefulList::onDrawListFromAjax', this.checkIsEmptyHandler);
			BX.removeCustomEvent('Crm.EntityTab::onSearch', this.onSearchHandler);
			BX.removeCustomEvent('Crm.EntityTab::onSearchHide', this.onSearchHideHandler);

			ActivityCountersStoreManager
				.unsubscribe('activityCountersModel/setCounters', this.updateEntityTypesHandler)
			;
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
				this.getCurrentStatefulList()
					.setMenuButtons(this.getMenuButtons())
					.initMenu()
				;
			}
		}

		blinkItemListView(itemId)
		{
			return this.getCurrentStatefulList().blinkItem(itemId);
		}

		deleteRowFromListView(itemId)
		{
			this.getCurrentStatefulList().deleteRowFromListView({ itemId });
		}

		onSearch(params)
		{
			const { data, isCancel, counter } = params;
			this.state.searchButtonBackgroundColor = (data && data.background ? data.background : null);

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

		onSearchHide()
		{
			if (this.getCurrentStatefulList().getSimpleList().getViewMode() === ViewMode.empty)
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
			const { filter } = this;

			if (filter.currentFilterId === code && filter.search === text)
			{
				filter.clear();
			}

			// @todo remove after creating view mode Activity in the mobile
			else if (code === 'my_pending')
			{
				const assignedById = (excludeUsers ? ASSIGNED_WITH_OTHER_USERS : env.userId);

				filter.set({
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
				const assignedById = (excludeUsers ? ASSIGNED_WITH_OTHER_USERS : env.userId);

				filter.set({
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

			this.reload(this.getReloadParamsByFilter());
		}

		setPresetFilter(params)
		{
			const { preset, text } = params;
			const presetId = (preset ? preset.id : 'tmp_filter');
			this.filter.set({
				presetId,
				search: text,
				currentFilterId: presetId,
				counterId: null,
				tmpFields: {},
			});

			this.reload(this.getReloadParamsByFilter());
		}

		getReloadParamsByFilter()
		{
			return {
				skipFillSlides: true,
				menuButtons: this.getMenuButtons(),
				force: true,
				forcedShowSkeleton: true,
			};
		}

		checkIsEmpty(data)
		{
			const { blockPage, items, params } = data;

			if (
				blockPage !== 1
				|| (params.extra && params.extra.filterParams && params.extra.filterParams.stageId)
			)
			{
				return;
			}

			this.isEmpty = items.length === 0;
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
					backgroundColor: '#f5f7f8',
					flex: 1,
				},
			};
		}

		getMenuActions()
		{
			const actions = [
				{
					type: UI.Menu.Types.DESKTOP,
					showHint: false,
					data: {
						qrUrl: this.getEntityType(this.props.entityTypeId).link,
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
			return [
				{
					type: 'search',
					badgeCode: 'search',
					callback: this.showSearchBarHandler,
					svg: this.getSearchButtonSvg(),
				},
			];
		}

		getSearchButtonSvg()
		{
			const counters = this.getCounters();
			const hasCounter = counters
				.filter((counter) => !counter.excludeUsers)
				.some((counter) => counter.value > 0)
			;

			return {
				content: magnifierWithMenuAndDot(
					'#a8adb4',
					this.state.searchButtonBackgroundColor,
					(hasCounter ? '#ff5752' : null),
				),
			};
		}

		showSearchBar()
		{
			let presetId = this.filter.presetId;
			if (!presetId && !this.filter.wasShown)
			{
				const entityType = this.getCurrentEntityType();
				presetId = entityType.data.presetId;
			}

			const counterId = this.filter.counterId;
			if (counterId)
			{
				presetId = null;
			}

			this.filter.set({
				presetId,
				counterId,
				search: this.filter.search,
			}).setWasShown();

			BX.postComponentEvent('Crm.EntityTab::onSearchShow', [{
				presetId,
				counterId,
				search: this.filter.search,
			}]);
		}

		getAdditionalParamsForItem()
		{
			return {};
		}

		/**
		 * @returns {Object}
		 */
		prepareActionParams()
		{
			return clone(this.props.actionParams);
		}

		getEmptyListComponent(params = null)
		{
			if (params === null)
			{
				const model = this.getEntityTypeModel();
				const { searchRef } = this.props;

				if (
					this.filter.isActive()
					|| this.filter.hasSearchText()
					|| this.filter.hasSelectedNotDefaultPreset(searchRef)
				)
				{
					params = model.getEmptySearchScreenConfig();
				}
				else
				{
					params = (
						this.isEmpty
							? model.getEmptyEntityScreenConfig()
							: this.getEmptyColumnScreenConfig(model)
					);
				}
			}

			params.onRefresh = () => {
				this.getCurrentStatefulList().getSimpleList().reloadList();
			};

			return new EmptyScreen(params);
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
				return (item.typeName === name);
			});
		}

		getPullConfig()
		{
			return {
				moduleId: PULL_MODULE_ID,
				callback: (data, context) => {
					if (
						this.isWrongPullContext(context)
						// currently eventId support only in deleted events
						// || (data.params.eventName === PULL_EVENT_NAME_ITEM_DELETED && !data.params.eventId)
					)
					{
						return Promise.reject();
					}

					return new Promise((resolve, reject) => {
						if (this.isNeedProcessPull(data, context))
						{
							const { item, eventName } = data.params;
							const oldItem = this.getCurrentStatefulList().getItemComponent(item.id);
							const viewComponent = this.getViewComponent();

							// update item column
							if (
								eventName === PULL_EVENT_NAME_ITEM_UPDATED
								&& this.hasColumnChangesInItem(item, oldItem)
								&& this.hasItemInCurrentColumn(item.data.id)
								&& !this.isCurrentSlideName(item.data.columnId, context.slideName)
							)
							{
								let action;

								// update columnId in AllStages column
								if (viewComponent.getSlideName() === context.slideName)
								{
									action = 'update';
									this.updateItemColumn(item.id, item.data.columnId);
								}
								// or delete item from current (not AllStages) column
								else
								{
									action = 'delete';
									viewComponent.deleteItemFromStatefulList(item.id);
								}

								BX.postComponentEvent('UI.Kanban::onItemMoved', [{
									item,
									oldItem: oldItem.props.item,
									resolveParams: { action },
								}]);

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
				// notificationUpdateText: 'updated...', // @todo replace to BX.message
				// notificationAddText: 'added...', // @todo replace to BX.message
			};
		}

		isWrongPullContext(context = {})
		{
			return false;
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
			const viewComponent = this.getViewComponent();
			return (
				oldItem
				&& oldItem.state.columnId
				&& oldItem.state.columnId !== viewComponent.getColumnIdByName(item.data.columnId)
			);
		}

		getPullCommand(prefix)
		{
			this.abstract();
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

		isCurrentSlideName(itemColumnId, slideName)
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

		getCategoryId(entityTypeId)
		{
			const entityType = this.getEntityType(entityTypeId);
			if (!entityType || !entityType.data || !entityType.isCategoriesSupported)
			{
				return null;
			}

			return BX.prop.getInteger(entityType.data, 'currentCategoryId', null);
		}

		handleItemDetailOpen(entityId, item, params = {})
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

			// @todo temporary, for release option support
			const hasTelegramConnector = get(params, ['connectors', 'telegram'], false);
			const isGoToChatAvailable = !hasTelegramConnector;

			EntityDetailOpener.open(
				{ ...params, entityTypeId, entityId, categoryId, tabs, isGoToChatAvailable, permissions },
				{ titleParams },
			);
		}

		showForbiddenCreateNotification(entityTypeId = null)
		{
			const title = this.getEntityMessage('M_CRM_ENTITY_TAB_FORBIDDEN_CREATE_TITLE', entityTypeId);
			const text = BX.message('M_CRM_ENTITY_TAB_FORBIDDEN_TEXT');

			Notify.showUniqueMessage(text, title, { time: 3 });
		}

		handleNewItemOpen(entityTypeId)
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

				const categoryId = (
					entityType.isCategoriesSupported
						? this.getCategoryId(entityTypeId)
						: null
				);

				EntityDetailOpener.open({ entityTypeId, categoryId });
			}
			else if (entityType.hasRestrictions)
			{
				PlanRestriction.open({ title: entityType.titleInPlural || entityType.title });
			}
			else if (entityType.link)
			{
				qrauth.open({
					title: entityType.titleInPlural || entityType.title,
					redirectUrl: entityType.link,
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
			componentParams = (componentParams || {});
			titleParams = (titleParams || {});

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
				return (entityType.permissions && entityType.permissions.add);
			});
		}

		createFloatingMenu()
		{
			const actions = this.entityTypes.map((item) => {
				const entityName = Type.getCamelizedEntityTypeName(item.typeName);
				const svgIcon = EntitySvg[entityName] ? EntitySvg[entityName]('#6a737f') : null;
				let svgIconAfter;

				if (!item.selectable)
				{
					if (item.hasRestrictions)
					{
						svgIconAfter = { type: ContextMenuItem.ImageAfterTypes.LOCK };
					}
					else if (item.link)
					{
						svgIconAfter = { type: ContextMenuItem.ImageAfterTypes.WEB };
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
			entityTypeId = entityTypeId || this.props.entityTypeId;
			return getEntityMessage(messageCode, entityTypeId);
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
					title: (
						canUpdate
							? this.getEntityMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_EDIT')
							: this.getEntityMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_OPEN')
					),
					showArrow: true,
					onClickCallback: (action, itemId, { parentWidget, parent }) => {
						parentWidget.close(() => this.handleItemDetailOpen(itemId, parent.data));
					},
					data: {
						svgIcon: canUpdate ? editEntitySvg : openEntitySvg,
					},
				},
			];

			actions.push(...this.getItemActionsByEntityType());

			const {
				id: copyEntityId,
				title: copyEntityTitle,
				svgIcon: copyEntitySvgIcon,
				onAction: copyEntityOnAction,
			} = getActionToCopyEntity(entityTypeId);

			const {
				id: conversionId,
				title: conversionTitle,
				svgIcon: conversionSvg,
				canUseConversion,
				onAction: conversionAction,
			} = getActionToConversion();

			if (canUseConversion(entityTypeId))
			{
				const conversionMenuItem = {
					id: conversionId,
					sort: 200,
					title: conversionTitle,
					type: conversionId,
					showActionLoader: true,
					showArrow: true,
					onClickCallback: (action, entityId, { parentWidget }) => new Promise((resolve) => {
						conversionAction({
							entityId,
							entityTypeId,
							onFinishConverted: () => {
								this.getCurrentStatefulList().updateItems([entityId], true, true, false);

								return Promise.resolve();
							},
						}).then((menu) => {
							resolve({ closeMenu: false });
							parentWidget.close(() => menu.show());
						});
					}),
					onDisableClick: this.showForbiddenActionNotification.bind(this),
					isDisabled: !canUpdate,
					data: { svgIcon: conversionSvg },
				};

				if (!restrictions.conversion)
				{
					conversionMenuItem.showArrow = false;
					conversionMenuItem.data = {
						svgIcon: conversionSvg,
						svgIconAfter: { type: ContextMenuItem.ImageAfterTypes.LOCK },
					};
					conversionMenuItem.onClickCallback = (action, entityId, { parentWidget }) => {
						parentWidget.close(() => {
							PlanRestriction.open({ title: conversionTitle });
						});
					};
				}

				actions.push(conversionMenuItem);
			}

			actions.push({
				id: copyEntityId,
				sort: 300,
				title: copyEntityTitle,
				type: copyEntityId,
				showArrow: true,
				onClickCallback: (action, itemId, { parentWidget }) => {
					const categoryId = this.getCategoryId(entityTypeId);

					parentWidget.close(() => copyEntityOnAction({
						entityTypeId,
						entityId: itemId,
						categoryId,
					}));
				},
				onDisableClick: () => this.showForbiddenCreateNotification(entityTypeId),
				isDisabled: !canAdd,
				data: { svgIcon: copyEntitySvgIcon },
			});

			if (this.canUseChangeCategory())
			{
				const {
					id: changePipelineId,
					title: changePipelineTitle,
					svgIcon: changePipelineSvgIcon,
					onAction: changePipelineAction,
				} = getActionToChangePipeline();

				actions.push({
					id: changePipelineId,
					sort: 400,
					title: changePipelineTitle,
					type: changePipelineId,
					showArrow: true,
					onClickCallback: (action, itemId, { parentWidget }) => {
						const categoryId = this.getCategoryId(entityTypeId);
						parentWidget.close(() => {
							const changeParams = { categoryId, itemId, entityTypeId };
							changePipelineAction(changeParams)
								.then(() => this.blinkItemListView(itemId))
								.then(() => this.deleteRowFromListView(itemId));
						});
					},
					onDisableClick: this.showForbiddenActionNotification.bind(this),
					isDisabled: !canUpdate,
					data: { svgIcon: changePipelineSvgIcon },
				});
			}

			actions.push(
				{
					id: 'delete',
					sort: 900,
					title: this.getEntityMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_DELETE'),
					type: 'delete',
					onClickCallback: this.deleteItemConfirm,
					onDisableClick: this.showForbiddenDeleteNotification.bind(this),
					isDisabled: !permissions.delete,
				},
				{
					id: 'showActivityDetailTab',
					sort: 1000,
					title: BX.message('M_CRM_ENTITY_TAB_ITEM_ACTION_ACTIVITIES2'),
					showArrow: true,
					onClickCallback: (action, itemId, { parentWidget, parent }) => {
						const params = {
							activeTab: TabType.TIMELINE,
						};
						parentWidget.close(() => this.handleItemDetailOpen(itemId, parent.data, params));
					},
					sectionCode: 'additional',
					data: { svgIcon: activitiesSvg },
				},
			);

			const linkTemplate = this.getEntityType(entityTypeId).entityLink;
			if (CoreType.isStringFilled(linkTemplate))
			{
				const {
					id: shareId,
					title: shareTitle,
					svgIcon: shareSvgIcon,
					onAction: onShareAction,
				} = getActionToShare();

				actions.push({
					id: shareId,
					sort: 1100,
					title: shareTitle,
					onClickCallback: (action, itemId, { parentWidget }) => {
						const linkToItem = linkTemplate.replace('#ENTITY_ID#', itemId);

						parentWidget.close(() => onShareAction(linkToItem));
					},
					sectionCode: shareId,
					data: { svgIcon: shareSvgIcon },
				});
			}

			actions.sort(({ sort: sortA = Infinity }, { sort: sortB = Infinity }) => sortA - sortB);

			return actions;
		}

		canUseChangeCategory()
		{
			const { entityTypeId } = this.props;
			const { isCategoriesSupported } = this.getCurrentEntityType();
			// ToDo move to isCategoriesEnabled
			return isCategoriesSupported && entityTypeId === TypeId.Deal;
		}

		getItemActionsByEntityType()
		{
			const entity = this.getEntityTypeModel();

			return (entity ? entity.getItemActions(this.props.permissions) : []);
		}

		/**
		 *
		 * @param {Object} params
		 * @returns {null|Object}
		 */
		getEntityTypeModel(params = {})
		{
			params.categoryId = this.getCategoryId(this.props.entityTypeId);
			params.categoriesCount = this.getCurrentEntityType().data.categoriesCount || 0;
			params.userInfo = this.props.userInfo;

			return TypeFactory.getEntityByType(this.entityTypeName, params);
		}

		/**
		 * @param {String} action
		 * @param {Number} itemId
		 */
		deleteItemConfirmHandler(action, itemId)
		{
			return new Promise((resolve, reject) => {
				Alert.confirm(
					this.getEntityMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_DELETE'),
					BX.message('M_CRM_ENTITY_TAB_ITEM_ACTION_DELETE_CONFIRMATION'),
					[
						{
							type: 'cancel',
							onPress: reject,
						},
						{
							text: BX.message('M_CRM_ENTITY_TAB_ITEM_ACTION_DELETE_CONFIRMATION_OK'),
							type: 'destructive',
							onPress: () => {
								this.deleteItem(itemId);
								resolve({
									action: 'delete',
									id: itemId,
								});
							},
						},
					],
				);
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
			const title = BX.message('M_CRM_ENTITY_TAB_FORBIDDEN_TITLE');
			const text = BX.message('M_CRM_ENTITY_TAB_FORBIDDEN_TEXT');

			Notify.showUniqueMessage(text, title, { time: 3 });
		}

		showForbiddenDeleteNotification()
		{
			const title = this.getEntityMessage('M_CRM_ENTITY_TAB_FORBIDDEN_DELETE_TITLE');
			const text = BX.message('M_CRM_ENTITY_TAB_FORBIDDEN_TEXT');

			Notify.showUniqueMessage(text, title, { time: 3 });
		}

		getCounters()
		{
			const entity = this.getCurrentEntityType();

			return (entity.data.counters || []);
		}

		setIsLoading()
		{
			if (this.state.isLoading)
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => this.setState({ isLoading: true }, resolve));
		}

		scrollToTop()
		{
			this.abstract();
		}

		scrollSimpleListToTop(simpleList)
		{
			if (simpleList)
			{
				const listView = simpleList.listView;
				if (listView)
				{
					listView.scrollToBegin(true);
				}
			}
		}

		getDefaultPresetId()
		{
			const entityType = this.getCurrentEntityType();
			if (!entityType)
			{
				return null;
			}

			const defaultFilterId = BX.prop.getString(entityType.data, 'defaultFilterId', null);
			return (defaultFilterId || BX.prop.getString(entityType.data, 'presetId', null));
		}
	}

	const openEntitySvg = '<svg width="17" height="21" viewBox="0 0 17 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.4234 17.7238C14.4234 17.8825 14.2934 18.01 14.1346 18.01H2.56338C2.40338 18.01 2.27463 17.8825 2.27463 17.7238V2.2875C2.27463 2.13 2.40338 2.00125 2.56338 2.00125H8.05963C8.21963 2.00125 8.34838 2.13 8.34838 2.2875V7.72C8.34838 7.8775 8.47838 8.005 8.63838 8.005H14.1346C14.2934 8.005 14.4234 8.13375 14.4234 8.29125V17.7238ZM10.3734 3.09C10.3734 3.0325 10.4221 2.98375 10.4821 2.98375C10.5109 2.98375 10.5384 2.995 10.5584 3.015L13.3984 5.82125C13.4409 5.8625 13.4409 5.93 13.3984 5.9725C13.3771 5.9925 13.3509 6.00375 13.3209 6.00375H10.4821C10.4221 6.00375 10.3734 5.955 10.3734 5.89625V3.09ZM16.0234 5.585L10.6909 0.31375C10.4884 0.11375 10.2121 0 9.92338 0H1.33463C0.734634 0 0.249634 0.48 0.249634 1.0725V18.94C0.249634 19.5313 0.734634 20.0113 1.33463 20.0113H15.3634C15.9609 20.0113 16.4471 19.5313 16.4471 18.94V6.59625C16.4471 6.21625 16.2946 5.8525 16.0234 5.585ZM12.0359 10.0063H4.65963C4.46088 10.0063 4.29838 10.1663 4.29838 10.3638V11.65C4.29838 11.8463 4.46088 12.0075 4.65963 12.0075H12.0359C12.2359 12.0075 12.3984 11.8463 12.3984 11.65V10.3638C12.3984 10.1663 12.2359 10.0063 12.0359 10.0063ZM4.73338 8.005H5.88963C6.12963 8.005 6.32338 7.8125 6.32338 7.575V6.4325C6.32338 6.195 6.12963 6.00375 5.88963 6.00375H4.73338C4.49338 6.00375 4.29838 6.195 4.29838 6.4325V7.575C4.29838 7.8125 4.49338 8.005 4.73338 8.005ZM12.0359 14.0087H4.65963C4.46088 14.0087 4.29838 14.1675 4.29838 14.365V15.6525C4.29838 15.8488 4.46088 16.01 4.65963 16.01H12.0359C12.2359 16.01 12.3984 15.8488 12.3984 15.6525V14.365C12.3984 14.1675 12.2359 14.0087 12.0359 14.0087Z" fill="#6a737f"/></svg>';

	const editEntitySvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.0242 3.54235C17.2203 3.34691 17.5378 3.34801 17.7326 3.54479L20.6219 6.46453C20.8156 6.66031 20.8145 6.97592 20.6195 7.17036L9.43665 18.3165L5.84393 14.686L17.0242 3.54235ZM4.1756 19.5286C4.14163 19.6572 4.17803 19.7931 4.27024 19.8877C4.36488 19.9823 4.50078 20.0187 4.62939 19.9823L8.64557 18.9003L5.25791 15.5137L4.1756 19.5286Z" fill="#6a737f"/></svg>';

	const activitiesSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.70785 3.97461C3.24153 3.97461 2.8635 4.35264 2.8635 4.81897V5.79497C1.97592 6.13038 1.3457 6.9791 1.3457 7.9731C1.3457 8.96711 1.97592 9.81583 2.8635 10.1512V13.0186C1.97592 13.354 1.3457 14.2027 1.3457 15.1967C1.3457 16.1907 1.97592 17.0395 2.8635 17.3749V18.8377C2.8635 19.304 3.24153 19.6821 3.70785 19.6821C4.17418 19.6821 4.55221 19.304 4.55221 18.8377V17.3749C5.43981 17.0395 6.07006 16.1908 6.07006 15.1967C6.07006 14.2027 5.43981 13.354 4.55221 13.0186V10.1513C5.43981 9.81586 6.07006 8.96713 6.07006 7.9731C6.07006 6.97908 5.43981 6.13034 4.55221 5.79495V4.81897C4.55221 4.35264 4.17418 3.97461 3.70785 3.97461ZM7.94922 6.37598C7.94922 5.82369 8.39693 5.37598 8.94922 5.37598H21.619C22.1713 5.37598 22.619 5.82369 22.619 6.37598V9.57077C22.619 10.1231 22.1713 10.5708 21.619 10.5708H8.94922C8.39693 10.5708 7.94922 10.1231 7.94922 9.57077V6.37598ZM7.94922 13.5986C7.94922 13.0463 8.39693 12.5986 8.94922 12.5986H21.619C22.1713 12.5986 22.619 13.0463 22.619 13.5986V16.7934C22.619 17.3457 22.1713 17.7934 21.619 17.7934H8.94922C8.39693 17.7934 7.94922 17.3457 7.94922 16.7934V13.5986Z" fill="#6a737f"/></svg>';

	module.exports = { EntityTab };
});
