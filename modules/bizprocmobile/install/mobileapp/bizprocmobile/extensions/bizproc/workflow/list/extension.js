/**
 * @module bizproc/workflow/list
 */
// eslint-disable-next-line max-classes-per-file
jn.define('bizproc/workflow/list', (require, exports, module) => {
	const { dispatch } = require('statemanager/redux/store');
	const { usersUpserted, usersAdded } = require('statemanager/redux/slices/users');
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { ListItemsFactory } = require('layout/ui/simple-list/items');
	const { WorkflowItem } = require('bizproc/workflow/list/item');
	const { SearchLayout } = require('layout/ui/search-bar');
	const { magnifierWithMenuAndDot } = require('assets/common');
	const AppTheme = require('apptheme');
	const { SkeletonFactory } = require('layout/ui/simple-list/skeleton');
	const { Skeleton } = require('bizproc/workflow/list/skeleton');
	const { EventEmitter } = require('event-emitter');
	const { showToast } = require('toast');

	SkeletonFactory.register('workflow', Skeleton);
	class WorkflowList extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.statefulList = null;

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

			// eslint-disable-next-line no-undef
			this.customEventEmitter = EventEmitter.createWithUid('bizproc');
			this.onTaskTouch = this.onTaskTouch.bind(this);
		}

		componentDidMount()
		{
			this.customEventEmitter.on('Task:onTouch', this.onTaskTouch);
		}

		componentWillUnmount()
		{
			this.customEventEmitter.off('Task:onTouch', this.onTaskTouch);
		}

		onTaskTouch({ task, isInline })
		{
			const item = this.statefulList.getItem(task.workflowId);
			if (item && item.data.authorId !== Number(env.userId))
			{
				this.statefulList.deleteItem(task.workflowId);
			}

			if (task && task.name && isInline)
			{
				showToast(
					{
						message: Loc.getMessage(
							'BPMOBILE_WORKFLOW_LIST_TASK_TOUCHED',
							{ '#TASK_NAME#': task.name },
						),
						time: 2,
					},
					this.layout,
				);
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
				this.statefulList.reload(
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

		getMenuButtons()
		{
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

		get layout()
		{
			return this.props.layout || {};
		}

		render()
		{
			return this.createStatefulList();
		}

		createStatefulList()
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
				isShowFloatingButton: true,
				itemDetailOpenHandler: this.handleWorkflowDetailOpen.bind(this),
				itemActions: this.getItemActions(),
				itemParams: {},
				getEmptyListComponent: this.renderEmptyListComponent.bind(this),
				layout,
				// layoutMenuActions: this.getMenuActions(),
				layoutOptions: {
					useSearch: false,
					useOnViewLoaded: true,
				},
				onPanListHandler: this.onPanList,
				menuButtons: this.getMenuButtons(),
				onFloatingButtonClick: this.handleFloatingButtonClick.bind(this),
				cacheName: `bizproc.workflow.list.${env.userId}`,
				itemType: 'workflow',
				itemFactory: WorkflowItemsFactory,
				pull: {
					moduleId: 'bizproc',
					callback: (data) => {
						return new Promise((resolve) => {
							if (data.command === 'workflow')
							{
								resolve(data);
							}
						});
					},
					notificationAddText: Loc.getMessage('BPMOBILE_WORKFLOW_LIST_NEW_TASKS_NOTIFICATION'),
					shouldReloadDynamically: true,
				},
				// eslint-disable-next-line no-return-assign
				ref: (ref) => this.statefulList = ref,
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
			});
		}

		handleWorkflowDetailOpen(entityId, item)
		{
			const task = item.tasks[0];
			if (!task)
			{
				void requireLazy('bizproc:workflow/details')
					.then(({ WorkflowDetails }) => {
						if (WorkflowDetails)
						{
							WorkflowDetails.open(
								{
									workflowId: entityId,
									title: item.typeName || null,
								},
								this.props.layout,
							);
						}
					})
				;

				return;
			}

			void requireLazy('bizproc:task/details').then(({ TaskDetails }) => {
				void TaskDetails.open(
					this.props.layout,
					{
						taskId: task.id,
						title: item.typeName,
					},
				);
			});
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
