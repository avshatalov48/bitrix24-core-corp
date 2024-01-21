/**
 * @module tasks/layout/dashboard/kanban-adapter
 */
jn.define('tasks/layout/dashboard/kanban-adapter', (require, exports, module) => {
	const { Kanban } = require('layout/ui/kanban');
	const { TasksDashboardBaseView } = require('tasks/layout/dashboard/base-view');
	const { TasksToolbar } = require('tasks/layout/dashboard/toolbar');
	const { ListItemType, ListItemsFactory } = require('tasks/layout/simple-list/items');
	const store = require('statemanager/redux/store');
	const { selectByViewAndProjectId, selectById: selectStageById } = require('tasks/statemanager/redux/slices/stage-settings');
	const { allStagesId } = require('tasks/statemanager/redux/slices/stage-counters');
	const { selectTaskStageId } = require('tasks/statemanager/redux/slices/tasks-stages');
	const { ViewMode } = require('tasks/enum');

	/**
	 * @class KanbanAdapter
	 */
	class KanbanAdapter extends TasksDashboardBaseView
	{
		constructor(props)
		{
			super(props);

			this.stagesProviderHandler = this.getStagesProvider.bind(this);
			this.selectItemStageId = this.selectItemStageId.bind(this);
			this.mutateItemStage = () => {};
			this.onPrepareItemParams = this.onPrepareItemParams.bind(this);
			this.onChangeItemStage = this.onChangeItemStage.bind(this);
		}

		getActiveStage()
		{
			return this.viewComponent?.getActiveStage();
		}

		isAllStagesDisplayed()
		{
			return this.viewComponent?.isAllStagesDisplayed();
		}

		getStagesProvider()
		{
			return selectByViewAndProjectId(store.getState(), {
				view: this.getView(),
				projectId: this.getProjectId(),
			});
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				new Kanban({
					id: this.props.id,
					stagesProvider: this.stagesProviderHandler,
					toolbar: {
						enabled: true,
						componentClass: TasksToolbar,
						props: {
							title: this.props.title,
							filterParams: this.getFilterParams(),
							activeStageId: allStagesId,
						},
					},
					// onMoveItemError: this.onMoveItemError,
					actions: this.props.actions,
					actionParams: this.props.actionParams || {},
					itemsLoadLimit: this.props.itemsLoadLimit,
					actionCallbacks: this.props.actionCallbacks || {},
					layout: this.props.layout,
					itemDetailOpenHandler: this.props.onItemClick,
					onItemLongClick: this.props.onItemLongClick,
					// itemCounterLongClickHandler: this.getCounterLongClickHandler(),
					getEmptyListComponent: this.props.getEmptyListComponent,
					isShowFloatingButton: BX.prop.getBoolean(this.props, 'isShowFloatingButton', true),
					onFloatingButtonClick: this.props.onFloatingButtonClick,
					// onFloatingButtonLongClick: this.handleFloatingButtonLongClick.bind(this),
					// onDetailCardUpdateHandler: this.onDetailCardUpdate.bind(this),
					// onDetailCardCreateHandler: this.onDetailCardCreate.bind(this),
					// onNotViewableHandler: this.onNotViewable,
					onPanListHandler: this.props.onPanList,
					onItemAdded: this.props.onItemAdded,
					onItemDeleted: this.props.onItemDeleted,
					onListReloaded: this.props.onListReloaded,
					onBeforeItemsSetState: this.props.onBeforeItemsSetState,
					onBeforeItemsRender: this.props.onBeforeItemsRender,
					changeItemsOperations: this.props.changeItemsOperations,
					showTitleLoader: this.props.showTitleLoader,
					hideTitleLoader: this.props.hideTitleLoader,
					// initCountersHandler: this.initCategoryCounters,
					itemType: ListItemType.KANBAN,
					itemFactory: ListItemsFactory,
					itemActions: [],
					itemParams: {
						view: this.getView(),
						projectId: this.getProjectId(),
						ownerId: this.getOwnerId(),
					},
					onPrepareItemParams: this.onPrepareItemParams,
					pull: this.props.pull,
					sortingConfig: this.props.sortingConfig,
					layoutOptions: {},
					itemLayoutOptions: this.props.itemLayoutOptions,
					menuButtons: (this.props.layoutMenuButtons || []),
					needInitMenu: true,
					ref: this.bindRef,
					animationTypes: this.props.animationTypes,
					// todo: add typeGenerator
					// getMenuButtons: this.getMenuButtons.bind(this),
					selectItemStageId: this.selectItemStageId,
					mutateItemStage: this.mutateItemStage,
				}),
			);
		}

		onPrepareItemParams(params)
		{
			return {
				...params,
				onChangeItemStage: this.onChangeItemStage,
			};
		}

		onChangeItemStage(stageId, category, { itemId, prevStageId, nextStageId })
		{
			if (!this.viewComponent || prevStageId === nextStageId)
			{
				return;
			}

			/** @type {Kanban} */
			const kanbanInstance = this.viewComponent;
			const state = store.getState();
			const prevStage = selectStageById(state, prevStageId);
			const nextStage = selectStageById(state, nextStageId);
			const activeStageId = kanbanInstance.getActiveStageId();

			if (!prevStage || !nextStage)
			{
				return;
			}

			if (activeStageId && activeStageId === prevStage.id)
			{
				// just tiny timeout for better animation appearance
				setTimeout(() => {
					const selectStageCode = (item) => item.id;
					const animationType = kanbanInstance.getAnimationType(prevStage.id, nextStage.id, selectStageCode);

					kanbanInstance.deleteItemFromStatefulList(itemId, animationType);
				}, 300);
			}

			if (this.getView() === ViewMode.DEADLINE)
			{
				return;
			}

			const data = {
				...this.props.actionParams.updateItemStage,
				id: itemId,
				stageId: nextStageId,
			};

			BX.ajax.runAction(this.props.actions.updateItemStage, { data })
				.then((response) => {
					if (response.errors && response.errors.length > 0)
					{
						throw new Error(response);
					}
				})
				.catch((response) => {
					console.error('TASKS_UPDATE_STAGE_ERROR', response);

					const reload = () => this.reload();

					kanbanInstance.handleAjaxErrors(response.errors || [])
						.then(reload)
						.catch(reload);
				});
		}

		/**
		 * @private
		 * @param {object} item
		 * @return {number|undefined}
		 */
		selectItemStageId(item)
		{
			return selectTaskStageId(store.getState(), item.id, this.getView(), this.getOwnerId());
		}

		/**
		 * @private
		 * @return {string}
		 */
		getView()
		{
			return this.props.loadStagesParams.view;
		}

		/**
		 * @private
		 * @return {number|undefined}
		 */
		getProjectId()
		{
			return this.props.loadStagesParams.projectId;
		}

		/**
		 * @private
		 * @return {number}
		 */
		getOwnerId()
		{
			return this.props.ownerId;
		}

		/**
		 * @private
		 * @return {object}
		 */
		getFilterParams()
		{
			return this.props.loadStagesParams;
		}

		/**
		 * @override
		 * @param {object[]} buttons
		 */
		updateTopButtons(buttons)
		{
			if (this.viewComponent)
			{
				this.viewComponent.updateTopButtons(buttons);
			}
		}

		/**
		 * @override
		 * @param {object} params
		 */
		reload(params = {})
		{
			if (this.viewComponent)
			{
				this.viewComponent.reload(false, params);
			}
		}
	}

	module.exports = { KanbanAdapter };
});
