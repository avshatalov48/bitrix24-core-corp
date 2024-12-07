/**
 * @module bizproc/workflow/list/simple-list
 */
jn.define('bizproc/workflow/list/simple-list', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { EventEmitter } = require('event-emitter');
	const { Loc } = require('loc');
	const { showToast, Position } = require('toast');
	const { Type } = require('type');
	const { clone, isEmpty } = require('utils/object');

	const { PureComponent } = require('layout/pure-component');
	const { SimpleList } = require('layout/ui/simple-list');
	const { ListItemsFactory, ListItemType } = require('layout/ui/simple-list/items');

	const { TaskErrorCode } = require('bizproc/task/task-constants');
	const { ViewMode } = require('bizproc/workflow/list/view-mode');
	const { WorkflowSimpleListItem } = require('bizproc/workflow/list/simple-list/item');
	const { BottomPanel } = require('bizproc/workflow/list/simple-list/bottom-panel');

	class WorkflowSimpleList extends PureComponent
	{
		static open(layout = PageManager, props = {})
		{
			layout.openWidget('layout', {
				modal: true,
				titleParams: {
					text: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_TITLE_MSGVER_1'),
				},
				backgroundColor: AppTheme.colors.bgContentPrimary,
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionPercent: 90,
					navigationBarColor: AppTheme.colors.bgSecondary,
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
				},
				onReady: (readyLayout) => {
					readyLayout.showComponent(new WorkflowSimpleList({
						layout: readyLayout,
						...props,
					}));
				},
			});
		}

		constructor(props)
		{
			super(props);

			this.state = {
				selectedTasks: null,
				viewMode: ViewMode.REGULAR,
				isRefreshing: false,
			};

			this.items = BX.prop.getArray(this.props, 'items', []);
			this.fillSelectedTasks();

			this.uid = props.uid || 'bizproc';
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.onTaskTouch = this.onTaskTouch.bind(this);
			this.handleWorkflowDetailOpen = this.handleWorkflowDetailOpen.bind(this);
			this.onTaskSelected = this.onTaskSelected.bind(this);
			this.onTaskDeselected = this.onTaskDeselected.bind(this);
			this.onTaskLoadFailed = this.onTaskLoadFailed.bind(this);
			this.onTaskDelegated = this.onTaskDelegated.bind(this);

			this.listRef = null;
			this.listCallbackRef = this.listCallbackRef.bind(this);
		}

		fillSelectedTasks()
		{
			if (Array.isArray(this.items))
			{
				this.state.selectedTasks = new Map();
				this.state.viewMode = ViewMode.MULTISELECT;
				this.items.forEach((item) => {
					if (item.selected === true && !isEmpty(item.data.tasks))
					{
						const task = item.data.tasks[0];
						const taskId = parseInt(task.id, 10);
						this.state.selectedTasks.set(taskId, {
							...task,
							workflowId: item.id,
							typeName: item.data.typeName,
							item,
						});
					}
				});

				if (this.state.selectedTasks.size <= 0)
				{
					this.state.selectedTasks = null;
					this.state.viewMode = ViewMode.REGULAR;
				}
			}
		}

		componentDidMount()
		{
			this.customEventEmitter
				.on('TaskDetails:onTaskDelegated', this.onTaskDelegated)
				.on('TaskDetails:onLoadFailed', this.onTaskLoadFailed)
				.on('Task:onTouch', this.onTaskTouch)
				.on('Task:onSelect', this.onTaskSelected)
				.on('Task:onDeselect', this.onTaskDeselected)
			;
		}

		componentWillUnmount()
		{
			this.customEventEmitter
				.off('TaskDetails:onTaskDelegated', this.onTaskDelegated)
				.off('TaskDetails:onLoadFailed', this.onTaskLoadFailed)
				.off('Task:onTouch', this.onTaskTouch)
				.off('Task:onSelect', this.onTaskSelected)
				.off('Task:onDeselect', this.onTaskDeselected)
			;
		}

		componentWillReceiveProps(props)
		{
			if (props.items !== undefined)
			{
				this.items = props.items;
				this.fillSelectedTasks();
			}
		}

		listCallbackRef(ref)
		{
			this.listRef = ref;
		}

		onTaskTouch({ task, isInline })
		{
			const item = this.listRef.getItem(task.workflowId);
			if (item)
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

		onTaskSelected({ task, workflowId, typeName, item })
		{
			if (!task)
			{
				return;
			}

			const selectedTasks = clone(this.selectedTasks);

			selectedTasks.set(parseInt(task.id, 10), {
				...task,
				workflowId,
				typeName,
				item,
			});

			this.setMultipleViewMode(selectedTasks);
		}

		onTaskDeselected({ task })
		{
			if (!task)
			{
				return;
			}

			const selectedTasks = clone(this.selectedTasks);
			selectedTasks.delete(parseInt(task.id, 10));

			if (isEmpty(selectedTasks))
			{
				this.setRegularViewMode();

				return;
			}

			this.setMultipleViewMode(selectedTasks);
		}

		onTaskLoadFailed({ errors, taskId, workflowId })
		{
			if (!this.listRef || !workflowId)
			{
				return;
			}

			const firstError = Array.isArray(errors) && errors.length > 0 ? errors[0] : {};
			if (!TaskErrorCode.isTaskNotFoundErrorCode(firstError.code))
			{
				return;
			}

			if (this.isWorkflowFirstTask(workflowId, taskId))
			{
				this.hideItem(workflowId);
				if (this.state.selectedTasks)
				{
					this.onTaskDeselected({ task: { id: taskId } });
				}
			}
		}

		onTaskDelegated({ task })
		{
			if (this.state.selectedTasks)
			{
				this.onTaskDeselected({ task });
			}

			if (this.isWorkflowFirstTask(task.workflowId, task.id))
			{
				this.hideItem(task.workflowId);
			}
		}

		notifyAboutCompletedTask(task)
		{
			if (task && task.name && BX.prop.getBoolean(this.props, 'showNotifications', true) === true)
			{
				showToast(
					{
						message: Loc.getMessage(
							'BPMOBILE_WORKFLOW_SIMPLE_LIST_TASK_TOUCHED',
							{ '#TASK_NAME#': task.name },
						),
						time: 2,
						position: this.state.viewMode === ViewMode.REGULAR ? Position.BOTTOM : Position.TOP,
					},
					this.layout,
				);
			}
		}

		get layout()
		{
			return this.props.layout || {};
		}

		get selectedTasks()
		{
			return this.state.selectedTasks || new Map();
		}

		render()
		{
			return View(
				{},
				this.createList(),
				this.renderBottomToolbar(),
			);
		}

		createList()
		{
			return new SimpleList({
				testId: 'BX_WORKFLOW_SIMPLE_LIST',
				layout: this.layout,
				isDynamicMode: false,
				allItemsLoaded: true,
				showFloatingButton: false,
				itemType: 'workflow',
				items: this.addEmptyItem(this.prepareItems(this.items)),
				itemFactory: WorkflowSimpleListItemsFactory,
				itemDetailOpenHandler: this.handleWorkflowDetailOpen.bind(this),
				isRefreshing: this.state.isRefreshing,
				reloadListHandler: () => {
					this.setState({ isRefreshing: true }, () => {
						this.setState({ isRefreshing: false });
					});
				},
				ref: (ref) => {
					this.listRef = ref;
				},
			});
		}

		prepareItems(items)
		{
			const preparedItems = clone(items);
			const selectedTasks = this.selectedTasks;

			for (const item of preparedItems)
			{
				item.uid = this.uid;
				item.viewMode = this.state.viewMode;
				item.selected = false;
				if (!isEmpty(item.data.tasks))
				{
					const taskId = parseInt(item.data.tasks[0].id, 10);

					item.selected = selectedTasks.has(taskId);
				}
			}

			return preparedItems;
		}

		addEmptyItem(items)
		{
			if (items.length > 0)
			{
				items.push({
					itemType: ListItemType.EMPTY_SPACE,
					type: ListItemType.EMPTY_SPACE,
					key: `${ListItemType.EMPTY_SPACE}_bottom`,
					height: 150,
				});
			}

			return items;
		}

		renderBottomToolbar()
		{
			if (this.state.viewMode === ViewMode.MULTISELECT && Type.isFunction(this.props.bottomPanelContent))
			{
				const selectedTasks = this.selectedTasks;

				return new BottomPanel({
					items: [...selectedTasks.values()],
					renderContent: this.props.bottomPanelContent,
				});
			}

			return null;
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
									uid: this.uid,
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
						uid: this.uid,
						taskId: task.id,
						workflowId: item.id,
						title: item.typeName,
						readOnlyTimeline: BX.prop.getBoolean(this.props, 'readOnlyTimeline', false),
						showNotifications: BX.prop.getBoolean(this.props, 'showNotifications', true),
					},
				);
			});
		}

		hideItem(id)
		{
			if (BX.prop.getBoolean(this.props, 'canRemoveItems', false) === true)
			{
				const index = this.items.findIndex((item) => item.id === id);
				if (index !== -1)
				{
					this.items.splice(index, 1);
				}
			}

			this.listRef.deleteRow(id);
		}

		isWorkflowFirstTask(workflowId, taskId)
		{
			const item = this.listRef.getItem(workflowId);

			return Boolean(item && item.data.tasks && item.data.tasks[0]?.id === taskId);
		}

		setRegularViewMode()
		{
			this.setState({ selectedTasks: null, viewMode: ViewMode.REGULAR });
		}

		setMultipleViewMode(selectedTasks)
		{
			this.setState({ selectedTasks, viewMode: ViewMode.MULTISELECT });
		}
	}

	class WorkflowSimpleListItemsFactory extends ListItemsFactory
	{
		static create(type, data)
		{
			if (type === 'workflow')
			{
				return new WorkflowSimpleListItem(data);
			}

			return super.create(type, data);
		}
	}

	module.exports = { WorkflowSimpleList };
});
