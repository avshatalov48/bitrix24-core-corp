/**
 * @module tasks/enum
 */
jn.define('tasks/enum', (require, exports, module) => {
	const ViewMode = {
		LIST: 'LIST',
		KANBAN: 'KANBAN',
		PLANNER: 'PLANNER',
		DEADLINE: 'DEADLINE',
	};

	const WorkMode = {
		GROUP: 'G',
		USER: 'U',
		TIMELINE: 'P',
		ACTIVE_SPRINT: 'A',
	};

	const WorkModeByViewMode = {
		[ViewMode.LIST]: ViewMode.LIST,
		[ViewMode.KANBAN]: WorkMode.GROUP,
		[ViewMode.PLANNER]: WorkMode.USER,
		[ViewMode.DEADLINE]: WorkMode.TIMELINE,
	};

	const DeadlinePeriod = {
		PERIOD_OVERDUE: 'PERIOD1',
		PERIOD_TODAY: 'PERIOD2',
		PERIOD_THIS_WEEK: 'PERIOD3',
		PERIOD_NEXT_WEEK: 'PERIOD4',
		PERIOD_NO_DEADLINE: 'PERIOD5',
		PERIOD_OVER_TWO_WEEKS: 'PERIOD6',
	};

	const TaskUserOption = {
		MUTED: 1,
		PINNED: 2,
		PINNED_IN_GROUP: 3,
	};

	const TaskMark = {
		NONE: null,
		POSITIVE: 'P',
		NEGATIVE: 'N',
	};

	const TaskStatus = {
		PENDING: 2,
		IN_PROGRESS: 3,
		SUPPOSEDLY_COMPLETED: 4,
		COMPLETED: 5,
		DEFERRED: 6,
	};

	const TaskPriority = {
		HIGH: 2,
		NORMAL: 1,
	};

	const TaskRole = {
		ALL: 'view_all',
		RESPONSIBLE: 'view_role_responsible',
		ACCOMPLICE: 'view_role_accomplice',
		ORIGINATOR: 'view_role_originator',
		AUDITOR: 'view_role_auditor',
	};

	const TaskCounter = {
		GRAY: 'GRAY',
		ALERT: 'ALERT',
		SUCCESS: 'SUCCESS',
	};

	const TaskActionAccess = {
		UPDATE: 'update',
		UPDATE_CREATOR: 'updateCreator',
		UPDATE_RESPONSIBLE: 'updateResponsible',
		UPDATE_ACCOMPLICES: 'updateAccomplices',
		UPDATE_DEADLINE: 'updateDeadline',
		UPDATE_PROJECT: 'updateProject',
	};

	const TaskField = {
		TITLE: 'title',
		DESCRIPTION: 'description',
		PROJECT: 'project',
		FLOW: 'flow',
		PRIORITY: 'priority',
		TIME_ESTIMATE: 'timeEstimate',
		MARK: 'mark',
		STAGE: 'stage',
		CHECKLIST: 'checklist',
		RESULT: 'result',

		CREATOR: 'creator',
		RESPONSIBLE: 'responsible',
		ACCOMPLICES: 'accomplices',
		AUDITORS: 'auditors',

		FILES: 'files',
		UPLOADED_FILES: 'uploadedFiles',
		CRM: 'crm',
		TAGS: 'tags',
		SUBTASKS: 'subtasks',
		RELATED_TASKS: 'relatedTasks',
		USER_FIELDS: 'userFields',

		DEADLINE: 'deadline',
		DATE_PLAN: 'datePlan',
		START_DATE_PLAN: 'startDatePlan',
		END_DATE_PLAN: 'endDatePlan',

		ALLOW_CHANGE_DEADLINE: 'allowChangeDeadline',
		ALLOW_TIME_TRACKING: 'allowTimeTracking',
		ALLOW_TASK_CONTROL: 'allowTaskControl',
		IS_MATCH_WORK_TIME: 'isMatchWorkTime',
		IS_RESULT_REQUIRED: 'isResultRequired',
	};

	const TaskFieldActionAccess = {
		[TaskField.TITLE]: TaskActionAccess.UPDATE,
		[TaskField.DESCRIPTION]: TaskActionAccess.UPDATE,
		[TaskField.PROJECT]: TaskActionAccess.UPDATE_PROJECT,
		[TaskField.FLOW]: TaskActionAccess.UPDATE,
		[TaskField.RESULT]: TaskActionAccess.UPDATE,
		[TaskField.FILES]: TaskActionAccess.UPDATE,
		[TaskField.CHECKLIST]: TaskActionAccess.UPDATE,
		[TaskField.AUDITORS]: TaskActionAccess.UPDATE,
		[TaskField.TAGS]: TaskActionAccess.UPDATE,
		[TaskField.CRM]: TaskActionAccess.UPDATE,
		[TaskField.SUBTASKS]: TaskActionAccess.UPDATE,
		[TaskField.RELATED_TASKS]: TaskActionAccess.UPDATE,
		[TaskField.USER_FIELDS]: TaskActionAccess.UPDATE,

		[TaskField.CREATOR]: TaskActionAccess.UPDATE_CREATOR,
		[TaskField.RESPONSIBLE]: TaskActionAccess.UPDATE_RESPONSIBLE,
		[TaskField.ACCOMPLICES]: TaskActionAccess.UPDATE_ACCOMPLICES,
		[TaskField.DEADLINE]: TaskActionAccess.UPDATE_DEADLINE,
		[TaskField.DATE_PLAN]: TaskActionAccess.UPDATE,

		[TaskField.ALLOW_TIME_TRACKING]: TaskActionAccess.UPDATE,

		// [TaskField.PRIORITY]: 'update',
		// [TaskField.TIME_ESTIMATE]: 'update',
		// [TaskField.MARK]: 'update',
		// [TaskField.STAGE]: 'update',
		// [TaskField.UPLOADED_FILES]: 'update',
		// [TaskField.SUBTASKS]: 'update',
		// [TaskField.RELATED_TASKS]: 'update',
		//
		// [TaskField.START_DATE_PLAN]: 'update',
		// [TaskField.END_DATE_PLAN]: 'update',
		//
		// [TaskField.ALLOW_CHANGE_DEADLINE]: 'update',
		// [TaskField.ALLOW_TASK_CONTROL]: 'update',
		// [TaskField.IS_MATCH_WORK_TIME]: 'update',
		// [TaskField.IS_RESULT_REQUIRED]: 'update',
	};

	const PullCommand = {
		TASK_ADD: 'task_add',
		TASK_UPDATE: 'task_update',
		TASK_REMOVE: 'task_remove',
		TASK_VIEW: 'task_view',

		COMMENT_ADD: 'comment_add',
		COMMENT_DELETE: 'comment_delete',
		COMMENT_READ_ALL: 'comment_read_all',
		PROJECT_READ_ALL: 'project_read_all',

		TASK_RESULT_CREATE: 'task_result_create',
		TASK_RESULT_UPDATE: 'task_result_update',
		TASK_RESULT_DELETE: 'task_result_delete',

		TASK_TIMER_START: 'task_timer_start',
		TASK_TIMER_STOP: 'task_timer_stop',

		USER_OPTION_CHANGED: 'user_option_changed',
	};

	const TimerState = {
		OVERDUE: 'overdue',
		PAUSED: 'paused',
		RUNNING: 'running',
	};

	const FeatureId = {
		FLOW: 'tasks_flow',
		EFFICIENCY: 'tasks_efficiency',
		DELEGATING: 'tasks_delegating',
		ACCOMPLICE_AUDITOR: 'tasks_observers_participants',
		CRM: 'tasks_crm_integration',
		TIME_TRACKING: 'tasks_time_tracking',
		RESULT_REQUIREMENT: 'tasks_status_summary',
		TASK_CONTROL: 'tasks_control',
		WORK_TIME_MATCH: 'tasks_skip_weekends',
		USER_FIELDS: 'tasks_custom_fields',
		SEARCH: 'tasks_search',
	};

	const UserFieldType = {
		BOOLEAN: 'boolean',
		DATETIME: 'datetime',
		DOUBLE: 'double',
		STRING: 'string',
	};

	module.exports = {
		ViewMode,
		WorkMode,
		WorkModeByViewMode,
		DeadlinePeriod,
		FeatureId,
		PullCommand,
		TaskUserOption,
		TaskMark,
		TaskStatus,
		TaskPriority,
		TaskRole,
		TaskCounter,
		TaskActionAccess,
		TaskField,
		TaskFieldActionAccess,
		TimerState,
		UserFieldType,
	};
});
