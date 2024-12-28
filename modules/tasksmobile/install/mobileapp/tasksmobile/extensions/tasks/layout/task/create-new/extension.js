/**
 * @module tasks/layout/task/create-new
 */
jn.define('tasks/layout/task/create-new', (require, exports, module) => {
	// eslint-disable-next-line no-undef
	include('InAppNotifier');
	const { Color, Indent, Component } = require('tokens');
	const { Icon } = require('assets/icons');
	const { Type } = require('type');

	const { executeIfOnline } = require('tasks/layout/online');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { Form, CompactMode } = require('layout/ui/form');
	const { ColorScheme } = require('layout/ui/fields/base/theme/air-compact');
	const { Description } = require('tasks/layout/task/create-new/description');
	const { Priority } = require('tasks/layout/task/create-new/priority');
	const { BottomPanel } = require('tasks/layout/task/create-new/bottom-panel');
	const { DeadlineField } = require('tasks/layout/fields/deadline/theme/air-compact');
	const { UserField: UserFieldCompact } = require('layout/ui/fields/user/theme/air-compact');
	const { ProjectField } = require('layout/ui/fields/project/theme/air-compact');
	const { FileField } = require('layout/ui/fields/file/theme/air-compact');
	const { ChecklistField, ClickStrategy } = require('tasks/layout/fields/checklist/theme/air-compact');
	const { TaskFlowField } = require('tasks/layout/fields/flow/theme/air-compact');
	const { TagField } = require('layout/ui/fields/tag/theme/air-compact');
	const { DatePlanField } = require('tasks/layout/fields/date-plan/theme/air-compact');
	const { CrmElementField } = require('layout/ui/fields/crm-element/theme/air-compact');
	const { TimeTrackingField } = require('tasks/layout/fields/time-tracking/theme/air-compact');
	const { UserFieldsField } = require('tasks/layout/fields/user-fields/theme/air-compact');
	const { isFieldValid } = require('tasks/layout/fields/user-fields/validator');

	const store = require('statemanager/redux/store');
	const { batchActions } = require('statemanager/redux/batched-actions');
	const { dispatch } = store;
	const { usersSelector, usersUpserted, usersAddedFromEntitySelector } = require('statemanager/redux/slices/users');
	const { getUniqId, selectStages } = require('tasks/statemanager/redux/slices/kanban-settings');
	const { setTaskStage } = require('tasks/statemanager/redux/slices/tasks-stages');
	const { selectFirstStage } = require('tasks/statemanager/redux/slices/stage-settings');
	const { create } = require('tasks/statemanager/redux/slices/tasks');
	const { groupsAddedFromEntitySelector, groupsUpserted } = require('tasks/statemanager/redux/slices/groups');
	const { upsertFlows } = require('tasks/statemanager/redux/slices/flows');

	const {
		makeProjectFieldConfig,
		makeAccomplicesFieldConfig,
		makeAuditorsFieldConfig,
		makeTagsFieldConfig,
		makeCrmFieldConfig,
	} = require('tasks/layout/task/form-utils');
	const { CalendarSettings } = require('tasks/task/calendar');
	const { ChecklistController } = require('tasks/checklist');
	const { Entry } = require('tasks/entry');
	const {
		DeadlinePeriod,
		ViewMode,
		TaskField: Fields,
		TimerState,
	} = require('tasks/enum');
	const { getDiskFolderId } = require('tasks/disk');
	const { confirmClosing, confirmDefaultAction } = require('alert');
	const { Feature } = require('feature');
	const { clone } = require('utils/object');
	const { debounce } = require('utils/function');
	const { guid: getGuid } = require('utils/guid');
	const { dayMonth } = require('utils/date/formats');
	const { Haptics } = require('haptics');
	const { Loc } = require('tasks/loc');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { showToast, showErrorToast, Position } = require('toast');
	const { ParentTask } = require('tasks/layout/task/parent-task');
	const {
		getFieldRestrictionPolicy,
		getFieldShowRestrictionCallback,
		isFieldRestricted,
	} = require('tasks/fields/restriction');
	const { tariffPlanRestrictionsReady } = require('tariff-plan-restriction');
	const { AnalyticsEvent } = require('analytics');

	const isAndroid = Application.getPlatform() !== 'ios';
	const PARENT_TASK_HEIGHT = 30;
	const TITLE_MIN_HEIGHT = 22;
	const DESCRIPTION_MIN_HEIGHT = 18 + (2 * Indent.XL.toNumber());
	const FIELDS_HEIGHT = Component.itbChipHeight.getValue() + (2 * Indent.XL.toNumber());
	const getTopMargin = (hasParentTask) => (hasParentTask ? Indent.XS.toNumber() : Indent.L.toNumber());
	const TITLE_MAX_HEIGHT = Math.floor(device.screen.height * 5 / 667) * TITLE_MIN_HEIGHT;

	class CreateNew extends LayoutComponent
	{
		static getStartingLayoutHeight(hasParentTask)
		{
			return (
				(hasParentTask ? PARENT_TASK_HEIGHT : 0)
				+ getTopMargin(hasParentTask)
				+ TITLE_MIN_HEIGHT
				+ DESCRIPTION_MIN_HEIGHT
				+ FIELDS_HEIGHT
				+ BottomPanel.height
			);
		}

		static removeRestrictedFieldsFromTaskData(taskData)
		{
			const preparedTaskData = { ...taskData };
			const map = {
				[Fields.FLOW]: 'flowId',
				[Fields.PROJECT]: 'group',
				[Fields.ACCOMPLICES]: 'accomplices',
				[Fields.AUDITORS]: 'auditors',
				[Fields.CRM]: 'crm',
			};

			Object.entries(map).forEach(([fieldId, property]) => {
				if (fieldId === Fields.PROJECT && preparedTaskData[property]?.isCollab === true)
				{
					return;
				}

				if (isFieldRestricted(fieldId))
				{
					delete preparedTaskData[property];
				}
			});

			return preparedTaskData;
		}

		static open(data = {})
		{
			const parentWidget = (data.layoutWidget || PageManager);

			const createNew = new CreateNew({
				initialTaskData: CreateNew.removeRestrictedFieldsFromTaskData(data.initialTaskData),
				view: (data.view || ViewMode.LIST),
				stage: data.stage,
				loadStagesParams: data.loadStagesParams,
				closeAfterSave: data.closeAfterSave !== false,
				context: data.context,
				copyId: data.copyId,
				analyticsLabel: data.analyticsLabel || {},
				parentWidget: data.layoutWidget,
			});

			parentWidget.openWidget('layout', {
				backdrop: {
					showOnTop: false,
					onlyMediumPosition: false,
					mediumPositionHeight: CreateNew.getStartingLayoutHeight(Boolean(data.initialTaskData?.parentId)),
					bounceEnable: true,
					swipeAllowed: true,
					hideNavigationBar: true,
					horizontalSwipeAllowed: false,
					shouldResizeContent: true,
					adoptHeightByKeyboard: true,
				},
			})
				.then((layoutWidget) => {
					layoutWidget.showComponent(createNew);

					createNew.layoutWidget = layoutWidget;
					createNew.isLayoutWidgetClosed = false;
				})
				.catch(() => {})
			;
		}

		constructor(props)
		{
			super(props);

			this.userId = Number(env.userId);
			this.diskFolderId = null;
			this.sourceTaskData = null;
			this.flow = null;

			this.layoutWidget = null;
			this.isLayoutWidgetClosed = false;

			this.layoutHeight = 0;
			this.keyboardHeight = 0;

			this.state = {
				isLoading: true,
				isFakeReload: true,
				titleMaxHeight: TITLE_MIN_HEIGHT,
				flowId: Number(this.props.initialTaskData?.flowId),
				flowLoading: false,
			};

			/** @type {BottomPanel|null} */
			this.bottomPanelRef = null;

			/** @type {FileField|null} */
			this.filesInnerRef = null;

			/** @type {Form|null} */
			this.extraFieldsFormRef = null;

			this.focusTitle = this.focusTitle.bind(this);
			this.delayedFocusTitle = this.delayedFocusTitle.bind(this);
			this.save = this.save.bind(this);
			this.bindExtraFieldsFormRef = this.bindExtraFieldsFormRef.bind(this);
			this.onChangeDeadline = this.onChangeDeadline.bind(this);
			this.onChangeProject = this.onChangeProject.bind(this);
			this.onChangeFlow = this.onChangeFlow.bind(this);
			this.onChangeAccomplices = this.onChangeAccomplices.bind(this);
			this.onChangeAuditors = this.onChangeAuditors.bind(this);
			this.onChangeFiles = this.onChangeFiles.bind(this);
			this.onChangeTags = this.onChangeTags.bind(this);
			this.onChangeCrmElements = this.onChangeCrmElements.bind(this);
			this.onChangeTimeTrackingSettings = this.onChangeTimeTrackingSettings.bind(this);
			this.onChangeUserFields = this.onChangeUserFields.bind(this);
			this.notifyDeadlineDisabledByFlow = this.notifyDeadlineDisabledByFlow.bind(this);
			this.notifyProjectDisabledByFlow = this.notifyProjectDisabledByFlow.bind(this);

			this.refreshBottomPanelDebounced = debounce(this.refreshBottomPanel, 500, this);
		}

		componentDidMount()
		{
			const preloads = [
				this.#preloadCurrentUserData(),
				this.#preloadSourceTask(),
				this.#preloadFlowData(),
				this.#preloadDefaultCollab(),
				this.#preloadUserFields(),
				getDiskFolderId().then(({ diskFolderId }) => {
					this.diskFolderId = diskFolderId;
				}),
				tariffPlanRestrictionsReady(),
				CalendarSettings.loadSettings(),
			];

			Promise.allSettled(preloads)
				.then(() => this.doFinalInitAction())
				.catch(console.error)
			;
		}

		#preloadUserFields()
		{
			return new Promise((resolve) => {
				(new RunActionExecutor('tasksmobile.UserField.getUserFields'))
					.setHandler((response) => {
						this.userFields = (response?.status === 'success' ? response.data : []);
						this.userFieldNames = this.userFields.map(({ fieldName }) => fieldName);
						resolve();
					})
					.call(false)
				;
			});
		}

		#isCollaberOrExtranet = () => {
			return env.isCollaber || env.extranet;
		};

		#preloadDefaultCollab()
		{
			if (!this.#isCollaberOrExtranet())
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				(new RunActionExecutor('tasksmobile.Collab.getDefaultCollab'))
					.setHandler((response) => {
						const { status, data } = response;

						if (status === 'success' && data)
						{
							dispatch(groupsUpserted([data]));
							this.defaultCollab = data;
						}
						resolve();
					})
					.call(false)
				;
			});
		}

		#preloadFlowData()
		{
			if (!this.state.flowId)
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				const route = 'tasksmobile.Flow.getTaskCreateMetadata';
				const options = {
					flowId: this.state.flowId,
					copyId: this.props.copyId ?? null,
				};

				(new RunActionExecutor(route, options))
					.setHandler((response) => {
						if (response?.status !== 'success')
						{
							console.error('tasksmobile.Flow.getTaskCreateMetadata error', response);

							resolve();

							return;
						}

						const { flow, groups, users, template, checklist } = response.data;

						dispatch(upsertFlows([flow]));
						dispatch(groupsUpserted(groups));
						dispatch(usersUpserted(users));

						this.sourceTaskData = {
							...template,
							checklist: checklist ?? template?.checklist,
							group: groups[0],
							accomplices: template?.accomplices.map((userId) => {
								return usersSelector.selectById(store.getState(), userId);
							}),
							auditors: template?.auditors.map((userId) => {
								return usersSelector.selectById(store.getState(), userId);
							}),
						};

						this.flow = {
							id: flow.id,
							title: flow.name,
						};

						resolve();
					})
					.call(false)
				;
			});
		}

		#preloadSourceTask()
		{
			if (this.state.flowId || !this.props.copyId)
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				const route = 'tasksmobile.Task.Copy.getSourceTaskData';
				const options = { taskId: this.props.copyId };

				(new RunActionExecutor(route, options))
					.setHandler((response) => {
						if (response?.status !== 'success')
						{
							console.error('tasksmobile.Task.Copy.getSourceTaskData error', response);

							resolve();

							return;
						}

						this.sourceTaskData = response.data;
						resolve();
					})
					.call(false)
				;
			});
		}

		#preloadCurrentUserData()
		{
			return new Promise((resolve) => {
				const fillCurrentUserData = (user) => {
					this.currentUserData = {
						id: user.id,
						name: user.fullName,
						image: user.avatarSize100,
					};
					resolve();
				};

				const user = usersSelector.selectById(store.getState(), this.userId);
				if (user)
				{
					fillCurrentUserData(user);

					return;
				}

				(new RunActionExecutor('tasksmobile.User.getCurrentUserData'))
					.setHandler((response) => {
						dispatch(usersUpserted(response.data));
						fillCurrentUserData(response.data[0]);
					})
					.call(false)
				;
			});
		}

		doFinalInitAction()
		{
			this.layoutWidget.on('preventDismiss', () => this.showConfirmOnFormClosing());
			this.layoutWidget.on('onViewHidden', () => {
				this.isLayoutWidgetClosed = true;
			});

			this.init();
			this.setState(
				{ isLoading: false },
				() => {
					if (Feature.isDidAdoptHeightByKeyboardEventSupported())
					{
						this.layoutWidget.once('didAdoptHeightByKeyboard', (isKeyboardOpened) => {
							if (isKeyboardOpened)
							{
								this.layoutWidget.getBottomSheetHeight()
									.then((height) => {
										this.layoutWidget.setBottomSheetParams({
											onlyMediumPosition: true,
											mediumPositionHeight: height,
											adoptHeightByKeyboard: false,
										});
										this.layoutHeight = height;
										this.keyboardHeight = height - CreateNew.getStartingLayoutHeight(this.hasParentTask());
									})
									.catch(() => console.error)
								;
							}
						});
						this.focusTitle();
					}
					else
					{
						this.focusTitle();

						setTimeout(() => {
							this.layoutWidget.getBottomSheetHeight()
								.then((height) => {
									this.layoutWidget.setBottomSheetParams({
										onlyMediumPosition: true,
										mediumPositionHeight: height,
										adoptHeightByKeyboard: false,
									});
									this.layoutHeight = height;
									this.keyboardHeight = height - CreateNew.getStartingLayoutHeight(this.hasParentTask());
								})
								.catch(() => console.error)
							;
						}, 500);
					}
				},
			);
		}

		init()
		{
			const { initialTaskData } = this.props;

			this.guid = initialTaskData?.guid || getGuid();
			this.task = {
				title: (initialTaskData?.title || this.sourceTaskData?.name || ''),
				description: (initialTaskData?.description || this.sourceTaskData?.description || ''),
				deadline: initialTaskData?.deadline,
				group: this.convertGroupToEntitySelectorFormat(
					this.task?.group
					|| initialTaskData?.group
					|| this.sourceTaskData?.group
					|| this.defaultCollab,
				),
				priority: (initialTaskData?.priority || this.sourceTaskData?.priority || 1),
				parentId: (initialTaskData?.parentId || 0),

				creator: this.currentUserData,
				responsible: (this.task?.responsible || initialTaskData?.responsible || this.currentUserData),
				accomplices: this.convertUsersCollectionToEntitySelectorFormat(
					initialTaskData?.accomplices || this.sourceTaskData?.accomplices,
				),
				auditors: this.convertUsersCollectionToEntitySelectorFormat(
					initialTaskData?.auditors || this.sourceTaskData?.auditors,
				),
				uploadedFiles: initialTaskData?.uploadedFiles || [],
				files: initialTaskData?.files || this.sourceTaskData?.files || [],
				tags: initialTaskData?.tags || this.sourceTaskData?.tags || [],
				crm: (initialTaskData?.crm || this.sourceTaskData?.crm || []),

				relatedTaskId: initialTaskData?.relatedTaskId || null,
				allowTimeTracking: initialTaskData?.allowTimeTracking || false,
				timeEstimate: 0,
				startDatePlan: initialTaskData?.startDatePlan || null,
				endDatePlan: initialTaskData?.endDatePlan || null,

				imChatId: initialTaskData?.IM_CHAT_ID,
				imMessageId: initialTaskData?.IM_MESSAGE_ID,
			};
			this.initialTaskData = {
				...this.task,
				group: this.convertGroupToEntitySelectorFormat(initialTaskData?.group),
				responsible: (initialTaskData?.responsible || this.currentUserData),
			};

			this.userFields.forEach((field) => {
				this.task[field.fieldName] = { ...field };
				this.initialTaskData[field.fieldName] = { ...field };
			});

			this.checklistController = new ChecklistController({
				taskId: undefined,
				userId: this.userId,
				groupId: this.task.group?.id || 0,
				inLayout: false,
				hideCompleted: false,
				parentWidget: this.layoutWidget,
				diskConfig: {
					folderId: this.diskFolderId,
				},
				onClose: () => {
					setTimeout(this.focusTitle, isAndroid ? 500 : 100);
				},
				onChange: (controller) => {
					// this.refreshBottomPanelDebounced();
					const { completed, uncompleted } = controller.getReduxData();

					this.extraFieldsFormRef
						?.getCompactField(Fields.CHECKLIST)
						?.triggerChange({ completed, uncompleted });
				},
			});

			this.checklistController.setChecklists({
				checklistsTree: this.sourceTaskData?.checklist,
				checklistsFlatTree: initialTaskData?.checklistFlatTree,
				clear: true,
			});
		}

		convertUsersCollectionToEntitySelectorFormat(users)
		{
			if (!Array.isArray(users))
			{
				return [];
			}

			return users.map((user) => ({
				id: user.id,
				title: user.name,
				imageUrl: user.image,
			}));
		}

		convertGroupToEntitySelectorFormat(group)
		{
			return group ? {
				id: group.id,
				title: group.name,
				imageUrl: group.image,
				customData: {
					datePlan: {
						dateStart: group.dateStart,
						dateFinish: group.dateFinish,
					},
				},
			} : undefined;
		}

		refreshBottomPanel()
		{
			if (!this.bottomPanelRef)
			{
				return;
			}

			this.bottomPanelRef.updateState({
				responsible: this.task.responsible,
				groupId: this.task.group?.id || 0,
				canSave: this.canSave(),
			});
		}

		canSave()
		{
			const title = this.task.title || '';
			const isExtraFieldsValid = this.extraFieldsFormRef ? this.extraFieldsFormRef.isValid() : true;
			const hasUploadingFiles = this.extraFieldsFormRef ? this.extraFieldsFormRef.hasUploadingFiles() : false;
			// const hasCheckListUploadingFiles = this.checklistController.hasUploadingFiles();

			return title.length > 0 && isExtraFieldsValid && !hasUploadingFiles;
		}

		hasParentTask()
		{
			return Boolean(this.task.parentId);
		}

		updateSheetHeight()
		{
			if (!this.titleHeight || !this.descriptionHeight)
			{
				return;
			}

			const layoutHeight = Math.round((
				(this.hasParentTask() ? PARENT_TASK_HEIGHT : 0)
				+ getTopMargin(this.hasParentTask())
				+ this.titleHeight
				+ this.descriptionHeight
				+ FIELDS_HEIGHT
				+ BottomPanel.height
				+ this.keyboardHeight
			));

			if (this.layoutHeight !== layoutHeight)
			{
				this.layoutHeight = layoutHeight;

				this.layoutWidget.setBottomSheetParams({ mediumPositionHeight: this.layoutHeight });
				this.layoutWidget.setBottomSheetHeight(this.layoutHeight)
					.then(() => this.layoutWidget.getBottomSheetHeight())
					.then((height) => {
						if (this.titleHeight !== this.state.titleMaxHeight)
						{
							const titleMaxHeight = height - this.layoutHeight + this.titleHeight;
							const preparedTitleMaxHeight = titleMaxHeight > 0
								? Math.min(this.titleHeight, titleMaxHeight)
								: this.titleHeight;

							if (preparedTitleMaxHeight !== this.state.titleMaxHeight)
							{
								this.setState({
									titleMaxHeight: preparedTitleMaxHeight,
								});
							}
						}
					})
					.catch(console.error)
				;
			}
		}

		render()
		{
			if (this.state.isLoading)
			{
				return View({}, new LoadingScreenComponent({ showAirStyle: true }));
			}

			return View(
				{
					resizableByKeyboard: true,
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				this.renderParentTask(),
				this.renderTitle(),
				this.renderDescription(),
				this.renderCompactFields(),
				this.renderBottomPanel(),
				this.renderPriority(),
			);
		}

		renderParentTask()
		{
			if (!this.hasParentTask())
			{
				return null;
			}

			return ParentTask({
				parentWidget: this.layoutWidget,
				taskId: this.task.parentId,
				testId: this.#getTestId('ParentTask'),
				enableToOpenTask: false,
			});
		}

		renderTitle()
		{
			return View(
				{
					style: {
						height: this.state.titleMaxHeight,
						marginTop: getTopMargin(this.hasParentTask()),
						marginHorizontal: Indent.XL3.toNumber(),
						paddingRight: 48,
					},
					testId: `${Fields.TITLE}_FIELD`,
				},
				TextInput({
					testId: `${Fields.TITLE}_CONTENT`,
					style: {
						minHeight: TITLE_MIN_HEIGHT,
						maxHeight: this.state.titleMaxHeight,
						fontSize: 18,
						fontWeight: '500',
						color: Color.base1.toHex(),
					},
					placeholder: Loc.getMessage('TASKSMOBILE_TASK_CREATE_FIELD_TITLE_PLACEHOLDER'),
					forcedValue: this.task.title,
					ref: (ref) => {
						/** @var {TextInput} */
						this.titleInnerRef = ref;
					},
					onContentSizeChange: ({ height }) => {
						this.titleHeight = height > TITLE_MAX_HEIGHT ? TITLE_MAX_HEIGHT : height;
						this.updateSheetHeight();
					},
					onChangeText: (text) => {
						this.task.title = text;
						this.refreshBottomPanel();
						this.handlePreventBottomSheetDismiss();
					},
					onSubmitEditing: () => {},
				}),
			);
		}

		renderDescription()
		{
			return new Description({
				style: {
					minHeight: DESCRIPTION_MIN_HEIGHT,
					marginHorizontal: Indent.XL3.toNumber(),
					paddingVertical: Indent.XL.toNumber(),
				},
				description: this.task.description,
				files: [...this.task.files, ...this.task.uploadedFiles],
				parentWidget: this.layoutWidget,
				ref: (ref) => {
					this.descriptionRef = ref;
				},
				onLayout: ({ height }) => {
					this.descriptionHeight = height;
					this.updateSheetHeight();
				},
				onChange: (description) => {
					this.task.description = description;
					this.focusTitle();
					this.handlePreventBottomSheetDismiss();
				},
			});
		}

		#getAnalyticsForUserField = () => {
			const { analyticsLabel } = this.props;
			const analytics = new AnalyticsEvent();
			if (analyticsLabel)
			{
				analytics.setSection(new AnalyticsEvent(analyticsLabel).getSection());
			}

			return analytics;
		};

		renderCompactFields()
		{
			const hasFlow = Boolean(this.state.flowId);

			return new Form({
				forceUpdate: this.state.forceUpdate,
				ref: this.bindExtraFieldsFormRef,
				testId: this.#getTestId('ExtraFields'),
				parentWidget: this.layoutWidget,
				style: {
					height: FIELDS_HEIGHT,
					primaryContainer: {
						paddingBottom: 0,
					},
					compactContainer: {
						height: FIELDS_HEIGHT,
					},
					compactInnerContainer: {
						paddingHorizontal: Indent.XL3.toNumber(),
						paddingTop: 0,
						paddingBottom: 0,
					},
				},
				compactMode: CompactMode.ONLY,
				hideCompactReadonly: false,
				primaryFields: [],
				secondaryFields: [
					{
						factory: DeadlineField,
						props: {
							id: Fields.DEADLINE,
							value: (this.task.deadline && !hasFlow)
								? Math.round(this.task.deadline.getTime() / 1000)
								: null,
							readOnly: hasFlow,
							required: false,
							wideMode: hasFlow,
							colorScheme: hasFlow ? ColorScheme.DEFAULT : null,
							title: hasFlow
								? Loc.getMessage('M_TASKS_DEADLINE_DISABLED_BY_FLOW')
								: Loc.getMessage('TASKSMOBILE_TASK_CREATE_FIELD_DEADLINE_PLACEHOLDER'),
							config: {
								dateFormat: dayMonth(),
							},
							onChange: this.onChangeDeadline,
							onFocusOut: this.delayedFocusTitle,
							onContentClick: this.notifyDeadlineDisabledByFlow,
						},
						compact: DeadlineField,
					},
					{
						factory: ProjectField,
						props: {
							id: Fields.PROJECT,
							value: this.task.group?.id,
							title: Loc.getMessage('M_TASK_FORM_FIELD_PROJECT_TITLE'),
							readOnly: hasFlow,
							colorScheme: hasFlow ? ColorScheme.DEFAULT : null,
							config: makeProjectFieldConfig({
								items: [this.task.group].filter(Boolean),
								canUnselectLast: !this.#isCollaberOrExtranet(),
							}),
							restrictionPolicy: getFieldRestrictionPolicy(Fields.PROJECT),
							showRestrictionCallback: getFieldShowRestrictionCallback(Fields.PROJECT, this.layoutWidget),
							onSelectorHidden: this.focusTitle,
							onChange: this.onChangeProject,
							onContentClick: this.notifyProjectDisabledByFlow,
						},
						compact: ProjectField,
					},
					!this.#isCollaberOrExtranet() && {
						factory: TaskFlowField,
						props: {
							id: Fields.FLOW,
							value: this.state.flowId,
							title: Loc.getMessage('M_TASKS_FLOW'),
							readOnly: false,
							required: false,
							showLoader: this.state.flowLoading,
							config: {
								items: [this.flow].filter(Boolean),
								provider: {
									options: {
										onlyActive: true,
									},
								},
							},
							restrictionPolicy: getFieldRestrictionPolicy(Fields.FLOW),
							showRestrictionCallback: getFieldShowRestrictionCallback(Fields.FLOW, this.layoutWidget),
							onSelectorHidden: this.focusTitle,
							onChange: this.onChangeFlow,
						},
						compact: TaskFlowField,
					},
					{
						factory: FileField,
						props: {
							id: Fields.FILES,
							value: [...this.task.files, ...this.task.uploadedFiles],
							readOnly: false,
							required: false,
							multiple: true,
							title: Loc.getMessage('M_TASKS_FIELDS_FILES'),
							onChange: this.onChangeFiles,
							config: {
								textMultiple: Loc.getMessage('M_TASKS_FIELDS_FILES_MULTI'),
								parentWidget: this.layoutWidget,
								uploadController: {
									endpoint: 'tasks.FileUploader.TaskController',
									options: {
										taskId: 0,
									},
								},
								disk: {
									isDiskModuleInstalled: true,
									isWebDavModuleInstalled: true,
									fileAttachPath: `/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${this.currentUserData.id}`,
								},
							},
							showFilesName: true,
							onFileAttachmentViewHidden: this.focusTitle,
							onFocusOut: this.focusTitle,
						},
						compact: FileField,
					},
					{
						factory: ChecklistField,
						props: {
							id: Fields.CHECKLIST,
							value: this.checklistController.getReduxData(),
							readOnly: false,
							multiple: true,
							config: {
								checklistController: this.checklistController,
								clickStrategy: ClickStrategy.OPEN,
							},
							loading: false,
						},
						compact: ChecklistField,
					},
					{
						factory: UserFieldCompact,
						props: {
							id: Fields.ACCOMPLICES,
							value: this.task.accomplices.map((item) => item.id),
							readOnly: false,
							required: false,
							multiple: true,
							title: Loc.getMessage('M_TASK_FORM_FIELD_ACCOMPLICES_TITLE'),
							config: makeAccomplicesFieldConfig({
								items: this.task.accomplices,
								groupId: this.task.group?.id || 0,
							}),
							restrictionPolicy: getFieldRestrictionPolicy(Fields.ACCOMPLICES),
							showRestrictionCallback: getFieldShowRestrictionCallback(Fields.ACCOMPLICES, this.layoutWidget),
							onSelectorHidden: this.focusTitle,
							onChange: this.onChangeAccomplices,
							analytics: this.#getAnalyticsForUserField(),
						},
						compact: UserFieldCompact,
					},
					{
						factory: UserFieldCompact,
						props: {
							id: Fields.AUDITORS,
							value: this.task.auditors.map((item) => item.id),
							readOnly: false,
							required: false,
							multiple: true,
							title: Loc.getMessage('M_TASK_FORM_FIELD_AUDITORS_TITLE'),
							config: makeAuditorsFieldConfig({
								items: this.task.auditors,
							}),
							restrictionPolicy: getFieldRestrictionPolicy(Fields.AUDITORS),
							showRestrictionCallback: getFieldShowRestrictionCallback(Fields.AUDITORS, this.layoutWidget),
							onSelectorHidden: this.focusTitle,
							onChange: this.onChangeAuditors,
							analytics: this.#getAnalyticsForUserField(),
						},
						compact: UserFieldCompact,
					},
					{
						factory: TagField,
						props: {
							id: Fields.TAGS,
							title: Loc.getMessage('M_TASKS_FIELDS_TAGS'),
							value: Object.values(this.task.tags).map((item) => item.id),
							readOnly: false,
							multiple: true,
							config: makeTagsFieldConfig({
								items: Object.values(this.task.tags).map((item) => ({
									id: item.id,
									title: item.name,
									type: 'task-tag',
								})),
								castType: 'string',
								provider: {
									options: {
										groupId: this.task.group?.id || 0,
										canPreselectTemplateTags: hasFlow,
									},
								},
							}),
							onSelectorHidden: this.focusTitle,
							onChange: this.onChangeTags,
						},
						compact: TagField,
					},
					this.userFieldNames.length > 0 && {
						factory: UserFieldsField,
						props: {
							id: Fields.USER_FIELDS,
							taskId: 0,
							areUserFieldsLoaded: true,
							userFields: this.userFieldNames.map((fieldName) => this.task[fieldName]),
							onChange: this.onChangeUserFields,
							onEditWidgetClose: this.focusTitle,
						},
						compact: UserFieldsField,
					},
					{
						factory: CrmElementField,
						props: {
							id: Fields.CRM,
							title: Loc.getMessage('M_TASKS_FIELDS_CRM'),
							value: this.task.crm.map((item) => item.id),
							readOnly: false,
							multiple: true,
							showHiddenEntities: false,
							config: makeCrmFieldConfig({
								items: this.task.crm,
								provider: {
									options: {
										groupId: this.task.group?.id || 0,
									},
								},
							}),
							restrictionPolicy: getFieldRestrictionPolicy(Fields.CRM),
							showRestrictionCallback: getFieldShowRestrictionCallback(Fields.CRM, this.layoutWidget),
							onSelectorHidden: this.focusTitle,
							onChange: this.onChangeCrmElements,
						},
						compact: CrmElementField,
					},
					{
						factory: TimeTrackingField,
						props: {
							id: Fields.ALLOW_TIME_TRACKING,
							value: {
								timerState: TimerState.PAUSED,
								allowTimeTracking: this.task.allowTimeTracking,
								timeElapsed: 0,
								timeEstimate: this.task.timeEstimate,
								isTimerRunningForCurrentUser: false,
							},
							required: false,
							readOnly: false,
							onChange: this.onChangeTimeTrackingSettings,
							onSettingsWidgetClose: this.focusTitle,
						},
						compact: TimeTrackingField,
					},
					{
						factory: DatePlanField,
						props: {
							id: Fields.DATE_PLAN,
							taskId: this.task.id,
							readOnly: false,
							startDatePlan: this.task.startDatePlan,
							endDatePlan: this.task.endDatePlan,
							groupId: this.task.group?.id || 0,
							onChange: this.onChangeDatePlan,
							parentWidget: this.layoutWidget,
							mode: 'create',
							onHidden: this.focusTitle,
						},
						compact: DatePlanField,
					},
				].filter(Boolean),
			});
		}

		renderBottomPanel()
		{
			return new BottomPanel({
				ref: (ref) => {
					this.bottomPanelRef = ref;
				},
				responsible: this.task.responsible,
				canSave: this.canSave(),
				parentWidget: this.layoutWidget,
				flowId: this.state.flowId,
				groupId: this.task.group?.id || 0,
				onResponsibleChange: (responsible) => {
					this.task.responsible = responsible;
					this.handlePreventBottomSheetDismiss();
				},
				onResponsibleSelectorHidden: this.focusTitle,
				onSave: this.save,
			});
		}

		renderPriority()
		{
			return new Priority({
				testId: this.#getTestId('Priority'),
				priority: this.task.priority,
				style: {
					top: this.hasParentTask() ? (PARENT_TASK_HEIGHT - 8) : 0,
				},
				onChange: (priority) => {
					this.task.priority = priority;
					this.handlePreventBottomSheetDismiss();
				},
			});
		}

		bindExtraFieldsFormRef(ref)
		{
			this.extraFieldsFormRef = ref;
		}

		onChangeDeadline(deadline)
		{
			const groupPlanRange = this.task.group?.customData?.datePlan || {};
			if (deadline && !this.isDateInGroupPlanRange(deadline, groupPlanRange))
			{
				this.showDateConflictToast(Loc.getMessage('M_TASKS_DEADLINE_IS_OUT_OF_PROJECT_RANGE'));
				this.setState({ forceUpdate: Math.random() });

				return;
			}

			this.task.deadline = (deadline ? new Date(deadline * 1000) : null);
			this.handlePreventBottomSheetDismiss();
		}

		onChangeDatePlan = (startDatePlan, endDatePlan) => {
			this.task.startDatePlan = startDatePlan ?? null;
			this.task.endDatePlan = endDatePlan ?? null;
			this.setState({ forceUpdate: Math.random() });
		};

		onChangeProject(_, groups = [])
		{
			let selectedGroup = groups[0];

			if (selectedGroup)
			{
				const groupPlanRange = selectedGroup?.customData?.datePlan;

				if (this.task.deadline && !this.isDateInGroupPlanRange(this.task.deadline / 1000, groupPlanRange))
				{
					this.showDateConflictToast(Loc.getMessage('M_TASKS_DEADLINE_IS_OUT_OF_PROJECT_RANGE'));

					selectedGroup = this.task.group;
				}
				else if (
					!this.isDateInGroupPlanRange(this.task.startDatePlan, groupPlanRange)
					&& !this.isDateInGroupPlanRange(this.task.endDatePlan, groupPlanRange)
				)
				{
					this.showDateConflictToast(Loc.getMessage('M_TASKS_PLANNING_START_AND_END_DATE_IS_OUT_OF_PROJECT_RANGE'));

					selectedGroup = this.task.group;
				}
				else if (!this.isDateInGroupPlanRange(this.task.startDatePlan, groupPlanRange))
				{
					this.showDateConflictToast(Loc.getMessage('M_TASKS_PLANNING_START_DATE_IS_OUT_OF_PROJECT_RANGE'));

					selectedGroup = this.task.group;
				}
				else if (!this.isDateInGroupPlanRange(this.task.endDatePlan, groupPlanRange))
				{
					this.showDateConflictToast(Loc.getMessage('M_TASKS_PLANNING_FINISH_DATE_OUT_OF_PROJECT_RANGE'));

					selectedGroup = this.task.group;
				}

				this.task.group = selectedGroup;
			}
			else
			{
				this.task.group = null;
			}

			if (groups.length > 0)
			{
				dispatch(groupsAddedFromEntitySelector(groups));
			}

			this.checklistController.setGroupId(this.task.group?.id || 0);
			this.handlePreventBottomSheetDismiss();
			this.setState({ forceUpdate: Math.random() });
		}

		/**
		 * @param {number} date
		 * @param {Object} groupPlanRange
		 * @param {number|null} [groupPlanRange.dateStart=null]
		 * @param {number|null} [groupPlanRange.dateFinish=null]
		 * @returns {boolean}
		 */
		isDateInGroupPlanRange(date, groupPlanRange)
		{
			if (!date || !Type.isNumber(date))
			{
				return true;
			}

			const { dateStart = null, dateFinish = null } = groupPlanRange;

			let dateAfterFinish = (new Date(dateFinish * 1000));
			dateAfterFinish.setDate(dateAfterFinish.getDate() + 1);
			dateAfterFinish.setHours(0, 0, 0, 0);
			dateAfterFinish = Math.floor(dateAfterFinish / 1000);

			return (
				(Type.isNil(dateStart) || date >= dateStart)
				&& (Type.isNil(dateFinish) || date < dateAfterFinish)
			);
		}

		showDateConflictToast(message)
		{
			showToast({
				message,
				icon: Icon.CLOCK,
				position: Position.TOP,
			});
		}

		onChangeFlow(flowId, flows = [])
		{
			const prevFlowId = this.state.flowId;
			const prevFlow = clone(this.flow);

			if (flowId === prevFlowId)
			{
				return;
			}

			if (!flowId)
			{
				this.flow = null;
				this.setState({ flowId: 0, flowLoading: false });

				return;
			}

			const rollbackFlow = () => {
				this.flow = prevFlow;
				this.setState({
					flowId: prevFlowId,
					flowLoading: false,
					forceUpdate: Math.random(),
				});
			};

			const showLoader = () => new Promise((resolve) => {
				this.flow = flows[0];
				this.setState({ flowId, flowLoading: true }, resolve);
			});

			showLoader()
				.then(() => this.#askFlowChangeAllowed(flows[0]))
				.then(() => {
					(new RunActionExecutor('tasksmobile.Flow.getTaskCreateMetadata', { flowId }))
						.setHandler((response) => {
							if (response?.status !== 'success')
							{
								rollbackFlow();

								return;
							}

							batchActions([
								upsertFlows([response.data.flow]),
								groupsUpserted(response.data.groups),
								usersUpserted(response.data.users),
							]);

							const template = response.data.template;
							if (template)
							{
								this.task.title = template.name;
								this.task.description = template.description || this.task.description;
								this.task.priority = template.priority;
								this.task.files = template.files.length > 0 ? template.files : this.task.files;
								this.task.tags = template.tags.length > 0 ? template.tags : this.task.tags;
								this.task.crm = template.crm.length > 0 ? template.crm : this.task.crm;

								if (template.accomplices.length > 0)
								{
									this.task.accomplices = this.convertUsersCollectionToEntitySelectorFormat(
										template.accomplices.map((userId) => {
											return usersSelector.selectById(store.getState(), userId);
										}),
									);
								}

								if (template.auditors.length > 0)
								{
									this.task.auditors = this.convertUsersCollectionToEntitySelectorFormat(
										template.auditors.map((userId) => {
											return usersSelector.selectById(store.getState(), userId);
										}),
									);
								}

								if (template.checklist)
								{
									this.checklistController.setChecklistTree(template.checklist);
								}
							}

							this.task.group = this.convertGroupToEntitySelectorFormat(response.data.groups[0]);
							this.checklistController.setGroupId(this.task.group?.id || 0);
							this.flow = flows[0];
							this.setState({ flowId, flowLoading: false });
						})
						.call(false)
					;
				})
				.catch(() => rollbackFlow());
		}

		#askFlowChangeAllowed(flow)
		{
			return new Promise((resolve, reject) => {
				const { templateId = 0 } = flow?.customData || {};

				if (templateId > 0 && this.#formHasChanges())
				{
					confirmDefaultAction({
						title: Loc.getMessage('M_TASKS_CHANGE_FLOW_CONFIRM_TITLE'),
						description: Loc.getMessage('M_TASKS_CHANGE_FLOW_CONFIRM_BODY'),
						actionButtonText: Loc.getMessage('M_TASKS_CHANGE_FLOW_CONFIRM_OK'),
						onAction: resolve,
						onCancel: reject,
					});
				}
				else
				{
					resolve();
				}
			});
		}

		onChangeFiles(files)
		{
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
			this.task.files = existingFiles;
			this.task.uploadedFiles = uploadedFiles;
			this.refreshBottomPanelDebounced();
			this.handlePreventBottomSheetDismiss();
		}

		onChangeAccomplices(_, users = [])
		{
			this.task.accomplices = users;

			if (users.length > 0)
			{
				dispatch(usersAddedFromEntitySelector(users));
			}

			this.handlePreventBottomSheetDismiss();
		}

		onChangeAuditors(_, users = [])
		{
			this.task.auditors = users;

			if (users.length > 0)
			{
				dispatch(usersAddedFromEntitySelector(users));
			}

			this.handlePreventBottomSheetDismiss();
		}

		onChangeTags(_, tags = [])
		{
			this.task.tags = tags.map((item) => ({
				id: item.id,
				name: item.title,
			}));

			this.handlePreventBottomSheetDismiss();
		}

		onChangeCrmElements(_, elements = [])
		{
			this.task.crm = elements;

			this.handlePreventBottomSheetDismiss();
		}

		onChangeTimeTrackingSettings({ allowTimeTracking = false, timeEstimate = 0 })
		{
			this.task.allowTimeTracking = allowTimeTracking;
			this.task.timeEstimate = timeEstimate;
		}

		/**
		 * @param {Map} userFields
		 */
		onChangeUserFields(userFields)
		{
			if (userFields.size === 0)
			{
				return;
			}

			userFields.forEach((value, fieldName) => {
				this.task[fieldName].value = value;
			});

			this.handlePreventBottomSheetDismiss();
			this.setState({ forceUpdate: Math.random() });
		}

		handlePreventBottomSheetDismiss()
		{
			this.layoutWidget.preventBottomSheetDismiss(this.#formHasChanges());
		}

		#formHasChanges()
		{
			const initialAccomplices = this.initialTaskData.accomplices.map((user) => user.id);
			const currentAccomplices = this.task.accomplices.map((user) => user.id);

			const initialAuditors = this.initialTaskData.auditors.map((user) => user.id);
			const currentAuditors = this.task.auditors.map((user) => user.id);

			return (
				this.task.title !== this.initialTaskData.title
				|| this.task.description !== this.initialTaskData.description
				|| this.task.deadline !== this.initialTaskData.deadline
				|| this.task.group?.id !== this.initialTaskData.group?.id
				|| this.task.priority !== this.initialTaskData.priority
				|| this.task.parentId !== this.initialTaskData.parentId
				|| this.task.responsible?.id !== this.initialTaskData.responsible?.id
				|| currentAccomplices.length !== initialAccomplices.length
				|| !currentAccomplices.every((userId) => initialAccomplices.includes(userId))
				|| currentAuditors.length !== initialAuditors.length
				|| !currentAuditors.every((userId) => initialAuditors.includes(userId))
				|| this.task.uploadedFiles.length > 0
				|| this.task.startDatePlan !== this.initialTaskData.startDatePlan
				|| this.task.endDatePlan !== this.initialTaskData.endDatePlan
				|| this.task.allowTimeTracking !== this.initialTaskData.allowTimeTracking
				|| this.task.timeEstimate !== this.initialTaskData.timeEstimate
				|| this.#hasUserFieldsChanged()
			);
		}

		#hasUserFieldsChanged()
		{
			for (const fieldName of this.userFieldNames)
			{
				const { value: initialValue, isMultiple } = this.initialTaskData[fieldName];
				const { value: currentValue } = this.task[fieldName];

				if (isMultiple)
				{
					if (
						!Array.isArray(initialValue)
						|| !Array.isArray(currentValue)
						|| initialValue.length !== currentValue.length
						|| !initialValue.every((value, index) => value === currentValue[index])
					)
					{
						return true;
					}
				}
				else if (initialValue !== currentValue)
				{
					return true;
				}
			}

			return false;
		}

		showConfirmOnFormClosing()
		{
			Haptics.impactLight();

			confirmClosing({
				hasSaveAndClose: false,
				title: Loc.getMessage('TASKSMOBILE_TASK_CREATE_CANCEL_ALERT_TITLE'),
				description: Loc.getMessage('TASKSMOBILE_TASK_CREATE_CANCEL_ALERT_DESCRIPTION'),
				onClose: () => this.layoutWidget.close(),
			});
		}

		save()
		{
			const saveTask = () => {
				Haptics.notifySuccess();

				if (!this.isListView())
				{
					dispatch(
						setTaskStage({
							taskId: this.guid,
							userId: this.currentUserData.id,
							nextStageId: this.getStageId(),
							viewMode: this.props.view,
						}),
					);
				}
				const { analyticsLabel = {}, closeAfterSave, parentWidget } = this.props;

				const analyticsLabelParams = [{
					tool: 'tasks',
					category: 'task_operations',
					type: 'task',
					event: 'task_create',
					...analyticsLabel,
				}];

				if (this.task.parentId > 0)
				{
					analyticsLabelParams.push({
						tool: 'tasks',
						category: 'task_operations',
						type: 'task',
						event: 'subtask_add',
						...analyticsLabel,
					});
				}

				dispatch(
					create({
						taskId: this.guid,
						reduxFields: this.prepareReduxFields(),
						serverFields: this.prepareFieldsToSave(),
						relatedTaskId: this.task.relatedTaskId,
						analyticsLabel: analyticsLabelParams,
					}),
				)
					.then(({ payload }) => {
						if (payload.status === 'error')
						{
							showErrorToast(
								{
									message: Loc.getMessage('TASKSMOBILE_TASK_CREATE_CREATION_ERROR'),
								},
								parentWidget,
							);
						}
					})
					.catch(console.error)
				;
				this.showCreationNotification(this.guid, analyticsLabelParams[0]);
				this.init();
				this.setState({ isFakeReload: !this.state.isFakeReload });
				this.handlePreventBottomSheetDismiss();

				if (closeAfterSave)
				{
					this.layoutWidget.close();
				}
			};

			executeIfOnline(
				() => {
					if (this.isUserFieldsFieldValid())
					{
						saveTask();
					}
					else
					{
						this.extraFieldsFormRef?.getField(Fields.USER_FIELDS)?.openUserFieldsEdit(true);
					}
				},
				null,
				{ position: Position.TOP },
			);
		}

		isUserFieldsFieldValid()
		{
			return this.userFieldNames.map((fieldName) => this.task[fieldName]).every((field) => isFieldValid(field));
		}

		getStageId()
		{
			if (this.isListView())
			{
				return 0;
			}

			const { initialTaskData, stage, view } = this.props;
			const stageId = (stage?.id || 0);

			if (
				this.isDeadlineView()
				&& (
					!stageId
					|| stage.statusId === DeadlinePeriod.PERIOD_OVERDUE
					|| this.task.deadline !== initialTaskData?.deadline
					|| (stage.leftBorder && this.task.deadline < new Date(stage.leftBorder * 1000))
					|| (stage.rightBorder && this.task.deadline > new Date(stage.rightBorder * 1000))
				)
			)
			{
				return 0;
			}

			if (!stageId)
			{
				const uniqueId = getUniqId(view, initialTaskData?.groupId, initialTaskData?.responsible.id);
				const stages = selectStages(store.getState(), uniqueId);
				const firstStage = selectFirstStage(store.getState(), stages);

				return (firstStage?.id || 0);
			}

			return stageId;
		}

		prepareReduxFields()
		{
			const { completed, uncompleted, checklistDetails } = this.checklistController.getReduxData();

			return {
				id: this.guid,
				guid: this.guid,
				name: this.task.title,
				description: this.task.description,
				deadline: this.state.flowId ? null : (this.task.deadline ? this.task.deadline.getTime() / 1000 : null),
				groupId: this.task.group?.id || 0,
				flowId: this.state.flowId || 0,
				parentId: this.task.parentId,
				priority: this.task.priority,

				creator: this.task.creator.id,
				responsible: this.state.flowId ? null : this.task.responsible.id,
				accomplices: this.task.accomplices?.map((item) => item.id) || [],
				auditors: this.task.auditors?.map((item) => item.id) || [],
				tags: this.task.tags || [],
				crm: this.task.crm || [],

				files: this.task.files,
				uploadedFiles: this.task.uploadedFiles,
				relatedTaskId: this.task.relatedTaskId,

				checklist: { completed, uncompleted },
				checklistDetails,
				checklistFlatTree: this.checklistController.getChecklistFlatTree(),

				startDatePlan: this.task.startDatePlan,
				endDatePlan: this.task.endDatePlan,
				allowTimeTracking: this.task.allowTimeTracking,
				timeEstimate: this.task.timeEstimate,

				imChatId: this.task.imChatId,
				imMessageId: this.task.imMessageId,

				...Object.fromEntries(
					this.userFieldNames.map((fieldName) => [fieldName, this.task[fieldName]]),
				),
			};
		}

		prepareFieldsToSave()
		{
			const fieldsToSave = {
				TITLE: this.task.title,
				DESCRIPTION: this.task.description,
				DEADLINE: (this.task.deadline ? (new Date(this.task.deadline)).toISOString() : ''),
				GROUP_ID: (this.task.group?.id || 0),
				FLOW_ID: (this.state.flowId || 0),
				PARENT_ID: this.task.parentId,
				PRIORITY: this.task.priority,
				CREATED_BY: this.task.creator.id,
				RESPONSIBLE_ID: this.task.responsible.id,
				ACCOMPLICES: this.task.accomplices?.map((item) => item.id) || [],
				AUDITORS: this.task.auditors?.map((item) => item.id) || [],
				UPLOADED_FILES: this.task.uploadedFiles.map((file) => file.token),
				UF_TASK_WEBDAV_FILES: this.task.files.map((file) => file.id),
				STAGE_ID: 0,
				TAGS: this.task.tags?.map((item) => item.name),
				CRM: this.task.crm,
				IM_CHAT_ID: this.task.imChatId,
				IM_MESSAGE_ID: this.task.imMessageId,
				CHECKLIST: this.checklistController.getChecklistRequestData(),
				START_DATE_PLAN: this.task.startDatePlan ? this.convertDateFromUnixToISOString(this.task.startDatePlan) : '',
				END_DATE_PLAN: this.task.endDatePlan ? this.convertDateFromUnixToISOString(this.task.endDatePlan) : '',
				ALLOW_TIME_TRACKING: (this.task.allowTimeTracking ? 'Y' : 'N'),
				TIME_ESTIMATE: this.task.timeEstimate,
				USER_FIELDS: {
					...Object.fromEntries(
						this.userFieldNames.map((fieldName) => ([
							fieldName,
							{
								value: this.task[fieldName].value,
								type: this.task[fieldName].type,
							},
						])),
					),
				},
			};

			if (this.isPlannerView() || this.isKanbanView())
			{
				fieldsToSave.STAGE_ID = this.getStageId();
			}

			return fieldsToSave;
		}

		convertDateFromUnixToISOString(date)
		{
			return (new Date(date * 1000)).toISOString();
		}

		showCreationNotification(taskId, analyticsLabel = {})
		{
			showToast(
				{
					message: Loc.getMessage('TASKSMOBILE_TASK_CREATE_NOTIFICATION_TITLE'),
					buttonText: Loc.getMessage('TASKSMOBILE_TASK_CREATE_NOTIFICATION_BUTTON'),
					svg: {
						content: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M20.361 6.35445C20.5953 6.58876 20.5953 6.96866 20.361 7.20298L9.79422 17.7697C9.6817 17.8822 9.52908 17.9454 9.36995 17.9454C9.21082 17.9454 9.05821 17.8822 8.94569 17.7697L4.14839 12.9723C3.91408 12.738 3.91408 12.3581 4.1484 12.1238C4.38271 11.8895 4.76261 11.8895 4.99692 12.1238L9.36996 16.4969L19.5124 6.35445C19.7468 6.12013 20.1267 6.12013 20.361 6.35445Z" fill="#333333"/></svg>',
					},
					position: this.props.closeAfterSave ? Position.BOTTOM : Position.TOP,
					onButtonTap: () => {
						const analyticsLabelParams = {
							...analyticsLabel,
							event: 'task_view',
							c_element: 'view_button',
						};
						this.onNotificationClick(taskId, analyticsLabelParams);
					},
				},
				(this.props.parentWidget || PageManager),
			);
		}

		onNotificationClick(taskId, analyticsLabel)
		{
			if (this.isLayoutWidgetClosed)
			{
				this.openCreatedTask(taskId, analyticsLabel);
			}
			else
			{
				this.layoutWidget.close(() => this.openCreatedTask(taskId, analyticsLabel));
			}
		}

		openCreatedTask(taskId, analyticsLabel)
		{
			Entry.openTask(
				{ id: taskId },
				{
					parentWidget: this.props.parentWidget,
					context: this.props.context,
					analyticsLabel,
				},
			);
		}

		focusTitle()
		{
			if (this.titleInnerRef)
			{
				this.titleInnerRef.focus();
			}
		}

		delayedFocusTitle()
		{
			setTimeout(this.focusTitle, isAndroid ? 300 : 0);
		}

		notifyDeadlineDisabledByFlow()
		{
			this.notifyFieldDisabledByFlow(
				Loc.getMessage('M_TASKS_FIELD_DISABLED_BY_FLOW_EXPLANATION_DEADLINE'),
			);
		}

		notifyProjectDisabledByFlow()
		{
			this.notifyFieldDisabledByFlow(
				Loc.getMessage('M_TASKS_FIELD_DISABLED_BY_FLOW_EXPLANATION_PROJECT'),
			);
		}

		notifyFieldDisabledByFlow(message)
		{
			if (!this.state.flowId)
			{
				return;
			}

			showToast({
				message,
				position: Position.TOP,
				iconName: Icon.LOCK.getIconName(),
				code: 'fieldDisabledByFlow',
			});
		}

		isListView()
		{
			return (this.props.view === ViewMode.LIST);
		}

		isPlannerView()
		{
			return (this.props.view === ViewMode.PLANNER);
		}

		isDeadlineView()
		{
			return (this.props.view === ViewMode.DEADLINE);
		}

		isKanbanView()
		{
			return (this.props.view === ViewMode.KANBAN);
		}

		/**
		 * @param {string} code
		 * @return {string}
		 */
		#getTestId(code)
		{
			const prefix = 'TaskCreate';

			return `${prefix}_${code}`;
		}
	}

	module.exports = { CreateNew };
});
