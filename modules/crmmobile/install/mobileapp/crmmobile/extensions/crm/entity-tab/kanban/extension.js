/**
 * @module crm/entity-tab/kanban
 */
jn.define('crm/entity-tab/kanban', (require, exports, module) => {
	const { TypeId } = require('crm/type');
	const { EntityTab } = require('crm/entity-tab');
	const { TypeSort } = require('crm/entity-tab/sort');
	const { TypePull } = require('crm/entity-tab/pull-manager');
	const { ToolbarFactory } = require('crm/kanban/toolbar');
	const { CategoryCountersStoreManager } = require('crm/state-storage');
	const { getActionChangeCrmMode } = require('crm/entity-actions/change-crm-mode');
	const { getSmartActivityMenuItem } = require('crm/entity-detail/component/smart-activity-menu-item');
	const { get } = require('utils/object');
	const { ListItemType, ListItemsFactory } = require('crm/simple-list/items');

	const PLUS_ONE_ACTION = 'plus';
	const MINUS_ONE_ACTION = 'minus';

	/**
	 * @class KanbanTab
	 */
	class KanbanTab extends EntityTab
	{
		constructor(props)
		{
			super(props);

			this.setSmartActivityStatus = this.setSmartActivityStatus.bind(this);
			this.setCounterFilter = this.setCounterFilter.bind(this);
			this.initCategoryCounters = this.initCategoryCounters.bind(this);
			this.onBeforeReload = this.onBeforeReloadHandler.bind(this);
			this.onItemMovedHandler = this.handleOnItemMoved.bind(this);
			this.onItemDeletedHandler = this.handleOnItemDeleted.bind(this);
			this.onItemUpdatedHandler = this.handleOnItemUpdated.bind(this);
			this.onItemChangedCategoryHandler = this.handleOnItemChangedCategory.bind(this);

			this.toolbarFactory = new ToolbarFactory();
		}

		reload(params = {})
		{
			if (this.changeCategoryIfViewNotFound())
			{
				return;
			}

			const viewComponent = this.getViewComponent();

			if (viewComponent.setFilter)
			{
				viewComponent.setFilter(this.filter);
			}

			const force = BX.prop.getBoolean(params, 'force', false);

			viewComponent.reload(viewComponent.getCurrentSlideName(), force, params);
		}

		initCategoryCounters(params = {})
		{
			CategoryCountersStoreManager.init(this.props.entityTypeId, this.getCurrentCategoryId(), params);
		}

		componentDidMount()
		{
			BX.addCustomEvent('Crm.Activity.Todo::onChangeNotifications', this.setSmartActivityStatus);
			BX.addCustomEvent('UI.SimpleList::onUpdateItem', this.onItemUpdatedHandler);
			BX.addCustomEvent('UI.SimpleList::onDeleteItem', this.onItemDeletedHandler);
			BX.addCustomEvent('UI.Kanban::onItemMoved', this.onItemMovedHandler);
			BX.addCustomEvent('Crm.Item::onChangePipeline', this.onItemChangedCategoryHandler);

			super.componentDidMount();
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();

			BX.removeCustomEvent('Crm.Activity.Todo::onChangeNotifications', this.setSmartActivityStatus);
			BX.removeCustomEvent('UI.SimpleList::onUpdateItem', this.onItemUpdatedHandler);
			BX.removeCustomEvent('UI.SimpleList::onDeleteItem', this.onItemDeletedHandler);
			BX.removeCustomEvent('UI.Kanban::onItemMoved', this.onItemMovedHandler);
			BX.removeCustomEvent('Crm.Item::onChangePipeline', this.onItemChangedCategoryHandler);
		}

		render()
		{
			const { isLoading, isEmptyAvailableCategories } = this.state;

			let content = null;

			if (isEmptyAvailableCategories)
			{
				content = this.renderKanban({
					forbidden: true,
				});
			}
			else if (isLoading)
			{
				content = new LoadingScreenComponent();
			}
			else
			{
				content = this.renderKanban();
			}

			return View(
				this.getViewConfig(),
				content,
			);
		}

		renderKanban(config = {})
		{
			const entityType = this.getCurrentEntityType();

			return new UI.Kanban({
				toolbar: {
					enabled: this.toolbarFactory.has(this.entityTypeName),
					componentClass: this.toolbarFactory.get(this.entityTypeName),
					props: {
						entityTypeName: this.entityTypeName,
						entityTypeId: this.props.entityTypeId,
						filterParams: this.getFilterParams(),
						showSum: entityType ? entityType.isLinkWithProductsEnabled : false,
					},
				},
				entityTypeName: this.entityTypeName,
				entityTypeId: this.props.entityTypeId,
				actions: this.props.actions,
				actionParams: this.prepareActionParams(),
				filterParams: this.getFilterParams(),
				layout: this.props.layout,
				layoutMenuActions: this.getMenuActions(),
				itemDetailOpenHandler: this.handleItemDetailOpen.bind(this),
				itemCounterLongClickHandler: this.getCounterLongClickHandler(),
				isShowFloatingButton: this.isShowFloatingButton(),
				floatingButtonClickHandler: this.handleFloatingButtonClick.bind(this),
				floatingButtonLongClickHandler: this.handleFloatingButtonLongClick.bind(this),
				onDetailCardUpdateHandler: this.onDetailCardUpdate.bind(this),
				onDetailCardCreateHandler: this.onDetailCardCreate.bind(this),
				onNotViewableHandler: this.onNotViewable,
				onPanListHandler: this.props.onPanList || null,
				initCountersHandler: this.initCategoryCounters,
				itemType: ListItemType.CRM_ENTITY,
				itemFactory: ListItemsFactory,
				itemActions: this.getItemActions(),
				itemParams: {
					isClientEnabled: this.isClientEnabled(),
					...this.props.itemParams,
				},
				pull: this.getPullConfig(),
				layoutOptions: this.getLayoutOptions(),
				itemLayoutOptions: this.getItemLayoutOptions(),
				menuButtons: this.getMenuButtons(),
				cacheName: this.getCacheName(),
				getEmptyListComponent: this.getEmptyListComponent.bind(this),
				onBeforeReload: this.onBeforeReload,
				config,
				needInitMenu: this.props.needInitMenu,
				ref: (ref) => {
					if (ref)
					{
						this.viewComponent = ref;
					}
				},
				getMenuButtons: this.getMenuButtons.bind(this),
				analyticsLabel: {
					module: 'crm',
					source: 'crm-entity-tab',
					entityTypeId: this.props.entityTypeId,
				},
			});
		}

		/**
		 * @returns Object
		 */
		getFilterParams()
		{
			const params = {};
			const categoryId = this.getCategoryId();

			if (Number.isInteger(categoryId))
			{
				params.CATEGORY_ID = categoryId;
			}

			return params;
		}

		getAdditionalParamsForItem()
		{
			const viewComponent = this.getViewComponent();

			return viewComponent ? viewComponent.getAdditionalParamsForItem() : {};
		}

		deleteItem(itemId)
		{
			const params = {
				eventId: this.pullManager.registerRandomEventId(),
			};

			this.getViewComponent().deleteItem(itemId, params);
		}

		updateItemColumn(itemId, columnName)
		{
			this.getViewComponent().updateItemColumn(itemId, columnName);
		}

		getCurrentStatefulList()
		{
			return this.getViewComponent().getCurrentStatefulList();
		}

		/**
		 * @param {Object} data
		 * @param {Object} context
		 * @returns {Boolean}
		 */
		isNeedProcessPull(data, context)
		{
			const { command, params } = data;

			if (this.pullManager.hasEvent(params.eventId))
			{
				return false;
			}

			if (command !== this.getPullCommand(TypePull.Command))
			{
				return false;
			}

			const viewComponent = this.getViewComponent();

			const isUpdatedItemInCurrentSlide = (params, slideName) => {
				return (
					params.eventName !== TypePull.EventNameItemAdded
					&& viewComponent.getCurrentSlideName() === context.slideName
					&& this.hasItemInCurrentColumn(params.item.id)
				);
			};

			const columnId = get(params, 'item.data.columnId', '');

			const isCurrentSlide = (
				this.isCurrentSlideName(params.item.data.columnId, context.slideName)
				|| isUpdatedItemInCurrentSlide(params, context.slideName)
			);
			const isAllStagesSlide = (viewComponent.getCurrentSlideName() === viewComponent.getSlideName());

			if (!isCurrentSlide && !isAllStagesSlide)
			{
				return false;
			}

			if (this.hasItemInCurrentColumn(params.item.id))
			{
				return true;
			}

			if (
				params.eventName === TypePull.EventNameItemUpdated
				&& (viewComponent.getSlideName(columnId) === context.slideName || isAllStagesSlide)
			)
			{
				return true;
			}

			return (params.eventName === TypePull.EventNameItemAdded);
		}

		/**
		 * @param {string} itemColumnId
		 * @param {string} slideName
		 * @returns {boolean}
		 */
		isCurrentSlideName(itemColumnId, slideName)
		{
			const currentSlideName = this.getViewComponent().getCurrentSlideName();

			return (
				slideName === this.getViewComponent().getSlideName(itemColumnId)
				&& slideName === currentSlideName
			);
		}

		getEmptyColumnScreenConfig(model)
		{
			const kanban = this.getViewComponent();
			const slideName = kanban.getCurrentSlideName();
			const columnStatusId = kanban.getColumnStatusIdFromSlideName(slideName);
			const currentColumn = kanban.getColumnByName(columnStatusId);

			return model.getEmptyColumnScreenConfig({
				column: currentColumn,
			});
		}

		isWrongPullContext(context = {})
		{
			return (this.getViewComponent().getCurrentSlideName() !== context.slideName);
		}

		scrollToTop()
		{
			const simpleList = this.getViewComponent().getCurrentStatefulList().getSimpleList();
			this.scrollSimpleListToTop(simpleList);
		}

		getMenuActions()
		{
			const menuActions = [];
			const { permissions, entityTypeId } = this.props;
			const entityType = this.getCurrentEntityType();
			if (entityType && entityType.isLastActivityEnabled)
			{
				menuActions.push(this.itemsSortManager.getSortMenuAction(this.onSetSortTypeHandler));
			}

			const smartActivityAction = this.getSmartActivityAction();
			if (smartActivityAction)
			{
				menuActions.push(smartActivityAction);
			}

			let topSeparatorInstalled = false;

			if (!permissions.crmMode && (entityTypeId === TypeId.Deal || entityTypeId === TypeId.Lead))
			{
				topSeparatorInstalled = true;
				menuActions.push(this.getCrmModeAction());
			}

			const parentMenu = super.getMenuActions();
			if (menuActions.length > 0 && !topSeparatorInstalled)
			{
				parentMenu[0].showTopSeparator = true;
			}

			return [...menuActions, ...parentMenu];
		}

		getCrmModeAction()
		{
			const { restrictions } = this.props;
			const { id, title, iconUrl, onAction } = getActionChangeCrmMode();

			return {
				id,
				title,
				iconUrl,
				showTopSeparator: true,
				onItemSelected: async () => {
					if (restrictions.crmMode)
					{
						await onAction();
					}
					else
					{
						const { PlanRestriction } = await requireLazy('layout/ui/plan-restriction');

						PlanRestriction.open({ title });
					}
				},
			};
		}

		getSmartActivityAction()
		{
			const smartActivitySettings = this.getSmartActivitySettings();
			if (!smartActivitySettings)
			{
				return null;
			}

			return getSmartActivityMenuItem(smartActivitySettings.notificationEnabled, this.props.entityTypeId);
		}

		getSmartActivitySettings()
		{
			const { smartActivitySettings } = this.getCurrentEntityType().data;

			return smartActivitySettings;
		}

		setSmartActivityStatus(status)
		{
			const smartActivitySettings = this.getSmartActivitySettings();
			if (!smartActivitySettings)
			{
				return;
			}

			if (smartActivitySettings.notificationEnabled === status)
			{
				return;
			}

			smartActivitySettings.notificationEnabled = status;

			this.getCurrentStatefulList().initMenu({
				layoutMenuActions: this.getMenuActions(),
			});
		}

		getPullItemConfig(item)
		{
			const itemConfig = super.getPullItemConfig(item);
			if (itemConfig.showReloadListNotification)
			{
				const kanban = this.getViewComponent();
				const currentSlideName = kanban.getCurrentSlideName();
				if (
					currentSlideName !== kanban.getSlideName()
					&& currentSlideName !== kanban.getSlideName(item.data.columnId)
				)
				{
					itemConfig.showReloadListNotification = false;
				}
			}

			return itemConfig;
		}

		getEmptyListComponent(params = null)
		{
			if (this.isUnsuitableCurrentStage())
			{
				const model = this.getEntityTypeModel();
				params = model.getUnsuitableStageScreenConfig();

				return super.getEmptyListComponent(params);
			}

			if (this.filter.isActive())
			{
				return super.getEmptyListComponent();
			}

			const viewComponent = this.getViewComponent();

			if (
				this.getCurrentEntityType().isStagesEnabled
				&& viewComponent.getSlideName() === viewComponent.getCurrentSlideName()
			)
			{
				this.isEmpty = this.getCurrentStatefulList().getItems().size === 0;
			}

			return super.getEmptyListComponent();
		}

		isUnsuitableCurrentStage()
		{
			const viewComponent = this.getViewComponent();
			const stages = CategoryCountersStoreManager.getStages();
			const stageId = viewComponent.getCurrentColumnId();
			const currentStage = stages.find((stage) => stage.id === stageId);

			return (currentStage && currentStage.dropzone);
		}

		onBeforeReloadHandler()
		{
			const sortType = this.getCurrentTypeSort();
			if (sortType === TypeSort.Id)
			{
				return {
					reload: false,
					animate: true,
				};
			}

			return {
				reload: false,
				animate: !this.getCurrentStatefulList().getSimpleList().shouldShowReloadListNotification(),
			};
		}

		handleOnItemUpdated(params)
		{
			const oldAmount = params.oldItem.data ? params.oldItem.data.price : 0;
			const amount = params.item.data.price;

			if (oldAmount === amount)
			{
				return;
			}

			const columnId = params.item.columnId;

			this.modifyCountersByColumnId(PLUS_ONE_ACTION, columnId, amount, 0);
		}

		handleOnItemDeleted(params)
		{
			const oldAmount = params.oldItem.data ? params.oldItem.data.price : 0;
			const oldColumnId = params.oldItem.data.columnId;

			this.modifyCountersByColumnId(MINUS_ONE_ACTION, oldColumnId, oldAmount);
		}

		handleOnItemMoved(params)
		{
			const oldColumnId = params.oldItem.data.columnId;
			const columnId = params.item.data.columnId;

			if (oldColumnId === columnId)
			{
				return;
			}

			const amount = params.item.data.price;
			this.modifyCountersByColumnId(PLUS_ONE_ACTION, columnId, amount);
			this.modifyCountersByColumnId(MINUS_ONE_ACTION, oldColumnId, amount);

			this.blinkItemListView(params.item.id);
		}

		handleOnItemChangedCategory(params)
		{
			const statefulList = this.getCurrentStatefulList();
			if (!statefulList)
			{
				return;
			}

			const { ids = [] } = params;

			ids.forEach((id) => {
				const item = statefulList.getItem(id);
				if (item)
				{
					this.modifyCountersByColumnId(MINUS_ONE_ACTION, item.data.columnId, item.data.price);
				}
			});
		}

		modifyCountersByColumnId(action, columnId, amount = 0, count = 1)
		{
			if (action !== PLUS_ONE_ACTION && action !== MINUS_ONE_ACTION)
			{
				throw new Error(`ModifyCounters action type ${action} is not known`);
			}

			const category = this.getCategoryFromCategoryStorage();
			if (!category)
			{
				return;
			}

			const { processStages = [], successStages = [], failedStages = [] } = category;
			const stages = [...processStages, ...successStages, ...failedStages];

			const stage = stages.find((item) => item.statusId === columnId);
			if (!stage)
			{
				return;
			}

			const stageCounters = CategoryCountersStoreManager.getStages().find((item) => item.id === stage.id);

			const data = {
				total: (action === PLUS_ONE_ACTION ? stageCounters.total + amount : stageCounters.total - amount),
				count: (action === PLUS_ONE_ACTION ? stageCounters.count + count : stageCounters.count - count),
			};

			CategoryCountersStoreManager.updateStage(stage.id, data);
		}
	}

	module.exports = { KanbanTab };
});
