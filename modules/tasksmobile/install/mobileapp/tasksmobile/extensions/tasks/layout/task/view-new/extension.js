/**
 * @module tasks/layout/task/view-new
 */
jn.define('tasks/layout/task/view-new', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { Color } = require('tokens');
	const { Loc } = require('tasks/loc');
	const { isEqual } = require('utils/object');
	const { Icon } = require('assets/icons');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { getDiskFolderId } = require('tasks/disk');
	const {
		PullCommand,
		TaskMark,
		TaskPriority,
		TaskField: Field,
		TaskActionAccess,
		ViewMode,
		WorkModeByViewMode,
	} = require('tasks/enum');
	const { ParentTask } = require('tasks/layout/task/parent-task');
	const { LikesPanel } = require('tasks/layout/task/view-new/ui/likes-panel');
	const { ActionButtons } = require('tasks/layout/task/view-new/ui/action-buttons');
	const { StatusBadge } = require('tasks/layout/task/view-new/ui/status-badge');
	const { CommentsButton } = require('tasks/layout/task/view-new/ui/comments-button');
	const { TaskEditForm } = require('tasks/layout/task/view-new/ui/task-edit-form');
	const { AccessToast } = require('tasks/layout/task/view-new/services/access-toast');
	const { LayoutButtons } = require('tasks/layout/task/view-new/services/layout-buttons');
	const { StickyTitle } = require('tasks/layout/task/view-new/services/sticky-title');
	const { PullListener } = require('tasks/layout/task/view-new/services/pull-listener');
	const { CommentsOpener } = require('tasks/layout/task/view-new/services/comments-opener');
	const { ActionMenu, TopMenuEngine } = require('tasks/layout/action-menu');
	const { ActionId, ActionMeta } = require('tasks/layout/action-menu/actions');
	const { executeIfOnline } = require('tasks/layout/online');
	const { isOnline } = require('device/connection');
	const { showOfflineToast, showToast } = require('toast');
	const { ChecklistController } = require('tasks/checklist');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { Type } = require('type');
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { makeLibraryImagePath } = require('asset-manager');

	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const { batchActions } = require('statemanager/redux/batched-actions');
	const { usersUpserted, usersAddedFromEntitySelector } = require('statemanager/redux/slices/users');
	const {
		updateUploadingFiles,
		update,
		delegate,
		tasksUpserted,
		taskRemoved,
		selectActions,
		selectIsCreating,
		selectByTaskIdOrGuid,
		selectHasChecklist,
		updateChecklist,
		tasksRead,
		setRelatedTasks,
		selectSubTasksIdsByTaskId,
		updateSubTasks,
		updateRelatedTasks,
		selectDatePlan,
		setAttachedFiles,
		updateDeadline,
	} = require('tasks/statemanager/redux/slices/tasks');
	const { groupsUpserted, groupsAddedFromEntitySelector, selectGroupById } = require(
		'tasks/statemanager/redux/slices/groups',
	);
	const { fetch, setFromServer } = require('tasks/statemanager/redux/slices/tasks-results');
	const { observeCreationError } = require('tasks/statemanager/redux/slices/tasks/observers/creation-error-observer');
	const { AnalyticsEvent } = require('analytics');

	const { setKanbanSettings } = require('tasks/statemanager/redux/slices/kanban-settings/action');
	const { taskStageUpserted } = require('tasks/statemanager/redux/slices/tasks-stages');
	const { updateTaskStage } = require('tasks/statemanager/redux/slices/tasks-stages/thunk');
	const { selectTaskStageByTaskIdOrGuid } = require('tasks/statemanager/redux/slices/tasks-stages');
	const { getUniqId, selectStages } = require('tasks/statemanager/redux/slices/kanban-settings');

	/**
	 * @class TaskView
	 */
	class TaskView extends LayoutComponent
	{
		static open(props)
		{
			const { layoutWidget: parentWidget = PageManager } = props;
			const taskViewComponent = new TaskView(props);

			parentWidget
				.openWidget('layout', {
					titleParams: {
						text: Loc.getMessage('M_TASK_DETAILS_WIDGET_TITLE_MSGVER_1'),
						type: 'entity',
					},
				})
				.then((newLayout) => {
					taskViewComponent.init({
						...props,
						layout: newLayout,
					});

					newLayout.showComponent(taskViewComponent);
				})
				.catch(console.error)
			;
		}

		// region init

		constructor(props)
		{
			super(props);

			this.layout = null;

			/** @type {Form|null|undefined} */
			this.formRef = null;
			this.scrollViewRef = null;

			if (props.layout)
			{
				this.init(props);
			}

			this.analyticsLabel = {
				tool: 'tasks',
				category: 'task_operations',
				type: 'task',
				event: 'task_view',
				...this.props.analyticsLabel,
			};

			this.bindScrollViewRef = this.bindScrollViewRef.bind(this);
			this.bindFormRef = this.bindFormRef.bind(this);
			this.scrollableProvider = this.scrollableProvider.bind(this);
			this.onChangeField = this.onChangeField.bind(this);
			this.onChangeUserField = this.onChangeUserField.bind(this);
			this.onChangeProjectField = this.onChangeProjectField.bind(this);
			this.onChangeSubTaskField = this.onChangeSubTaskField.bind(this);
			this.onChangeRelatedTaskField = this.onChangeRelatedTaskField.bind(this);
			this.onBlurField = this.onBlurField.bind(this);
			this.openComments = this.openComments.bind(this);
			this.renderLikes = this.renderLikes.bind(this);
			this.renderBeforeCompactFields = this.renderBeforeCompactFields.bind(this);
			this.renderStatus = this.renderStatus.bind(this);
			this.onFieldContentClick = this.onFieldContentClick.bind(this);
			this.onCreationErrorChange = this.onCreationErrorChange.bind(this);
		}

		init(props)
		{
			this.layout = props.layout;
			this.layout.enableNavigationBarBorder(false);
			this.layout.on('titleClick', () => this.scrollViewRef?.scrollToBegin(true));

			this.checklistController = new ChecklistController({
				taskId: this.#taskId,
				userId: this.props.userId,
				groupId: this.#task?.groupId || 0,
				inLayout: true,
				hideCompleted: false,
				parentWidget: this.layout,
				onChange: () => {
					const { completed, uncompleted, checklistDetails } = this.checklistController.getReduxData();

					dispatch(updateChecklist({
						checklistDetails,
						taskId: this.#taskId,
						checklist: { completed, uncompleted },
					}));
				},
			});

			this.layoutButtons = new LayoutButtons({
				taskId: this.#taskId,
				layout: this.layout,
				onTogglePriority: this.togglePriority.bind(this),
				onShowContextMenu: this.showContextMenu.bind(this),
			});

			this.stickyTitle = new StickyTitle({
				taskId: this.#taskId,
				layout: this.layout,
				defaultTitle: Loc.getMessage('M_TASK_DETAILS_WIDGET_TITLE_MSGVER_1'),
			});

			this.accessToast = new AccessToast(this.layout);

			this.pullListener = new PullListener({
				taskId: this.#taskId,
				callbacks: {
					[PullCommand.TASK_UPDATE]: () => this.#getTaskData(),
					[PullCommand.COMMENT_ADD]: () => this.#getTaskData(),
					[PullCommand.TASK_TIMER_START]: () => this.#getTaskData(),
					[PullCommand.TASK_TIMER_STOP]: () => this.#getTaskData(),
					[PullCommand.USER_OPTION_CHANGED]: () => this.#getTaskData(),
					[PullCommand.TASK_RESULT_CREATE]: () => this.#getTaskResultData(),
					[PullCommand.TASK_RESULT_UPDATE]: () => this.#getTaskResultData(),
					[PullCommand.TASK_RESULT_DELETE]: () => this.#getTaskResultData(),
				},
			});

			this.view = (!this.props.view || this.props.view === ViewMode.LIST) ? ViewMode.KANBAN : this.props.view;
			this.kanbanOwnerId = this.view === ViewMode.PLANNER ? this.props.kanbanOwnerId : this.props.userId;
			this.initialStageId = selectTaskStageByTaskIdOrGuid(
				store.getState(),
				this.#taskId,
				this.#task?.guid,
				this.view,
				this.kanbanOwnerId,
			)?.stageId;
			this.initialStages = selectStages(
				store.getState(),
				getUniqId(
					this.view,
					this.#task?.groupId,
					this.kanbanOwnerId,
				),
			);

			this.commentsOpener = new CommentsOpener(this.analyticsLabel);

			if (this.#task)
			{
				this.#initFromRedux();
			}
			else
			{
				this.#initFromBackend();
			}

			this.#observeTaskState();
			this.unsubscribeCreationErrorObserver = observeCreationError(store, this.onCreationErrorChange);
		}

		componentDidMount()
		{
			this.pullListener.subscribe();

			BX.addCustomEvent('tasks.task.comments:onCommentsRead', (eventData) => {
				if (Number(eventData.taskId) === this.#taskId)
				{
					dispatch(
						tasksRead({ taskIds: [this.#taskId] }),
					);
				}
			});
			BX.addCustomEvent('task.view.onCommentAction', (eventData) => this.#onCommentAction(eventData));
		}

		componentWillUnmount()
		{
			this.pullListener.unsubscribe();
			this.layoutButtons.unsubscribe();
			this.stickyTitle.unsubscribe();
			this.unsubscribeTaskStateObserver?.();
			this.unsubscribeCreationErrorObserver?.();
		}

		#initFromRedux()
		{
			this.layout.setTitle({ useProgress: true }, true);

			if (!selectIsCreating(this.#task))
			{
				Promise
					.allSettled([
						this.#getTaskData({
							withResultData: true,
							withChecklistData: true,
						}),
						this.#getDiskFolderId(),
					])
					.then((results) => setTimeout(() => this.#onDataFetchSuccess(results), 100))
					.catch(this.#onDataFetchFailed)
				;
			}

			this.state = {
				loading: false,
				isForbidden: false,
				checklistLoading: selectHasChecklist(this.#task),
			};

			if (this.props.shouldOpenComments)
			{
				this.openComments();
			}
		}

		#initFromBackend()
		{
			this.state = {
				loading: true,
				isForbidden: false,
			};
			this.layout.setTitle({ useProgress: true }, true);

			Promise
				.allSettled([
					this.#getTaskData({
						withResultData: true,
						withChecklistData: true,
					}),
					this.#getDiskFolderId(),
				])
				.then((results) => this.#onDataFetchSuccess(results, this.props.shouldOpenComments))
				.catch(this.#onDataFetchFailed)
			;
		}

		#onDataFetchFailed(error)
		{
			console.error(error);

			new AnalyticsEvent(this.analyticsLabel).setStatus('error').send();
		}

		#onDataFetchSuccess(results, shouldOpenComments = false)
		{
			const isForbidden = !results[0].value;

			if (isForbidden)
			{
				new AnalyticsEvent(this.analyticsLabel).setStatus('error').send();
				this.#setForbiddenState();
			}
			else
			{
				new AnalyticsEvent(this.analyticsLabel).setStatus('success').send();
				this.layout.setTitle({ useProgress: false }, true);

				if (this.state.loading || this.state.checklistLoading)
				{
					this.setState({ loading: false, checklistLoading: false }, () => shouldOpenComments && this.openComments());
				}
			}
		}

		#setForbiddenState()
		{
			this.pullListener.unsubscribe();
			this.unsubscribeTaskStateObserver?.();

			this.setState({
				loading: false,
				isForbidden: true,
			});
			this.layout.setTitle({ useProgress: false }, true);
			this.layout.setRightButtons([]);

			this.commentsOpener.closeCommentsWidget(this.#taskId);
		}

		/**
		 * @param {object} [options = {}]
		 * @param {boolean} [options.withResultData = false]
		 * @param {boolean} [options.withChecklistData = false]
		 * @return {Promise}
		 */
		#getTaskData(options = {})
		{
			const { withResultData = false, withChecklistData = false } = options;

			return new Promise((resolve) => {
				new RunActionExecutor('tasksmobile.Task.get', {
					taskId: this.#taskId,
					params: {
						WITH_RESULT_DATA: (withResultData ? 'Y' : 'N'),
						WITH_CHECKLIST_DATA: (withChecklistData ? 'Y' : 'N'),
						WORK_MODE: WorkModeByViewMode[this.view],
						KANBAN_OWNER_ID: this.kanbanOwnerId,
					},
				})
					.setHandler((response) => {
						if (Type.isArray(response.data) && !Type.isArrayFilled(response.data))
						{
							dispatch(
								taskRemoved({ taskId: this.#taskId }),
							);
							this.#setForbiddenState();

							return resolve(false);
						}

						this.#updateReduxStore(response.data);

						if (withChecklistData)
						{
							this.checklistController.setChecklistTree(response.data.checklist.tree);
						}
						this.checklistController.setGroupId(response.data.groupId);

						this.#onAfterGetTaskData(response);

						return resolve(true);
					})
					.call(false)
				;
			});
		}

		#onAfterGetTaskData(response)
		{
			const stages = response.data?.kanban?.stages;
			const stageId = response.data?.taskstage?.[0]?.stageId;

			this.#animateStageField(stageId, stages);
		}

		#getDiskFolderId()
		{
			return getDiskFolderId().then(({ diskFolderId }) => {
				this.checklistController.setDiskConfig({ folderId: diskFolderId });
			});
		}

		#updateReduxStore(responseData)
		{
			const {
				tasks = [],
				users = [],
				groups = [],
				relatedtaskids = [],
				taskstage = [],
				kanban = null,
				results,
			} = responseData || {};

			const actions = [
				tasks.length > 0 && tasksUpserted(tasks),
				users.length > 0 && usersUpserted(users),
				groups.length > 0 && groupsUpserted(groups),
				relatedtaskids.length > 0 && setRelatedTasks({ taskId: this.#taskId, relatedTasks: relatedtaskids }),
				Type.isArray(results) && setFromServer({ taskId: this.#taskId, results }),
				taskstage.length > 0 && taskStageUpserted(taskstage),
				kanban && setKanbanSettings(this.#prepareKanbanData(kanban, this.getCurrentTaskProjectId(tasks))),
			].filter(Boolean);

			if (actions.length > 0)
			{
				dispatch(batchActions(actions));
			}
		}

		/**
		 * @param {array} tasks
		 * @return {number}
		 */
		getCurrentTaskProjectId(tasks)
		{
			return tasks.find((task) => task.id === Number(this.#taskId))?.groupId || null;
		}

		/**
		 * @param {object} data
		 * @param {number} projectId
		 * @return {object}
		 */
		#prepareKanbanData(data, projectId)
		{
			return {
				view: this.view,
				projectId,
				userId: this.kanbanOwnerId,
				stages: data?.stages,
				canEdit: data?.canedit,
				canMoveStage: data?.canmovestage,
			};
		}

		#getTaskResultData()
		{
			dispatch(
				fetch({
					taskId: this.#taskId,
				}),
			);
		}

		#observeTaskState()
		{
			let prevTask = this.#task;

			this.unsubscribeTaskStateObserver = store.subscribe(() => {
				const nextTask = this.#task;

				if (prevTask && selectIsCreating(prevTask) && nextTask && !selectIsCreating(nextTask))
				{
					this.layout.setTitle({ useProgress: false }, true);
					this.formRef?.flushOriginalValues();
					this.checklistController.setTaskId(this.#taskId);
				}

				if (prevTask && !nextTask)
				{
					this.#setForbiddenState();
				}

				prevTask = nextTask;
			});
		}

		onCreationErrorChange({ added })
		{
			if (added.some((task) => task.id === this.#task.id))
			{
				this.close();
			}
		}

		#onCommentAction(eventData)
		{
			const { taskId, userId, action } = eventData;

			if (
				Number(taskId) !== Number(this.#taskId)
				|| Number(userId) !== Number(this.props.userId)
			)
			{
				return;
			}

			switch (action)
			{
				case 'deadlineChange':
					this.formRef?.getField(Field.DEADLINE)?.getContentClickHandler()?.();
					break;

				case 'taskApprove':
					ActionMeta[ActionId.APPROVE]?.handleAction({ taskId });
					break;

				case 'taskDisapprove':
					ActionMeta[ActionId.DISAPPROVE]?.handleAction({ taskId });
					break;

				case 'taskComplete':
					ActionMeta[ActionId.COMPLETE]?.handleAction({ taskId });
					break;

				default:
					break;
			}
		}

		// endregion

		/**
		 * @return {TaskReduxModel}
		 */
		get #task()
		{
			return selectByTaskIdOrGuid(store.getState(), this.props.taskId);
		}

		/**
		 * @return {number}
		 */
		get #taskId()
		{
			return this.#task?.id || this.props.taskId;
		}

		get #actions()
		{
			return selectActions(this.#task);
		}

		shouldBlockUI()
		{
			return this.state.loading || selectIsCreating(this.#task);
		}

		get subTasksIds()
		{
			return selectSubTasksIdsByTaskId(store.getState(), this.#taskId);
		}

		showContextMenu()
		{
			if (this.shouldBlockUI())
			{
				return;
			}

			const actions = [
				ActionMenu.action.pin,
				ActionMenu.action.unpin,
				ActionMenu.action.mute,
				ActionMenu.action.unmute,
				ActionMenu.action.follow,
				ActionMenu.action.unfollow,
				// ActionMenu.action.start,
				// ActionMenu.action.startTimer,
				// ActionMenu.action.pause,
				// ActionMenu.action.pauseTimer,
				// ActionMenu.action.defer,
				ActionMenu.action.delegate,
				// ActionMenu.action.renew,
				// ActionMenu.action.complete,
				// ActionMenu.action.approve,
				// ActionMenu.action.disapprove,
				ActionMenu.action.extraSettings,
				ActionMenu.action.copyId,
				ActionMenu.action.copy,
				ActionMenu.action.share,
				ActionMenu.action.remove,
			];

			(new ActionMenu({
				actions,
				layoutWidget: this.layout,
				task: this.#task,
				analyticsLabel: {
					c_section: this.props.analyticsLabel?.c_section,
					c_sub_section: 'task_card',
				},
				engine: new TopMenuEngine(),
				allowSuccessToasts: true,
			})).show();
		}

		close()
		{
			return this.layout.back();
		}

		togglePriority()
		{
			if (this.shouldBlockUI())
			{
				return;
			}

			if (!this.#actions.update)
			{
				this.accessToast.showByAccess(TaskActionAccess.UPDATE);

				return;
			}

			executeIfOnline(
				() => {
					Haptics.impactLight();

					this.updateTask(
						Field.PRIORITY,
						(this.#task.priority === TaskPriority.HIGH ? TaskPriority.NORMAL : TaskPriority.HIGH),
					);
				},
				this.layout,
			);
		}

		bindScrollViewRef(ref)
		{
			this.scrollViewRef = ref;
		}

		bindFormRef(ref)
		{
			this.formRef = ref;
		}

		scrollableProvider()
		{
			return this.scrollViewRef;
		}

		onChangeField(data)
		{
			switch (data.fieldId)
			{
				case Field.TITLE:
				case Field.DEADLINE:
				case Field.PROJECT:
				case Field.CHECKLIST:
					break;

				case Field.FILES:
				{
					/**
					 - uploaded files from disk - need to be uploaded through the  "update" action
					 - files that exist in this.task.files and are deleted - they need to be deleted via "update" action
					 - new uploaded files that are not from disk  - do nothing, just store them in uploadedFiles
					 (background process will attach it)
					 */

					const newObjectIds = new Set(data.value.map((file) => file.objectId).filter(Boolean));
					const removedFiles = this.#task.files.filter(({ objectId }) => !newObjectIds.has(objectId));
					const hasRemovedFiles = removedFiles.length > 0;
					const hasAddedFilesFromDisk = data.value.some((file) => file.isDiskFile);
					const newAttachedFiles = data.value.filter((file) => newObjectIds.has(file.objectId) && !file.hasError);

					if (hasRemovedFiles || hasAddedFilesFromDisk)
					{
						const reduxFields = this.prepareReduxFields(data.fieldId, data.value, data.extendedValue);
						const serverFields = this.prepareServerFields(data.fieldId, data.value, data.extendedValue);

						dispatch(
							update({
								taskId: this.#taskId,
								reduxFields,
								serverFields,
							}),
						);
					}
					else if (!isEqual(this.#task.files, newAttachedFiles))
					{
						dispatch(
							setAttachedFiles({
								taskId: this.#taskId,
								files: newAttachedFiles,
							}),
						);
					}

					const notAttachedFiles = data.value.filter((file) => {
						if (file.isDiskFile)
						{
							return false;
						}

						if (!Number.isInteger(file.objectId))
						{
							return true;
						}

						return !newObjectIds.has(file.objectId);
					});

					if (!isEqual(this.#task.uploadedFiles, notAttachedFiles))
					{
						dispatch(
							updateUploadingFiles({
								taskId: this.#taskId,
								uploadedFiles: notAttachedFiles,
							}),
						);
					}

					break;
				}

				case Field.USER_FIELDS:
				{
					this.updateTask(
						data.fieldId,
						Object.fromEntries(
							[...data.value].map(([fieldName, value]) => (
								[fieldName, { value, type: this.#task[fieldName].type }]
							)),
						),
						Object.fromEntries(
							[...data.value].map(([fieldName, value]) => (
								[fieldName, { ...this.#task[fieldName], value }]
							)),
						),
					);

					break;
				}

				default:
					this.updateTask(data.fieldId, data.value, data.extendedValue);
			}
		}

		onChangeUserField(_, users = [])
		{
			if (users.length > 0)
			{
				dispatch(usersAddedFromEntitySelector(users));
			}
		}

		onChangeDeadlineField = (deadline) => {
			const group = selectGroupById(store.getState(), this.#task.groupId);
			if (group)
			{
				const { dateStart, dateFinish } = group;

				if (!this.isDateInGroupPlanRange({ dateStart, dateFinish }, deadline))
				{
					showToast({
						message: Loc.getMessage('M_TASKS_DEADLINE_IS_OUT_OF_PROJECT_RANGE'),
						icon: Icon.CLOCK,
					});

					return;
				}
			}

			this.updateTask(Field.DEADLINE, deadline, this.#task.deadline);
		};

		onChangeProjectField(_, groups = [])
		{
			const groupId = groups[0]?.id || 0;
			const selectedGroup = groups[0] || null;
			if (groups.length > 0)
			{
				const datePlan = selectedGroup?.customData?.datePlan;

				const state = store.getState();
				const deadline = this.#task.deadline;

				if (!this.isDateInGroupPlanRange(datePlan, deadline))
				{
					showToast({
						message: Loc.getMessage('M_TASKS_DEADLINE_IS_OUT_OF_PROJECT_RANGE'),
						icon: Icon.CLOCK,
					});

					return;
				}

				const { startDatePlan, endDatePlan } = selectDatePlan(state, this.#taskId);

				if (
					!this.isDateInGroupPlanRange(datePlan, startDatePlan)
					&& !this.isDateInGroupPlanRange(datePlan, endDatePlan)
				)
				{
					showToast({
						message: Loc.getMessage('M_TASKS_PLANNING_START_AND_END_DATE_IS_OUT_OF_PROJECT_RANGE'),
						icon: Icon.CLOCK,
					});

					return;
				}

				if (!this.isDateInGroupPlanRange(datePlan, startDatePlan))
				{
					showToast({
						message: Loc.getMessage('M_TASKS_PLANNING_START_DATE_IS_OUT_OF_PROJECT_RANGE'),
						icon: Icon.CLOCK,
					});

					return;
				}

				if (!this.isDateInGroupPlanRange(datePlan, endDatePlan))
				{
					showToast({
						message: Loc.getMessage('M_TASKS_PLANNING_FINISH_DATE_OUT_OF_PROJECT_RANGE'),
						icon: Icon.CLOCK,
					});

					return;
				}

				dispatch(groupsAddedFromEntitySelector(groups));
			}
			this.updateTask(Field.PROJECT, groupId, selectedGroup);

			this.checklistController.setGroupId(groupId);
		}

		isDateInGroupPlanRange(groupPlan, date)
		{
			if (!date || !Type.isNumber(date))
			{
				return true;
			}

			const { dateStart = null, dateFinish = null } = groupPlan;
			let dateAfterFinish = (new Date(dateFinish * 1000));
			dateAfterFinish.setDate(dateAfterFinish.getDate() + 1);
			dateAfterFinish.setHours(0, 0, 0, 0);
			dateAfterFinish = Math.floor(dateAfterFinish / 1000);

			return (
				(Type.isNil(dateStart) || date >= dateStart)
				&& (Type.isNil(dateFinish) || date < dateAfterFinish)
			);
		}

		onChangeSubTaskField(subTaskIds, tasks = [])
		{
			const newSubTasks = subTaskIds.filter((subtask) => !this.subTasksIds.includes(subtask));
			const deletedSubTasks = this.subTasksIds.filter((subtask) => !subTaskIds.includes(subtask));

			dispatch(updateSubTasks({
				parentId: this.#taskId,
				newSubTasks,
				deletedSubTasks,
				analyticsLabel: newSubTasks.length > 0 && {
					tool: 'tasks',
					category: 'task_operations',
					event: 'subtask_add',
					type: 'task',
					c_section: this.props.analyticsLabel?.c_section,
					c_sub_section: 'task_card',
					c_element: 'create_button',
				},
			}));
		}

		onChangeRelatedTaskField(relatedTaskIds, tasks = [])
		{
			const newRelatedTasks = relatedTaskIds.filter(
				(relatedTask) => !this.#task.relatedTasks.includes(relatedTask),
			);

			const deletedRelatedTasks = this.#task.relatedTasks.filter(
				(relatedTask) => !relatedTaskIds.includes(relatedTask),
			);

			dispatch(updateRelatedTasks({
				taskId: this.#taskId,
				newRelatedTasks,
				deletedRelatedTasks,
				relatedTasks: relatedTaskIds,
			}));
		}

		onBlurField(data)
		{
			if (data.fieldId !== Field.TITLE)
			{
				return;
			}

			if (data.value?.trim() === '')
			{
				void this.formRef?.resetField(data.fieldId);

				return;
			}

			if (!isOnline())
			{
				showOfflineToast({}, this.layout);
				void this.formRef?.resetField(data.fieldId);

				return;
			}

			this.updateTask(data.fieldId, data.value, data.extendedValue);
		}

		/**
		 * @private
		 * @param {string} code
		 * @return {string}
		 */
		getTestId(code)
		{
			const prefix = 'TaskDetails';

			return `${prefix}_${code}`;
		}

		// region render

		render()
		{
			const loading = this.state.loading;
			const isForbidden = this.state.isForbidden;
			const ready = !loading && !isForbidden;

			return View(
				{},
				loading && this.#renderLoadingState(),
				isForbidden && this.#renderForbiddenState(),
				ready && this.#renderTaskContent(),
				ready && this.#renderCommentsButton(),
			);
		}

		#renderLoadingState()
		{
			return new LoadingScreenComponent({
				backgroundColor: Color.bgNavigation.toHex(),
				testId: this.getTestId('LoadingScreen'),
			});
		}

		#renderForbiddenState()
		{
			return StatusBlock({
				image: Image({
					resizeMode: 'contain',
					style: {
						width: 152,
						height: 140,
					},
					svg: {
						uri: makeLibraryImagePath('access.svg', 'empty-states'),
					},
				}),
				title: Loc.getMessage('M_TASK_DETAILS_NO_TASK_POPUP_TITLE'),
				description: Loc.getMessage('M_TASK_DETAILS_NO_TASK_POPUP_DESCRIPTION'),
			});
		}

		#renderTaskContent()
		{
			return ScrollView(
				{
					ref: this.bindScrollViewRef,
					showsVerticalScrollIndicator: false,
					scrollEventThrottle: 10,
					onScroll: (pos) => {
						this.stickyTitle.onScroll(pos);
					},
					style: {
						flex: 1,
						width: '100%',
						backgroundColorGradient: {
							start: Color.bgNavigation.toHex(),
							middle: Color.bgContentSecondary.toHex(),
							end: Color.bgContentSecondary.toHex(),
							angle: 90,
						},
					},
				},
				View(
					{
						testId: this.getTestId('MainContent'),
						style: {
							paddingBottom: 96,
							backgroundColor: Color.bgContentSecondary.toHex(),
						},
						onClick: () => Keyboard.dismiss(),
					},
					ParentTask({
						parentWidget: this.layout,
						taskId: this.#task?.parentId,
						testId: this.getTestId('ParentTask'),
					}),
					TaskEditForm({
						id: this.props.taskId,
						forwardedRef: this.bindFormRef,
						testId: this.getTestId('Form'),
						parentWidget: this.layout,
						onChange: this.onChangeField,
						onBlur: this.onBlurField,
						scrollableProvider: this.scrollableProvider,
						// renderAfterCompactBar: this.renderLikes,
						onChangeProjectField: this.onChangeProjectField,
						onChangeSubTaskField: this.onChangeSubTaskField,
						onChangeRelatedTaskField: this.onChangeRelatedTaskField,
						onChangeUserField: this.onChangeUserField,
						onChangeDeadlineField: this.onChangeDeadlineField,
						renderBeforeCompactFields: this.renderBeforeCompactFields,
						renderStatus: this.renderStatus,
						onFieldContentClick: this.onFieldContentClick,
						userId: this.props.userId,
						checklistController: this.checklistController,
						checklistLoading: this.state.checklistLoading,
						analyticsLabel: this.props.analyticsLabel,
						view: this.view,
						isStageSelectorInitiallyHidden: this.#isStageSelectorInitiallyHidden(),
						kanbanOwnerId: this.kanbanOwnerId,
					}),
				),
			);
		}

		#isStageSelectorInitiallyHidden()
		{
			return this.view === ViewMode.KANBAN && (
				(
					// backlog task
					(
						this.#task?.groupId !== 0
						&& Number.isInteger(this.initialStageId)
						&& (Array.isArray(this.initialStages) && this.initialStages.length === 0)
					)
					|| this.#task?.groupId === 0
				)
			);
		}

		/**
		 * @param {UIFormBaseField} field
		 * @return {void}
		 */
		onFieldContentClick(field)
		{
			if (this.shouldBlockUI())
			{
				return;
			}

			if (field.isReadOnly() && !field.getCustomContentClickHandler())
			{
				this.accessToast.showByFieldId(field.getId());
			}
		}

		renderBeforeCompactFields(form)
		{
			return ActionButtons({
				taskId: this.#taskId,
				testId: this.getTestId('ActionButtons'),
				layout: this.layout,
				showDivider: form.hasCompactVisibleFields(),
				analyticsLabel: this.props.analyticsLabel,
			});
		}

		renderStatus()
		{
			return StatusBadge({
				taskId: this.#taskId,
				testId: this.getTestId('StatusBadge'),
			});
		}

		renderLikes()
		{
			return LikesPanel({
				taskId: this.#taskId,
				testId: this.getTestId('LikesPanel'),
			});
		}

		#renderCommentsButton()
		{
			return CommentsButton({
				taskId: this.#taskId,
				testId: this.getTestId('CommentsButton'),
				onClick: this.openComments,
			});
		}

		openComments()
		{
			if (!this.shouldBlockUI())
			{
				executeIfOnline(() => this.commentsOpener.openCommentsWidget(this.#taskId), this.layout);
			}
		}

		// endregion

		updateTask(field, value, extendedValue)
		{
			if (field === Field.STAGE)
			{
				dispatch(
					updateTaskStage({
						taskId: this.#taskId,
						stageId: value,
						projectId: this.#task.groupId,
						view: this.view,
						userId: this.props.userId,
					}),
				);
			}
			else if (field === Field.DEADLINE)
			{
				dispatch(
					updateDeadline({
						taskId: this.#taskId,
						deadline: value * 1000,
						userId: this.props.userId,
					}),
				);
			}
			else
			{
				const reduxFields = this.prepareReduxFields(field, value, extendedValue);

				if (!this.isAnyReduxFieldChanged(reduxFields))
				{
					return;
				}

				if (
					field === Field.RESPONSIBLE
					&& !this.#actions.updateResponsible
					&& this.#actions.delegate
				)
				{
					dispatch(
						delegate({
							taskId: this.#taskId,
							userId: reduxFields.responsible,
						}),
					);

					return;
				}

				const serverFields = this.prepareServerFields(field, value, extendedValue);

				dispatch(
					update({
						taskId: this.#taskId,
						reduxFields,
						serverFields,
						withStageData: field === Field.PROJECT ? 'Y' : 'N',
						userId: this.kanbanOwnerId,
					}),
				)
					.then((response) => {
						this.#onAfterFieldUpdate(field, value, response);
					})
					.catch(console.error)
				;
			}
		}

		#onAfterFieldUpdate(field, value, response)
		{
			if (
				field === Field.PROJECT
				&& response?.payload?.data?.isSuccess
			)
			{
				this.#onAfterSaveProjectField(value, response);
			}
		}

		#onAfterSaveProjectField(value, response)
		{
			if (this.view === ViewMode.KANBAN)
			{
				if (value === 0)
				{
					this.formRef?.getField(Field.STAGE)?.hide();
				}
				else
				{
					const data = response?.payload?.data;
					const stages = data?.kanban?.stages;

					this.#animateStageField(data?.stageId, stages);
				}
			}
		}

		#animateStageField(stageId, stages)
		{
			if (!this.formRef || !this.formRef?.getField(Field.STAGE))
			{
				this.initialStageId = stageId;
				this.initialStages = stages;

				return;
			}

			if (Array.isArray(stages) && stages.length > 0)
			{
				const stage = stages.find(({ id, aliasId }) => id === stageId || aliasId === stageId);
				if (stage)
				{
					this.formRef?.getField(Field.STAGE)?.show();
				}
				else
				{
					this.formRef?.getField(Field.STAGE)?.hide();
				}
			}
			else
			{
				this.formRef?.getField(Field.STAGE)?.hide();
			}
		}

		isAnyReduxFieldChanged(reduxFields)
		{
			return Object.keys(reduxFields).some((field) => !isEqual(reduxFields[field], this.#task[field]));
		}

		prepareReduxFields(field, value, extendedValue)
		{
			switch (field)
			{
				case Field.TITLE:
					return { name: value };

				case Field.DESCRIPTION:
					return { description: value };

				case Field.PROJECT:
					return { groupId: value || 0 };

				case Field.FLOW:
					return { flowId: value || 0 };

				case Field.PRIORITY:
					return { priority: value };

				case Field.TIME_ESTIMATE:
					return { timeEstimate: value };

				case Field.MARK:
					return { mark: value };

				case Field.CREATOR:
					return {
						creator: value,
						responsible: Number(env.userId),
					};

				case Field.RESPONSIBLE:
					return { responsible: value };

				case Field.ACCOMPLICES:
					return { accomplices: value };

				case Field.AUDITORS:
					return { auditors: value };

				case Field.FILES:
					return { files: value };

				case Field.CRM:
					return { crm: extendedValue };

				case Field.TAGS:
					return {
						tags: extendedValue.map((tag) => ({
							id: tag.id,
							name: tag.title,
						})),
					};
				case Field.DEADLINE:
					return { deadline: (value || null) };

				case Field.START_DATE_PLAN:
					return { startDatePlan: (value || null) };

				case Field.END_DATE_PLAN:
					return { endDatePlan: (value || null) };

				case Field.ALLOW_CHANGE_DEADLINE:
					return { allowChangeDeadline: value };

				case Field.ALLOW_TIME_TRACKING:
				case `${Field.ALLOW_TIME_TRACKING}Timer`:
					return {
						allowTimeTracking: value.allowTimeTracking,
						timeEstimate: value.timeEstimate,
					};

				case Field.ALLOW_TASK_CONTROL:
					return { allowTaskControl: value };

				case Field.IS_MATCH_WORK_TIME:
					return { isMatchWorkTime: value };

				case Field.IS_RESULT_REQUIRED:
					return { isResultRequired: value };

				case Field.USER_FIELDS:
					return { ...extendedValue };

				default:
					return {};
			}
		}

		prepareServerFields(field, value, extendedValue)
		{
			switch (field)
			{
				case Field.TITLE:
					return { TITLE: value };

				case Field.DESCRIPTION:
					return { DESCRIPTION: value };

				case Field.PROJECT:
					return { GROUP_ID: (value || 0) };

				case Field.FLOW:
					return { FLOW_ID: (value || 0) };

				case Field.PRIORITY:
					return { PRIORITY: value };

				case Field.TIME_ESTIMATE:
					return { TIME_ESTIMATE: value };

				case Field.MARK:
					return { MARK: (value === TaskMark.NONE ? '' : value) };

				case Field.CREATOR:
					return {
						CREATED_BY: value,
						RESPONSIBLE_ID: Number(env.userId),
					};

				case Field.RESPONSIBLE:
					return { RESPONSIBLE_ID: value };

				case Field.ACCOMPLICES:
					return { ACCOMPLICES: (value.length > 0 ? value : '') };

				case Field.AUDITORS:
					return { AUDITORS: (value.length > 0 ? value : '') };

				case Field.FILES:
					return { UF_TASK_WEBDAV_FILES: this.getUploadedFiles(value) };

				case Field.CRM:
					return { CRM: this.prepareCrmData(value, extendedValue) };

				case Field.TAGS:
					return { TAGS: (extendedValue.length > 0 ? extendedValue.map((tag) => tag.title) : '') };

				case Field.DEADLINE:
					return { DEADLINE: (value ? (new Date(value * 1000)).toISOString() : '') };

				case Field.START_DATE_PLAN:
					return { START_DATE_PLAN: (value ? (new Date(value * 1000)).toISOString() : '') };

				case Field.END_DATE_PLAN:
					return { END_DATE_PLAN: (value ? (new Date(value * 1000)).toISOString() : '') };

				case Field.ALLOW_CHANGE_DEADLINE:
					return { ALLOW_CHANGE_DEADLINE: (value ? 'Y' : 'N') };

				case Field.ALLOW_TIME_TRACKING:
				case `${Field.ALLOW_TIME_TRACKING}Timer`:
					return {
						ALLOW_TIME_TRACKING: (value.allowTimeTracking ? 'Y' : 'N'),
						TIME_ESTIMATE: value.timeEstimate,
					};

				case Field.ALLOW_TASK_CONTROL:
					return { TASK_CONTROL: (value ? 'Y' : 'N') };

				case Field.IS_MATCH_WORK_TIME:
					return { MATCH_WORK_TIME: (value ? 'Y' : 'N') };

				case Field.IS_RESULT_REQUIRED:
					return { SE_PARAMETER: [{ CODE: 3, VALUE: (value ? 'Y' : 'N') }] };

				case Field.USER_FIELDS:
					return { USER_FIELDS: value };

				default:
					return {};
			}
		}

		getUploadedFiles(files)
		{
			const uploadedFiles = (
				files
					.filter((file) => Number.isInteger(file.id) || file.isDiskFile)
					.map((file) => file.id)
			);

			if (uploadedFiles.length === 0)
			{
				// hack to send empty array to server
				return '';
			}

			return uploadedFiles;
		}

		prepareCrmData(crmIds, crmData)
		{
			const crm = Object.fromEntries(crmData.map((item) => [
				`${item.type}_${item.id}`, {
					id: item.id,
					title: item.title,
					subtitle: item.subtitle,
					type: item.type,
				},
			]));
			const newCrm = Object.keys(crm);
			const oldCrm = this.#task.crm.map((item) => `${item.type}_${item.id}`);

			const difference = [
				...newCrm.filter((id) => !oldCrm.includes(id)),
				...oldCrm.filter((id) => !newCrm.includes(id)),
			];

			if (difference.length > 0)
			{
				return Object.keys(crm).length > 0 ? crm : '';
			}

			return this.#task.crm;
		}
	}

	module.exports = { TaskView };
});
