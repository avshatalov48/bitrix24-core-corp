/**
 * @module tasks/layout/task/view-new/ui/task-edit-form
 */
jn.define('tasks/layout/task/view-new/ui/task-edit-form', (require, exports, module) => {
	const { Color, Component, Indent } = require('tokens');
	const { Loc } = require('tasks/loc');
	const { Form, CompactMode } = require('layout/ui/form');
	const { Moment } = require('utils/date');
	const { useCallback } = require('utils/function');
	const { Formatter } = require('tasks/layout/task/view-new/services/deadline-format');
	const {
		makeProjectFieldConfig,
		makeAccomplicesFieldConfig,
		makeAuditorsFieldConfig,
		makeTagsFieldConfig,
		makeCrmFieldConfig,
		makeSubTasksFieldConfig,
		makeRelatedTasksFieldConfig,
	} = require('tasks/layout/task/form-utils');
	const { TaskField: Field, TaskFieldActionAccess, FeatureId, ViewMode } = require('tasks/enum');
	const {
		getFieldRestrictionPolicy,
		getFieldShowRestrictionCallback,
	} = require('tasks/fields/restriction');
	const { getFeatureRestriction } = require('tariff-plan-restriction');

	const { connect } = require('statemanager/redux/connect');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const {
		selectByTaskIdOrGuid,
		selectActions,
		selectIsResponsible,
		selectIsAccomplice,
		selectIsDeferred,
		selectIsCompleted,
		selectIsExpired,
		selectSubTasksById,
		selectRelatedTasksById,
		selectTimerState,
		setTimeElapsed,
	} = require('tasks/statemanager/redux/slices/tasks');
	const { selectGroupById } = require('tasks/statemanager/redux/slices/groups');
	const { selectById } = require('tasks/statemanager/redux/slices/flows');
	const { usersSelector } = require('statemanager/redux/slices/users');
	const {
		selectTaskStageByTaskIdOrGuid,
	} = require('tasks/statemanager/redux/slices/tasks-stages');
	const {
		getUniqId,
		selectCanMoveStage,
	} = require('tasks/statemanager/redux/slices/kanban-settings');

	const { TextAreaField: Title } = require('layout/ui/fields/textarea/theme/air-title');
	const { TextAreaField: Description } = require('layout/ui/fields/textarea/theme/air-description');
	const { DeadlineField } = require('tasks/layout/fields/deadline/theme/air');
	const { UserField } = require('layout/ui/fields/user/theme/air');
	const { UserField: UserFieldCompact } = require('layout/ui/fields/user/theme/air-compact');
	const { ProjectField } = require('layout/ui/fields/project/theme/air');
	const { ProjectField: ProjectFieldCompact } = require('layout/ui/fields/project/theme/air-compact');
	const { TagField } = require('layout/ui/fields/tag/theme/air');
	const { TagField: TagFieldCompact } = require('layout/ui/fields/tag/theme/air-compact');
	const { CrmElementField } = require('layout/ui/fields/crm-element/theme/air');
	const { CrmElementField: CrmElementFieldCompact } = require('layout/ui/fields/crm-element/theme/air-compact');
	const { FileWithBackgroundAttachField } = require('layout/ui/fields/file-with-background-attach/theme/air');
	const {
		FileWithBackgroundAttachField: FileWithBackgroundAttachFieldCompact,
	} = require('layout/ui/fields/file-with-background-attach/theme/air-compact');
	const { ChecklistField } = require('tasks/layout/checklist/preview');
	const { ChecklistField: ChecklistFieldCompact } = require('tasks/layout/fields/checklist/theme/air-compact');

	const { TaskFlowField } = require('tasks/layout/fields/flow/theme/air');
	const { TaskFlowField: TaskFlowFieldCompact } = require('tasks/layout/fields/flow/theme/air-compact');
	const { TimeTrackingField } = require('tasks/layout/fields/time-tracking');
	const { TimeTrackingField: TimeTrackingFieldCompact } = require('tasks/layout/fields/time-tracking/theme/air-compact');
	const { ActionButtonsView } = require('tasks/layout/task/view-new/ui/action-buttons');
	const { ActionId, ActionMeta } = require('tasks/layout/action-menu/actions');

	const { SubTasksField } = require('tasks/layout/fields/subtask/theme/air');
	const { SubTasksField: SubTasksFieldCompact } = require('tasks/layout/fields/subtask/theme/air-compact');

	const { RelatedTasksField } = require('tasks/layout/fields/related-task/theme/air');
	const { RelatedTasksField: RelatedTasksFieldCompact } = require('tasks/layout/fields/related-task/theme/air-compact');
	const { TasksStageSelector } = require('tasks/layout/fields/stage-selector');

	const { DatePlanField } = require('tasks/layout/fields/date-plan/theme/air');
	const { DatePlanField: DatePlanFieldCompact } = require('tasks/layout/fields/date-plan/theme/air-compact');

	const { TaskResultField } = require('tasks/layout/fields/result/theme/air');
	const { TaskResultField: TaskResultFieldCompact } = require('tasks/layout/fields/result/theme/air-compact');
	const { AnalyticsEvent } = require('analytics');

	const { UserFieldsField } = require('tasks/layout/fields/user-fields/theme/air');
	const { UserFieldsField: UserFieldsFieldCompact } = require('tasks/layout/fields/user-fields/theme/air-compact');

	const deadlineFormatter = (ts) => Formatter.format(Moment.createFromTimestamp(ts));

	const TaskEditForm = (props) => {
		const {
			task,
			actions,
			project,
			flow,
			creator,
			responsible,
			auditors,
			accomplices,
			ref,
			testId,
			parentWidget,
			onChange,
			onBlur,
			scrollableProvider,
			renderAfterCompactBar,
			shouldShowCompactButtons,
			renderBeforeCompactFields,
			renderStatus,
			onChangeProjectField,
			onChangeSubTaskField,
			onChangeRelatedTaskField,
			onChangeUserField,
			onChangeDeadlineField,
			userId,
			deadlineColor,
			checklistLoading,
			checklistController,
			onFieldContentClick,
			timerState,
			analyticsLabel,
			view,
			canMoveStage,
			kanbanOwnerId,
			isStageSelectorInitiallyHidden,
		} = props;

		const taskCreateButtonAnalytics = {
			...analyticsLabel,
			c_sub_section: 'task_card',
			c_element: 'create_button',
		};

		return new Form({
			ref,
			testId,
			parentWidget,
			onChange,
			onBlur,
			scrollableProvider,
			renderAfterCompactBar,
			renderBeforeCompactFields: shouldShowCompactButtons ? renderBeforeCompactFields : null,
			useState: false,
			style: FormStyle,
			compactMode: CompactMode.FILL_COMPACT_AND_KEEP,
			compactOrder: [
				'...',
				Field.RESULT,
				Field.TAGS,
				Field.CRM,
				Field.ALLOW_TIME_TRACKING,
				Field.DATE_PLAN,
				Field.RELATED_TASKS,
			],
			primaryFields: [
				{
					factory: Title,
					props: {
						id: Field.TITLE,
						value: task.name,
						placeholder: Loc.getMessage('M_TASK_DETAILS_FIELD_TITLE_PLACEHOLDER'),
						readOnly: !actions[TaskFieldActionAccess[Field.TITLE]],
						required: true,
						onContentClick: onFieldContentClick,
					},
				},
				{
					factory: Description,
					props: {
						id: Field.DESCRIPTION,
						value: task.description,
						readOnly: !actions[TaskFieldActionAccess[Field.DESCRIPTION]],
						required: false,
						title: Loc.getMessage('M_TASK_DETAILS_FIELD_DESCRIPTION_TITLE'),
						placeholder: Loc.getMessage('M_TASK_DETAILS_FIELD_DESCRIPTION_PLACEHOLDER'),
						onContentClick: onFieldContentClick,
						useBBCodeEditor: true,
						config: {
							fileField: {
								value: task.files,
							},
							allowFiles: false,
							autoFocus: false,
						},
					},
				},
				{
					factory: UserField,
					props: {
						id: Field.CREATOR,
						value: task.creator,
						readOnly: !actions[TaskFieldActionAccess[Field.CREATOR]] && !actions.update,
						required: true,
						title: Loc.getMessage('M_TASK_DETAILS_FIELD_CREATOR_TITLE'),
						showTitle: false,
						useState: false,
						onContentClick: onFieldContentClick,
						config: {
							enableCreation: !(env.isCollaber || env.extranet),
							provider: {
								context: 'TASKS_MEMBER_SELECTOR_EDIT_originator',
							},
							items: [creator].filter(Boolean),
							canUnselectLast: false,
							useLettersForEmptyAvatar: true,
							selectorTitle: Loc.getMessage('M_TASK_DETAILS_FIELD_CREATOR_TITLE'),
							styles: {
								airContainer: {
									marginTop: Indent.XL3.toNumber(),
								},
							},
						},
						onChange: onChangeUserField,
						analytics: getAnalyticsForUserField(analyticsLabel),
					},
				},
				{
					factory: UserField,
					props: {
						id: Field.RESPONSIBLE,
						value: task.responsible,
						readOnly: (
							!actions[TaskFieldActionAccess[Field.RESPONSIBLE]]
							&& (!actions.delegate || getFeatureRestriction(FeatureId.DELEGATING).isRestricted())
						),
						required: true,
						title: Loc.getMessage('M_TASK_DETAILS_FIELD_RESPONSIBLE_TITLE'),
						showTitle: false,
						useState: false,
						onContentClick: onFieldContentClick,
						config: {
							enableCreation: !(env.isCollaber || env.extranet),
							provider: {
								context: 'TASKS_MEMBER_SELECTOR_EDIT_responsible',
								options: {
									recentItemsLimit: 10,
									maxUsersInRecentTab: 10,
									searchLimit: 20,
								},
								filters: [
									{
										id: 'tasks.userDataFilter',
										options: {
											role: 'R',
											groupId: task.groupId,
										},
									},
								],
							},
							items: [responsible].filter(Boolean),
							canUnselectLast: false,
							useLettersForEmptyAvatar: true,
							getNonSelectableErrorText: useCallback((item) => {
								const isCollaber = item.params.entityType === 'collaber';
								const isCollab = selectGroupById(store.getState(), task.groupId)?.isCollab;

								if (isCollaber && !isCollab)
								{
									return Loc.getMessage('M_TASKS_DENIED_SELECT_COLLABER_WITHOUT_COLLAB');
								}

								return (
									actions.updateResponsible
										? Loc.getMessage('M_TASKS_DENIED_SELECT_USER_AS_RESPONSIBLE')
										: Loc.getMessage('M_TASKS_DENIED_DELEGATE_USER_AS_RESPONSIBLE')
								);
							}),
							selectorTitle: (
								actions.updateResponsible
									? Loc.getMessage('M_TASK_DETAILS_FIELD_RESPONSIBLE_TITLE')
									: Loc.getMessage('M_TASK_DETAILS_FIELD_DELEGATE_TITLE')
							),
						},
						onChange: onChangeUserField,
						analytics: getAnalyticsForUserField(analyticsLabel),
					},
				},
				{
					factory: DeadlineField,
					props: {
						id: Field.DEADLINE,
						value: task.deadline,
						readOnly: !actions[TaskFieldActionAccess[Field.DEADLINE]],
						required: false,
						onContentClick: onFieldContentClick,
						onChange: onChangeDeadlineField,
						config: {
							dateFormatter: deadlineFormatter,
							color: deadlineColor,
							renderAfter: renderStatus,
						},
					},
				},
				{
					factory: TasksStageSelector,
					props: {
						id: Field.STAGE,
						config: {
							parentWidget,
							isReversed: view === ViewMode.DEADLINE,
							deepMergeStyles: {
								wrapper: {
									paddingTop: 0,
									paddingBottom: 0,
								},
								readOnlyWrapper: {
									paddingTop: 0,
									paddingBottom: 0,
								},
							},
							initiallyHidden: isStageSelectorInitiallyHidden,
						},
						notifyAboutReadOnlyStatus: onFieldContentClick,
						readOnly: view === ViewMode.DEADLINE
							? !(canMoveStage && actions[TaskFieldActionAccess[Field.UPDATE_DEADLINE]])
							: !canMoveStage,
						value: task.stageId,
						view,
						projectId: task.groupId,
						userId: kanbanOwnerId,
						taskId: task.id,
						showTitle: false,
					},
				},
				task.allowTimeTracking && {
					factory: TimeTrackingField,
					props: {
						id: `${Field.ALLOW_TIME_TRACKING}Timer`,
						value: {
							timerState,
							allowTimeTracking: task.allowTimeTracking,
							timeElapsed: task.timeElapsed,
							timeEstimate: task.timeEstimate,
							isTimerRunningForCurrentUser: task.isTimerRunningForCurrentUser,
							taskId: task.id,
						},
						onToggleTimer,
						onTimeOver: useCallback((timeElapsed) => {
							dispatch(setTimeElapsed({
								timeElapsed,
								taskId: task.id,
							}));
						}),
						required: false,
						readOnly: !actions[TaskFieldActionAccess[Field.ALLOW_TIME_TRACKING]],
					},
				},
			].filter(Boolean),
			secondaryFields: [
				{
					factory: TaskResultField,
					props: {
						id: Field.RESULT,
						taskId: task.id,
						resultsCount: task.resultsCount,
						readOnly: !selectIsResponsible(task) && !selectIsAccomplice(task),
						onContentClick: onFieldContentClick,
					},
					compact: TaskResultFieldCompact,
				},
				{
					factory: FileWithBackgroundAttachField,
					props: {
						id: Field.FILES,
						value: [...task.files, ...task.uploadedFiles],
						readOnly: !actions[TaskFieldActionAccess[Field.FILES]],
						required: false,
						title: Loc.getMessage('M_TASKS_FIELDS_FILES'),
						showTitle: true,
						multiple: true,
						onContentClick: onFieldContentClick,
						config: {
							textMultiple: Loc.getMessage('M_TASKS_FIELDS_FILES_MULTI'),
							listenCacheChanges: false,
							attachToEntityController: {
								entityId: `TASK_${task.id}`,
								actionName: 'tasksmobile.Task.attachUploadedFiles',
								fieldName: 'fileId',
								actionConfigData: {
									taskId: task.id,
								},
								entityUrl: `${currentDomain}/company/personal/user/${env.userId}/tasks/task/view/${task.id}/`,
							},
							uploadController: {
								endpoint: 'tasks.FileUploader.TaskController',
								options: {
									taskId: task.id,
								},
							},
							disk: {
								isDiskModuleInstalled: true,
								isWebDavModuleInstalled: true,
								fileAttachPath: `/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${userId}`,
							},
							enableCreation: true,
							closeAfterCreation: false,
							canUseRecent: false,
							selectorType: 'file',
						},
						showFilesName: true,
					},
					compact: {
						factory: FileWithBackgroundAttachFieldCompact,
						extraProps: {
							config: {
								listenCacheChanges: true,
							},
						},
					},
				},
				{
					factory: ChecklistField,
					props: {
						id: Field.CHECKLIST,
						value: task.checklist,
						readOnly: !actions[TaskFieldActionAccess[Field.CHECKLIST]],
						onContentClick: onFieldContentClick,
						multiple: true,
						config: {
							checklistController,
							initialState: task.checklistDetails,
							taskId: task.id,
						},
						loading: checklistLoading,
					},
					compact: ChecklistFieldCompact,
				},
				{
					factory: ProjectField,
					props: {
						id: Field.PROJECT,
						value: task.groupId,
						title: (
							project?.isCollab
								? Loc.getMessage('M_TASK_FORM_FIELD_PROJECT_COLLAB_TITLE')
								: Loc.getMessage('M_TASK_FORM_FIELD_PROJECT_TITLE')
						),
						showTitle: true,
						readOnly: !actions[TaskFieldActionAccess[Field.PROJECT]],
						useState: false,
						onContentClick: onFieldContentClick,
						config: makeProjectFieldConfig({
							items: [project].filter(Boolean),
							canUnselectLast: !env.isCollaber,
						}),
						restrictionPolicy: getFieldRestrictionPolicy(Field.PROJECT),
						showRestrictionCallback: getFieldShowRestrictionCallback(Field.PROJECT, parentWidget),
						onChange: onChangeProjectField,
					},
					compact: ProjectFieldCompact,
				},
				{
					factory: TaskFlowField,
					props: {
						id: Field.FLOW,
						value: task.flowId,
						title: Loc.getMessage('M_TASKS_FLOW'),
						showTitle: true,
						readOnly: !actions[TaskFieldActionAccess[Field.FLOW]] || env.isCollaber,
						useState: false,
						onContentClick: onFieldContentClick,
						config: {
							items: [flow].filter(Boolean),
						},
						restrictionPolicy: getFieldRestrictionPolicy(Field.FLOW),
						showRestrictionCallback: getFieldShowRestrictionCallback(Field.FLOW, parentWidget),
					},
					compact: TaskFlowFieldCompact,
				},
				{
					factory: UserField,
					props: {
						id: Field.ACCOMPLICES,
						value: task.accomplices,
						readOnly: !actions[TaskFieldActionAccess[Field.ACCOMPLICES]],
						required: false,
						multiple: true,
						title: Loc.getMessage('M_TASK_FORM_FIELD_ACCOMPLICES_TITLE'),
						showTitle: true,
						useState: false,
						onContentClick: onFieldContentClick,
						config: makeAccomplicesFieldConfig({
							items: accomplices.filter(Boolean),
							groupId: task.groupId,
						}),
						restrictionPolicy: getFieldRestrictionPolicy(Field.ACCOMPLICES),
						showRestrictionCallback: getFieldShowRestrictionCallback(Field.ACCOMPLICES, parentWidget),
						onChange: onChangeUserField,
						analytics: getAnalyticsForUserField(analyticsLabel),
					},
					compact: UserFieldCompact,
				},
				{
					factory: UserField,
					props: {
						id: Field.AUDITORS,
						value: task.auditors,
						readOnly: !actions[TaskFieldActionAccess[Field.AUDITORS]],
						required: false,
						multiple: true,
						title: Loc.getMessage('M_TASK_FORM_FIELD_AUDITORS_TITLE'),
						showTitle: true,
						useState: false,
						onContentClick: onFieldContentClick,
						config: makeAuditorsFieldConfig({
							items: auditors.filter(Boolean),
						}),
						restrictionPolicy: getFieldRestrictionPolicy(Field.AUDITORS),
						showRestrictionCallback: getFieldShowRestrictionCallback(Field.AUDITORS, parentWidget),
						onChange: onChangeUserField,
						analytics: getAnalyticsForUserField(analyticsLabel),
					},
					compact: UserFieldCompact,
				},
				{
					factory: SubTasksField,
					props: {
						id: Field.SUBTASKS,
						title: Loc.getMessage('M_TASK_FORM_FIELD_SUBTASK_TITLE'),
						value: task.subTasks.map((item) => item.id),
						showTitle: true,
						readOnly: !actions[TaskFieldActionAccess[Field.SUBTASKS]],
						multiple: true,
						useState: false,
						onContentClick: onFieldContentClick,
						onChange: onChangeSubTaskField,
						config: makeSubTasksFieldConfig({
							items: task.subTasks.filter(Boolean),
							currentTaskId: task.id,
							userId,
							groupId: task.groupId,
							group: [project].filter(Boolean).find((item) => item.id === task.groupId),
						}),
						analyticsLabel: taskCreateButtonAnalytics,
					},
					compact: SubTasksFieldCompact,
				},
				{
					factory: TagField,
					props: {
						id: Field.TAGS,
						title: Loc.getMessage('M_TASKS_FIELDS_TAGS'),
						value: Object.values(task.tags).map((item) => item.id),
						showTitle: true,
						readOnly: !actions[TaskFieldActionAccess[Field.TAGS]],
						multiple: true,
						useState: false,
						onContentClick: onFieldContentClick,
						config: makeTagsFieldConfig({
							items: Object.values(task.tags).map((item) => ({
								id: item.id,
								title: item.name,
								type: 'task-tag',
							})),
							provider: {
								options: {
									// todo: taskId should work correctly when its guid string
									taskId: task.id,
									groupId: task.groupId,
								},
							},
						}),
					},
					compact: TagFieldCompact,
				},
				task.userFieldNames.length > 0 && {
					factory: UserFieldsField,
					props: {
						id: Field.USER_FIELDS,
						taskId: task.id,
						areUserFieldsLoaded: task.areUserFieldsLoaded,
						userFields: task.userFields.filter(Boolean),
						readOnly: !actions[TaskFieldActionAccess[Field.USER_FIELDS]],
						required: false,
						onContentClick: onFieldContentClick,
					},
					compact: UserFieldsFieldCompact,
				},
				{
					factory: CrmElementField,
					props: {
						id: Field.CRM,
						title: Loc.getMessage('M_TASKS_FIELDS_CRM'),
						value: task.crm.map((item) => item.id),
						showTitle: true,
						readOnly: !actions[TaskFieldActionAccess[Field.CRM]],
						multiple: true,
						useState: false,
						onContentClick: onFieldContentClick,
						showHiddenEntities: false,
						config: makeCrmFieldConfig({
							items: task.crm,
							provider: {
								options: {
									// todo: taskId should work correctly when its guid string
									taskId: task.id,
									groupId: task.groupId,
								},
							},
						}),
						restrictionPolicy: getFieldRestrictionPolicy(Field.CRM),
						showRestrictionCallback: getFieldShowRestrictionCallback(Field.CRM, parentWidget),
					},
					compact: CrmElementFieldCompact,
				},
				{
					factory: RelatedTasksField,
					props: {
						id: Field.RELATED_TASKS,
						title: Loc.getMessage('M_TASK_FORM_FIELD_RELATED_TITLE'),
						value: task.relatedTasks.map((item) => item.id),
						showTitle: true,
						readOnly: !actions[TaskFieldActionAccess[Field.RELATED_TASKS]],
						multiple: true,
						useState: false,
						onChange: onChangeRelatedTaskField,
						onContentClick: onFieldContentClick,
						config: makeRelatedTasksFieldConfig({
							items: task.relatedTasks.filter(Boolean),
							currentTaskId: task.id,
							userId,
							groupId: task.groupId,
							group: [project].filter(Boolean).find((item) => item.id === task.groupId),
						}),
						analyticsLabel: taskCreateButtonAnalytics,
					},
					compact: RelatedTasksFieldCompact,
				},
				{
					factory: TimeTrackingFieldCompact,
					props: {
						id: Field.ALLOW_TIME_TRACKING,
						value: {
							timerState,
							allowTimeTracking: task.allowTimeTracking,
							timeElapsed: task.timeElapsed,
							timeEstimate: task.timeEstimate,
							isTimerRunningForCurrentUser: task.isTimerRunningForCurrentUser,
						},
						required: false,
						readOnly: !actions[TaskFieldActionAccess[Field.ALLOW_TIME_TRACKING]],
					},
					compact: {
						factory: TimeTrackingFieldCompact,
						mode: CompactMode.ONLY,
					},
				},
				{
					factory: DatePlanField,
					props: {
						id: Field.DATE_PLAN,
						taskId: task.id,
						readOnly: !actions[TaskFieldActionAccess[Field.DATE_PLAN]],
						onContentClick: onFieldContentClick,
						startDatePlan: task.startDatePlan,
						endDatePlan: task.endDatePlan,
						groupId: task.groupId,
						config: {},
					},
					compact: DatePlanFieldCompact,
				},
			].filter(Boolean),
		});
	};

	const getAnalyticsForUserField = (analyticsLabel) => {
		const analytics = new AnalyticsEvent();
		if (analyticsLabel)
		{
			analytics.setSection(new AnalyticsEvent(analyticsLabel).getSection());
		}

		return analytics;
	};

	const FormStyle = {
		primaryContainer: {
			backgroundColor: Color.bgContentPrimary.toHex(),
			paddingTop: 10,
		},
		primaryField: (field) => ({
			paddingHorizontal: field.getId() === Field.STAGE ? 0 : Component.areaPaddingLr.toNumber(),
		}),
		secondaryContainer: {
			marginTop: Indent.XL2.toNumber(),
			paddingHorizontal: Component.areaPaddingLr.toNumber(),
		},
		secondaryField: (field) => {
			if (field.getId() === Field.RESULT || field.getId() === Field.CHECKLIST)
			{
				return {
					paddingLeft: 0,
					paddingRight: 0,
					paddingTop: 0,
					paddingBottom: 0,
					marginBottom: Indent.M.getValue(),
				};
			}

			return {
				marginBottom: Indent.M.getValue(),
			};
		},
		compactContainer: {
			backgroundColor: Color.bgContentPrimary.toHex(),
		},
		compactInnerContainer: {
			paddingHorizontal: Component.areaPaddingLr.toNumber(),
		},
	};

	const selectMappedGroupById = (state, id) => {
		const group = selectGroupById(state, id);

		return group ? {
			id: group.id,
			title: group.name,
			imageUrl: group.image,
			isCollab: group.isCollab,
			dialogId: group.additionalData.DIALOG_ID,
		} : undefined;
	};

	const selectMappedFlowById = (state, id) => {
		const flow = selectById(state, id);

		return flow ? {
			id: flow.id,
			title: flow.name,
		} : undefined;
	};

	const selectMappedUserById = (state, id) => {
		const user = usersSelector.selectById(state, id);

		return user ? {
			id: user.id,
			imageUrl: user.avatarSize100,
			title: user.fullName,
			customData: {
				position: user.workPosition,
			},
		} : undefined;
	};

	const getDeadlineColor = (task) => {
		if (selectIsDeferred(task) || selectIsCompleted(task))
		{
			return Color.base5;
		}

		if (selectIsExpired(task))
		{
			return Color.accentMainAlert;
		}

		return null;
	};

	const onToggleTimer = ({ isTimerRunningForCurrentUser, taskId, layout }) => {
		const actionId = isTimerRunningForCurrentUser ? ActionId.PAUSE_TIMER : ActionId.START_TIMER;
		const { handleAction } = ActionMeta[actionId];

		handleAction({ taskId, layout });
	};

	const mapStateToProps = (state, ownProps) => {
		const taskId = ownProps.id;
		const task = selectByTaskIdOrGuid(state, taskId);
		const actions = selectActions(task);

		const shouldShowCompactButtons = ActionButtonsView.hasAllowedActions(task);
		const deadlineColor = getDeadlineColor(task);

		const stageData = selectTaskStageByTaskIdOrGuid(
			state,
			task.id,
			task.guid,
			ownProps.view,
			ownProps.kanbanOwnerId,
		);

		const canMoveStage = selectCanMoveStage(state, getUniqId(
			ownProps.view,
			task.groupId,
			ownProps.kanbanOwnerId,
		));

		return {
			// !!! add here only props which trigger render
			task: {
				id: task.id,
				name: task.name,
				description: task.description,
				creator: task.creator,
				responsible: task.responsible,
				deadline: task.deadline,
				files: task.files,
				uploadedFiles: task.uploadedFiles,
				checklist: task.checklist,
				checklistDetails: task.checklistDetails,
				groupId: task.groupId,
				flowId: task.flowId,
				accomplices: task.accomplices,
				auditors: task.auditors,
				tags: task.tags,
				crm: task.crm,
				resultsCount: task.resultsCount,
				subTasks: selectSubTasksById(state, task.id),
				relatedTasks: selectRelatedTasksById(state, task.id),
				parentId: task.parentId,
				allowTimeTracking: task.allowTimeTracking,
				timeElapsed: task.timeElapsed,
				timeEstimate: task.timeEstimate,
				isTimerRunningForCurrentUser: task.isTimerRunningForCurrentUser,
				startDatePlan: task.startDatePlan,
				endDatePlan: task.endDatePlan,
				stageId: stageData?.stageId,
				areUserFieldsLoaded: task.areUserFieldsLoaded,
				userFieldNames: task.userFieldNames,
				userFields: task.areUserFieldsLoaded ? task.userFieldNames.map((name) => task[name]) : [],
			},
			actions,
			shouldShowCompactButtons,
			deadlineColor,
			project: selectMappedGroupById(state, task.groupId),
			flow: selectMappedFlowById(state, task.flowId),
			creator: selectMappedUserById(state, task.creator),
			responsible: selectMappedUserById(state, task.responsible),
			auditors: task.auditors.map((id) => selectMappedUserById(state, id)),
			accomplices: task.accomplices.map((id) => selectMappedUserById(state, id)),
			timerState: selectTimerState(task),
			view: ownProps.view,
			canMoveStage,
		};
	};

	module.exports = {
		TaskEditForm: connect(mapStateToProps)(TaskEditForm),
	};
});
