/**
 * @module bizproc/workflow/list
 */
// eslint-disable-next-line max-classes-per-file
jn.define('bizproc/workflow/list', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { magnifierWithMenuAndDot } = require('assets/common');
	const { Loc } = require('loc');
	const { showToast, Position } = require('toast');

	const { usersUpserted, usersAdded } = require('statemanager/redux/slices/users');
	const { dispatch } = require('statemanager/redux/store');

	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { SearchLayout } = require('layout/ui/search-bar');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { ListItemsFactory } = require('layout/ui/simple-list/items');
	const { SkeletonFactory } = require('layout/ui/simple-list/skeleton');

	const { WorkflowSimpleList } = require('bizproc/workflow/list/simple-list');
	const { ViewMode } = require('bizproc/workflow/list/view-mode');

	const { WorkflowItem } = require('bizproc/workflow/list/item');
	const { Skeleton } = require('bizproc/workflow/list/skeleton');
	const { BottomPanel } = require('bizproc/workflow/list/bottom-panel');

	SkeletonFactory.register('workflow', Skeleton);

	class WorkflowList extends WorkflowSimpleList
	{
		constructor(props)
		{
			super(props);

			this.showSearch = this.showSearch.bind(this);
			this.onSearch = this.onSearch.bind(this);
			this.onPanList = this.onPanList.bind(this);

			this.search = new SearchLayout({
				layout: this.layout,
				id: 'bp_list',
				cacheId: `bp_list_${env.userId}`,
				presetId: 'in_work',
				searchDataAction: 'bizprocmobile.Workflow.getFilterPresets',
				searchDataActionParams: {
					// groupId: this.props.projectId,
				},
				onSearch: this.onSearch,
				onCancel: this.onSearch,
			});
			this.filterPresetId = 'in_work';
			this.filterSearchQuery = '';

			this.onMultiSelectTasksCompleted = this.onMultiSelectTasksCompleted.bind(this);

			this.handleBeforeItemsRender = this.handleBeforeItemsRender.bind(this);
			this.handleFloatingButtonClick = this.handleFloatingButtonClick.bind(this);
			this.renderEmptyListComponent = this.renderEmptyListComponent.bind(this);
			this.handleRefreshEmptyScreen = this.handleRefreshEmptyScreen.bind(this);

			this.pullCallback = (data) => {
				return new Promise((resolve) => {
					if (data.command === 'workflow')
					{
						resolve(data);
					}
				});
			};
		}

		onTaskTouch({ task, isInline })
		{
			const item = this.listRef.getItem(task.workflowId);
			if (item && item.data.authorId !== Number(env.userId))
			{
				this.hideItem(task.workflowId);
			}

			if (this.state.selectedTasks)
			{
				this.onTaskDeselected({ task });
			}

			if (isInline)
			{
				this.notifyAboutCompletedTask(task);
			}
		}

		onSearch({ text, presetId })
		{
			if (this.filterPresetId === presetId && this.filterSearchQuery === text)
			{
				return;
			}

			this.filterPresetId = presetId;
			this.filterSearchQuery = text;

			// todo avoid setting state and duplicated ajax request
			this.setState({}, () => {
				this.listRef.reload(
					{ menuButtons: this.getMenuButtons() },
					{
						extra: this.getSearchParams(),
						useCache: false,
					},
				);
			});
		}

		onPanList()
		{
			this.search.close();
		}

		getSearchParams()
		{
			return {
				filterPresetId: this.filterPresetId,
				filterSearchQuery: this.filterSearchQuery,
			};
		}

		showSearch()
		{
			this.search.show();
		}

		getMenuActions()
		{
			return (this.state.viewMode === ViewMode.REGULAR && [this.getSelectAllAction()]) || [];
		}

		getSelectAllAction()
		{
			return {
				id: 'bizproc-select-all',
				title: Loc.getMessage('BPMOBILE_WORKFLOW_TASK_LAYOUT_ACTION_SELECT_ALL'),
				iconUrl: `${currentDomain}/bitrix/mobileapp/bizprocmobile/extensions/bizproc/assets/workflow/list/select-all.png`,
				onItemSelected: this.onSelectAllActionClick.bind(this),
			};
		}

		onSelectAllActionClick()
		{
			if (!this.listRef)
			{
				return;
			}

			const items = this.listRef.getItems();
			const selectedTasks = new Map();
			items.forEach((item) => {
				if (item.data.tasks && item.data.tasks[0])
				{
					const task = item.data.tasks[0];

					selectedTasks.set(
						parseInt(task.id, 10),
						{
							...task,
							workflowId: item.id,
							typeName: item.data.typeName,
							item,
						},
					);
				}
			});

			if (selectedTasks.size > 0)
			{
				this.setMultipleViewMode(selectedTasks);

				return;
			}

			showToast(
				{
					message: Loc.getMessage('BPMOBILE_WORKFLOW_TASK_LAYOUT_ACTION_SELECT_ALL_EMPTY_ERROR'),
					position: Position.TOP,
				},
				this.layout,
			);
		}

		getMenuButtons()
		{
			if (this.state.viewMode === ViewMode.MULTISELECT)
			{
				return [this.getCancelButton()];
			}

			return [
				this.getSearchButton(),
			];
		}

		getSearchButton()
		{
			return {
				testId: 'BP_WORKFLOW_LIST_SEARCH_BUTTON',
				type: 'search',
				badgeCode: 'search',
				callback: this.showSearch,
				svg: {
					content: magnifierWithMenuAndDot(
						AppTheme.colors.base4,
						this.search.getSearchButtonBackgroundColor(),
					),
				},
			};
		}

		getCancelButton()
		{
			return {
				id: 'cancel',
				name: Loc.getMessage('BPMOBILE_WORKFLOW_LIST_CANCEL'),
				type: 'text',
				color: AppTheme.colors.accentMainLinks,
				callback: this.setRegularViewMode.bind(this),
			};
		}

		createList()
		{
			return new StatefulList({
				testId: 'BP_WORKFLOW_LIST',
				actions: {
					loadItems: 'bizprocmobile.Workflow.loadList',
				},
				actionParams: {
					loadItems: {
						extra: this.getSearchParams(),
					},
				},
				actionCallbacks: {
					loadItems: this.onItemsLoaded,
				},
				itemLayoutOptions: {
					useItemMenu: true,
				},
				isShowFloatingButton: this.state.viewMode !== ViewMode.MULTISELECT,
				itemDetailOpenHandler: this.handleWorkflowDetailOpen,
				itemActions: this.getItemActions(),
				getEmptyListComponent: this.renderEmptyListComponent,
				layout: this.layout,
				layoutMenuActions: this.getMenuActions(),
				layoutOptions: {
					useSearch: false,
					useOnViewLoaded: true,
				},
				onPanListHandler: this.onPanList,
				menuButtons: this.getMenuButtons(),
				onFloatingButtonClick: this.handleFloatingButtonClick,
				cacheName: `bizproc.workflow.list.${env.userId}`,
				itemType: 'workflow',
				itemFactory: WorkflowItemsFactory,
				onBeforeItemsRender: this.handleBeforeItemsRender,
				pull: {
					moduleId: 'bizproc',
					callback: this.pullCallback,
					notificationAddText: Loc.getMessage('BPMOBILE_WORKFLOW_LIST_NEW_TASKS_NOTIFICATION'),
					shouldReloadDynamically: true,
				},
				ref: this.listCallbackRef,
			});
		}

		renderEmptyListComponent()
		{
			const isFiltered = this.filterPresetId !== 'in_work' || this.filterSearchQuery !== '';

			return new EmptyScreen({
				styles: {
					container: {
						paddingHorizontal: 20,
					},
					icon: {
						marginBottom: 50,
					},
				},
				image: {
					uri: EmptyScreen.makeLibraryImagePath('workflows.png', 'bizproc'),
					style: {
						width: 148,
						height: 149,
					},
				},
				title: Loc.getMessage(
					isFiltered
						? 'BPMOBILE_WORKFLOW_LIST_EMPTY_TITLE'
						: 'BPMOBILE_WORKFLOW_LIST_EMPTY_FILTERED_TITLE_MSGVER_1'
					,
				),
				description: isFiltered ? null : Loc.getMessage('BPMOBILE_WORKFLOW_LIST_EMPTY_DESCRIPTION'),
				onRefresh: this.handleRefreshEmptyScreen,
			});
		}

		handleRefreshEmptyScreen()
		{
			this.reload();
		}

		reload()
		{
			if (this.listRef && this.listRef.reloadList)
			{
				this.listRef.reloadList();
			}
		}

		renderBottomToolbar()
		{
			const selectedTasks = this.selectedTasks;
			if (this.state.viewMode === ViewMode.MULTISELECT && selectedTasks.size > 0)
			{
				return new BottomPanel({
					tasks: [...selectedTasks.values()],
					layout: this.layout,
					onTasksCompleted: this.onMultiSelectTasksCompleted,
				});
			}

			return null;
		}

		onMultiSelectTasksCompleted(completedTasks, delegatedTasks)
		{
			const tasks = [
				...(Array.isArray(completedTasks) ? completedTasks : []),
				...(Array.isArray(delegatedTasks) ? delegatedTasks : []),
			];

			tasks.forEach((task) => {
				setTimeout(
					() => {
						if (this.isWorkflowFirstTask(task.workflowId, task.id))
						{
							const item = this.listRef.getItem(task.workflowId);
							if (item)
							{
								if (item.data.authorId === Number(env.userId))
								{
									const itemComponent = this.listRef.getItemComponent(task.workflowId);
									if (itemComponent)
									{
										itemComponent.setIsDoing(true);
									}
								}
								else
								{
									this.hideItem(task.workflowId);
								}
							}
						}
					},
					200,
				);
			});

			this.setRegularViewMode();
		}

		getItemActions()
		{
			return [];
		}

		handleFloatingButtonClick()
		{
			void requireLazy('lists:element-creation-guide').then(({ ElementCreationGuide }) => {
				ElementCreationGuide.open({
					layout: this.layout,
				});
			});
		}

		handleBeforeItemsRender(items)
		{
			return this.prepareItems(items);
		}

		/**
		 * @private
		 * @param {object|undefined} responseData
		 * @param {'ajax'|'cache'} context
		 */
		onItemsLoaded(responseData, context)
		{
			const { users = [] } = responseData || {};
			const isCache = context === 'cache';

			if (users.length > 0)
			{
				dispatch(isCache ? usersAdded(users) : usersUpserted(users));
			}
		}

		hideItem(id)
		{
			this.listRef.deleteItem(id);
		}

		setRegularViewMode()
		{
			this.setState({ selectedTasks: null, viewMode: ViewMode.REGULAR }, () => {
				if (this.listRef && this.listRef.initMenu)
				{
					this.listRef.initMenu();
				}
			});
		}

		setMultipleViewMode(selectedTasks)
		{
			this.setState({ selectedTasks, viewMode: ViewMode.MULTISELECT }, () => {
				if (this.listRef && this.listRef.initMenu)
				{
					this.listRef.initMenu();
				}
			});
		}
	}

	class WorkflowItemsFactory extends ListItemsFactory
	{
		static create(type, data)
		{
			if (type === 'workflow')
			{
				return new WorkflowItem(data);
			}

			return super.create(type, data);
		}
	}

	module.exports = { WorkflowList };
});
