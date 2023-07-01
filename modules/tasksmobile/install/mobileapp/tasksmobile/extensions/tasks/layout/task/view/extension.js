jn.define('tasks/layout/task/view', (require, exports, module) => {
	const {Creator} = require('tasks/layout/task/fields/creator');
	const {Responsible} = require('tasks/layout/task/fields/responsible');
	const {Accomplices} = require('tasks/layout/task/fields/accomplices');
	const {Auditors} = require('tasks/layout/task/fields/auditors');
	const {Title} = require('tasks/layout/task/fields/title');
	const {Description} = require('tasks/layout/task/fields/description');
	const {Deadline} = require('tasks/layout/task/fields/deadline');
	const {Status} = require('tasks/layout/task/fields/status');
	const {Project} = require('tasks/layout/task/fields/project');
	const {IsImportant} = require('tasks/layout/task/fields/isImportant');
	const {Tags} = require('tasks/layout/task/fields/tags');
	const {Files} = require('tasks/layout/task/fields/files');
	const {CanChangeDeadline} = require('tasks/layout/task/fields/canChangeDeadline');
	const {IsMatchWorkTime} = require('tasks/layout/task/fields/isMatchWorkTime');
	const {IsTaskControl} = require('tasks/layout/task/fields/isTaskControl');
	const {IsResultRequired} = require('tasks/layout/task/fields/isResultRequired');
	const {TimeTracking} = require('tasks/layout/task/fields/timeTracking');
	const {DatePlan} = require('tasks/layout/task/fields/datePlan');
	const {Mark} = require('tasks/layout/task/fields/mark');
	const {Crm} = require('tasks/layout/task/fields/crm');
	const {ParentTask} = require('tasks/layout/task/fields/parentTask');
	const {RelatedTasks} = require('tasks/layout/task/fields/relatedTasks');
	const {SubTasks} = require('tasks/layout/task/fields/subTasks');
	const {Comments} = require('tasks/layout/task/fields/comments');
	const {TaskResultList} = require('tasks/layout/task/fields/taskResultList');
	const {CheckList} = require('tasks/layout/task/fields/checkList');

	const {ActionMenu} = require('tasks/layout/task/actionMenu');
	const {CheckListTree} = require('tasks/checklist');

	const {CalendarSettings} = require('tasks/task/calendar');
	const {DatesResolver} = require('tasks/task/datesResolver');
	const {EventEmitter} = require('event-emitter');
	const {Alert} = require('alert');
	const {Haptics} = require('haptics');
	const {Loc} = require('loc');
	const {Type} = require('type');

	const fieldHeight = 66;

	class Pull
	{
		/**
		 * @param {TaskView} taskView
		 * @param {Task} task
		 */
		constructor(taskView, task)
		{
			this.taskView = taskView;
			this.task = task;
			this.userId = taskView.userId;

			this.comments = new Set();
		}

		getEventHandlers()
		{
			return {
				task_view: {
					method: this.onPullView,
					context: this,
				},
				task_update: {
					method: this.onPullUpdate,
					context: this,
				},
				comment_add: {
					method: this.onPullComment,
					context: this,
				},
				comment_read_all: {
					method: this.onPullCommentReadAll,
					context: this,
				},
				project_read_all: {
					method: this.onPullProjectReadAll,
					context: this,
				},
				task_result_create: {
					method: this.onPullTaskResultCreate,
					context: this,
				},
				task_result_update: {
					method: this.onPullTaskResultUpdate,
					context: this,
				},
				task_result_delete: {
					method: this.onPullTaskResultDelete,
					context: this,
				},
				task_timer_start: {
					method: this.onPullTaskTimerStart,
					context: this,
				},
				task_timer_stop: {
					method: this.onPullTaskTimerStop,
					context: this,
				},
			};
		}

		subscribe()
		{
			BX.PULL.subscribe({
				moduleId: 'tasks',
				callback: data => this.executePullEvent(data),
			});
		}

		executePullEvent(data)
		{
			const has = Object.prototype.hasOwnProperty;
			const eventHandlers = this.getEventHandlers();
			const {command, params} = data;

			if (has.call(eventHandlers, command))
			{
				const {method, context} = eventHandlers[command];
				if (method)
				{
					method.apply(context, [params]);
				}
			}
		}

		onPullView(data)
		{
			this.taskView.onCommentsRead({taskId: data.TASK_ID});
		}

		onPullUpdate(data)
		{
			if (data.TASK_ID.toString() === this.task.id && data.params.updateCommentExists === false)
			{
				this.taskView.getTaskData().then(() => {
					this.taskView.updateViewTab();
					this.taskView.updateFields();
				});
			}
		}

		onPullComment(data)
		{
			const [entityType, entityId] = data.entityXmlId.split('_');
			const {messageId} = data;

			if (
				entityType !== 'TASK'
				|| entityId !== this.task.id
				|| this.comments.has(messageId)
			)
			{
				return;
			}

			this.comments.add(messageId);
			this.taskView.getTaskData().then(() => {
				this.taskView.updateViewTab();
				this.taskView.updateCommentsValues();
				this.taskView.updateFields();
			});
		}

		onPullCommentReadAll(data)
		{
			const roles = {
				all: 'view_all',
				responsible: 'view_role_responsible',
				accomplice: 'view_role_accomplice',
				originator: 'view_role_originator',
				auditor: 'view_role_auditor',
			};
			const userId = Number(data.USER_ID);
			const groupId = Number(data.GROUP_ID);
			const role = (data.ROLE || roles.all);

			if (userId > 0 && userId !== this.userId)
			{
				return;
			}

			const roleCondition = {
				[roles.all]: this.task.isMember(),
				[roles.responsible]: this.task.isResponsible(),
				[roles.accomplice]: this.task.isAccomplice(),
				[roles.originator]: this.task.isPureCreator(),
				[roles.auditor]: this.task.isAuditor(),
			};
			const groupCondition = (!groupId || Number(this.task.groupId) === groupId);
			if (roleCondition[role] && groupCondition)
			{
				this.taskView.onCommentsRead({taskId: this.task.id});
			}
		}

		onPullProjectReadAll(data)
		{
			const userId = Number(data.USER_ID);
			const groupId = Number(data.GROUP_ID);

			if (userId > 0 && userId !== this.userId)
			{
				return;
			}

			// todo: check if this is not scrum project
			if (groupId ? Number(this.task.groupId) === groupId : Number(this.task.groupId) > 0)
			{
				this.taskView.onCommentsRead({taskId: this.task.id});
			}
		}

		onPullTaskResultCreate(data)
		{
			this.updateTaskResultData(data);
		}

		onPullTaskResultUpdate(data)
		{
			this.updateTaskResultData(data);
		}

		onPullTaskResultDelete(data)
		{
			this.updateTaskResultData(data);
		}

		updateTaskResultData(data)
		{
			if (data.taskId.toString() === this.task.id)
			{
				this.taskView.getTaskResultData().then(() => {
					this.taskView.updateFields([TaskView.field.taskResultList]);
				});
				this.task.updateData({
					taskRequireResult: data.taskRequireResult,
					taskHasOpenResult: data.taskHasOpenResult,
					taskHasResult: data.taskHasResult,
				});
			}
		}

		onPullTaskTimerStart(data)
		{
			if (data.taskId.toString() === this.task.id)
			{
				this.task.updateData({
					timerIsRunningForCurrentUser: 'Y',
					timeElapsed: data.timeElapsed,
				});
				this.task.updateActions({
					canStartTimer: false,
					canPauseTimer: true,
				});
				this.taskView.updateFields([TaskView.field.status]);
			}
		}

		onPullTaskTimerStop(data)
		{
			if (data.taskId.toString() === this.task.id)
			{
				if (Number(data.userId) === Number(this.task.currentUser.id))
				{
					this.task.updateData({timerIsRunningForCurrentUser: 'N'});
					this.task.updateActions({
						canStartTimer: true,
						canPauseTimer: false,
					});
				}
				this.task.updateData({timeElapsed: data.timeElapsed[this.task.currentUser.id]});
				this.taskView.updateFields([TaskView.field.status]);
			}
		}
	}

	class TaskView extends LayoutComponent
	{
		static get section()
		{
			return {
				main: 'main',
				result: 'result',
				common: 'common',
				more: 'more',
			};
		}

		static get selectFields()
		{
			return [
				'ID',
				'TITLE',
				'DESCRIPTION',
				'STATUS',
				'GROUP_ID',
				'PARENT_ID',

				'CREATED_BY',
				'RESPONSIBLE_ID',
				'ACCOMPLICES',
				'AUDITORS',

				'DEADLINE',
				'ACTIVITY_DATE',
				'START_DATE_PLAN',
				'END_DATE_PLAN',

				'FAVORITE',
				'NOT_VIEWED',
				'IS_MUTED',
				'IS_PINNED',
				'MATCH_WORK_TIME',
				'ALLOW_CHANGE_DEADLINE',
				'TASK_CONTROL',
				'ALLOW_TIME_TRACKING',
				'TIME_SPENT_IN_LOGS',
				'TIME_ESTIMATE',
				'PRIORITY',
				'MARK',

				'CHECKLIST',
				'TAGS',
				'UF_CRM_TASK',
				'UF_TASK_WEBDAV_FILES',
				'COUNTERS',
				'COMMENTS_COUNT',
				'SERVICE_COMMENTS_COUNT',

				'RELATED_TASKS',
				'SUB_TASKS',
			];
		}

		static get queryParams()
		{
			return {
				GET_TASK_LIMIT_EXCEEDED: true,
				WITH_RESULT_INFO: 'Y',
				WITH_TIMER_INFO: 'Y',
				WITH_FILES_INFO: 'Y',
				WITH_CRM_INFO: 'Y',
				WITH_PARENT_TASK_INFO: 'Y',
				WITH_PARSED_DESCRIPTION: 'Y'
			};
		}

		static get field()
		{
			return {
				title: 'title',
				creator: 'creator',
				responsible: 'responsible',
				deadline: 'deadline',
				status: 'status',
				taskResultList: 'taskResultList',
				description: 'description',
				files: 'files',
				checklist: 'checklist',
				project: 'project',
				accomplices: 'accomplices',
				auditors: 'auditors',
				isImportant: 'isImportant',
				tags: 'tags',
				datePlan: 'datePlan',
				datePlanIs: 'datePlanIs',
				datePlanStart: 'datePlanStart',
				datePlanEnd: 'datePlanEnd',
				datePlanDuration: 'datePlanDuration',
				timeTracking: 'timeTracking',
				crm: 'crm',
				canChangeDeadline: 'canChangeDeadline',
				isMatchWorkTime: 'isMatchWorkTime',
				isTaskControl: 'isTaskControl',
				isResultRequired: 'isResultRequired',
				mark: 'mark',
				parentTask: 'parentTask',
				relatedTasks: 'relatedTasks',
				subTasks: 'subTasks',
				comments: 'comments',
			};
		}

		static open(data)
		{
			const taskView = new this({
				layoutWidget: data.layoutWidget,
				userId: data.userId,
				taskId: data.taskId,
				guid: data.guid,
				taskObject: data.taskObject,
				showLoading: !data.taskObject,
			});

			data.layoutWidget.showComponent(taskView);
		}

		constructor(props)
		{
			super(props);

			this.state = {
				showLoading: props.showLoading,
				readOnly: true,
				isMoreExpanded: false,
			};

			this.layoutWidget = props.layoutWidget;
			this.userId = Number(props.userId);
			this.taskId = Number(props.taskId);
			this.guid = props.guid;
			this.diskFolderId = Number(props.diskFolderId);
			this.pathToImages = '/bitrix/mobileapp/tasksmobile/extensions/tasks/layout/task/images';

			this.deadlines = [];
			if (this.getDeadlinesCachedOption())
			{
				this.deadlines = Object.entries(Task.deadlines).map(([key, value]) => {
					return {
						name: value.name,
						value: this.getDeadlinesCachedOption().value[key] * 1000,
					};
				});
			}

			this.componentEventEmitter = EventEmitter.createWithUid(this.guid);
			this.checkList = CheckListTree.buildTree();
			this.checkList.setLoading(true);

			this.currentUser = null;
			this.isInitial = true;
			this.task = new Task({id: this.userId});
			this.task.updateData({id: this.taskId});

			if (props.taskObject)
			{
				this.task.importProperties(props.taskObject);
				this.currentUser = this.task.currentUser;
				this.state.readOnly = !this.task.actions.edit;
			}

			this.scrollY = 0;
			this.datesResolver = new DatesResolver({
				id: this.task.id,
				guid: this.guid,
				deadline: this.task.deadline,
				startDatePlan: this.task.startDatePlan,
				endDatePlan: this.task.endDatePlan,
				isMatchWorkTime: this.task.isMatchWorkTime,
			});

			this.setLeftButtons();
		}

		componentDidMount()
		{
			Promise.allSettled([
				this.getTaskData(),
				this.getTaskResultData(),
				this.getDiskFolderId(),
				this.getDeadlines(),
				this.getCurrentUserData(),
				CalendarSettings.loadSettings(),
			]).then(() => this.doFinalInitAction());
		}

		doFinalInitAction()
		{
			if (this.currentUser)
			{
				this.task.currentUser = this.currentUser;
			}
			this.task.enableFieldChangesTracker();
			this.isInitial = false;

			this.pull = new Pull(this, this.task);
			this.pull.subscribe();

			this.taskEventEmitter = EventEmitter.createWithUid(this.task.id);

			this.bindEvents();
			this.updateViewTab();
			this.updateCommentsValues();

			this.actionMenu = new ActionMenu({
				layoutWidget: this.layoutWidget,
				task: this.task,
				diskFolderId: this.diskFolderId,
				deadlines: this.getDeadlinesCachedOption().value,
				isTaskLimitExceeded: this.taskLimitExceeded,
			});
			this.updateRightButtons();

			this.setState({
				showLoading: false,
				readOnly: !this.task.actions.edit,
			});
		}

		getTaskData()
		{
			return new Promise((resolve) => {
				(new RequestExecutor('tasks.task.get', {
					taskId: this.taskId,
					select: (
						this.isInitial
							? TaskView.selectFields
							: TaskView.selectFields.filter(field => field !== 'CHECKLIST')
					),
					params: TaskView.queryParams,
				}))
					.call()
					.then((response) => {
						const {task} = response.result;
						if (!task)
						{
							Alert.confirm(
								Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_VIEW_NO_TASK_ALERT_TITLE'),
								Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_VIEW_NO_TASK_ALERT_DESCRIPTION'),
								[{
									text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_VIEW_NO_TASK_ALERT_BUTTON_OK'),
									onPress: () => this.close(),
								}]
							);
						}

						if (this.isInitial)
						{
							this.task.setData(task);

							if (task.hasOwnProperty('checkListTree'))
							{
								this.checkList = CheckListTree.buildTree(task.checkListTree);
							}
							if (task.hasOwnProperty('checkListCanAdd'))
							{
								this.checkList.setCanAdd(task.checkListCanAdd);
							}
							this.checkList.setTaskId(this.taskId);
							this.checkList.setLoading(false);
						}
						else
						{
							const dataToUpdate = this.removeChangedFieldFromTask(task);
							this.task.updateData(dataToUpdate);
						}

						this.isDatePlan = (this.task.startDatePlan || this.task.endDatePlan);
						this.datesResolver.setData(this.task);
						this.taskLimitExceeded = task.taskLimitExceeded;
						this.componentEventEmitter.emit('tasks.task.view:updateTitle', {title: task.title});
						resolve();
					})
				;
			});
		}

		removeChangedFieldFromTask(task)
		{
			const propertiesToRemove = {
				[Task.fields.title]: 'title',
				[Task.fields.description]: 'description',
				[Task.fields.group]: ['groupId', 'group'],
				[Task.fields.timeEstimate]: 'timeEstimate',
				[Task.fields.priority]: 'priority',
				[Task.fields.mark]: 'mark',
				[Task.fields.creator]: 'creator',
				[Task.fields.responsible]: 'responsible',
				[Task.fields.accomplices]: 'accomplicesData',
				[Task.fields.auditors]: 'auditorsData',
				[Task.fields.crm]: 'crm',
				[Task.fields.tags]: 'tags',
				[Task.fields.files]: 'files',
				[Task.fields.uploadedFiles]: 'uploadedFiles',
				[Task.fields.isResultRequired]: 'taskRequireResult',
				[Task.fields.isMatchWorkTime]: 'matchWorkTime',
				[Task.fields.allowChangeDeadline]: 'allowChangeDeadline',
				[Task.fields.allowTaskControl]: 'taskControl',
				[Task.fields.allowTimeTracking]: 'allowTimeTracking',
				[Task.fields.deadline]: 'deadline',
				[Task.fields.startDatePlan]: 'startDatePlan',
				[Task.fields.endDatePlan]: 'endDatePlan',
			};
			const newTask = {...task};

			this.task.getChangedFields().forEach((field) => {
				let properties = propertiesToRemove[field];
				if (properties)
				{
					if (!Type.isArray(properties))
					{
						properties = [properties];
					}
					properties.forEach(property => delete newTask[property]);
				}
			});

			return newTask;
		}

		getTaskResultData()
		{
			return new Promise((resolve) => {
				(new RequestExecutor('tasks.task.result.list', {
					taskId: this.taskId,
					params: {
						WITH_USER_INFO: 'Y',
						WITH_FILE_INFO: 'Y',
						WITH_PARSED_TEXT: 'Y',
					},
				}))
					.call()
					.then((response) => {
						this.taskResultList = response.result;
						resolve();
					})
				;
			});
		}

		getDiskFolderId()
		{
			return new Promise((resolve) => {
				if (this.diskFolderId)
				{
					return resolve();
				}
				(new RequestExecutor('mobile.disk.getUploadedFilesFolder'))
					.call()
					.then((response) => {
						this.diskFolderId = Number(response.result);
						resolve();
					})
				;
			});
		}

		getDeadlines()
		{
			return new Promise((resolve) => {
				const now = new Date();
				let isUpdateNeeded = true;
				if (this.getDeadlinesCachedOption())
				{
					const lastTime = new Date(this.getDeadlinesCachedOption().lastTime);
					isUpdateNeeded = now.getDate() !== lastTime.getDate();
				}
				if (this.deadlines.length > 0 && !isUpdateNeeded)
				{
					return resolve();
				}
				(new RequestExecutor('mobile.tasks.deadlines.get'))
					.call()
					.then((response) => {
						this.deadlines = Object.entries(Task.deadlines).map(([key, value]) => {
							return {
								name: value.name,
								value: response.result[key] * 1000,
							};
						});
						this.updateDeadlinesCachedOption({
							lastTime: now.getTime(),
							value: response.result,
						});
						resolve();
					})
				;
			});
		}

		getDeadlinesCachedOption()
		{
			const optionsCache = Application.sharedStorage('tasksTaskList').get('options');

			if (Type.isString(optionsCache))
			{
				return JSON.parse(optionsCache).deadlines;
			}

			return null;
		}

		updateDeadlinesCachedOption(value)
		{
			const storage = Application.sharedStorage('tasksTaskList');
			const optionsCache = storage.get('options');
			const currentOption = (Type.isString(optionsCache) ? JSON.parse(optionsCache) : {});
			currentOption.deadlines = value;
			storage.set('options', JSON.stringify(currentOption));
		}

		getCurrentUserData()
		{
			return new Promise((resolve) => {
				if (this.currentUser)
				{
					return resolve();
				}
				(new RequestExecutor('tasksmobile.User.getUsersData', {userIds: [this.userId]}))
					.call()
					.then((response) => {
						this.currentUser = response.result[this.userId];
						resolve();
					})
				;
			});
		}

		bindEvents()
		{
			this.componentEventEmitter.on(
				'tasks.task.tabs:onCommentsTabTopButtonCloseClick',
				() => this.onButtonCloseClick(),
			);
			this.componentEventEmitter.on(
				'tasks.task.tabs:onCommentsTabTopButtonMoreClick',
				() => this.actionMenu.show()
			);

			this.bindActionMenuEvents();
			this.bindDatesResolverEvents();

			BX.addCustomEvent('tasks.task.comments:onCommentsRead', eventData => this.onCommentsRead(eventData));
			BX.addCustomEvent('task.view.onCommentAction', eventData => this.onCommentAction(eventData));
		}

		bindActionMenuEvents()
		{
			this.taskEventEmitter.on(
				'tasks.task.actionMenu:startTimer',
				() => this.updateFields([TaskView.field.status])
			);
			this.taskEventEmitter.on(
				'tasks.task.actionMenu:pauseTimer',
				() => this.updateFields([TaskView.field.status])
			);
			this.taskEventEmitter.on(
				'tasks.task.actionMenu:start',
				() => this.updateFields([TaskView.field.status])
			);
			this.taskEventEmitter.on(
				'tasks.task.actionMenu:pause',
				() => this.updateFields([TaskView.field.status])
			);
			this.taskEventEmitter.on(
				'tasks.task.actionMenu:complete',
				() => this.updateFields([TaskView.field.status, TaskView.field.deadline])
			);
			this.taskEventEmitter.on(
				'tasks.task.actionMenu:renew',
				() => this.updateFields([TaskView.field.status, TaskView.field.deadline])
			);
			this.taskEventEmitter.on(
				'tasks.task.actionMenu:approve',
				() => this.updateFields([TaskView.field.status, TaskView.field.deadline])
			);
			this.taskEventEmitter.on(
				'tasks.task.actionMenu:disapprove',
				() => this.updateFields([TaskView.field.status, TaskView.field.deadline])
			);
			this.taskEventEmitter.on(
				'tasks.task.actionMenu:delegate',
				() => this.updateFields([TaskView.field.responsible, TaskView.field.auditors])
			);
			this.taskEventEmitter.on(
				'tasks.task.actionMenu:remove',
				() => this.close()
			);
		}

		bindDatesResolverEvents()
		{
			const handleChangedFields = () => {
				this.task.addChangedFields([
					Task.fields.deadline,
					Task.fields.startDatePlan,
					Task.fields.endDatePlan,
					Task.fields.isMatchWorkTime,
				]);
				this.updateRightButtons();
			};
			this.datesResolver.on('datesResolver:deadlineChanged', (deadline, shouldSave) => {
				this.task.deadline = deadline * 1000;
				this.updateFields([TaskView.field.deadline]);
				if (shouldSave)
				{
					void this.task.saveDeadline();
				}
				else
				{
					handleChangedFields();
				}
			});
			this.datesResolver.on('datesResolver:datesChanged', (startDatePlan, endDatePlan) => {
				this.task.startDatePlan = startDatePlan * 1000;
				this.task.endDatePlan = endDatePlan * 1000;
				this.updateFields([
					TaskView.field.datePlanIs,
					TaskView.field.datePlanStart,
					TaskView.field.datePlanEnd,
					TaskView.field.datePlanDuration,
				]);
				handleChangedFields();
			});
		}

		onCommentsRead(eventData)
		{
			if (this.task.id === eventData.taskId.toString())
			{
				this.task.pseudoRead();
				this.updateCommentsValues();
			}
		}

		updateCommentsValues()
		{
			this.updateFields([TaskView.field.comments]);
			this.componentEventEmitter.emit('tasks.task.view:updateTab', {
				tab: 'comments',
				...this.getNewCommentsCounterData(),
			});
		}

		onCommentAction(eventData)
		{
			const {taskId, userId, action,} = eventData;

			if (
				Number(taskId) !== Number(this.task.id)
				|| Number(userId) !== Number(this.userId)
			)
			{
				return;
			}

			switch (action)
			{
				case 'deadlineChange':
					this.deadlineRef.openPicker();
					break;

				case 'taskApprove':
					void this.task.approve();
					break;

				case 'taskDisapprove':
					void this.task.disapprove();
					break;

				case 'taskComplete':
					void this.task.complete();
					break;

				default:
					break;
			}
		}

		updateViewTab()
		{
			this.componentEventEmitter.emit('tasks.task.view:updateTab', {
				tab: 'view',
				...this.getExpiredCounterData(),
			});
		}

		getExpiredCounterData()
		{
			return {
				value: Number(!this.task.isCompletedCounts && !this.task.isDeferred && this.task.isExpired),
				color: (this.task.isMember() && !this.task.isMuted ? Task.counterColors.danger : Task.counterColors.gray),
			};
		}

		getNewCommentsCounterData()
		{
			return {
				value: Number(this.task.getNewCommentsCount()),
				color: (this.task.isMember() && !this.task.isMuted ? Task.counterColors.success : Task.counterColors.gray),
			};
		}

		close()
		{
			this.componentEventEmitter.emit('tasks.task.view:close');
		}

		setLeftButtons()
		{
			this.layoutWidget.setLeftButtons([{
				svg: {
					content: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
				},
				callback: () => this.onButtonCloseClick(),
			}]);
		}

		onButtonCloseClick()
		{
			if (this.task.haveChangedFields())
			{
				this.showConfirmOnFormClosing();
			}
			else
			{
				this.close();
			}
		}

		showConfirmOnFormClosing()
		{
			Haptics.impactLight();
			Alert.confirm(
				Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_VIEW_CANCEL_ALERT_TITLE'),
				Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_VIEW_CANCEL_ALERT_DESCRIPTION'),
				[
					{
						text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_VIEW_CANCEL_ALERT_SAVE'),
						type: 'default',
						onPress: () => this.save().then(() => this.close()),
					},
					{
						text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_VIEW_CANCEL_ALERT_DISCARD'),
						type: 'destructive',
						onPress: () => this.close(),
					},
					{
						text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_VIEW_CANCEL_ALERT_CONTINUE'),
						type: 'cancel',
					},
				],
			);
		}

		updateRightButtons()
		{
			const buttons = [];

			if (this.task.haveChangedFields())
			{
				buttons.push({
					type: 'text',
					name: Loc.getMessage(
						this.isSaving
							? 'TASKSMOBILE_LAYOUT_TASK_VIEW_SAVING_BUTTON'
							: 'TASKSMOBILE_LAYOUT_TASK_VIEW_SAVE_BUTTON'
					),
					color: (this.isSaving ? '#bdc1c6' : '#2066b0'),
					callback: () => void this.save(),
				});
			}
			else
			{
				buttons.push({
					type: 'more',
					callback: () => this.actionMenu.show(),
				});
			}

			this.layoutWidget.setRightButtons(buttons);
		}

		checkCanSave()
		{
			if (this.isSaving)
			{
				return false;
			}

			if (this.task.title === '')
			{
				Notify.showIndicatorError({
					hideAfter: 2000,
					text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_VIEW_SAVE_ERROR_NO_TITLE'),
				});
				return false;
			}

			if (this.filesInnerRef && this.filesInnerRef.hasUploadingFiles())
			{
				Notify.showIndicatorError({
					hideAfter: 2000,
					text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_VIEW_SAVE_ERROR_LOADING_FILES'),
				});
				return false;
			}

			return true;
		}

		save()
		{
			return new Promise((resolve, reject) => {
				if (!this.checkCanSave())
				{
					return reject();
				}
				this.isSaving = true;
				this.updateRightButtons();
				Notify.showIndicatorLoading();

				if (
					this.task.isFieldChanged(Task.fields.checkList)
					&& this.task.getChangedFields().length === 1
				)
				{
					this.checkList.save(this.task.id).then(
						() => {
							this.onSaveSuccess();
							resolve();
						},
						() => reject()
					);
				}
				else if (!this.task.actions.edit && this.task.actions.changeDeadline)
				{
					this.task.saveDeadline().then(
						() => {
							this.onSaveSuccess(this.task.isFieldChanged(Task.fields.checkList));
							resolve();
						},
						(response) => {
							this.onSaveFail(response);
							reject();
						}
					);
				}
				else
				{
					this.task.save().then(
						() => {
							this.onSaveSuccess(this.task.isFieldChanged(Task.fields.checkList));
							resolve();
						},
						(response) => {
							this.onSaveFail(response);
							reject();
						}
					);
				}
			});
		}

		onSaveSuccess(shouldSaveChecklist = false)
		{
			if (shouldSaveChecklist)
			{
				void this.checkList.save(this.task.id);
			}
			this.isSaving = false;
			this.task.clearChangedFields();
			this.task.updateData({uploadedFiles: []});
			this.updateRightButtons();
			Notify.showIndicatorSuccess({hideAfter: 1000});
		}

		onSaveFail(response)
		{
			this.isSaving = false;
			this.updateRightButtons();
			Notify.showIndicatorError({
				hideAfter: 3000,
				text: response.error.description.replace(/<\/?[^>]+(>|$)/g, ''),
			});
		}

		getStyleForField(name = '')
		{
			const fullBorderedFields = [
				TaskView.field.description,
				TaskView.field.datePlan,
				TaskView.field.timeTracking,
			];
			const style = {
				marginHorizontal: 16,
			};

			if (fullBorderedFields.includes(name))
			{
				style.marginHorizontal = 6;
				style.borderWidth = 1;
				style.borderColor = '#e6e7e9';
				style.borderRadius = 7;
			}

			return style;
		}

		getDeepMergeStylesForField(isExpandable = false)
		{
			const deepMergeStyles = {
				externalWrapper: (isExpandable) => ({
					height: (isExpandable ? undefined : fieldHeight),
					minHeight: (isExpandable ? fieldHeight : undefined),
					justifyContent: 'center',
					paddingTop: 10,
					paddingBottom: 10,
				}),
			};

			return Object.entries(deepMergeStyles).reduce((result, [key, value]) => {
				result[key] = (Type.isFunction(value) ? value(isExpandable) : value);
				return result;
			}, {});
		}

		render()
		{
			if (this.state.showLoading)
			{
				return this.renderLoadingScreen();
			}

			return this.renderTaskViewScreen();
		}

		renderLoadingScreen()
		{
			return View({}, new LoadingScreenComponent());
		}

		renderTaskViewScreen()
		{
			return View(
				{
					resizableByKeyboard: true,
					style: {
						flex: 1,
						backgroundColor: '#eef2f4',
						paddingBottom: Comments.getHeight() + 5,
					},
					safeArea: {
						bottom: true,
					},
				},
				ScrollView(
					{
						ref: (ref) => this.scrollViewRef = ref,
						style: {
							flex: 1,
							borderRadius: 12,
						},
						bounces: true,
						showsVerticalScrollIndicator: false,
						onScroll: params => this.scrollY = params.contentOffset.y,
					},
					View({}, ...this.renderSections()),
				),
				new Comments({
					commentsCount: (this.task.commentsCount - this.task.serviceCommentsCount),
					newCommentsCounter: this.getNewCommentsCounterData(),
					ref: ref => this.commentsRef = ref,
					onClick: () => this.componentEventEmitter.emit('tasks.task.view:setActiveTab', {tab: 'comments'}),
				}),
			);
		}

		renderSections()
		{
			const fieldsContent = this.getFieldsContent();
			const sections = {
				[TaskView.section.main]: {
					fields: {
						[TaskView.field.title]: fieldsContent[TaskView.field.title],
						[TaskView.field.creator]: fieldsContent[TaskView.field.creator],
						[TaskView.field.responsible]: fieldsContent[TaskView.field.responsible],
						[TaskView.field.deadline]: fieldsContent[TaskView.field.deadline],
						[TaskView.field.status]: fieldsContent[TaskView.field.status],
					},
				},
				[TaskView.section.result]: {
					fields: {
						[TaskView.field.taskResultList]: fieldsContent[TaskView.field.taskResultList],
					},
				},
				[TaskView.section.common]: {
					fields: {
						[TaskView.field.checklist]: fieldsContent[TaskView.field.checklist],
						[TaskView.field.project]: fieldsContent[TaskView.field.project],
						[TaskView.field.description]: fieldsContent[TaskView.field.description],
						[TaskView.field.files]: fieldsContent[TaskView.field.files],
						[TaskView.field.accomplices]: fieldsContent[TaskView.field.accomplices],
						[TaskView.field.auditors]: fieldsContent[TaskView.field.auditors],
					},
				},
				[TaskView.section.more]: {
					header: this.getSectionMoreHeader(),
					fields: {
						[TaskView.field.datePlan]: fieldsContent[TaskView.field.datePlan],
						[TaskView.field.timeTracking]: fieldsContent[TaskView.field.timeTracking],
						[TaskView.field.isImportant]: fieldsContent[TaskView.field.isImportant],
						[TaskView.field.crm]: fieldsContent[TaskView.field.crm],
						[TaskView.field.tags]: fieldsContent[TaskView.field.tags],
						[TaskView.field.parentTask]: fieldsContent[TaskView.field.parentTask],
						[TaskView.field.relatedTasks]: fieldsContent[TaskView.field.relatedTasks],
						[TaskView.field.subTasks]: fieldsContent[TaskView.field.subTasks],
						[TaskView.field.mark]: fieldsContent[TaskView.field.mark],
						[TaskView.field.canChangeDeadline]: fieldsContent[TaskView.field.canChangeDeadline],
						[TaskView.field.isMatchWorkTime]: fieldsContent[TaskView.field.isMatchWorkTime],
						[TaskView.field.isResultRequired]: fieldsContent[TaskView.field.isResultRequired],
						[TaskView.field.isTaskControl]: fieldsContent[TaskView.field.isTaskControl],
					},
				},
			};

			return Object.entries(sections).map(([name, data]) => {
				return View(
					{
						style: {
							backgroundColor: '#ffffff',
							borderRadius: 12,
							paddingTop: (name === TaskView.section.main || name === TaskView.section.result ? 0 : 6),
							paddingBottom: (name === TaskView.section.main || name === TaskView.section.result ? 0 : 6),
							marginTop: (name === TaskView.section.common ? 0 : 12),
						},
						testId: `taskViewSection_${name}`,
					},
					data.header,
					(
						(name !== TaskView.section.more || this.state.isMoreExpanded)
						&& View(
							{},
							...Object.entries(data.fields).map(([key, field]) => this.renderField(key, field)),
						)
					),
				);
			});
		}

		renderField(name, content)
		{
			const fieldsWithoutTopBorder = [
				TaskView.field.title,
				TaskView.field.taskResultList,
				TaskView.field.checklist,
				TaskView.field.project,
				TaskView.field.description,
				TaskView.field.files,
				TaskView.field.datePlan,
				TaskView.field.timeTracking,
				TaskView.field.isImportant,
			];

			if (fieldsWithoutTopBorder.includes(name))
			{
				return content;
			}

			return View(
				{},
				View({
					style: {
						...this.getStyleForField(),
						height: 0.5,
						backgroundColor: '#e6e7e9',
					},
				}),
				content,
			);
		}

		getSectionMoreHeader()
		{
			const arrowDirection = (this.state.isMoreExpanded ? 'up' : 'down');
			const arrowUri = `${this.pathToImages}/tasksmobile-layout-task-section-more-arrow-${arrowDirection}.png`;

			return View(
				{
					ref: ref => this.sectionMoreRef = ref,
					style: {
						...this.getStyleForField(),
						flexDirection: 'row',
						height: 54,
						justifyContent: 'space-between',
						paddingBottom: (this.state.isMoreExpanded ? 6 : 0),
					},
					testId: `taskViewSection_${TaskView.section.more}_header`,
					onClick: () => {
						this.setState(
							{isMoreExpanded: !this.state.isMoreExpanded},
							() => {
								if (
									this.state.isMoreExpanded
									&& this.scrollViewRef
									&& this.sectionMoreRef
								)
								{
									const position = this.scrollViewRef.getPosition(this.sectionMoreRef);
									this.scrollViewRef.scrollTo({
										y: position.y - 6,
										animated: true,
									});
								}
							}
						);
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					Image({
						style: {
							alignSelf: 'center',
							width: 24,
							height: 24,
							marginRight: 8,
						},
						uri: this.getImageUrl(`${this.pathToImages}/tasksmobile-layout-task-section-more-icon.png`),
					}),
					Text({
						style: {
							fontSize: 16,
							fontWeight: '400',
							color: '#a8adb4',
						},
						text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_VIEW_SECTION_MORE'),
					}),
				),
				Image({
					style: {
						alignSelf: 'center',
						width: 24,
						height: 24,
					},
					uri: this.getImageUrl(arrowUri),
				}),
			);
		}

		getFieldsContent()
		{
			return {
				[TaskView.field.title]: new Title({
					readOnly: this.state.readOnly,
					title: this.task.title,
					focus: null,
					style: this.getStyleForField(TaskView.field.title),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					ref: ref => this.titleRef = ref,
					onChange: (title) => {
						this.task.updateData({title});
						this.task.addChangedFields(Task.fields.title);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.creator]: new Creator({
					readOnly: this.state.readOnly,
					creator: this.task.creator,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskView.field.creator),
					deepMergeStyles: this.getDeepMergeStylesForField(),
					ref: ref => this.creatorRef = ref,
					onChange: (creator) => {
						this.task.updateData({creator});
						this.task.addChangedFields(Task.fields.creator);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.responsible]: new Responsible({
					readOnly: this.state.readOnly,
					responsible: this.task.responsible,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskView.field.responsible),
					deepMergeStyles: this.getDeepMergeStylesForField(),
					ref: ref => this.responsibleRef = ref,
					onChange: (responsible) => {
						this.task.updateData({responsible});
						this.task.addChangedFields(Task.fields.responsible);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.deadline]: new Deadline({
					readOnly: (!this.task.actions.edit && !this.task.actions.changeDeadline),
					deadline: this.task.deadline,
					taskState: this.task.getState(),
					deadlines: this.deadlines,
					showBalloonDate: (
						!this.task.isCompleted
						&& !this.task.isDeferred
						&& !this.task.isSupposedlyCompleted
					),
					counter: (
						!this.task.isCompleted
						&& !this.task.isDeferred
						&& !this.task.isSupposedlyCompleted
						&& this.getExpiredCounterData()
					),
					style: this.getStyleForField(TaskView.field.deadline),
					deepMergeStyles: this.getDeepMergeStylesForField(),
					pathToImages: this.pathToImages,
					datesResolver: this.datesResolver,
					ref: ref => this.deadlineRef = ref,
				}),
				[TaskView.field.status]: new Status({
					readOnly: (
						!this.task.actions.startTimer
						&& !this.task.actions.pauseTimer
						&& !this.task.actions.start
						&& !this.task.actions.pause
						&& !this.task.actions.complete
						&& !this.task.actions.renew
						&& !this.task.actions.approve
						&& !this.task.actions.disapprove
						&& !this.task.actions.defer
					),
					task: this.task,
					status: this.task.status,
					isTimerExisting: (
						this.task.allowTimeTracking
						&& (this.task.actions.startTimer || this.task.actions.pauseTimer)
					),
					isTimerRunning: this.task.isTimerRunningForCurrentUser,
					timeElapsed: this.task.timeElapsed,
					timeEstimate: this.task.timeEstimate,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskView.field.status),
					deepMergeStyles: this.getDeepMergeStylesForField(),
					pathToImages: this.pathToImages,
					ref: ref => this.statusRef = ref,
				}),
				[TaskView.field.taskResultList]: new TaskResultList({
					resultList: this.taskResultList,
					taskId: this.task.id,
					parentWidget: this.layoutWidget,
					ref: ref => this.taskResultListRef = ref,
				}),
				[TaskView.field.checklist]: new CheckList({
					checkList: this.checkList,
					taskId: this.task.id,
					taskGuid: this.task.guid,
					userId: this.userId,
					diskConfig: {
						folderId: this.diskFolderId,
					},
					parentWidget: this.layoutWidget,
					isLoading: this.checkList.isLoading(),
					style: {
						marginHorizontal: 6,
					},
					onFieldFocus: (ref) => {
						if (this.scrollViewRef && ref)
						{
							const {y} = this.scrollViewRef.getPosition(ref);
							if (y > this.scrollY + device.screen.height * 0.4)
							{
								this.scrollViewRef.scrollTo({
									y: y - 150,
									animated: true,
								});
							}
						}
					},
					onChange: () => {
						this.task.addChangedFields(Task.fields.checkList);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.project]: new Project({
					readOnly: this.state.readOnly,
					groupId: this.task.groupId,
					groupData: this.task.group,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskView.field.project),
					deepMergeStyles: this.getDeepMergeStylesForField(),
					ref: ref => this.projectRef = ref,
					onChange: (groupId, group) => {
						this.task.updateData({groupId, group});
						this.task.addChangedFields(Task.fields.group);
						this.updateFields([TaskView.field.tags]);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.description]: new Description({
					readOnly: (this.state.readOnly || (this.task.description !== this.task.parsedDescription)),
					description: this.task.description,
					parsedDescription: this.task.parsedDescription,
					task: this.task,
					style: this.getStyleForField(TaskView.field.description),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					ref: ref => this.descriptionRef = ref,
					onChange: (description) => {
						this.task.updateData({description});
						this.task.addChangedFields(Task.fields.description);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.files]: new Files({
					readOnly: this.state.readOnly,
					userId: this.userId,
					taskId: this.task.id,
					files: [...(this.task.files || []), ...(this.task.uploadedFiles || [])],
					isAlwaysShowed: true,
					showAddButton: !this.state.readOnly,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskView.field.files),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					ref: ref => this.filesRef = ref,
					onInnerRef: ref => this.filesInnerRef = ref,
					onChange: (files) => {
						const uploadedFiles = [];
						const existingFiles = [];

						files.forEach((file) => {
							if (file.isUploading || file.token)
							{
								uploadedFiles.push(file);
							}
							else if (file.id && !file.hasError)
							{
								existingFiles.push(file);
							}
						});
						this.task.updateData({
							uploadedFiles,
							files: existingFiles,
						});
						this.task.addChangedFields([Task.fields.files, Task.fields.uploadedFiles]);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.accomplices]: new Accomplices({
					readOnly: this.state.readOnly,
					accomplices: this.task.accomplices,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskView.field.accomplices),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					checkList: this.checkList,
					ref: ref => this.accomplicesRef = ref,
					onChange: (accomplicesData) => {
						this.task.updateData({accomplicesData});
						this.task.addChangedFields(Task.fields.accomplices);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.auditors]: new Auditors({
					readOnly: this.state.readOnly,
					auditors: this.task.auditors,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskView.field.auditors),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					checkList: this.checkList,
					ref: ref => this.auditorsRef = ref,
					onChange: (auditorsData) => {
						this.task.updateData({auditorsData});
						this.task.addChangedFields(Task.fields.auditors);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.datePlan]: new DatePlan({
					readOnly: this.state.readOnly,
					isDatePlan: this.isDatePlan,
					startDatePlan: this.task.startDatePlan,
					endDatePlan: this.task.endDatePlan,
					style: this.getStyleForField(TaskView.field.datePlan),
					deepMergeStyles: this.getDeepMergeStylesForField(),
					datesResolver: this.datesResolver,
					ref: ref => this.datePlanRef = ref,
					onDatePlanIsRef: ref => this.datePlanIsRef = ref,
					onDatePlanStartRef: ref => this.datePlanStartRef = ref,
					onDatePlanEndRef: ref => this.datePlanEndRef = ref,
					onDatePlanDurationRef: ref => this.datePlanDurationRef = ref,
					onChange: isDatePlan => this.isDatePlan = isDatePlan,
				}),
				[TaskView.field.timeTracking]: new TimeTracking({
					readOnly: this.state.readOnly,
					isTimeTracking: this.task.allowTimeTracking,
					timeEstimate: this.task.timeEstimate,
					style: {
						...this.getStyleForField(TaskView.field.timeTracking),
						marginTop: 6,
					},
					deepMergeStyles: this.getDeepMergeStylesForField(),
					ref: ref => this.timeTrackingRef = ref,
					onChange: (values) => {
						this.task.updateData(values);
						if (!Type.isUndefined(values.allowTimeTracking))
						{
							this.task.addChangedFields(Task.fields.allowTimeTracking);
							this.updateRightButtons();
						}
						if (!Type.isUndefined(values.timeEstimate))
						{
							this.task.addChangedFields(Task.fields.timeEstimate);
							this.updateRightButtons();
						}
					},
				}),
				[TaskView.field.isImportant]: new IsImportant({
					readOnly: this.state.readOnly,
					isImportant: (this.task.priority === Task.priority.important),
					style: this.getStyleForField(TaskView.field.isImportant),
					deepMergeStyles: this.getDeepMergeStylesForField(),
					pathToImages: this.pathToImages,
					ref: ref => this.isImportantRef = ref,
					onChange: (value) => {
						this.task.updateData({priority: (value ? Task.priority.important : Task.priority.none)});
						this.task.addChangedFields(Task.fields.priority);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.crm]: new Crm({
					readOnly: this.state.readOnly,
					crm: this.task.crm,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskView.field.crm),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					ref: ref => this.crmRef = ref,
					onChange: (crm) => {
						this.task.updateData({crm});
						this.task.addChangedFields(Task.fields.crm);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.tags]: new Tags({
					readOnly: this.state.readOnly,
					tags: this.task.tags,
					taskId: this.taskId,
					groupId: this.task.groupId,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskView.field.tags),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					ref: ref => this.tagsRef = ref,
					onChange: (tags) => {
						this.task.updateData({tags});
						this.task.addChangedFields(Task.fields.tags);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.parentTask]: new ParentTask({
					parentTask: this.task.parentTask,
					canOpenEntity: true,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskView.field.parentTask),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					ref: ref => this.parentTaskRef = ref,
				}),
				[TaskView.field.subTasks]: new SubTasks({
					subTasks: this.task.subTasks,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskView.field.subTasks),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					ref: ref => this.subTasksRef = ref,
				}),
				[TaskView.field.relatedTasks]: new RelatedTasks({
					relatedTasks: this.task.relatedTasks,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskView.field.relatedTasks),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					ref: ref => this.relatedTasksRef = ref,
				}),
				[TaskView.field.mark]: new Mark({
					readOnly: this.state.readOnly,
					mark: this.task.mark,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskView.field.mark),
					deepMergeStyles: this.getDeepMergeStylesForField(),
					ref: ref => this.markRef = ref,
					onChange: (mark) => {
						this.task.updateData({mark});
						this.task.addChangedFields(Task.fields.mark);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.canChangeDeadline]: new CanChangeDeadline({
					readOnly: this.state.readOnly,
					canChangeDeadline: this.task.allowChangeDeadline,
					style: this.getStyleForField(TaskView.field.canChangeDeadline),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					ref: ref => this.canChangeDeadlineRef = ref,
					onChange: (value) => {
						this.task.updateData({allowChangeDeadline : (value ? 'Y' : 'N')});
						this.task.addChangedFields(Task.fields.allowChangeDeadline);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.isMatchWorkTime]: new IsMatchWorkTime({
					readOnly: this.state.readOnly,
					isMatchWorkTime: this.task.isMatchWorkTime,
					style: this.getStyleForField(TaskView.field.isMatchWorkTime),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					datesResolver: this.datesResolver,
					ref: ref => this.isMatchWorkTimeRef = ref,
					onChange: (value) => {
						this.task.updateData({matchWorkTime: (value ? 'Y' : 'N')});
						this.task.addChangedFields([
							Task.fields.isMatchWorkTime,
							Task.fields.deadline,
							Task.fields.startDatePlan,
							Task.fields.endDatePlan,
						]);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.isTaskControl]: new IsTaskControl({
					readOnly: this.state.readOnly,
					isTaskControl: this.task.allowTaskControl,
					style: this.getStyleForField(TaskView.field.isTaskControl),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					ref: ref => this.isTaskControlRef = ref,
					onChange: (value) => {
						this.task.updateData({taskControl: (value ? 'Y' : 'N')});
						this.task.addChangedFields(Task.fields.allowTaskControl);
						this.updateRightButtons();
					},
				}),
				[TaskView.field.isResultRequired]: new IsResultRequired({
					readOnly: this.state.readOnly,
					isResultRequired: this.task.isResultRequired,
					style: this.getStyleForField(TaskView.field.isResultRequired),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					ref: ref => this.isResultRequiredRef = ref,
					onChange: (value) => {
						this.task.updateData({taskRequireResult: (value ? 'Y' : 'N')});
						this.task.addChangedFields(Task.fields.isResultRequired);
						this.updateRightButtons();
					},
				}),
			};
		}

		updateFields(fields = [])
		{
			const fieldRefs = {
				[TaskView.field.title]: {
					ref: this.titleRef,
					newState: {
						readOnly: this.state.readOnly,
						title: this.task.title,
					},
				},
				[TaskView.field.creator]: {
					ref: this.creatorRef,
					newState: {
						readOnly: this.state.readOnly,
						creator: this.task.creator,
					},
				},
				[TaskView.field.responsible]: {
					ref: this.responsibleRef,
					newState: {
						readOnly: this.state.readOnly,
						responsible: this.task.responsible,
					},
				},
				[TaskView.field.deadline]: {
					ref: this.deadlineRef,
					newState: {
						readOnly: (!this.task.actions.edit && !this.task.actions.changeDeadline),
						deadline: this.task.deadline,
						taskState: this.task.getState(),
						deadlines: this.deadlines,
						showBalloonDate: (
							!this.task.isCompleted
							&& !this.task.isDeferred
							&& !this.task.isSupposedlyCompleted
						),
						counter: (
							!this.task.isCompleted
							&& !this.task.isDeferred
							&& !this.task.isSupposedlyCompleted
							&& this.getExpiredCounterData()
						),
					},
				},
				[TaskView.field.status]: {
					ref: this.statusRef,
					newState: {
						readOnly: (
							!this.task.actions.start
							&& !this.task.actions.pause
							&& !this.task.actions.complete
							&& !this.task.actions.renew
							&& !this.task.actions.approve
							&& !this.task.actions.disapprove
							&& !this.task.actions.defer
						),
						status: this.task.status,
						isTimerExisting: (
							this.task.allowTimeTracking
							&& (this.task.actions.startTimer || this.task.actions.pauseTimer)
						),
						isTimerRunning: this.task.isTimerRunningForCurrentUser,
						timeElapsed: this.task.timeElapsed,
						timeEstimate: this.task.timeEstimate,
					},
				},
				[TaskView.field.taskResultList]: {
					ref: this.taskResultListRef,
					newState: {
						resultList: this.taskResultList,
					},
				},
				[TaskView.field.description]: {
					ref: this.descriptionRef,
					newState: {
						readOnly: (this.state.readOnly || (this.task.description !== this.task.parsedDescription)),
						description: this.task.description,
						parsedDescription: this.task.parsedDescription,
					},
				},
				[TaskView.field.files]: {
					ref: this.filesRef,
					newState: {
						readOnly: this.state.readOnly,
						files: [...(this.task.files || []), ...(this.task.uploadedFiles || [])],
						showAddButton: !this.state.readOnly,
					},
				},
				[TaskView.field.project]: {
					ref: this.projectRef,
					newState: {
						readOnly: this.state.readOnly,
						groupId: this.task.groupId,
						groupData: this.task.group,
					},
				},
				[TaskView.field.accomplices]: {
					ref: this.accomplicesRef,
					newState: {
						readOnly: this.state.readOnly,
						accomplices: this.task.accomplices,
					},
				},
				[TaskView.field.auditors]: {
					ref: this.auditorsRef,
					newState: {
						readOnly: this.state.readOnly,
						auditors: this.task.auditors,
					},
				},
				[TaskView.field.isImportant]: {
					ref: this.isImportantRef,
					newState: {
						readOnly: this.state.readOnly,
						isImportant: (this.task.priority === Task.priority.important),
					},
				},
				[TaskView.field.tags]: {
					ref: this.tagsRef,
					newState: {
						readOnly: this.state.readOnly,
						tags: this.task.tags,
						groupId: this.task.groupId,
					},
				},
				[TaskView.field.datePlanIs]: {
					ref: this.datePlanIsRef,
					newState: {
						readOnly: this.state.readOnly,
						isDatePlan: this.isDatePlan,
					},
				},
				[TaskView.field.datePlanStart]: {
					ref: this.datePlanStartRef,
					newState: {
						readOnly: this.state.readOnly,
						startDatePlan: this.task.startDatePlan,
					},
				},
				[TaskView.field.datePlanEnd]: {
					ref: this.datePlanEndRef,
					newState: {
						readOnly: this.state.readOnly,
						endDatePlan: this.task.endDatePlan,
					},
				},
				[TaskView.field.datePlanDuration]: {
					ref: this.datePlanDurationRef,
					newState: {
						readOnly: this.state.readOnly,
						duration: this.datesResolver.durationByType,
						durationType: this.datesResolver.durationType,
					},
				},
				[TaskView.field.timeTracking]: {
					ref: this.timeTrackingRef,
					newState: {
						readOnly: this.state.readOnly,
						isTimeTracking: this.task.allowTimeTracking,
						timeEstimate: this.task.timeEstimate,
					},
				},
				[TaskView.field.crm]: {
					ref: this.crmRef,
					newState: {
						readOnly: this.state.readOnly,
						crm: this.task.crm,
					},
				},
				[TaskView.field.canChangeDeadline]: {
					ref: this.canChangeDeadlineRef,
					newState: {
						readOnly: this.state.readOnly,
						canChangeDeadline: this.task.allowChangeDeadline,
					},
				},
				[TaskView.field.isMatchWorkTime]: {
					ref: this.isMatchWorkTimeRef,
					newState: {
						readOnly: this.state.readOnly,
						isMatchWorkTime: this.task.isMatchWorkTime,
					},
				},
				[TaskView.field.isTaskControl]: {
					ref: this.isTaskControlRef,
					newState: {
						readOnly: this.state.readOnly,
						isTaskControl: this.task.allowTaskControl,
					},
				},
				[TaskView.field.isResultRequired]: {
					ref: this.isResultRequiredRef,
					newState: {
						readOnly: this.state.readOnly,
						isResultRequired: this.task.isResultRequired,
					},
				},
				[TaskView.field.mark]: {
					ref: this.markRef,
					newState: {
						readOnly: this.state.readOnly,
						mark: this.task.mark,
					},
				},
				[TaskView.field.parentTask]: {
					ref: this.parentTaskRef,
					newState: {
						parentTask: this.task.parentTask,
					},
				},
				[TaskView.field.relatedTasks]: {
					ref: this.relatedTasksRef,
					newState: {
						relatedTasks: this.task.relatedTasks,
					},
				},
				[TaskView.field.subTasks]: {
					ref: this.subTasksRef,
					newState: {
						subTasks: this.task.subTasks,
					},
				},
				[TaskView.field.comments]: {
					ref: this.commentsRef,
					newState: {
						commentsCount: (this.task.commentsCount - this.task.serviceCommentsCount),
						newCommentsCounter: this.getNewCommentsCounterData(),
					},
				},
			};

			if (!fields.length)
			{
				fields = Object.keys(fieldRefs);
			}
			fields = fields.filter(field => Object.keys(fieldRefs).includes(field));

			fields.forEach((field) => {
				const ref = fieldRefs[field].ref;
				if (ref)
				{
					ref.updateState(fieldRefs[field].newState);
				}
				else
				{
					console.error(`${field} ref is undefined`);
				}
			});

			if (fields.includes(TaskView.field.datePlanIs) && this.datePlanRef)
			{
				this.datePlanRef.animateBlock(this.isDatePlan);
			}
		}

		getImageUrl(imageUrl)
		{
			if (imageUrl.indexOf(currentDomain) !== 0)
			{
				imageUrl = imageUrl.replace(`${currentDomain}`, '');
				imageUrl = (imageUrl.indexOf('http') !== 0 ? `${currentDomain}${imageUrl}` : imageUrl);
			}

			return encodeURI(imageUrl);
		}
	}

	module.exports = {TaskView};
});
