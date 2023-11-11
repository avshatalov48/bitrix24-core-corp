/**
 * @module tasks/layout/task-list
 */
jn.define('tasks/layout/task-list', (require, exports, module) => {
	const { ListItemType } = require('layout/ui/simple-list/items');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { TaskCreate } = require('tasks/layout/task/create');

	class TaskList extends LayoutComponent
	{
		static open(layout, userId, params)
		{
			layout.showComponent(new this({ layout, userId, params }));
		}

		constructor(props)
		{
			super(props);

			this.userId = props.userId;
			this.params = props.params;

			this.floatingButtonClickHandler = () => TaskCreate.open();
			this.itemDetailOpenHandler = (id, data) => {
				BX.postComponentEvent('taskbackground::task::open', [{
					taskId: id,
					taskInfo: { title: data.name },
				}]);
			};
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
					actions: {
						loadItems: 'tasksmobile.TaskKanban.loadItems',
					},
					actionParams: {
						loadItems: {},
					},
					itemLayoutOptions: {
						useItemMenu: true,
						// useCountersBlock: true,
						// useConnectsBlock: true,
					},
					itemDetailOpenHandler: this.itemDetailOpenHandler.bind(this),
					// itemCounterLongClickHandler: this.props.itemCounterLongClickHandler,
					// getItemCustomStyles: this.getItemCustomStyles,
					isShowFloatingButton: BX.prop.getBoolean(this.props, 'isShowFloatingButton', true),
					floatingButtonClickHandler: this.floatingButtonClickHandler.bind(this),
					// floatingButtonLongClickHandler: this.props.floatingButtonLongClickHandler,
					// needInitMenu: (params.hasOwnProperty('needInitMenu') ? params.needInitMenu : true),
					itemActions: [
						{
							id: 'open',
							title: 'Open',
							onClickCallback: (actionId, itemId, { parentWidget, parent }) => {
								parentWidget.close(() => this.itemDetailOpenHandler(itemId, parent.data));
							},
						},
					],
					// emptyListText: BX.message('M_UI_KANBAN_EMPTY_LIST_TEXT'),
					// emptySearchText: BX.message('M_UI_KANBAN_EMPTY_SEARCH_TEXT'),
					layout: this.props.layout,
					// layoutOptions: this.props.layoutOptions,
					cacheName: 'task-list',
					// layoutMenuActions: this.props.layoutMenuActions,
					// menuButtons: (this.props.menuButtons || []),
					// itemType: ListItemType.BASE,
					// itemParams: (params.itemParams || {}),
					// getEmptyListComponent: this.props.getEmptyListComponent || null,
					// getRuntimeParams: this.getRuntimeParams,
					// showEmptySpaceItem: this.isEnabledKanbanToolbar(),
					// pull: (this.props.pull || null),
					// onDetailCardUpdateHandler: this.props.onDetailCardUpdateHandler || null,
					// onDetailCardCreateHandler: this.props.onDetailCardCreateHandler || null,
					// onPanListHandler: this.props.onPanListHandler || null,
					// onNotViewableHandler: this.props.onNotViewableHandler || null,
					// reloadListCallbackHandler: this.reloadListCallbackHandler,
					// skipRenderIfEmpty: true,
					// context: {
					// 	slideName,
					// },
					// ref: useCallback((ref) => {
					// 	this.refsContainer.setColumn(slideName, ref);
					// }, [slideName]),
					// analyticsLabel: this.props.analyticsLabel || {}
				}),
			);
		}
	}

	module.exports = { TaskList };
});
