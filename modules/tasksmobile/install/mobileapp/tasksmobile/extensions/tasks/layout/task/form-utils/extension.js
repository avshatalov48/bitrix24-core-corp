/**
 * @module tasks/layout/task/form-utils
 */
jn.define('tasks/layout/task/form-utils', (require, exports, module) => {
	const { mergeImmutable } = require('utils/object');
	const { Loc } = require('tasks/loc');
	const { Icon } = require('assets/icons');

	const makeProjectFieldConfig = (extra = {}) => mergeImmutable({
		provider: {
			context: 'TASKS_PROJECTLINK',
			options: {
				recentItemsLimit: 15,
				maxProjectsInRecentTab: 10,
				searchLimit: 20,
				shouldSelectProjectDates: true,
			},
			filters: [
				{
					id: 'tasks.projectDataFilter',
				},
			],
		},
		nonSelectableErrorText: Loc.getMessage('M_TASKS_DENIED_SELECT_PROJECT'),
		selectorTitle: Loc.getMessage('M_TASK_FORM_FIELD_PROJECT_TITLE'),
	}, extra);

	const makeAccomplicesFieldConfig = (extra = {}) => mergeImmutable({
		provider: {
			context: 'TASKS_MEMBER_SELECTOR_EDIT_accomplice',
			options: {
				recentItemsLimit: 10,
				maxUsersInRecentTab: 10,
				searchLimit: 20,
			},
			filters: [
				{
					id: 'tasks.userDataFilter',
					options: {
						role: 'A',
						groupId: extra.groupId,
					},
				},
			],
		},
		useLettersForEmptyAvatar: true,
		nonSelectableErrorText: Loc.getMessage('M_TASKS_DENIED_SELECT_USER_AS_RESPONSIBLE'),
		selectorTitle: Loc.getMessage('M_TASK_FORM_FIELD_ACCOMPLICES_TITLE'),
		textMultiple: Loc.getMessage('M_TASK_FORM_FIELD_ACCOMPLICES_TITLE_MULTI'),
	}, extra);

	const makeAuditorsFieldConfig = (extra = {}) => mergeImmutable({
		provider: {
			context: 'TASKS_MEMBER_SELECTOR_EDIT_auditor',
		},
		useLettersForEmptyAvatar: true,
		selectorTitle: Loc.getMessage('M_TASK_FORM_FIELD_AUDITORS_TITLE'),
		nonSelectableErrorText: Loc.getMessage('M_TASKS_DENIED_SELECT_USER_AS_RESPONSIBLE'),
		defaultLeftIcon: Icon.OBSERVER,
		textMultiple: Loc.getMessage('M_TASK_FORM_FIELD_AUDITORS_TITLE_MULTI'),
	}, extra);

	const makeTagsFieldConfig = (extra = {}) => mergeImmutable({
		enableCreation: true,
		closeAfterCreation: false,
		canUseRecent: false,
		selectorType: 'task_tag',
		provider: {
			context: 'TASKS_TAG',
		},
		selectorTitle: Loc.getMessage('M_TASKS_FIELDS_TAGS'),
		textMultiple: Loc.getMessage('M_TASKS_FIELDS_TAGS_MULTI'),
	}, extra);

	const makeCrmFieldConfig = (extra = {}) => mergeImmutable({
		isComplex: true,
		reloadEntityListFromProps: true,
		enableCreation: true,
		closeAfterCreation: false,
		canUseRecent: false,
		selectorType: 'crm-element',
		selectorTitle: Loc.getMessage('M_TASKS_FIELDS_CRM'),
		textMultiple: Loc.getMessage('M_TASKS_FIELDS_CRM_MULTI'),
		provider: {
			context: 'TASKS_CRM',
		},
	}, extra);

	const makeSubTasksFieldConfig = (extra = {}) => mergeImmutable({
		provider: {
			context: 'TASKS_SUBTASK_SELECTOR',
			options: {
				recentItemsLimit: 15,
				maxProjectsInRecentTab: 10,
				searchLimit: 20,
				parentId: extra.currentTaskId,
				excludeIds: [extra.currentTaskId],
			},
		},
		nonSelectableErrorText: Loc.getMessage('M_TASKS_DENIED_SELECT_TASK'),
		selectorTitle: Loc.getMessage('M_TASK_FORM_FIELD_SUBTASK_TITLE'),
		textMultiple: Loc.getMessage('M_TASK_FORM_FIELD_SUBTASK_TITLE_MULTI'),
		canUseRecent: false,
	}, extra);

	const makeRelatedTasksFieldConfig = (extra = {}) => mergeImmutable({
		provider: {
			context: 'TASKS_RELATED_TASK_SELECTOR',
			options: {
				recentItemsLimit: 15,
				maxProjectsInRecentTab: 10,
				searchLimit: 20,
				excludeIds: [extra.currentTaskId],
			},
		},
		nonSelectableErrorText: Loc.getMessage('M_TASKS_DENIED_SELECT_TASK'),
		selectorTitle: Loc.getMessage('M_TASK_FORM_FIELD_RELATED_TITLE'),
		textMultiple: Loc.getMessage('M_TASK_FORM_FIELD_RELATED_TITLE_MULTI'),
		canUseRecent: false,
	}, extra);

	module.exports = {
		makeProjectFieldConfig,
		makeAccomplicesFieldConfig,
		makeAuditorsFieldConfig,
		makeTagsFieldConfig,
		makeCrmFieldConfig,
		makeSubTasksFieldConfig,
		makeRelatedTasksFieldConfig,
	};
});
