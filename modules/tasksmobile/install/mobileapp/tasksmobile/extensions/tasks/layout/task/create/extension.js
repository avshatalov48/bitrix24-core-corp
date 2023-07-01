/**
 * @module tasks/layout/task/create
 */
jn.define('tasks/layout/task/create', (require, exports, module) => {
	const {CheckList} = require('tasks/layout/task/fields/checkList');
	const {Responsible} = require('tasks/layout/task/fields/responsible');
	const {Accomplices} = require('tasks/layout/task/fields/accomplices');
	const {Auditors} = require('tasks/layout/task/fields/auditors');
	const {Title} = require('tasks/layout/task/fields/title');
	const {Deadline} = require('tasks/layout/task/fields/deadline');
	const {Description} = require('tasks/layout/task/fields/description');
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
	const {Crm} = require('tasks/layout/task/fields/crm');
	const {ParentTask} = require('tasks/layout/task/fields/parentTask');
	const {BottomPanel} = require('tasks/layout/task/create/bottomPanel');

	const {CalendarSettings} = require('tasks/task/calendar');
	const {DatesResolver} = require('tasks/task/datesResolver');
	const {CheckListTree} = require('tasks/checklist');

	const {Alert} = require('alert');
	const {Haptics} = require('haptics');
	const {Loc} = require('loc');
	const {Type} = require('type');

	const fieldHeight = 66;

	class TaskCreate extends LayoutComponent
	{
		static get section()
		{
			return {
				main: 'main',
				common: 'common',
				more: 'more',
			};
		}

		static get field()
		{
			return {
				title: 'title',
				responsible: 'responsible',
				deadline: 'deadline',
				description: 'description',
				files: 'files',
				checklist: 'checklist',
				project: 'project',
				accomplices: 'accomplices',
				auditors: 'auditors',
				isImportant: 'isImportant',
				tags: 'tags',
				parentTask: 'parentTask',
				datePlan: 'datePlan',
				timeTracking: 'timeTracking',
				crm: 'crm',
				canChangeDeadline: 'canChangeDeadline',
				isMatchWorkTime: 'isMatchWorkTime',
				isTaskControl: 'isTaskControl',
				isResultRequired: 'isResultRequired',
			};
		}

		static createGuid()
		{
			function s4()
			{
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
			}

			return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
		}

		static open(data = {})
		{
			const taskCreate = new TaskCreate({
				currentUser: data.currentUser,
				diskFolderId: data.diskFolderId,
				deadlines: data.deadlines,
				initialTaskData: data.initialTaskData,
			});
			const parentWidget = (data.layoutWidget || PageManager);

			parentWidget.openWidget('layout', {
				backdrop: {
					bounceEnable: true,
					swipeAllowed: false,
					showOnTop: false,
					hideNavigationBar: true,
					horizontalSwipeAllowed: false,
					shouldResizeContent: true,
					mediumPositionHeight: fieldHeight * 3 + BottomPanel.getPanelHeight() + 8,
					adoptHeightByKeyboard: true,
				},
			}).then((layoutWidget) => {
				layoutWidget.showComponent(taskCreate);
				taskCreate.layoutWidget = layoutWidget;
			});
		}

		constructor(props)
		{
			super(props);

			this.currentUser = (props.currentUser || {});
			this.diskFolderId = Number(props.diskFolderId);

			this.pathToImages = '/bitrix/mobileapp/tasksmobile/extensions/tasks/layout/task/images';
			this.layoutWidget = null;
			this.scrollY = 0;

			this.checkList = CheckListTree.buildTree();
			this.guid = TaskCreate.createGuid();

			this.initialTaskData = props.initialTaskData;
			this.fillDeadlines(
				props.deadlines
				|| (this.getDeadlinesCachedOption() ? this.getDeadlinesCachedOption().value : null)
			);

			this.state = {
				showLoading: true,
				readOnly: false,
				isFullForm: false,
				isMoreExpanded: true,
				focus: true,
			};
		}

		componentDidMount()
		{
			Promise.allSettled([
				this.getCurrentUserData(),
				this.getDeadlines(),
				this.getDiskFolderId(),
				CalendarSettings.loadSettings(),
			]).then(() => this.doFinalInitAction());
		}

		doFinalInitAction()
		{
			this.task = new Task(this.currentUser);
			this.task.updateData({
				creator: this.currentUser,
				responsible: this.currentUser,
				deadline: new Date((new Date()).setSeconds(0, 0) + 86400 * 7 * 1000),
				...this.initialTaskData,
			});

			this.datesResolver = new DatesResolver({
				id: this.task.id,
				guid: this.guid,
				deadline: this.task.deadline,
				startDatePlan: this.task.startDatePlan,
				endDatePlan: this.task.endDatePlan,
				isMatchWorkTime: this.task.isMatchWorkTime,
			});

			this.bindEvents();
			this.setState({showLoading: false});
			setTimeout(() => this.setState({focus: null}));
		}

		getCurrentUserData()
		{
			return new Promise((resolve) => {
				if (this.currentUser && this.currentUser.id)
				{
					return resolve();
				}
				(new RequestExecutor('tasksmobile.User.getUsersData', {userIds: [env.userId]}))
					.call()
					.then((response) => {
						this.currentUser = response.result[env.userId];
						resolve();
					})
				;
			});
		}

		getDeadlines()
		{
			return new Promise((resolve) => {
				const now = new Date();
				if (
					this.deadlines.length > 0
					&& this.getDeadlinesCachedOption()
					&& now.getDate() === (new Date(this.getDeadlinesCachedOption().lastTime)).getDate()
				)
				{
					return resolve();
				}
				(new RequestExecutor('mobile.tasks.deadlines.get'))
					.call()
					.then((response) => {
						this.fillDeadlines(response.result);
						this.updateDeadlinesCachedOption({
							lastTime: now.getTime(),
							value: response.result,
						});
						resolve();
					})
				;
			});
		}

		fillDeadlines(values = {})
		{
			if (!values)
			{
				this.deadlines = [];
			}
			else
			{
				this.deadlines = Object.entries(Task.deadlines).map(([key, value]) => {
					return {
						name: value.name,
						value: values[key] * 1000,
					};
				});
			}
		}

		getDeadlinesCachedOption()
		{
			const storage = Application.sharedStorage('tasksTaskList');
			const optionsCache = storage.get('options');

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

		bindEvents()
		{
			this.bindDatesResolverEvents();
		}

		bindDatesResolverEvents()
		{
			this.datesResolver.on('datesResolver:deadlineChanged', (deadline) => {
				this.task.deadline = deadline * 1000;
				this.deadlineRef.updateState({
					readOnly: this.state.readOnly,
					deadline: this.task.deadline,
					taskState: this.task.getState(),
					deadlines: this.deadlines,
					showBalloonDate: true,
				});
			});
			this.datesResolver.on('datesResolver:datesChanged', (startDatePlan, endDatePlan) => {
				this.task.startDatePlan = startDatePlan * 1000;
				this.task.endDatePlan = endDatePlan * 1000;
				this.datePlanStartRef.updateState({
					readOnly: this.state.readOnly,
					startDatePlan: this.task.startDatePlan,
				});
				this.datePlanEndRef.updateState({
					readOnly: this.state.readOnly,
					endDatePlan: this.task.endDatePlan,
				});
				this.datePlanDurationRef.updateState({
					readOnly: this.state.readOnly,
					duration: this.datesResolver.durationByType,
					durationType: this.datesResolver.durationType,
				});
			});
		}

		getStyleForField(name = '')
		{
			const fullBorderedFields = [
				TaskCreate.field.description,
				TaskCreate.field.datePlan,
				TaskCreate.field.timeTracking,
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

			return this.renderTaskCreateScreen();
		}

		renderLoadingScreen()
		{
			return View({}, new LoadingScreenComponent());
		}

		renderTaskCreateScreen()
		{
			return View(
				{
					resizableByKeyboard: true,
					style: {
						flex: 1,
						backgroundColor: '#eef2f4',
						paddingBottom: (this.state.isFullForm ? 5 : BottomPanel.getPanelHeight()),
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
						showsVerticalScrollIndicator: true,
						onScroll: (params) => {
							this.scrollY = params.contentOffset.y;
						},
					},
					View({}, ...this.renderSections()),
				),
				(!this.state.isFullForm && new BottomPanel({
					ref: ref => this.bottomPanelRef = ref,
					pathToImages: this.pathToImages,
					onCreateButtonClick: () => this.save(),
					onExpandButtonClick: () => this.expandToFullForm(),
					onAttachmentButtonClick: () => this.filesInnerRef.focus(),
				})),
			);
		}

		renderSections()
		{
			const fieldsContent = this.getFieldsContent();
			const sections = {
				[TaskCreate.section.main]: {
					fields: {
						[TaskCreate.field.title]: fieldsContent[TaskCreate.field.title],
						[TaskCreate.field.responsible]: fieldsContent[TaskCreate.field.responsible],
						[TaskCreate.field.deadline]: fieldsContent[TaskCreate.field.deadline],
						[TaskCreate.field.files]: fieldsContent[TaskCreate.field.files],
					},
				},
				[TaskCreate.section.common]: {
					fields: {
						[TaskCreate.field.checklist]: fieldsContent[TaskCreate.field.checklist],
						[TaskCreate.field.project]: fieldsContent[TaskCreate.field.project],
						[TaskCreate.field.description]: fieldsContent[TaskCreate.field.description],
						[TaskCreate.field.files]: fieldsContent[TaskCreate.field.files],
						[TaskCreate.field.accomplices]: fieldsContent[TaskCreate.field.accomplices],
						[TaskCreate.field.auditors]: fieldsContent[TaskCreate.field.auditors],
					},
				},
				[TaskCreate.section.more]: {
					header: this.getSectionMoreHeader(),
					fields: {
						[TaskCreate.field.datePlan]: fieldsContent[TaskCreate.field.datePlan],
						[TaskCreate.field.timeTracking]: fieldsContent[TaskCreate.field.timeTracking],
						[TaskCreate.field.isImportant]: fieldsContent[TaskCreate.field.isImportant],
						[TaskCreate.field.crm]: fieldsContent[TaskCreate.field.crm],
						[TaskCreate.field.tags]: fieldsContent[TaskCreate.field.tags],
						[TaskCreate.field.parentTask]: fieldsContent[TaskCreate.field.parentTask],
						[TaskCreate.field.canChangeDeadline]: fieldsContent[TaskCreate.field.canChangeDeadline],
						[TaskCreate.field.isMatchWorkTime]: fieldsContent[TaskCreate.field.isMatchWorkTime],
						[TaskCreate.field.isResultRequired]: fieldsContent[TaskCreate.field.isResultRequired],
						[TaskCreate.field.isTaskControl]: fieldsContent[TaskCreate.field.isTaskControl],
					},
				},
			};

			delete sections[(this.state.isFullForm ? TaskCreate.section.main : TaskCreate.section.common)].fields[TaskCreate.field.files];

			return Object.entries(sections).map(([name, data]) => {
				if (!this.state.isFullForm && name !== TaskCreate.section.main)
				{
					return;
				}

				return View(
					{
						style: {
							backgroundColor: '#ffffff',
							borderRadius: 12,
							paddingTop: (name === TaskCreate.section.main ? 0 : 6),
							paddingBottom: (name === TaskCreate.section.main ? 0 : 6),
							marginTop: (name === TaskCreate.section.main && !this.state.isFullForm ? 0 : 12),
						},
						testId: `taskCreateSection_${name}`,
					},
					data.header,
					(
						(name !== TaskCreate.section.more || this.state.isMoreExpanded)
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
				TaskCreate.field.title,
				TaskCreate.field.checklist,
				TaskCreate.field.project,
				TaskCreate.field.description,
				TaskCreate.field.files,
				TaskCreate.field.datePlan,
				TaskCreate.field.timeTracking,
				TaskCreate.field.isImportant,
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
						marginBottom: (this.state.isMoreExpanded ? 6 : 0),
					},
					testId: `taskCreateSection_${TaskCreate.section.more}_header`,
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
						text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_CREATE_SECTION_MORE'),
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
				[TaskCreate.field.title]: new Title({
					readOnly: this.state.readOnly,
					title: this.task.title,
					focus: this.state.focus,
					style: this.getStyleForField(TaskCreate.field.title),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					onViewRef: ref => this.titleViewRef = ref,
					onChange: title => this.task.updateData({title}),
				}),
				[TaskCreate.field.responsible]: new Responsible({
					readOnly: this.state.readOnly,
					responsible: this.task.responsible,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskCreate.field.responsible),
					deepMergeStyles: this.getDeepMergeStylesForField(),
					onChange: responsible => this.task.updateData({responsible}),
				}),
				[TaskCreate.field.deadline]: new Deadline({
					readOnly: this.state.readOnly,
					deadline: this.task.deadline,
					taskState: this.task.getState(),
					deadlines: this.deadlines,
					showBalloonDate: true,
					style: this.getStyleForField(TaskCreate.field.deadline),
					deepMergeStyles: this.getDeepMergeStylesForField(),
					pathToImages: this.pathToImages,
					datesResolver: this.datesResolver,
					ref: ref => this.deadlineRef = ref,
				}),
				[TaskCreate.field.checklist]: new CheckList({
					checkList: this.checkList,
					taskId: 0,
					taskGuid: this.task.guid,
					userId: this.currentUser.id,
					diskConfig: {
						folderId: this.diskFolderId,
					},
					parentWidget: this.layoutWidget,
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
				}),
				[TaskCreate.field.project]: new Project({
					readOnly: this.state.readOnly,
					groupId: this.task.groupId,
					groupData: this.task.group,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskCreate.field.project),
					deepMergeStyles: this.getDeepMergeStylesForField(),
					onChange: (groupId, group) => {
						this.task.updateData({groupId, group});
						if (this.tagsRef)
						{
							this.tagsRef.updateState({
								readOnly: this.state.readOnly,
								tags: this.task.tags,
								groupId: this.task.groupId,
							});
						}
					},
				}),
				[TaskCreate.field.description]: new Description({
					readOnly: this.state.readOnly,
					description: this.task.description,
					style: this.getStyleForField(TaskCreate.field.description),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					onChange: description => this.task.updateData({description}),
				}),
				[TaskCreate.field.files]: new Files({
					readOnly: this.state.readOnly,
					userId: this.currentUser.id,
					taskId: 0,
					files: [...(this.task.files || []), ...(this.task.uploadedFiles || [])],
					isAlwaysShowed: this.state.isFullForm,
					showAddButton: false,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskCreate.field.files),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					onInnerRef: ref => this.filesInnerRef = ref,
					onViewRef: ref => this.filesViewRef = ref,
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
						if (this.bottomPanelRef)
						{
							this.bottomPanelRef.updateState({
								isAttachmentLoading: this.filesInnerRef.hasUploadingFiles(),
								attachmentCount: this.filesInnerRef.getFilesCount(),
							});
						}
					},
				}),
				[TaskCreate.field.accomplices]: new Accomplices({
					readOnly: this.state.readOnly,
					accomplices: this.task.accomplices,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskCreate.field.accomplices),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					checkList: this.checkList,
					onChange: accomplicesData => this.task.updateData({accomplicesData}),
				}),
				[TaskCreate.field.auditors]: new Auditors({
					readOnly: this.state.readOnly,
					auditors: this.task.auditors,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskCreate.field.auditors),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					checkList: this.checkList,
					onChange: auditorsData => this.task.updateData({auditorsData}),
				}),
				[TaskCreate.field.datePlan]: new DatePlan({
					readOnly: this.state.readOnly,
					isDatePlan: this.isDatePlan,
					startDatePlan: this.task.startDatePlan,
					endDatePlan: this.task.endDatePlan,
					style: this.getStyleForField(TaskCreate.field.datePlan),
					deepMergeStyles: this.getDeepMergeStylesForField(),
					datesResolver: this.datesResolver,
					ref: ref => this.datePlanRef = ref,
					onDatePlanIsRef: ref => this.datePlanIsRef = ref,
					onDatePlanStartRef: ref => this.datePlanStartRef = ref,
					onDatePlanEndRef: ref => this.datePlanEndRef = ref,
					onDatePlanDurationRef: ref => this.datePlanDurationRef = ref,
					onChange: isDatePlan => this.isDatePlan = isDatePlan,
				}),
				[TaskCreate.field.timeTracking]: new TimeTracking({
					readOnly: this.state.readOnly,
					isTimeTracking: this.task.allowTimeTracking,
					timeEstimate: this.task.timeEstimate,
					style: {
						...this.getStyleForField(TaskCreate.field.timeTracking),
						marginTop: 6,
					},
					deepMergeStyles: this.getDeepMergeStylesForField(),
					onChange: values => this.task.updateData(values),
				}),
				[TaskCreate.field.isImportant]: new IsImportant({
					readOnly: this.state.readOnly,
					isImportant: (this.task.priority === Task.priority.important),
					style: this.getStyleForField(TaskCreate.field.isImportant),
					deepMergeStyles: this.getDeepMergeStylesForField(),
					pathToImages: this.pathToImages,
					onChange: value => this.task.updateData({priority: (value ? Task.priority.important : Task.priority.none)}),
				}),
				[TaskCreate.field.crm]: new Crm({
					readOnly: this.state.readOnly,
					crm: this.task.crm,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskCreate.field.crm),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					onChange: crm => this.task.updateData({crm}),
				}),
				[TaskCreate.field.tags]: new Tags({
					readOnly: this.state.readOnly,
					tags: this.task.tags,
					taskId: 0,
					groupId: this.task.groupId,
					parentWidget: this.layoutWidget,
					style: this.getStyleForField(TaskCreate.field.tags),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					ref: ref => this.tagsRef = ref,
					onChange: tags => this.task.updateData({tags}),
				}),
				[TaskCreate.field.parentTask]: new ParentTask({
					parentTask: this.task.parentTask,
					canOpenEntity: false,
					style: this.getStyleForField(TaskCreate.field.parentTask),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
				}),
				[TaskCreate.field.canChangeDeadline]: new CanChangeDeadline({
					readOnly: this.state.readOnly,
					canChangeDeadline: this.task.allowChangeDeadline,
					style: this.getStyleForField(TaskCreate.field.canChangeDeadline),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					onChange: value => this.task.updateData({allowChangeDeadline : (value ? 'Y' : 'N')}),
				}),
				[TaskCreate.field.isMatchWorkTime]: new IsMatchWorkTime({
					readOnly: this.state.readOnly,
					isMatchWorkTime: this.task.isMatchWorkTime,
					style: this.getStyleForField(TaskCreate.field.isMatchWorkTime),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					datesResolver: this.datesResolver,
					onChange: value => this.task.updateData({matchWorkTime: (value ? 'Y' : 'N')}),
				}),
				[TaskCreate.field.isTaskControl]: new IsTaskControl({
					readOnly: this.state.readOnly,
					isTaskControl: this.task.allowTaskControl,
					style: this.getStyleForField(TaskCreate.field.isTaskControl),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					onChange: value => this.task.updateData({taskControl: (value ? 'Y' : 'N')}),
				}),
				[TaskCreate.field.isResultRequired]: new IsResultRequired({
					readOnly: this.state.readOnly,
					isResultRequired: this.task.isResultRequired,
					style: this.getStyleForField(TaskCreate.field.isResultRequired),
					deepMergeStyles: this.getDeepMergeStylesForField(true),
					onChange: value => this.task.updateData({taskRequireResult: (value ? 'Y' : 'N')}),
				}),
			};
		}

		expandToFullForm()
		{
			this.setState({isFullForm: true}, () => {
				this.layoutWidget.expandBottomSheet();
				this.layoutWidget.setBackButtonHandler(() => {
					this.showConfirmOnFormClosing();
					return true;
				});
				this.layoutWidget.setLeftButtons([
					{
						type: 'cross',
						callback: () => this.showConfirmOnFormClosing(),
					},
				]);
				this.layoutWidget.setRightButtons([
					{
						type: 'text',
						name: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_CREATE_BUTTON_CREATE'),
						color: '#2066b0',
						callback: () => this.save(),
					},
				]);
				this.layoutWidget.setTitle({text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_CREATE_TITLE')});
			});
		}

		showConfirmOnFormClosing()
		{
			Haptics.impactLight();
			Alert.confirm(
				Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_CREATE_CANCEL_ALERT_TITLE'),
				Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_CREATE_CANCEL_ALERT_DESCRIPTION'),
				[
					{
						text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_CREATE_CANCEL_ALERT_YES'),
						type: 'destructive',
						onPress: () => this.layoutWidget.close(),
					},
					{
						text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_CREATE_CANCEL_ALERT_NO'),
						type: 'cancel',
					},
				],
			);
		}

		save()
		{
			if (!this.checkCanSave())
			{
				return;
			}
			this.isSaving = true;
			Notify.showIndicatorLoading();

			this.task.save().then(
				() => {
					if (this.checkList.isActive())
					{
						this.checkList.save(this.task.id, 'TASK_ADD');
					}
					this.layoutWidget.close();
				},
				() => {
					Notify.hideCurrentIndicator();
					this.isSaving = false;
				}
			);
		}

		checkCanSave()
		{
			if (this.isSaving)
			{
				return false;
			}

			if (
				this.task.title === ''
				&& this.scrollViewRef
				&& this.titleViewRef
			)
			{
				this.scrollViewRef.scrollTo({
					...this.scrollViewRef.getPosition(this.titleViewRef),
					animated: true,
				});

				return false;
			}

			if (
				this.scrollViewRef
				&& this.filesViewRef
				&& this.filesInnerRef
				&& this.filesInnerRef.hasUploadingFiles()
			)
			{
				this.scrollViewRef.scrollTo({
					...this.scrollViewRef.getPosition(this.filesViewRef),
					animated: true,
				});
				Notify.showMessage(Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_CREATE_SAVE_ERROR_LOADING_FILES'));

				return false;
			}

			return true;
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

	module.exports = {TaskCreate};
});