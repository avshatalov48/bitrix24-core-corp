/**
 * @module bizproc/task/task-constants
 */
jn.define('bizproc/task/task-constants', (require, exports, module) => {
	const { Loc } = require('loc');

	const TaskStatus = Object.freeze({
		RUNNING: 0,
		COMPLETE_YES: 1,
		COMPLETE_NO: 2,
		COMPLETE_OK: 3,
		TIMEOUT: 4,
		COMPLETE_CANCEL: 5,
	});

	const TaskUserStatus = Object.freeze({
		WAITING: 0,
		YES: 1,
		NO: 2,
		OK: 3,
		CANCEL: 4,
		isDecline: (status) => {
			return status === TaskUserStatus.NO || status === TaskUserStatus.CANCEL;
		},
	});

	const TaskDelegationType = Object.freeze({
		SUBORDINATE: 0,
		ALL_EMPLOYEES: 1,
		NONE: 2,
	});

	const TaskButtonName = Object.freeze({
		APPROVE: 'approve',
		NON_APPROVE: 'nonapprove',
		REVIEW: 'review',
		CANCEL: 'cancel',
		COMPLETE: 'complete',
		getDefaultText: (name) => {
			switch (name)
			{
				case TaskButtonName.APPROVE:
					return Loc.getMessage('MBP_TASK_TASK_CONSTANTS_TASK_BUTTON_NAME_APPROVE');
				case TaskButtonName.NON_APPROVE:
					return Loc.getMessage('MBP_TASK_TASK_CONSTANTS_TASK_BUTTON_NAME_NON_APPROVE');
				case TaskButtonName.REVIEW:
					return Loc.getMessage('MBP_TASK_TASK_CONSTANTS_TASK_BUTTON_NAME_REVIEW');
				case TaskButtonName.CANCEL:
					return Loc.getMessage('MBP_TASK_TASK_CONSTANTS_TASK_BUTTON_NAME_CANCEL');
				case TaskButtonName.COMPLETE:
					return Loc.getMessage('MBP_TASK_TASK_CONSTANTS_TASK_BUTTON_NAME_COMPLETE');
				default:
					return '';
			}
		},
	});

	const DefaultButton = Object.freeze({
		APPROVE: {
			TARGET_USER_STATUS: TaskUserStatus.YES,
			NAME: TaskButtonName.APPROVE,
			TEXT: TaskButtonName.getDefaultText(TaskButtonName.APPROVE),
		},
		NON_APPROVE: {
			TARGET_USER_STATUS: TaskUserStatus.NO,
			NAME: TaskButtonName.NON_APPROVE,
			TEXT: TaskButtonName.getDefaultText(TaskButtonName.NON_APPROVE),
		},
		REVIEW: {
			TARGET_USER_STATUS: TaskUserStatus.OK,
			NAME: TaskButtonName.REVIEW,
			TEXT: TaskButtonName.getDefaultText(TaskButtonName.REVIEW),
		},
		CANCEL: {
			TARGET_USER_STATUS: TaskUserStatus.CANCEL,
			NAME: TaskButtonName.CANCEL,
			TEXT: TaskButtonName.getDefaultText(TaskButtonName.CANCEL),
		},
		COMPLETE: {
			TARGET_USER_STATUS: TaskUserStatus.OK,
			NAME: TaskButtonName.COMPLETE,
			TEXT: TaskButtonName.getDefaultText(TaskButtonName.COMPLETE),
		},
	});

	const TaskErrorCode = Object.freeze({
		ALREADY_DONE: 'TASK_ALREADY_DONE',
		USER_NOT_MEMBER: 'TASK_USER_NOT_MEMBER',
		NOT_FOUND: 'TASK_NOT_FOUND',
		isTaskNotFoundErrorCode: (code) => {
			return [TaskErrorCode.ALREADY_DONE, TaskErrorCode.USER_NOT_MEMBER, TaskErrorCode.NOT_FOUND].includes(code);
		},
	});

	module.exports = { TaskStatus, TaskUserStatus, TaskDelegationType, TaskButtonName, DefaultButton, TaskErrorCode };
});
