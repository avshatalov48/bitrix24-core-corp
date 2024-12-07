/**
 * @module crm/entity-tab/kanban
 */
jn.define('crm/entity-tab/kanban', (require, exports, module) => {
	const { TypeId } = require('crm/type');
	const { EntityTab } = require('crm/entity-tab');
	const { TypeSort } = require('crm/entity-tab/sort');
	const { TypePull } = require('crm/entity-tab/pull-manager');
	const { ToolbarFactory } = require('crm/kanban/toolbar');
	const { getActionChangeCrmMode } = require('crm/entity-actions/change-crm-mode');
	const { getSmartActivityMenuItem } = require('crm/entity-detail/component/smart-activity-menu-item');
	const { get } = require('utils/object');
	const { ListItemType, ListItemsFactory } = require('crm/simple-list/items');
	const { Kanban } = require('layout/ui/kanban');
	const { Icon } = require('ui-system/blocks/icon');

	const store = require('statemanager/redux/store');
	const {
		fetchStageCounters,
		counterIncremented,
		counterDecremented,
		selectById,
	} = require('crm/statemanager/redux/slices/stage-counters');

	const {
		getCrmKanbanUniqId,
		fetchCrmKanban: fetchCrmKanbanSettings,
		selectStagesIdsBySemantics,
	} = require('crm/statemanager/redux/slices/kanban-settings');

	const { selectById: selectStageById } = require('crm/statemanager/redux/slices/stage-settings');
	const { batchActions } = require('statemanager/redux/batched-actions');

	const PLUS_ONE_ACTION = 'plus';
	const MINUS_ONE_ACTION = 'minus';
	const UPDATE_ACTION = 'update';
	const CREATE_ACTION = 'create';
	const EXCLUDE_ACTION = 'excludeEntity';
	const DELETE_ACTION = 'deleteEntity';

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
			this.onItemDetailCardUpdateHandler = this.handleOnItemDetailCardUpdate.bind(this);
			this.onItemDetailCardCreateHandler = this.handleOnItemDetailCardCreate.bind(this);
			this.onItemDetailCardAccessDeniedHandler = this.handleOnItemDetailCardAccessDenied.bind(this);
			this.onMoveItemError = this.handleInItemMoveError.bind(this);
			this.getMenuButtonsHandler = this.getMenuButtons.bind(this);

			this.toolbarFactory = new ToolbarFactory();
			this.categoryPermissionsProbablyRevoked = false;

			this.props.layout.on('onViewShown', () => {
				if (this.categoryPermissionsProbablyRevoked)
				{
					this.categoryPermissionsProbablyRevoked = false;
					this.onNotViewableHandler();
				}
			});
		}

		getView()
		{
			return 'kanban';
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

			viewComponent.reload(force, params);
		}

		/**
		 * @param {object} params
		 * @param {boolean} force
		 */
		initCategoryCounters(params = {}, force = false)
		{
			store.dispatch(fetchStageCounters({
				entityTypeId: this.props.entityTypeId,
				categoryId: this.getCurrentCategoryId(),
				params,
				forceFetch: force,
			}));
		}

		initAfterCategoryChange(category, data)
		{
			super.initAfterCategoryChange(category, data);

			const { categoryId = 0 } = data;

			store.dispatch(fetchCrmKanbanSettings({
				categoryId,
				entityTypeId: this.props.entityTypeId,
			}));
		}

		componentDidMount()
		{
			BX.addCustomEvent('Crm.Activity.Todo::onChangeNotifications', this.setSmartActivityStatus);
			BX.addCustomEvent('UI.SimpleList::onUpdateItem', this.onItemUpdatedHandler);
			BX.addCustomEvent('UI.SimpleList::onDeleteItem', this.onItemDeletedHandler);
			BX.addCustomEvent('UI.Kanban::onItemMoved', this.onItemMovedHandler);
			BX.addCustomEvent('Crm.Item::onChangePipeline', this.onItemChangedCategoryHandler);
			BX.addCustomEvent('DetailCard::onUpdate', this.onItemDetailCardUpdateHandler);
			BX.addCustomEvent('DetailCard::onCreate', this.onItemDetailCardCreateHandler);
			BX.addCustomEvent('DetailCard::onAccessDenied', this.onItemDetailCardAccessDeniedHandler);

			store.dispatch(fetchCrmKanbanSettings({
				entityTypeId: this.props.entityTypeId,
				categoryId: this.getCurrentCategoryId(),
				forceFetch: true,
			}));

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
			BX.removeCustomEvent('DetailCard::onUpdate', this.onItemDetailCardUpdateHandler);
			BX.removeCustomEvent('DetailCard::onCreate', this.onItemDetailCardCreateHandler);
			BX.removeCustomEvent('DetailCard::onAccessDenied', this.onItemDetailCardAccessDeniedHandler);
		}

		render()
		{
			const { isLoading, isEmptyAvailableCategories } = this.state;

			let content = null;

			if (isEmptyAvailableCategories)
			{
				content = this.renderForbiddenScreen();
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

		renderForbiddenScreen()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						alignItems: 'center',
						flex: 1,
					},
				},
				Text({
					text: BX.message('M_CRM_ENTITY_TAB_FORBIDDEN_FOR_ALL_CATEGORIES'),
				}),
			);
		}

		renderKanban()
		{
			const entityType = this.getCurrentEntityType();
			const entityTypeId = this.props.entityTypeId;
			const categoryId = this.getCurrentCategoryId();

			return new Kanban({
				id: this.getKanbanId(),
				stagesProvider: () => {
					const stages = selectStagesIdsBySemantics(
						store.getState(),
						getCrmKanbanUniqId(entityTypeId, categoryId),
					);
					const stageIds = [...stages.processStages, ...stages.successStages, ...stages.failedStages];

					return stageIds.map((id) => selectStageById(store.getState(), id));
				},
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
				forcedShowSkeleton: false,
				onMoveItemError: this.onMoveItemError,
				actions: this.props.actions,
				actionParams: this.prepareActionParams(),
				actionCallbacks: this.props.actionCallbacks,
				filterParams: this.getFilterParams(),
				layout: this.props.layout,
				layoutMenuActions: this.getMenuActions(),
				itemCounterLongClickHandler: this.getCounterLongClickHandler(),
				isShowFloatingButton: this.isShowFloatingButton(),
				onNotViewableHandler: this.onNotViewable,
				onPanListHandler: this.props.onPanList || null,
				popupItemMenu: true,
				initCountersHandler: this.initCategoryCounters,
				itemType: ListItemType.CRM_ENTITY,
				itemFactory: ListItemsFactory,
				itemActions: this.getItemActions(),
				itemParams: {
					isClientEnabled: this.isClientEnabled(),
					...this.props.itemParams,
				},
				onPrepareItemParams: (params) => ({
					...params,
					categoryId: this.getCurrentCategoryId(),
				}),
				pull: this.getPullConfig(),
				layoutOptions: this.getLayoutOptions(),
				itemLayoutOptions: this.getItemLayoutOptions(),
				menuButtons: this.getMenuButtons(),
				cacheName: this.getCacheName(),
				getEmptyListComponent: this.getEmptyListComponent,
				needInitMenu: this.props.needInitMenu,
				onFloatingButtonClick: this.onFloatingButtonClickHandler,
				onFloatingButtonLongClick: this.onFloatingButtonLongClickHandler,
				itemDetailOpenHandler: this.itemDetailOpenHandler,
				onDetailCardUpdateHandler: this.onDetailCardUpdateHandler,
				onDetailCardCreateHandler: this.onDetailCardCreateHandler,
				getMenuButtons: this.getMenuButtonsHandler,
				ref: this.bindRef,
				analyticsLabel: {
					entityTypeId,
					module: 'crm',
					source: 'crm-entity-tab',
				},
			});
		}

		getKanbanId()
		{
			const categoryId = this.getCurrentCategoryId();

			return `KANBAN_${this.entityTypeName}_${categoryId}`;
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

		/**
		 * @return {StatefulList|null}
		 */
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

			if (params.eventName === TypePull.EventNameItemAdded)
			{
				const stageCode = get(params, 'item.data.columnId', '');

				return this.isCurrentStage(stageCode) || this.isAllStagesDisplayed();
			}

			return this.hasItemInCurrentColumn(params.item.id);
		}

		isAllStagesDisplayed()
		{
			return this.getViewComponent().isAllStagesDisplayed();
		}

		hasColumnChangesInItem(item, oldItem)
		{
			const viewComponent = this.getViewComponent();
			const nextStage = viewComponent.getStageByCode(item.data.columnId);

			return (
				oldItem
				&& oldItem.state.columnId
				&& oldItem.state.columnId !== nextStage?.id
			);
		}

		/**
		 * @param {string} stageCode
		 * @returns {boolean}
		 */
		isCurrentStage(stageCode)
		{
			const activeStage = this.getViewComponent().getActiveStage();

			return activeStage && activeStage.statusId === stageCode;
		}

		getEmptyColumnScreenConfig(model)
		{
			return model.getEmptyColumnScreenConfig({
				column: this.getViewComponent().getActiveStage(),
			});
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
			const { id, title, onAction } = getActionChangeCrmMode();

			return {
				id,
				title,
				icon: Icon.SETTINGS,
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
				const activeStage = this.getViewComponent().getActiveStage();

				if (activeStage && activeStage.statusId !== item.data.columnId)
				{
					itemConfig.showReloadListNotification = false;
				}
			}

			return itemConfig;
		}

		/**
		 * @return {Object}
		 */
		getEmptyScreenProps()
		{
			if (this.isUnsuitableCurrentStage())
			{
				const model = this.getEntityTypeModel();

				return model.getUnsuitableStageScreenConfig();
			}

			return super.getEmptyScreenProps();
		}

		isUnsuitableCurrentStage()
		{
			const viewComponent = this.getViewComponent();
			const stageId = viewComponent.getActiveStageId();
			const currentStage = selectById(store.getState(), stageId);

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
				animate: !this.getCurrentStatefulList().shouldShowReloadListNotification(),
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
			if (params.kanbanId && params.kanbanId !== this.getKanbanId())
			{
				return;
			}

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

			if (action === PLUS_ONE_ACTION)
			{
				store.dispatch(counterIncremented({
					id: stage.id,
					amount,
					count,
				}));
			}
			else
			{
				store.dispatch(counterDecremented({
					id: stage.id,
					amount,
					count,
				}));
			}
		}

		handleOnItemDetailCardUpdate(uid, params)
		{
			this.animateItemAfterBackFromDetail(params.actionName || UPDATE_ACTION, params);

			this.updateStageCountersAfterReturnFromDetail(params.actionName || UPDATE_ACTION, params);
		}

		handleOnItemDetailCardCreate(uid, params)
		{
			this.animateItemAfterBackFromDetail(CREATE_ACTION, params);

			this.updateStageCountersAfterReturnFromDetail(CREATE_ACTION, params);
		}

		animateItemAfterBackFromDetail(action, params)
		{
			const { entityTypeId } = this.props;
			const owner = BX.prop.getObject(params, 'owner', {});

			// todo remove all direct calls of stateful list. use viewComponent.publicMethods() instead
			const statefulList = this.getCurrentStatefulList();

			if (!statefulList)
			{
				return;
			}

			const isBackFromSlaveEntityType = (owner.id && statefulList.hasItem(owner.id));

			if (entityTypeId === params.entityTypeId || isBackFromSlaveEntityType)
			{
				const data = this.onBeforeReloadHandler();
				const id = isBackFromSlaveEntityType ? owner.id : params.entityId;
				const activeStageId = this.getViewComponent().getActiveStageId();

				if (
					!data.reload
					&& entityTypeId === params.entityTypeId
					&& (
						action === EXCLUDE_ACTION
						|| action === DELETE_ACTION
						|| (activeStageId && activeStageId !== params.entityModel.STAGE_ID)
						|| this.itemCategoryIsChanged(params)
					)
				)
				{
					void statefulList.deleteItem(id);

					return;
				}

				if (!data.reload && action === UPDATE_ACTION)
				{
					void statefulList.updateItems([id], BX.prop.getBoolean(data, 'animate', true), false, false);

					return;
				}

				statefulList.addToAnimateIds(id);

				this.reload({
					force: true,
					menuButtons: this.getMenuButtons(),
				});
			}
		}

		updateStageCountersAfterReturnFromDetail(action, params)
		{
			const newOpportunity = BX.prop.getNumber(params.entityModel, 'OPPORTUNITY', null);
			const newStageId = BX.prop.getNumber(params.entityModel, 'STAGE_ID', null);

			if (action === DELETE_ACTION || newStageId === null || newOpportunity === null)
			{
				// Update stage counters in handleOnItemDeleted
				return;
			}

			const updateCategory = BX.prop.getBoolean(params.additionalData, 'isCategoryUpdated', false);

			if (updateCategory)
			{
				// Update stage counters in modifyCountersByColumnId
				return;
			}

			if (action === CREATE_ACTION)
			{
				store.dispatch(counterIncremented({
					id: newStageId,
					amount: newOpportunity,
					count: 1,
				}));

				return;
			}

			const oldOpportunity = BX.prop.getNumber(params?.originalEntityModel, 'OPPORTUNITY', null);
			const originalStageId = BX.prop.getNumber(params?.originalEntityModel, 'STAGE_ID', null);

			if (oldOpportunity === null || originalStageId === null)
			{
				return;
			}

			if (originalStageId !== newStageId)
			{
				store.dispatch(
					batchActions([
						counterDecremented({
							id: originalStageId,
							amount: oldOpportunity,
							count: 1,
						}),
						counterIncremented({
							id: newStageId,
							amount: newOpportunity,
							count: 1,
						}),
					]),
				);
			}
			else if (oldOpportunity !== newOpportunity)
			{
				if (oldOpportunity < newOpportunity)
				{
					store.dispatch(counterIncremented({
						id: newStageId,
						amount: newOpportunity - oldOpportunity,
						count: 0,
					}));
				}
				else
				{
					store.dispatch(counterDecremented({
						id: newStageId,
						amount: oldOpportunity - newOpportunity,
						count: 0,
					}));
				}
			}
		}

		itemCategoryIsChanged(params)
		{
			const category = this.getCategoryFromCategoryStorage();

			if (!category || !category.categoriesEnabled)
			{
				return false;
			}

			return params.categoryId !== category.categoryId;
		}

		handleOnItemDetailCardAccessDenied(uid, params)
		{
			if (this.props.entityTypeId === params.entityTypeId)
			{
				this.categoryPermissionsProbablyRevoked = true;
			}
		}

		/**
		 * @param {KanbanBackendError[]} errors
		 * @param {number} itemId
		 * @param {KanbanStage} prevStage
		 * @param {KanbanStage} nextStage
		 * @param {Kanban} kanbanInstance
		 */
		handleInItemMoveError({ errors, itemId, prevStage, nextStage, kanbanInstance })
		{
			return new Promise((resolve) => {
				void requireLazy('crm:required-fields')
					.then(({ RequiredFields }) => {
						if (RequiredFields && RequiredFields.hasRequiredFields(errors))
						{
							const columnId = nextStage.id;

							RequiredFields.show({
								errors,
								params: {
									entityId: itemId,
									entityTypeId: this.props.entityTypeId,
									uid: itemId,
								},
								onSave: () => kanbanInstance.moveItem(itemId, columnId).then(resolve),
								onCancel: resolve,
							});
						}
						else
						{
							return kanbanInstance.handleAjaxErrors(errors);
						}
					})
					.catch(() => kanbanInstance.handleAjaxErrors(errors));
			});
		}
	}

	module.exports = { KanbanTab };
});
