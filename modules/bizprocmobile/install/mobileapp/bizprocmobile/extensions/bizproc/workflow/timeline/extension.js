/**
 * @module bizproc/workflow/timeline
 * */

jn.define('bizproc/workflow/timeline', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { cross, documents, union, clock, flag } = require('bizproc/workflow/timeline/icons');
	const { Skeleton } = require('bizproc/workflow/timeline/skeleton');
	const {
		ContentStub,
		UserStub,
		Counter,
		StepWrapper,
		StepContent,
		StepsListCollapsed,
		StepTitle,
		StepSubtitle,
		StepUserList,
	} = require('bizproc/workflow/timeline/components');
	const { PureComponent } = require('layout/pure-component');
	const { SafeImage } = require('layout/ui/safe-image');
	const { Loc } = require('loc');
	const { dispatch } = require('statemanager/redux/store');
	const { usersAdded } = require('statemanager/redux/slices/users');
	const { Duration } = require('utils/date/duration');
	const { shortTime, dayMonth } = require('utils/date/formats');
	const { mergeImmutable } = require('utils/object');
	const { Type } = require('type');
	const { inAppUrl } = require('in-app-url');
	const { openNativeViewer } = require('utils/file');
	const { throttle } = require('utils/function');
	const { NotifyManager } = require('notify-manager');
	const { roundSeconds } = require('bizproc/helper/duration');

	const approveTypeLocId = {
		all: 'BPMOBILE_WORKFLOW_TIMELINE_TASK_WAITING_FOR_ALL',
		any: 'BPMOBILE_WORKFLOW_TIMELINE_TASK_WAITING_FOR_ANY',
		vote: 'BPMOBILE_WORKFLOW_TIMELINE_TASK_VOTING',
	};
	const taskCompletedLocId = {
		1: 'BPMOBILE_WORKFLOW_TIMELINE_USER_STATUS_OK',
		2: 'BPMOBILE_WORKFLOW_TIMELINE_USER_STATUS_NO',
		3: 'BPMOBILE_WORKFLOW_TIMELINE_USER_STATUS_OK',
		4: 'BPMOBILE_WORKFLOW_TIMELINE_USER_STATUS_NO',
		5: 'BPMOBILE_WORKFLOW_TIMELINE_USER_STATUS_NO',
	};

	class WorkflowTimeline extends PureComponent
	{
		static open(layout = PageManager, props = {})
		{
			layout.openWidget('layout', {
				modal: true,
				titleParams: {
					text: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_TITLE_MSGVER_1'),
					type: 'dialog',
				},
				backgroundColor: AppTheme.colors.bgContentPrimary,
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionPercent: 90,
					navigationBarColor: AppTheme.colors.bgSecondary,
					swipeAllowed: true,
					swipeContentAllowed: true,
					horizontalSwipeAllowed: false,
				},
				onReady: (readyLayout) => {
					readyLayout.showComponent(new WorkflowTimeline({
						parentLayout: layout,
						layout: readyLayout,
						workflowId: props.workflowId,
						taskId: props.taskId,
						readOnly: props.readOnly || false,
					}));
				},
			});
		}

		constructor(props)
		{
			super(props);

			this.testId = 'BpTimeline';

			this.stepsUserList = [];
			this.state.timelineData = null;
			this.loadTimelineData()
				.then((timelineData) => {
					this.setState({ timelineData });
				})
				.catch((err) => console.warn(err));
		}

		get workflowId()
		{
			return this.props.workflowId;
		}

		get layout()
		{
			return this.props.layout;
		}

		get parentLayout()
		{
			return this.props.parentLayout;
		}

		get taskId()
		{
			const id = parseInt(this.props.taskId, 10);

			return Type.isNumber(id) ? id : 0;
		}

		get taskExecutionTimeLimit()
		{
			return (Duration.getLengthFormat().DAY / 1000) * 3;
		}

		/**
		 *
		 * @return {{
		 *	   documentType: ?[string, string, string],
		 *	   documentUrl: string,
		 *     documentName: string,
		 *     documentDiskFile: {
		 * 			error?: string,
		 * 			type?: ?string,
		 * 			name?: ?string,
		 * 			url?: ?string,
		 * 		},
		 *     entityName: string,
		 *     isWorkflowRunning: boolean,
		 *     timeToStart: ?number,
		 *     executionTime: number,
		 *     workflowModifiedDate: number,
		 *     moduleName: string,
		 *     started: number,
		 *     startedBy: number,
		 *     tasks: Array<{
		 *			canView: boolean,
		 *			id: number,
		 *			status: ?number,
		 *			approveType: ?string,
		 *			modified: number,
		 *			executionTime: ?number,
		 *			name: ?string,
		 *			users: Array<{id: number, status: number}>,
		 *     }>,
		 *     users: object[],
		 *     workflowResult?: {
		 * 			text: string,
		 * 			files: Object,
		 * 		},
		 * } | null}
		 */
		get timelineData()
		{
			return this.state.timelineData;
		}

		/**
		 * @return {boolean}
		 */
		isLoaded()
		{
			return !Type.isNil(this.timelineData);
		}

		/**
		 *
		 * @param {int} userId
		 * @return {undefined | object}
		 */
		getUserDataById(userId)
		{
			return this.timelineData.users.find((user) => user.id === userId);
		}

		async loadTimelineData()
		{
			const response = await BX.ajax.runAction(
				'bizprocmobile.Workflow.getTimeline',
				{ data: { workflowId: this.workflowId } },
			);

			for (const error of response.errors)
			{
				console.error(error);
			}

			if (response.errors.length === 0)
			{
				const timelineData = response.data;
				dispatch(usersAdded(timelineData.users));
				if (Type.isArray(timelineData.tasks))
				{
					timelineData.tasks.sort((task1, task2) => {
						const isLeftTaskCompleted = this.isTaskCompleted(task1);
						const isRightTaskCompleted = this.isTaskCompleted(task2);

						if (isLeftTaskCompleted && !isRightTaskCompleted)
						{
							return -1;
						}

						if (!isLeftTaskCompleted && isRightTaskCompleted)
						{
							return 1;
						}

						return task1.id - task2.id;
					});
				}

				return timelineData;
			}

			return {};
		}

		/**
		 *
		 * @return {View}
		 */
		render()
		{
			return ScrollView(
				{
					style: {
						// flex: 1,
					},
				},
				View(
					{
						style: {
							flexDirection: 'column',
							// flexGrow: 1,
							paddingHorizontal: 16,
							paddingVertical: 12,
						},
					},
					...this.renderContent(),
					View({ style: { height: 12 } }),
				),
			);
		}

		/**
		 * @return {View[]}
		 */
		renderContent()
		{
			if (this.isLoaded())
			{
				return [
					this.renderFirstStep(),
					...this.renderTasks(),
					this.renderLastStep(),
				];
			}

			return [
				new Skeleton(),
			];
		}

		/**
		 * @return {View}
		 */
		renderFirstStep()
		{
			return this.renderStep({
				wrapperOptions: {
					showBorders: true,
					backgroundColor: AppTheme.colors.bgContentSecondary,
				},
				counterOptions: {
					value: 1,
					tailColor: (
						this.timelineData.tasks.length > 0 || !this.timelineData.isWorkflowRunning
							? AppTheme.colors.base4
							: AppTheme.colors.base7
					),
				},
				titleOptions: {
					text: this.timelineData.entityName,
					testId: `${this.testId}WorkflowText`,
				},
				header: {
					title: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_WORKFLOW_STARTED_BY'),
					timestamp: this.timelineData.started,
				},
				users: [{ id: this.timelineData.startedBy }],
				additionalContent: [this.renderDocument()],
				footer: (
					Type.isNumber(this.timelineData.timeToStart)
						? Loc.getMessage(
							'BPMOBILE_WORKFLOW_TIMELINE_EXECUTION_TIME',
							{ '#DURATION#': this.formatDuration(this.timelineData.timeToStart) },
						)
						: ''
				),
			});
		}

		/**
		 *
		 * @param {int} timestamp
		 * @return {string}
		 */
		formatDate(timestamp)
		{
			return DateFormatter.getDateString(timestamp, `${dayMonth()} ${shortTime()}`);
		}

		/**
		 *
		 * @param {?int} time
		 * @return {string|null}
		 */
		formatDuration(time)
		{
			if (!Type.isInteger(time))
			{
				return null;
			}

			return Duration.createFromSeconds(time >= 60 ? roundSeconds(time) : time).format();
		}

		/**
		 *
		 * @return {View}
		 */
		renderDocument()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						paddingBottom: 8,
					},
				},
				// icon and module title
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					SafeImage({
						style: {
							width: 24,
							height: 24,
							marginRight: 2,
						},
						resizeMode: 'contain',
						placeholder: {
							content: this.getDocumentIconContent(),
						},
					}),
					Text({
						testId: `${this.testId}DocumentModuleName`,
						text: Type.isString(this.timelineData.moduleName) ? `${this.timelineData.moduleName}:` : '',
						style: {
							color: AppTheme.colors.base1,
							fontSize: 15,
							fontWeight: '400',
						},
					}),
				),
				// document name
				Type.isStringFilled(this.timelineData.documentName) && View(
					{
						onClick: this.openDocument.bind(this),
					},
					Text({
						testId: `${this.testId}DocumentName`,
						text: this.timelineData.documentName,
						style: {
							color: AppTheme.colors.accentMainLinks,
							fontSize: 15,
							fontWeight: '400',
						},
					}),
				),
			);
		}

		openDocument()
		{
			const moduleId = (
				Type.isString(this.timelineData.documentType[0])
					? this.timelineData.documentType[0].toLowerCase()
					: ''
			);

			if (
				!Type.isStringFilled(this.timelineData.documentUrl)
				|| (moduleId === 'disk' && Type.isNil(this.timelineData.documentDiskFile))
			)
			{
				NotifyManager.showError(Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_DOCUMENT_NOT_FOUND'));

				return;
			}

			if (moduleId === 'disk')
			{
				const file = this.timelineData.documentDiskFile;
				if (Type.isNil(file.error))
				{
					openNativeViewer({
						fileType: UI.File.getType(UI.File.getFileMimeType(file.type, file.name)),
						url: file.url,
						name: file.name,
					});
				}
				else
				{
					NotifyManager.showError(file.error);
				}

				return;
			}

			inAppUrl.open(
				this.timelineData.documentUrl,
				{
					parentWidget: this.layout,
				},
			);
		}

		/**
		 * @return {string}
		 */
		getDocumentIconContent()
		{
			if (Type.isArray(this.timelineData.documentType))
			{
				const moduleId = (
					Type.isString(this.timelineData.documentType[0])
						? this.timelineData.documentType[0].toLowerCase()
						: ''
				);
				let documentType = (
					Type.isString(this.timelineData.documentType[2])
						? this.timelineData.documentType[2].toLowerCase()
						: ''
				);

				if (documentType.startsWith('dynamic'))
				{
					documentType = 'dynamic';
				}

				if (moduleId === 'crm' && documentType.startsWith('smart_'))
				{
					documentType = documentType.slice('smart_'.length);
				}

				return (
					Type.isObjectLike(documents[moduleId]) && Type.isFunction(documents[moduleId][documentType])
						? documents[moduleId][documentType]()
						: documents.lists()
				);
			}

			return documents.lists();
		}

		/**
		 *
		 * @return {View[]}
		 */
		// eslint-disable-next-line max-lines-per-function
		renderTasks()
		{
			if (!this.timelineData.tasks || this.timelineData.tasks.length === 0)
			{
				return [];
			}

			const tasksView = [];
			const collapsedTasks = [];

			const taskCount = this.timelineData.tasks.length;
			const lastTaskIndex = taskCount - 1;
			let trunkColor = AppTheme.colors.base4;

			for (const [taskIndex, task] of this.timelineData.tasks.entries())
			{
				const isLastTask = taskIndex === lastTaskIndex;
				const isTaskCompleted = this.isTaskCompleted(task);

				let counterStepColor = AppTheme.colors.accentMainSuccess;
				if (!isTaskCompleted)
				{
					counterStepColor = AppTheme.colors.accentMainPrimary;
				}

				if (this.isTaskDeclined(task))
				{
					counterStepColor = AppTheme.colors.base5;
				}

				const tailColor = (
					(isLastTask && this.timelineData.isWorkflowRunning)
						? AppTheme.colors.base7
						: AppTheme.colors.base4
				);
				let step = null;
				if (task.canView)
				{
					const counterOptions = {
						trunkColor,
						hasTail: true,
						tailColor,
						backgroundColor: counterStepColor,
						value: taskIndex + 2,
					};
					let titleButton = null;
					if (this.taskId !== task.id && this.canCurrentUserDoTask(task))
					{
						titleButton = {
							id: task.id,
							testId: `${this.testId}TaskButton_${task.id}`,
							text: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_TASK_START'),
							onclick: () => {
								if (!this.props.readOnly)
								{
									this.openTaskDetails(task.id).catch((err) => console.error(err));
								}
							},
							readOnly: this.props.readOnly,
						};
					}

					let headerTitle = Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_USER_STATUS_OK');
					if (!isTaskCompleted)
					{
						if (Object.hasOwn(approveTypeLocId, task.approveType) && task.users.length > 0)
						{
							const completedCount = task.users.filter((user) => user.status !== 0).length;
							headerTitle = Loc.getMessage(approveTypeLocId[task.approveType], {
								'#FINISHED_COUNT#': completedCount,
								'#ALL_COUNT#': task.users.length,
							});
						}
						else
						{
							headerTitle = Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_TASK_WAITING_FOR_SINGLE');
						}
					}
					else if (Object.hasOwn(taskCompletedLocId, task.status))
					{
						headerTitle = Loc.getMessage(taskCompletedLocId[task.status]);
					}

					const users = task.users.map((user) => {
						let testId = `${this.testId}User_${user.id}`;
						if (user.status === 1 || user.status === 3)
						{
							testId += '_Accepted';
						}
						else if (user.status === 2 || user.status === 4)
						{
							testId += '_Declined';
						}

						return {
							id: user.id,
							status: user.status,
							testId,
						};
					});

					let footerOptions = null;
					if (task.executionTime)
					{
						footerOptions = {};
						if (task.executionTime > this.taskExecutionTimeLimit)
						{
							const timeColor = AppTheme.colors.accentMainLinks;
							footerOptions.footer = {
								text: Loc.getMessage(
									'BPMOBILE_WORKFLOW_TIMELINE_EXECUTION_TIME',
									{ '#DURATION#': `[color=${timeColor}][url]${this.formatDuration(task.executionTime)}[/url][/color]` },
								),
								onLinkClick: () => InAppNotifier.showNotification({
									message: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_TIME_LIMIT_EXCEEDED'),
									code: 'bp-workflow-timeline-execution-time-exceeded',
									time: 3,
								}),
							};
						}
						else
						{
							footerOptions.footer = Loc.getMessage(
								'BPMOBILE_WORKFLOW_TIMELINE_EXECUTION_TIME',
								{ '#DURATION#': this.formatDuration(task.executionTime) },
							);
						}
					}

					step = this.renderStep({
						wrapperOptions: {
							showBorders: false,
						},
						counterOptions,
						titleOptions: {
							text: task.name,
							testId: `${this.testId}TaskTitle_${task.id}`,
							button: titleButton,
						},
						header: {
							title: headerTitle,
							testId: `${this.testId}Task_${task.id}_Status`,
							timestamp: this.isTaskCompleted(task) ? task.modified : null,
							icon: this.isTaskCompleted(task) ? null : clock(),
						},
						users,
						usersTestId: `${this.testId}Task_${task.id}_Users`,
						...footerOptions,
					});
				}
				else
				{
					step = this.renderStub({
						title: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_TASK_RIGHTS_ERROR'),
						wrapperOptions: {
							showBorders: false,
						},
						counterOptions: {
							value: taskIndex + 2,
							hasTail: true,
							tailColor,
							trunkColor,
						},
					});
				}

				if (step)
				{
					if (isTaskCompleted)
					{
						collapsedTasks.push(step);
					}
					else
					{
						tasksView.push(step);
					}
				}
				trunkColor = tailColor;
			}

			if (collapsedTasks.length > 0)
			{
				tasksView.unshift(
					this.renderCollapsedTasks(
						collapsedTasks,
						{
							tailColor: (
								tasksView.length > 0 || !this.timelineData.isWorkflowRunning
									? AppTheme.colors.base4
									: AppTheme.colors.base7
							),
						},
					),
				);
			}

			return tasksView;
		}

		/**
		 * @param {View[]} taskViews
		 * @param {?object} counterOptions
		 */
		renderCollapsedTasks(taskViews, counterOptions = null)
		{
			return StepsListCollapsed({
				text: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_MORE_TASKS'),
				onStepsExpanding: () => {
					this.stepsUserList.forEach((userList) => userList.showUsers());
				},

				steps: taskViews,
				collapsedButtonTestId: `${this.testId}CollapsedButton`,
				counterOptions,
			});
		}

		/**
		 * @return {View}
		 */
		renderLastStep()
		{
			if (this.timelineData.isWorkflowRunning)
			{
				return this.renderStub({
					title: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_CONTINUE_EXECUTION'),
					wrapperOptions: {},
					counterOptions: {
						hasTail: false,
						backgroundColor: AppTheme.colors.base7,
						size: 11,
						trunkColor: AppTheme.colors.base7,
					},
				});
			}

			const lastTask = this.timelineData.tasks.at(-1);
			const isAccepted = !Type.isObjectLike(lastTask) || this.isTaskAccepted(lastTask);

			let totalTime = (
				Type.isNumber(this.timelineData.executionTime) ? this.timelineData.executionTime : null
			);
			if (Type.isNumber(this.timelineData.timeToStart))
			{
				totalTime = totalTime === null ? this.timelineData.timeToStart : totalTime + this.timelineData.timeToStart;
			}

			const content = [];
			const users = [];
			if (this.timelineData.workflowResult)
			{
				content.push(this.renderWorkflowResult(this.timelineData.workflowResult));
			}
			else if (Type.isObjectLike(lastTask))
			{
				content.push(this.renderExtendedTaskStatus(lastTask));
				users.push({ id: this.timelineData.startedBy });
			}

			return this.renderStep({
				wrapperOptions: {
					showBorders: true,
					borderOptions: {
						color: isAccepted ? AppTheme.colors.accentMainSuccess : null,
					},
					backgroundColor: isAccepted ? AppTheme.colors.accentSoftGreen3 : null,
				},
				counterOptions: {
					hasTail: false,
					trunkColor: AppTheme.colors.base4,
					iconContent: union(),
				},
				titleOptions: {
					text: this.timelineData.entityName,
					testId: `${this.testId}LastStepTitle`,
				},
				header: {
					title: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_WORKFLOW_COMPLETED'),
					testId: `${this.testId}LastStepStatus`,
					timestamp: this.timelineData.workflowModifiedDate,
				},
				content,
				users,
				footer: (
					Type.isNumber(totalTime)
						? Loc.getMessage(
							'BPMOBILE_WORKFLOW_TIMELINE_WORKFLOW_EXECUTION_TIME',
							{ '#DURATION#': this.formatDuration(totalTime) },
						)
						: ''
				),
			});
		}

		/**
		 * @param {{
		 * 		canView: boolean,
		 * 		id: number,
		 * 		status: ?number,
		 * 		approveType: ?string,
		 * 		modified: number,
		 * 		executionTime: ?number,
		 * 		name: ?string,
		 * 		users: Array<{id: number, status: number}>,
		 * }} task
		 * @return {View}
		 */
		renderExtendedTaskStatus(task)
		{
			if (!task.canView)
			{
				return View();
			}

			const isAccepted = this.isTaskAccepted(task);

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				SafeImage({
					style: {
						width: 24,
						height: 24,
						paddingRight: 2,
					},
					resizeMode: 'contain',
					placeholder: {
						content: isAccepted ? flag() : cross({ color: AppTheme.colors.base3 }),
					},
				}),
				Text({
					text: (
						isAccepted
							? Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_EXTENDED_TASK_STATUS_ACCEPTED')
							: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_EXTENDED_TASK_STATUS_DECLINED')
					),
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						color: AppTheme.colors.base1,
						fontSize: 15,
						fontWeight: '400',
					},
				}),
			);
		}

		renderWorkflowResult({ text, files })
		{
			return View(
				{
					style: {
						marginBottom: 8,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							marginBottom: 4,
						},
					},
					SafeImage({
						style: {
							width: 24,
							height: 24,
							paddingRight: 2,
						},
						resizeMode: 'contain',
						placeholder: {
							content: flag(),
						},
					}),
					Text({
						text: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_WORKFLOW_RESULT'),
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							color: AppTheme.colors.base1,
							fontSize: 15,
							fontWeight: '400',
						},
					}),
				),
				BBCodeText({
					testId: `${this.testId}WorkflowResultText`,
					linksUnderline: false,
					value: text,
					style: {
						color: AppTheme.colors.base4,
						fontSize: 13,
						fontWeight: '400',
					},
					onLinkClick: ({ url }) => {
						if (files[url])
						{
							const file = files[url];
							const openViewer = throttle(openNativeViewer, 500);
							openViewer({
								fileType: UI.File.getType(UI.File.getFileMimeType(file.type, file.name)),
								url: file.url,
								name: file.name,
							});

							return;
						}

						inAppUrl.open(url, { parentWidget: this.layout });
					},
				}),
			);
		}

		/**
		 *
		 * @param {{
		 *		canView: boolean,
		 *		id: number,
		 *		approveType: ?string,
		 *		executionTime: ?number,
		 *		name: ?string,
		 *		status: ?number,
		 * 		users: Array<{id: number, status: number}>,
		 * }} task
		 *
		 * @return boolean
		 */
		canCurrentUserDoTask(task)
		{
			if (!task.canView || this.isTaskCompleted(task))
			{
				return false;
			}

			const currentUserId = parseInt(env.userId, 10);
			if (!Type.isInteger(currentUserId))
			{
				return false;
			}

			const taskUser = task.users.find((user) => user.id === currentUserId);

			return Type.isObjectLike(taskUser) && taskUser.status === 0;
		}

		/**
		 *
		 * @param {number} taskId
		 * @return {Promise<void>}
		 */
		async openTaskDetails(taskId)
		{
			const { TaskDetails } = await requireLazy('bizproc:task/details');
			this.props.layout.close(() => TaskDetails.open(this.parentLayout, { taskId }));
		}

		/**
		 *
		 * @param {{
		 * 		wrapperOptions: ?{
		 * 	   		showBorders: ?boolean,
		 *     		borderOptions: ?{
		 *         		style: ?string,
		 *         		width: ?number,
		 *         		color: ?string,
		 *         		radius: ?number,
		 *     		},
		 *     		backgroundColor: string,
		 * 		},
		 * 		counterOptions: {
		 * 			value: ?number,
		 * 			iconContent: ?string,
		 * 			trunkColor: ?string,
		 * 		    hasTail: ?boolean,
		 * 		    tailColor: ?string,
		 * 		    backgroundColor: ?string,
		 * 		},
		 * 		titleOptions: ?{
		 * 		    text: ?string,
		 * 		    testId: ?string,
		 * 		    button: ?{
		 * 		        id: any,
		 * 		        text: string,
		 * 		        testId: ?string,
		 * 		        onclick: (id) => void,
		 * 		    },
		 * 		},
		 * 		header: ?{
		 * 			title: ?string,
		 * 			testId: ?string,
		 * 			timestamp: ?number,
		 * 			icon: ?string,
		 * 		},
		 * 		content: ?Array<View>,
		 * 		users: Array<{id: number, status: number, testId: ?string}>,
		 * 		shouldHideUsers: ?boolean,
		 * 		usersTestId: ?string,
		 *     	additionalContent: ?Array<View>,
		 *     	footer: ?string | { text: string, onLinkClick: ?(() => {}) },
		 * }} options
		 * @return {object}
		 */
		// eslint-disable-next-line max-lines-per-function
		renderStep({
			wrapperOptions = { showBorders: false },
			counterOptions = {},
			titleOptions: {
				text: title,
				button: titleButton,
				testId: titleTestId,
			} = {},
			header = {},
			content = [],
			users = [],
			shouldHideUsers = false,
			usersTestId,
			additionalContent,
			footer,
		} = {})
		{
			if (!Type.isArray(additionalContent))
			{
				additionalContent = [];
			}

			if (!Type.isArray(content))
			{
				content = [];
			}

			const subtitleProps = {
				title: Type.isString(header.title) ? header.title : null,
				testId: Type.isString(header.testId) ? header.testId : null,
				timestamp: Type.isNumber(header.timestamp) ? header.timestamp : null,
				icon: Type.isStringFilled(header.icon) ? header.icon : null,
			};

			if (Type.isString(footer))
			{
				footer = {
					text: footer,
				};
			}

			const hasTitleButton = Type.isObjectLike(titleButton);

			return StepWrapper(
				wrapperOptions,
				// left part
				Counter(counterOptions),
				// step content container
				StepContent(
					{
						style: {
							marginTop: hasTitleButton ? 5 : 12,
						},
					},
					// title and button
					StepTitle({
						testId: titleTestId,
						text: title,
						button: titleButton,
					}),
					// header container
					StepSubtitle(subtitleProps),
					...content,
					// user(s)
					StepUserList({
						ref: (ref) => {
							this.stepsUserList.push(ref);
						},
						layout: this.props.layout,
						shouldHideUsers: shouldHideUsers === true,
						testId: usersTestId,
						users: users
							.filter((user) => Type.isObjectLike(user) && user.id && this.getUserDataById(user.id))
							.map((user) => mergeImmutable(user, this.getUserDataById(user.id))),
					}),
					...additionalContent,
					// footer
					footer && View(
						{
							style: {
								flexDirection: 'row',
								marginTop: 4,
								marginBottom: 2,
							},
						},
						BBCodeText({
							linksUnderline: false,
							value: footer.text,
							onLinkClick: footer.onLinkClick,
							style: {
								color: AppTheme.colors.base4,
								fontSize: 12,
								fontWeight: '500',
							},
						}),
					),
				),
			);
		}

		/**
		 *
		 * @param {{
		 *		title: string,
		 *		wrapperOptions: ?{
		 * 	   		showBorders: ?boolean,
		 *     		borderOptions: ?{
		 *         		style: ?string,
		 *         		width: ?number,
		 *         		color: ?string,
		 *         		radius: ?number,
		 *     		},
		 *     		backgroundColor: string,
		 * 		},
		 *		counterOptions: {
		 *			value: ?number,
		 *			iconContent: ?string,
	 	 * 			backgroundColor: ?string,
	 	 * 			color: ?string,
		 * 			trunkColor: ?string,
	 	 * 			hasTail: ?boolean,
	 	 * 			tailColor: ?string,
		 * 			size: ?number,
		 * 		},
		 * }} options
		 * @return {object}
		 */
		renderStub(options)
		{
			const wrapperOptions = (
				Type.isObjectLike(options.wrapperOptions)
					? options.wrapperOptions
					: {}
			);

			return StepWrapper(
				wrapperOptions,
				Counter(options.counterOptions),
				ContentStub(options, UserStub()),
			);
		}

		isTaskCompleted(task)
		{
			return this.isTaskAccepted(task) || this.isTaskDeclined(task);
		}

		isTaskAccepted(task)
		{
			return task.status === 1 || task.status === 3;
		}

		isTaskDeclined(task)
		{
			return task.status === 2 || task.status === 4 || task.status === 5;
		}
	}

	module.exports = { WorkflowTimeline };
});
