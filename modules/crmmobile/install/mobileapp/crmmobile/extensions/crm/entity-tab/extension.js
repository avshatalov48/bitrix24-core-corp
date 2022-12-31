/**
 * @module crm/entity-tab
 */
jn.define('crm/entity-tab', (require, exports, module) => {
	const { TypeFactory } = require('crm/entity-tab/type');
	const { Filter } = require('crm/entity-tab/filter');
	const { TypeSort } = require('crm/entity-tab/sort');
	const { TypeId } = require('crm/type');
	const { getEntityMessage } = require('crm/loc');
	const { EntitySvg } = require('crm/assets/entity');
	const { Alert } = require('alert');
	const { EntityDetailOpener } = require('crm/entity-detail/opener');
	const { PullManager } = require('crm/entity-tab/pull-manager');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { ViewMode } = require('layout/ui/simple-list/view-mode');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { clone, get } = require('utils/object');
	const { capitalize } = require('utils/string');
	const { ActivityCountersStoreManager } = require('crm/state-storage');
	const { getActionToChangePipeline } = require('crm/entity-actions');

	const iconColor = '#767c87';

	const floatingButtonSvgIcons = {
		[TypeId.Lead]: EntitySvg.lead(iconColor),
		[TypeId.Deal]: EntitySvg.deal(iconColor),
		[TypeId.Contact]: EntitySvg.contact(iconColor),
		[TypeId.Company]: EntitySvg.company(iconColor),
	};

	const PULL_MODULE_ID = 'crm';
	const PULL_EVENT_NAME_ITEM_UPDATED = 'ITEMUPDATED';
	//const PULL_EVENT_NAME_ITEM_DELETED = 'ITEMDELETED';
	const PULL_QUEUE_TTL = 500; // @todo set more time

	const ASSIGNED_WITH_OTHER_USERS = 'other-users';

	/**
	 * @class EntityTab
	 * @abstract
	 */
	class EntityTab extends LayoutComponent
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
			this.eventUids = new Set();

			/** @type {ContextMenu}*/
			this.floatingButtonMenu = null;

			this.isEmpty = true;
			this.viewComponent = null;

			this.pullManager = new PullManager();
			this.pullQueue = new Map();
			this.pullQueueTimer = null;

			this.entityTypeName = props.entityTypeName;
			this.entityTypes = props.entityTypes;

			this.state.isLoading = false;
			this.state.searchButtonBackgroundColor = null;

			/** @type {Filter}*/
			this.filter = new Filter(this.getDefaultPresetId());
		}

		componentWillReceiveProps(nextProps)
		{
			this.entityTypes = nextProps.entityTypes;
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

		updateEntityTypes()
		{
			const counters = ActivityCountersStoreManager.getCounters();

			let needChangeSearchIcon = false;
			this.entityTypes.forEach(entityType => {
				entityType.data.counters.map(counter => {
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
			const { data } = params;
			this.state.searchButtonBackgroundColor = (data && data.background ? data.background : null);

			if (params.isCancel)
			{
				this.filter.unsetWasShown();
			}

			if (params.counter && params.counter.code)
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
				this.setState({});
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

			this.isEmpty = !Boolean(items.length);
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

			const entity = TypeFactory.getEntityByType(this.entityTypeName);

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
			let rect = '';
			let dot = '';
			let magnifierColor = '#A8ADB4';
			let magnifierSvg = null;

			if (this.state.searchButtonBackgroundColor)
			{
				rect = `<rect y="0.5" width="31" height="31" rx="15.5" fill="${this.state.searchButtonBackgroundColor}"/>`;
				magnifierColor = '#ffffff';
			}
			else
			{
				const counters = this.getCounters();
				const hasCounter = counters
					.filter(counter => !counter.excludeUsers)
					.some(counter => counter.value > 0)
				;

				if (hasCounter)
				{
					/////
					dot = '<circle cx="22" cy="9" r="3" fill="#FF5752"/>';
					magnifierSvg = '<path fill-rule="evenodd" clip-rule="evenodd" d="M22.8866 13.9536C22.5988 14.0048 22.3025 14.0315 22 14.0315C21.5937 14.0315 21.1986 13.9833 20.8201 13.8924C20.8413 14.1012 20.8521 14.313 20.8521 14.5274C20.8521 16.8848 19.5428 18.9363 17.6118 19.9946V22.2557C18.1599 22.0435 18.6798 21.7746 19.1644 21.4564L22.7394 25.0314C23.1299 25.4219 23.7631 25.4219 24.1536 25.0314L24.996 24.189C25.3865 23.7985 25.3865 23.1653 24.996 22.7748L21.4464 19.2252C22.3671 17.8902 22.9062 16.2718 22.9062 14.5274C22.9062 14.3346 22.8996 14.1432 22.8866 13.9536ZM17.4957 6.75529C16.6005 6.42416 15.6324 6.24329 14.622 6.24329C10.3072 6.24329 6.76285 9.5421 6.37341 13.7553H8.43934C8.81962 10.6788 11.4427 8.29734 14.622 8.29734C15.4543 8.29734 16.2485 8.46055 16.9743 8.7567C17.0084 8.04007 17.1924 7.3627 17.4957 6.75529ZM4 16.5C4 15.9477 4.44772 15.5 5 15.5H15C15.5523 15.5 16 15.9477 16 16.5C16 17.0523 15.5523 17.5 15 17.5H5C4.44772 17.5 4 17.0523 4 16.5ZM4 20.5C4 19.9477 4.44772 19.5 5 19.5H15C15.5523 19.5 16 19.9477 16 20.5C16 21.0523 15.5523 21.5 15 21.5H5C4.44772 21.5 4 21.0523 4 20.5ZM5 23.5C4.44772 23.5 4 23.9477 4 24.5C4 25.0523 4.44772 25.5 5 25.5H15C15.5523 25.5 16 25.0523 16 24.5C16 23.9477 15.5523 23.5 15 23.5H5Z" fill="#A8ADB4"/>';
				}
			}

			if (!magnifierSvg)
			{
				magnifierSvg = `<path fill-rule="evenodd" clip-rule="evenodd" d="M17.6118 21.292C17.9359 21.1366 18.248 20.9601 18.5462 20.7642L21.9831 24.2011C22.3736 24.5916 23.0067 24.5916 23.3973 24.2011L24.1669 23.4315C24.5574 23.041 24.5574 22.4078 24.1669 22.0173L20.7545 18.605C21.6456 17.3131 22.1673 15.7469 22.1673 14.0588C22.1673 9.63117 18.578 6.04187 14.1504 6.04187C9.82439 6.04187 6.29868 9.46828 6.1391 13.7552H8.12876C8.28687 10.5665 10.9224 8.02967 14.1504 8.02967C17.4801 8.02967 20.1795 10.729 20.1795 14.0588C20.1795 16.1005 19.1646 17.9051 17.6118 18.9958Z" fill="${magnifierColor}"/>`;
			}

			return {
				content: `<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg">\n` +
					`${rect}\n` +
					`${dot}\n` +
					`<rect x="4" y="15.5" width="12" height="2" rx="1" fill="${magnifierColor}"/>\n` +
					`<rect x="4" y="19.5" width="12" height="2" rx="1" fill="${magnifierColor}"/>\n` +
					`<rect x="4" y="23.5" width="12" height="2" rx="1" fill="${magnifierColor}"/>\n` +
					`${magnifierSvg}\n` +
					`</svg>`,
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

				const { filter } = this;

				if (
					filter.isActive()
					|| filter.hasSearchText()
					|| filter.hasSelectedNotDefaultPreset(this.props.searchRef)
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
		 * @returns {null|Object}
		 */
		getCurrentEntityType()
		{
			if (!this.entityTypes || !this.entityTypeName)
			{
				return null;
			}

			return this.entityTypes.find(item => {
				return (item.typeName === this.entityTypeName);
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
						//|| (data.params.eventName === PULL_EVENT_NAME_ITEM_DELETED && !data.params.eventId)
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

							this.processPullQueue().then(preparedData => {
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
				//notificationUpdateText: 'updated...', // @todo replace to BX.message
				//notificationAddText: 'added...', // @todo replace to BX.message
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
						if (item.eventName !== PULL_EVENT_NAME_ITEM_UPDATED)
						{
							item.eventName = this.getPreparedItemEventName(item.eventName);
							if (data[item.eventName] === undefined)
							{
								data[item.eventName] = [];
							}
							data[item.eventName].push(item);
						}
						else
						{
							itemIds.push(itemId);
						}
					});

					statefulList
						.loadItemsByIds(itemIds)
						.then(items => {
							items.map(item => {
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
			const { data: { counters: { lastActivity: newLastActivity } } } = item;

			if (!newLastActivity)
			{
				return {};
			}

			if (newLastActivity > oldLastActivity)
			{
				const position = this.getCurrentStatefulList().getItemPosition(item.id);
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
			return TypeSort.Id;
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
			if (!entityType || !entityType.data || !entityType.canUseCategoryId)
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

			EntityDetailOpener.open(
				{ ...params, entityTypeId, entityId, categoryId, tabs },
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

			if (!entityType.permissions.add)
			{
				this.showForbiddenCreateNotification();
				return;
			}

			if (entityType.selectable)
			{
				const categoryId = (
					entityType.canUseCategoryId
						? this.getCategoryId(entityTypeId)
						: null
				);

				EntityDetailOpener.open({ entityTypeId, categoryId });
			}
			else if (entityType.link)
			{
				qrauth.open({
					title: entityType.titleInPlural || entityType.title,
					redirectUrl: entityType.link,
				});
			}
		}

		getEntityType(entityTypeId)
		{
			return this.entityTypes.find((tab) => tab.id === entityTypeId);
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
				void menu.show();
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
			const { entityTypeId } = params;

			if (params.eventUid)
			{
				if (this.eventUids.has(params.eventUid))
				{
					return;
				}

				this.eventUids.add(params.eventUid);
			}

			if (this.props.entityTypeId === entityTypeId)
			{
				this.reload();
				return;
			}

			this.props.setActiveTab(entityTypeId, params);
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
			return this.entityTypes.some(entityType => {
				return (entityType.permissions && entityType.permissions.add);
			});
		}

		createFloatingMenu()
		{
			const actions = this.entityTypes.map((item) => {
				return {
					id: item.id,
					title: item.name || item.title,
					data: {
						svgIcon: (floatingButtonSvgIcons[item.id] ? floatingButtonSvgIcons[item.id] : null),
						svgIconAfter: !item.selectable && item.link && {
							type: ContextMenuItem.ImageAfterTypes.WEB,
						},
					},
					onClickCallback: this.handleFloatingMenuClick.bind(this),
					onDisableClick: this.showForbiddenCreateNotification.bind(this),
					isDisabled: !item.permissions.add,
				};
			});

			return new ContextMenu({
				testId: 'CRM_ENTITY_TAB_' + this.entityTypeName,
				actions,
				params: {
					showCancelButton: true,
					showActionLoader: false,
					title: BX.message('M_CRM_ENTITY_TAB_ADD_ENTITY_TEXT2'),
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
			const { typeName, permissions, canUseCategoryId } = this.getCurrentEntityType();
			const canUpdate = Boolean(permissions.update);

			const actions = [
				{
					id: 'open',
					showActionLoader: false,
					title: (
						canUpdate
							? this.getEntityMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_EDIT')
							: this.getEntityMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_OPEN')
					),
					onClickCallback: (action, itemId, { parentWidget, parent }) => {
						parentWidget.close(() => this.handleItemDetailOpen(itemId, parent.data));
					},
					data: {
						svgIcon: canUpdate ? editEntitySvg : openEntitySvg,
					},
				},
			];

			actions.push(...this.getItemActionsByEntityType());

			if (canUseCategoryId)
			{
				const { entityTypeId } = this.props;
				const actionName = 'changeCategory';
				const { title, svgIcon, onAction } = getActionToChangePipeline();

				actions.push({
					id: actionName,
					title,
					type: actionName,
					onClickCallback: (action, itemId, { parentWidget }) => {
						const categoryId = this.getCategoryId(entityTypeId);
						parentWidget.close(() => {
							const changeParams = { categoryId, itemId, parentWidget, entityType: typeName };
							onAction(changeParams)
								.then(() => this.blinkItemListView(itemId))
								.then(() => this.deleteRowFromListView(itemId));
						});
					},
					isDisabled: !canUpdate,
					data: { svgIcon },
				});
			}

			actions.push({
				id: 'delete',
				title: this.getEntityMessage('M_CRM_ENTITY_TAB_ITEM_ACTION_DELETE'),
				type: 'delete',
				onClickCallback: this.deleteItemConfirm,
				onDisableClick: this.showForbiddenDeleteNotification.bind(this),
				isDisabled: !permissions.delete,
			});

			actions.push({
				id: 'showActivityDetailTab',
				title: BX.message('M_CRM_ENTITY_TAB_ITEM_ACTION_ACTIVITIES2'),
				onClickCallback: (action, itemId, { parentWidget, parent }) => {
					const params = {
						activeTab: TabType.TIMELINE,
					};
					parentWidget.close(() => this.handleItemDetailOpen(itemId, parent.data, params));
				},
				sectionCode: 'additional',
				data: {
					svgIcon: activitiesSvg,
				},
			});

			return actions;
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

		showForbiddenDeleteNotification()
		{
			const title = BX.message('M_CRM_ENTITY_TAB_FORBIDDEN_DELETE_TITLE');
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

			return new Promise(resolve => this.setState({ isLoading: true }, resolve));
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

	const openEntitySvg = '<svg width="17" height="21" viewBox="0 0 17 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.4234 17.7238C14.4234 17.8825 14.2934 18.01 14.1346 18.01H2.56338C2.40338 18.01 2.27463 17.8825 2.27463 17.7238V2.2875C2.27463 2.13 2.40338 2.00125 2.56338 2.00125H8.05963C8.21963 2.00125 8.34838 2.13 8.34838 2.2875V7.72C8.34838 7.8775 8.47838 8.005 8.63838 8.005H14.1346C14.2934 8.005 14.4234 8.13375 14.4234 8.29125V17.7238ZM10.3734 3.09C10.3734 3.0325 10.4221 2.98375 10.4821 2.98375C10.5109 2.98375 10.5384 2.995 10.5584 3.015L13.3984 5.82125C13.4409 5.8625 13.4409 5.93 13.3984 5.9725C13.3771 5.9925 13.3509 6.00375 13.3209 6.00375H10.4821C10.4221 6.00375 10.3734 5.955 10.3734 5.89625V3.09ZM16.0234 5.585L10.6909 0.31375C10.4884 0.11375 10.2121 0 9.92338 0H1.33463C0.734634 0 0.249634 0.48 0.249634 1.0725V18.94C0.249634 19.5313 0.734634 20.0113 1.33463 20.0113H15.3634C15.9609 20.0113 16.4471 19.5313 16.4471 18.94V6.59625C16.4471 6.21625 16.2946 5.8525 16.0234 5.585ZM12.0359 10.0063H4.65963C4.46088 10.0063 4.29838 10.1663 4.29838 10.3638V11.65C4.29838 11.8463 4.46088 12.0075 4.65963 12.0075H12.0359C12.2359 12.0075 12.3984 11.8463 12.3984 11.65V10.3638C12.3984 10.1663 12.2359 10.0063 12.0359 10.0063ZM4.73338 8.005H5.88963C6.12963 8.005 6.32338 7.8125 6.32338 7.575V6.4325C6.32338 6.195 6.12963 6.00375 5.88963 6.00375H4.73338C4.49338 6.00375 4.29838 6.195 4.29838 6.4325V7.575C4.29838 7.8125 4.49338 8.005 4.73338 8.005ZM12.0359 14.0087H4.65963C4.46088 14.0087 4.29838 14.1675 4.29838 14.365V15.6525C4.29838 15.8488 4.46088 16.01 4.65963 16.01H12.0359C12.2359 16.01 12.3984 15.8488 12.3984 15.6525V14.365C12.3984 14.1675 12.2359 14.0087 12.0359 14.0087Z" fill="#767c87"/></svg>';

	const editEntitySvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.0242 3.54235C17.2203 3.34691 17.5378 3.34801 17.7326 3.54479L20.6219 6.46453C20.8156 6.66031 20.8145 6.97592 20.6195 7.17036L9.43665 18.3165L5.84393 14.686L17.0242 3.54235ZM4.1756 19.5286C4.14163 19.6572 4.17803 19.7931 4.27024 19.8877C4.36488 19.9823 4.50078 20.0187 4.62939 19.9823L8.64557 18.9003L5.25791 15.5137L4.1756 19.5286Z" fill="#767C87"/></svg>';

	const activitiesSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.70785 3.97461C3.24153 3.97461 2.8635 4.35264 2.8635 4.81897V5.79497C1.97592 6.13038 1.3457 6.9791 1.3457 7.9731C1.3457 8.96711 1.97592 9.81583 2.8635 10.1512V13.0186C1.97592 13.354 1.3457 14.2027 1.3457 15.1967C1.3457 16.1907 1.97592 17.0395 2.8635 17.3749V18.8377C2.8635 19.304 3.24153 19.6821 3.70785 19.6821C4.17418 19.6821 4.55221 19.304 4.55221 18.8377V17.3749C5.43981 17.0395 6.07006 16.1908 6.07006 15.1967C6.07006 14.2027 5.43981 13.354 4.55221 13.0186V10.1513C5.43981 9.81586 6.07006 8.96713 6.07006 7.9731C6.07006 6.97908 5.43981 6.13034 4.55221 5.79495V4.81897C4.55221 4.35264 4.17418 3.97461 3.70785 3.97461ZM7.94922 6.37598C7.94922 5.82369 8.39693 5.37598 8.94922 5.37598H21.619C22.1713 5.37598 22.619 5.82369 22.619 6.37598V9.57077C22.619 10.1231 22.1713 10.5708 21.619 10.5708H8.94922C8.39693 10.5708 7.94922 10.1231 7.94922 9.57077V6.37598ZM7.94922 13.5986C7.94922 13.0463 8.39693 12.5986 8.94922 12.5986H21.619C22.1713 12.5986 22.619 13.0463 22.619 13.5986V16.7934C22.619 17.3457 22.1713 17.7934 21.619 17.7934H8.94922C8.39693 17.7934 7.94922 17.3457 7.94922 16.7934V13.5986Z" fill="#767c87"/></svg>';

	module.exports = { EntityTab };
});
