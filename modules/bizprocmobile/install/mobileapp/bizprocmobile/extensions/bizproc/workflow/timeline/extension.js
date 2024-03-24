/**
 * @module bizproc/workflow/timeline
 * */

jn.define('bizproc/workflow/timeline', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { check, cross, documents, union, clock, flag } = require('bizproc/workflow/timeline/icons');
	const { ContentStub, UserStub, Counter, StepWrapper, StepContent, StepsListCollapsed } = require('bizproc/workflow/timeline/components');
	const { PureComponent } = require('layout/pure-component');
	const { SafeImage } = require('layout/ui/safe-image');
	const { ReduxAvatar } = require('layout/ui/user/avatar');
	const { Loc } = require('loc');
	const { dispatch } = require('statemanager/redux/store');
	const { usersAdded } = require('statemanager/redux/slices/users');
	const { ProfileView } = require('user/profile');
	const { Duration } = require('utils/date/duration');
	const { Type } = require('type');

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
					text: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_TITLE'),
				},
				backgroundColor: AppTheme.colors.bgContentPrimary,
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionPercent: 90,
					navigationBarColor: AppTheme.colors.bgSecondary,
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
				},
				onReady: (readyLayout) => {
					readyLayout.showComponent(new WorkflowTimeline({
						parentLayout: layout,
						layout: readyLayout,
						workflowId: props.workflowId,
						taskId: props.taskId,
					}));
				},
			});
		}

		constructor(props)
		{
			super(props);

			this.testId = 'BpTimeline';

			this.state.timelineData = null;
			this.loadTimelineData()
				.then((timelineData) => {
					this.setState({ timelineData });
					console.log('Timeline data:');
					console.log(timelineData);
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
		 *     documentName: string,
		 *     entityName: string,
		 *     isWorkflowRunning: boolean,
		 *     timeToStart: ?number,
		 *     executionTime: number,
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
		 * } | null}
		 */
		get timelineData()
		{
			return this.state.timelineData;
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
				console.log(error);
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
				this.renderContent(),
			);
		}

		/**
		 * @return {View}
		 */
		renderContent()
		{
			if (this.timelineData)
			{
				return View(
					{
						style: {
							flexDirection: 'column',
							// flexGrow: 1,
							paddingHorizontal: 16,
							paddingVertical: 12,
						},
					},
					this.renderFirstStep(),
					...this.renderTasks(),
					this.renderLastStep(),
				);
			}

			return Loader({
				style: { width: 75, height: 75 },
				animating: true,
			});
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
					value: this.formatDate(this.timelineData.started),
				},
				users: [{ id: this.timelineData.startedBy }],
				additionalContent: [this.renderDocument()],
				footer: Loc.getMessage(
					'BPMOBILE_WORKFLOW_TIMELINE_EXECUTION_TIME',
					{
						'#DURATION#': (
							Type.isNumber(this.timelineData.timeToStart)
								? this.formatDuration(this.timelineData.timeToStart)
								: ''
						),
					},
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
			return DateFormatter.getDateString(timestamp, 'd MMMM kk:mm');
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

			const duration = Duration.createFromSeconds(time);

			const years = duration.years;
			const months = duration.months;

			if (years !== 0)
			{
				return (
					(new Duration((months >= 6 ? years + 1 : years) * Duration.getLengthFormat().YEAR))
						.format('Y')
				);
			}

			const day = duration.days;

			if (months !== 0)
			{
				return (
					(new Duration((day >= 15 ? months + 1 : months) * Duration.getLengthFormat().MONTH))
						.format('m')
				);
			}

			const hour = duration.getUnitPropertyModByFormat('H');

			if (day !== 0)
			{
				return (
					(new Duration((hour >= 12 ? day + 1 : day) * Duration.getLengthFormat().DAY))
						.format('d')
				);
			}

			const minutes = duration.minutes;

			if (hour !== 0)
			{
				return (
					(new Duration((minutes >= 30 ? hour + 1 : hour) * Duration.getLengthFormat().HOUR))
						.format('H')
				);
			}

			if (minutes !== 0)
			{
				return duration.format('i');
			}

			return duration.format('s');
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
				Text({
					testId: `${this.testId}DocumentName`,
					text: this.timelineData.documentName,
					style: {
						color: AppTheme.colors.accentMainLinks,
						fontSize: 15,
						fontWeight: '400',
					},
				}),
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
					if (this.taskId !== task.id && this.canCurrentUserDoTask(task) && taskCount > 1)
					{
						titleButton = {
							id: task.id,
							testId: `${this.testId}TaskButton_${task.id}`,
							text: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_TASK_START'),
							onclick: () => {
								this.openTaskDetails(task.id).catch((err) => console.log(err));
							},
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
							title: {
								text: headerTitle,
								testId: `${this.testId}Task_${task.id}_Status`,
							},
							value: (
								this.isTaskCompleted(task)
									? this.formatDate(task.modified)
									: { iconContent: clock() }
							),
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
			const executionTime = Type.isObjectLike(lastTask) ? this.formatDate(lastTask.modified) : null;

			const isAccepted = !Type.isObjectLike(lastTask) || this.isTaskAccepted(lastTask);

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
					title: {
						text: Loc.getMessage('BPMOBILE_WORKFLOW_TIMELINE_WORKFLOW_COMPLETED'),
						testId: `${this.testId}LastStepStatus`,
					},
					value: executionTime,
				},
				content: Type.isObjectLike(lastTask) ? [this.renderExtendedTaskStatus(lastTask)] : [],
				users: [{ id: this.timelineData.startedBy }],
				footer: Loc.getMessage(
					'BPMOBILE_WORKFLOW_TIMELINE_WORKFLOW_EXECUTION_TIME',
					{ '#DURATION#': this.formatDuration(this.timelineData.executionTime) },
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
		 * 			title: string | { text: string, testId: string },
		 * 			value: string | { text: ?string, iconContent: ?string } | null | undefined,
		 * 		},
		 * 		content: ?Array<View>,
		 * 		users: Array<{id: number, status: number, testId: ?string}>,
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

			if (Type.isString(header.title))
			{
				header.title = {
					text: header.title,
					testId: undefined,
				};
			}

			if (Type.isString(header.value))
			{
				header.value = {
					text: header.value,
				};
			}
			else if (!Type.isObjectLike(header.value))
			{
				header.value = {};
			}

			if (Type.isString(footer))
			{
				footer = {
					text: footer,
				};
			}

			const showUserStatus = users.length > 1;
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
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
								marginBottom: 4,
								justifyContent: 'space-between',
							},
						},
						title && Text({
							testId: titleTestId,
							text: title,
							numberOfLines: 2,
							ellipsize: 'end',
							style: {
								paddingTop: hasTitleButton ? 5 : null,
								alignSelf: 'flex-start',
								flexShrink: 2,
								fontSize: 15,
								fontWeight: '600',
								color: AppTheme.colors.base1,
							},
						}),
						// title button + counter
						hasTitleButton && View(
							{
								style: {
									height: 36,
									justifyContent: 'center',
									marginLeft: 15,
								},
							},
							// button
							View(
								{
									testId: titleButton.testId,
									style: {
										width: 110,
										height: 22,
										justifyContent: 'center',
										alignItems: 'center',
										borderStyle: 'solid',
										borderWidth: 1.2,
										borderColor: AppTheme.colors.accentMainPrimary,
										borderRadius: 24,
									},
									onClick()
									{
										titleButton.onclick(titleButton.id);
									},
								},
								Text({
									text: titleButton.text,
									style: {
										fontSize: 12,
										fontWeight: '500',
										color: AppTheme.colors.accentMainPrimary,
									},
								}),
							),
							// counter
							Text(
								{
									style: {
										position: 'absolute',
										top: 0,
										right: 0,
										width: 18,
										height: 18,
										borderRadius: 9,
										backgroundColor: AppTheme.colors.accentMainAlert,
										textAlign: 'center',
										color: AppTheme.colors.baseWhiteFixed,
										fontSize: 12,
										fontWeight: '500',
									},
									text: '1',
								},
							),
						),
					),
					// header container
					header && View(
						{
							style: {
								flexDirection: 'row',
								justifyContent: 'space-between',
								alignContent: 'center',
								marginTop: 4,
								marginBottom: 2,
								paddingBottom: 8,
							},
						},
						Text({
							text: header.title.text,
							testId: header.title.testId,
							style: {
								color: AppTheme.colors.base4,
								fontSize: 11,
								fontWeight: '500',
							},
						}),
						header.value.text && Text({
							text: header.value.text,
							style: {
								color: AppTheme.colors.base4,
								fontSize: 12,
								fontWeight: '500',
							},
						}),
						!header.value.text && header.value.iconContent && SafeImage({
							style: {
								width: 18,
								height: 18,
							},
							resizeMode: 'contain',
							placeholder: {
								content: header.value.iconContent,
							},
						}),
					),
					...content,
					// user(s)
					View(
						{
							testId: usersTestId,
							style: {
								paddingBottom: 2,
							},
						},
						...users.map((user) => this.renderUser(user, showUserStatus)),
					),
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

		/**
		 *
		 * @param {{id: number, status: number, testId: ?string}} user
		 * @param {boolean} showStatus
		 * @return {View}
		 */
		renderUser(user, showStatus = true)
		{
			if (Type.isObjectLike(user) && user.id && this.getUserDataById(user.id))
			{
				const userData = this.getUserDataById(user.id);

				return View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							paddingBottom: 6,
						},
						onClick: () => this.openUserProfile(user.id),
					},
					ReduxAvatar({
						id: user.id,
					}),
					View(
						{
							style: {
								flex: 1,
								flexDirection: 'column',
								marginHorizontal: 6,
							},
						},
						View(
							{
								style: {
									flexDirection: 'row',
									alignItems: 'center',
								},
							},
							Text({
								testId: user.testId,
								style: {
									textAlign: 'center',
									fontSize: 15,
									fontWeight: '400',
									color: AppTheme.colors.accentMainLinks,
								},
								numberOfLines: 1,
								text: userData.fullName,
							}),
							showStatus && user.status && this.renderUserStatus(user),
						),
						userData.workPosition && Text({
							style: {
								fontSize: 13,
								fontWeight: '400',
								color: AppTheme.colors.base4,
							},
							numberOfLines: 1,
							ellipsize: 'end',
							text: userData.workPosition,
						}),
					),
				);
			}

			return View();
		}

		/**
		 *
		 * @param {{id: number, status: number}} user
		 * @return {?View}
		 */
		renderUserStatus(user)
		{
			if (Type.isObjectLike(user))
			{
				const isAccepted = user.status === 1 || user.status === 3;
				const isDeclined = user.status === 2 || user.status === 4;

				if (!isAccepted && !isDeclined)
				{
					return null;
				}

				const icon = (
					isAccepted
						? check({ color: AppTheme.colors.accentMainSuccess })
						: cross({ color: AppTheme.colors.base4 })
				);

				return View(
					{
						style: {
							marginLeft: 8,
							borderRadius: 4,
							borderStyle: 'solid',
							borderWidth: 1,
							borderColor: isAccepted ? AppTheme.colors.accentMainSuccess : AppTheme.colors.base4,
						},
					},
					SafeImage({
						style: {
							width: 16,
							height: 16,
						},
						resizeMode: 'contain',
						placeholder: {
							content: icon,
						},
					}),
				);
			}

			return null;
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

		/**
		 *
		 * @param {number} userId
		 */
		openUserProfile(userId)
		{
			this
				.props
				.layout
				.openWidget('list', {
					groupStyle: true,
					backdrop: {
						bounceEnable: false,
						swipeAllowed: true,
						showOnTop: true,
						hideNavigationBar: false,
						horizontalSwipeAllowed: false,
					},
				})
				.then((list) => ProfileView.open({ userId, isBackdrop: true }, list))
				.catch((err) => console.log(err))
			;
		}
	}

	module.exports = { WorkflowTimeline };
});
