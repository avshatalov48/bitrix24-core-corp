/**
 * @module tasks/layout/dashboard/list-adapter
 */
jn.define('tasks/layout/dashboard/list-adapter', (require, exports, module) => {
	const { Feature } = require('feature');
	const { SkeletonFactory } = require('layout/ui/simple-list/skeleton');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { TypeGenerator } = require('layout/ui/stateful-list/type-generator');
	const { TasksDashboardBaseView } = require('tasks/layout/dashboard/base-view');
	const { ListItemType, ListItemsFactory } = require('tasks/layout/simple-list/items');
	const {
		TaskListItemSkeleton,
		TaskKanbanItemSkeleton,
	} = require('tasks/layout/simple-list/skeleton');

	SkeletonFactory.register(ListItemType.TASK, TaskListItemSkeleton);
	SkeletonFactory.register(ListItemType.KANBAN, TaskKanbanItemSkeleton);

	const counterCallback = ({ value }) => value > 0;
	const checklistCallback = ({ uncompleted }) => uncompleted > 0;
	const statusCallback = (status, item) => {
		return (Number(status) === 4 && Number(item.createdBy) !== Number(env.userId)) || Number(status) === 5;
	};

	/**
	 * @class ListAdapter
	 */
	class ListAdapter extends TasksDashboardBaseView
	{
		getActiveStage()
		{
			return null;
		}

		isAllStagesDisplayed()
		{
			return true;
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				new StatefulList({
					testId: 'task-list',
					showAirStyle: Feature.isAirStyleSupported(),
					typeGenerator: {
						generator: TypeGenerator.generators.bySelectedProperties,
						properties: [
							'repetitive',
							'like',
							'counter',
							'checklist',
							'status',
							'isMuted',
							'isPinned',
							'priority',
							'responsible',
							'deadline',
							'allowTimeTracking',
							'isCreationErrorExist',
						],
						callbacks: {
							counter: counterCallback,
							checklist: checklistCallback,
							status: statusCallback,
						},
					},
					actions: this.props.actions,
					actionParams: this.props.actionParams,
					itemsLoadLimit: this.props.itemsLoadLimit,
					actionCallbacks: this.props.actionCallbacks || {},
					itemLayoutOptions: this.props.itemLayoutOptions,
					itemDetailOpenHandler: this.props.onItemClick,
					onItemLongClick: this.props.onItemLongClick,
					// itemCounterLongClickHandler: this.props.itemCounterLongClickHandler,
					// getItemCustomStyles: this.getItemCustomStyles,
					isShowFloatingButton: BX.prop.getBoolean(this.props, 'isShowFloatingButton', true),
					onFloatingButtonClick: this.props.onFloatingButtonClick,
					// onFloatingButtonLongClick: this.onFloatingButtonLongClick.bind(this),
					// needInitMenu: (params.hasOwnProperty('needInitMenu') ? params.needInitMenu : true),
					getEmptyListComponent: this.props.getEmptyListComponent,
					layout: this.props.layout,
					menuButtons: (this.props.layoutMenuButtons || []),
					itemType: ListItemType.TASK,
					itemFactory: ListItemsFactory,
					itemParams: {
						view: this.getView(),
					},
					// getRuntimeParams: this.getRuntimeParams,
					// showEmptySpaceItem: this.isEnabledKanbanToolbar(),
					pull: this.props.pull,
					sortingConfig: this.props.sortingConfig,
					// onDetailCardUpdateHandler: this.props.onDetailCardUpdateHandler || null,
					// onDetailCardCreateHandler: this.props.onDetailCardCreateHandler || null,
					onPanListHandler: this.props.onPanList,
					onItemAdded: this.props.onItemAdded,
					onItemDeleted: this.props.onItemDeleted,
					onBeforeItemsSetState: this.props.onBeforeItemsSetState,
					onBeforeItemsRender: this.props.onBeforeItemsRender,
					changeItemsOperations: this.props.changeItemsOperations,
					onListReloaded: this.props.onListReloaded,
					showTitleLoader: this.props.showTitleLoader,
					hideTitleLoader: this.props.hideTitleLoader,
					// onNotViewableHandler: this.props.onNotViewableHandler || null,
					// reloadListCallbackHandler: this.reloadListCallbackHandler,
					// context: {
					// 	slideName,
					// },
					ref: this.bindRef,
					animationTypes: this.props.animationTypes,
					currentView: this.props.currentView,
				}),
			);
		}

		/**
		 * @override
		 * @param {object[]} buttons
		 */
		updateTopButtons(buttons)
		{
			if (this.viewComponent)
			{
				this.viewComponent.initMenu(null, buttons);
			}
		}

		/**
		 * @override
		 * @param {object} params
		 */
		reload(params = {})
		{
			const { skipUseCache = true } = params;

			if (this.viewComponent)
			{
				this.viewComponent.reload(params, { useCache: !skipUseCache });
			}
		}

		/**
		 * @private
		 * @return {string}
		 */
		getView()
		{
			return this.props.loadStagesParams.view;
		}
	}

	module.exports = { ListAdapter };
});
