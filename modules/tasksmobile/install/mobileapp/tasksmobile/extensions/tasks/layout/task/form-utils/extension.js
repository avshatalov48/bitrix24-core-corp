/**
 * @module tasks/layout/task/form-utils
 */
jn.define('tasks/layout/task/form-utils', (require, exports, module) => {
	const { mergeImmutable } = require('utils/object');
	const { useCallback } = require('utils/function');
	const { Loc } = require('tasks/loc');
	const { Icon } = require('assets/icons');
	const store = require('statemanager/redux/store');
	const { selectGroupById } = require('tasks/statemanager/redux/slices/groups');

	const makeProjectFieldConfig = (extra = {}) => mergeImmutable({
		provider: {
			context: 'TASKS_PROJECTLINK',
			options: {
				recentItemsLimit: 15,
				maxProjectsInRecentTab: 10,
				searchLimit: 20,
				shouldSelectProjectDates: true,
				shouldSelectDialogId: true,
			},
			filters: [
				{
					id: 'tasks.projectDataFilter',
				},
			],
		},
		getNonSelectableErrorText: useCallback(() => Loc.getMessage('M_TASKS_DENIED_SELECT_PROJECT')),
		selectorTitle: Loc.getMessage('M_TASK_FORM_FIELD_PROJECT_TITLE'),
	}, extra);

	const makeAccomplicesFieldConfig = (extra = {}) => mergeImmutable({
		enableCreation: !(env.isCollaber || env.extranet),
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
		getNonSelectableErrorText: useCallback((item) => {
			const isCollaber = item.params.entityType === 'collaber';
			const isCollab = selectGroupById(store.getState(), extra.groupId)?.isCollab;

			if (isCollaber && !isCollab)
			{
				return Loc.getMessage('M_TASKS_DENIED_SELECT_COLLABER_WITHOUT_COLLAB');
			}

			return Loc.getMessage('M_TASKS_DENIED_SELECT_USER_AS_RESPONSIBLE');
		}),
		selectorTitle: Loc.getMessage('M_TASK_FORM_FIELD_ACCOMPLICES_TITLE'),
		textMultiple: Loc.getMessage('M_TASK_FORM_FIELD_ACCOMPLICES_TITLE_MULTI'),
	}, extra);

	const makeAuditorsFieldConfig = (extra = {}) => mergeImmutable({
		enableCreation: !(env.isCollaber || env.extranet),
		provider: {
			context: 'TASKS_MEMBER_SELECTOR_EDIT_auditor',
		},
		useLettersForEmptyAvatar: true,
		selectorTitle: Loc.getMessage('M_TASK_FORM_FIELD_AUDITORS_TITLE'),
		getNonSelectableErrorText: useCallback(() => {
			return Loc.getMessage('M_TASKS_DENIED_SELECT_USER_AS_RESPONSIBLE');
		}),
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
		getNonSelectableErrorText: useCallback(() => Loc.getMessage('M_TASKS_DENIED_SELECT_TASK')),
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
		getNonSelectableErrorText: useCallback(() => Loc.getMessage('M_TASKS_DENIED_SELECT_TASK')),
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
