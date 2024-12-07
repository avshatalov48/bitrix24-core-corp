/**
 * @module bizproc/task/tasks-performer/informers/task-list-informer
 */
jn.define('bizproc/task/tasks-performer/informers/task-list-informer', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { EventEmitter } = require('event-emitter');
	const { Loc } = require('loc');
	const { clone } = require('utils/object');
	const { Type } = require('type');
	const { showToast, Position } = require('toast');

	const { PureComponent } = require('layout/pure-component');
	const { WorkflowSimpleList } = require('bizproc/workflow/list/simple-list');
	const { AcceptButton, Button } = require('bizproc/task/buttons');
	const { TaskErrorCode } = require('bizproc/task/task-constants');

	class TaskListInformer extends PureComponent
	{
		/**
		 * @param {{}} props
		 * @param {[]} props.tasks
		 * @param {string} props.title
		 * @param {Function} props.onTasksCompleted
		 * @param {Function} props.onTasksDelegated
		 * @param {Function} props.onTaskNotFoundError
		 * @param {Function} props.generateExitButton
		 * @param {{}} layout
		 * @returns {Promise}
		 */
		static open(props = {}, layout = PageManager)
		{
			return new Promise((resolve, reject) => {
				layout.openWidget(
					'layout',
					{
						modal: true,
						titleParams: {
							text: props.title || '',
							type: 'dialog',
						},
						backgroundColor: AppTheme.colors.bgContentPrimary,
						backdrop: {
							onlyMediumPosition: false,
							mediumPositionPercent: 93,
							navigationBarColor: AppTheme.colors.bgSecondary,
							swipeAllowed: true,
							swipeContentAllowed: true,
							horizontalSwipeAllowed: false,
						},
						onReady: (readyLayout) => {
							readyLayout.showComponent(new TaskListInformer({
								layout: readyLayout,
								tasks: props.tasks,
								onClose: resolve,
								onTasksCompleted: props.onTasksCompleted,
								onTasksDelegated: props.onTasksDelegated,
								onTaskNotFoundError: props.onTaskNotFoundError,
								generateExitButton: props.generateExitButton,
							}));
						},
					},
					layout,
				).then(() => {}).catch(reject);
			});
		}

		/**
		 * @param props
		 * @param {{}} props.layout
		 * @param {[]} props.tasks
		 * @param {Function} props.onClose
		 * @param {Function} props.onTasksCompleted
		 * @param {Function} props.onTasksDelegated
		 * @param {Function} props.onTaskNotFoundError
		 * @param {Function} props.generateExitButton
		 */
		constructor(props)
		{
			super(props);

			this.uid = 'task-list-informer';
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.state = {
				tasks: {},
			};
			props.tasks.forEach((task) => {
				this.state.tasks[task.id] = task;
			});

			this.selectedTasks = {};
			this.isClosing = false;
			this.result = {
				applyToAllTasks: null,
				oneByOneTasks: null,
				cancel: false,
			};

			this.onViewHidden = () => {
				if (this.isClosing === false)
				{
					this.result = {
						oneByOneTasks: Object.values(this.state.tasks),
					};
				}

				this.onClose(this.result);
			};

			this.exitButton = null;
			if (Type.isFunction(props.generateExitButton))
			{
				this.exitButton = props.generateExitButton(() => {
					this.isClosing = true;
					this.result = { cancel: true };
					this.layout.close();
				});
			}

			this.onTaskCompleted = this.onTaskCompleted.bind(this);
			this.onTaskTouch = this.onTaskTouch.bind(this);
			this.onTasksDelegated = this.onTasksDelegated.bind(this);
			this.onTaskLoadFailed = this.onTaskLoadFailed.bind(this);
			this.onTaskCompleteFailed = this.onTaskCompleteFailed.bind(this);
			this.onApplyToSelectedButtonClick = this.onApplyToSelectedButtonClick.bind(this);
			this.onDoOneByOneButtonClick = this.onDoOneByOneButtonClick.bind(this);
			this.renderBottomPanelContent = this.renderBottomPanelContent.bind(this);
		}

		get layout()
		{
			return this.props.layout;
		}

		get onClose()
		{
			return BX.prop.getFunction(this.props, 'onClose', () => {});
		}

		componentDidMount()
		{
			if (this.exitButton)
			{
				this.layout.setRightButtons([this.exitButton]);
			}

			this.layout.on('onViewHidden', this.onViewHidden);
			this.customEventEmitter
				.on('Task:onTouch', this.onTaskTouch)
				.on('TaskDetails:onLoadFailed', this.onTaskLoadFailed)
				.on('TaskDetails:onTaskDelegated', this.onTasksDelegated)
				.on('TaskDetails:OnTaskCompleted', this.onTaskCompleted)
				.on('TaskDetails:OnTaskCompleteFailed', this.onTaskCompleteFailed)
			;
		}

		componentWillUnmount()
		{
			this.layout.off('onViewHidden', this.onViewHidden);
			this.customEventEmitter
				.off('Task:onTouch', this.onTaskTouch)
				.off('TaskDetails:onLoadFailed', this.onTaskLoadFailed)
				.off('TaskDetails:onTaskDelegated', this.onTasksDelegated)
				.off('TaskDetails:OnTaskCompleted', this.onTaskCompleted)
				.off('TaskDetails:OnTaskCompleteFailed', this.onTaskCompleteFailed)
			;
		}

		render()
		{
			return new WorkflowSimpleList({
				uid: this.uid,
				showNotifications: false,
				canRemoveItems: true,
				layout: this.layout,
				items: this.listItems,
				readOnlyTimeline: true,
				bottomPanelContent: this.renderBottomPanelContent,
			});
		}

		get listItems()
		{
			const listItems = [];

			Object.values(this.state.tasks).forEach((task) => {
				const item = clone(task.item);
				item.data.readOnlyTimeline = true;
				item.selected = true;
				listItems.push(item);
			});

			return listItems;
		}

		onTaskTouch({ task, isInline })
		{
			if (isInline)
			{
				this.onTaskCompleted({ task });
			}
		}

		onTaskCompleted({ task })
		{
			if (Type.isFunction(this.props.onTasksCompleted))
			{
				this.props.onTasksCompleted([task]);
			}

			this.startTaskDeleting(task.id);
		}

		onTasksDelegated({ task })
		{
			if (Type.isFunction(this.props.onTasksDelegated))
			{
				this.props.onTasksDelegated([task]);
			}

			this.startTaskDeleting(task.id);
		}

		onTaskLoadFailed({ taskId, errors })
		{
			const firstError = Array.isArray(errors) && errors.length > 0 ? errors[0] : {};
			if (!TaskErrorCode.isTaskNotFoundErrorCode(firstError.code))
			{
				return;
			}

			if (Type.isFunction(this.props.onTaskNotFoundError))
			{
				this.props.onTaskNotFoundError([{ id: taskId }]);
			}

			this.startTaskDeleting(taskId);
		}

		onTaskCompleteFailed({ errors, task })
		{
			const firstError = Array.isArray(errors) && errors.length > 0 ? errors[0] : {};
			if (!TaskErrorCode.isTaskNotFoundErrorCode(firstError.code))
			{
				return;
			}

			showToast({
				message: firstError.message,
				position: Position.TOP,
			});

			if (Type.isFunction(this.props.onTaskNotFoundError))
			{
				this.props.onTaskNotFoundError([{ id: task.id }]);
			}

			this.startTaskDeleting(task.id);
		}

		renderBottomPanelContent(selectedTasks)
		{
			this.selectedTasks = {};
			selectedTasks.forEach((task) => {
				this.selectedTasks[task.id] = task;
			});

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
						marginTop: 18,
					},
				},
				View(
					{ style: { marginBottom: 20 } },
					new AcceptButton({
						text: Loc.getMessage('BPMOBILE_TASK_TASKS_PERFORMER_INFORMERS_APPLY_TO_SELECTED'),
						onClick: this.onApplyToSelectedButtonClick,
						testId: 'MBPTasksPerformerInformersApplyToSelectedButton',
					}),
				),
				View(
					{},
					new Button({
						text: Loc.getMessage('BPMOBILE_TASK_TASKS_PERFORMER_INFORMERS_DO_ONE_BY_ONE'),
						style: { borderColor: AppTheme.colors.base3, textColor: AppTheme.colors.base2 },
						onClick: this.onDoOneByOneButtonClick,
						testId: 'MBPTasksPerformerInformersDoOneByOneButton',
					}),
				),
			);
		}

		onApplyToSelectedButtonClick()
		{
			const applyToAllTasks = [];
			const oneByOneTasks = [];

			Object.entries(this.state.tasks).forEach(([taskId, task]) => {
				if (Type.isUndefined(this.selectedTasks[taskId]))
				{
					oneByOneTasks.push(task);
				}
				else
				{
					applyToAllTasks.push(task);
				}
			});

			this.isClosing = true;
			this.result = {
				applyToAllTasks,
				oneByOneTasks,
			};
			this.layout.close();
		}

		onDoOneByOneButtonClick()
		{
			this.isClosing = true;
			this.result = {
				applyToAllTasks: null,
				oneByOneTasks: Object.values(this.state.tasks),
			};
			this.layout.close();
		}

		startTaskDeleting(taskId)
		{
			delete this.state.tasks[taskId];

			if (Object.keys(this.state.tasks).length <= 0)
			{
				setTimeout(
					() => {
						if (this.layout)
						{
							this.layout.close();
						}
					},
					100,
				);
			}
		}
	}

	module.exports = { TaskListInformer };
});
