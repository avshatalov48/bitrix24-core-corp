/**
 * @module tasks/layout/simple-list/items/task-redux/task-content
 */
jn.define('tasks/layout/simple-list/items/task-redux/task-content', (require, exports, module) => {
	const { transition, pause, chain } = require('animation');
	const { Color, Indent } = require('tokens');
	const { PureComponent } = require('layout/pure-component');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { CounterView } = require('layout/ui/counter-view');
	const { connect } = require('statemanager/redux/connect');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const { DeadlinePill } = require('tasks/layout/deadline-pill');
	const {
		selectByTaskIdOrGuid,
		selectCounter,
		selectIsCompleted,
		selectTimerState,
		setTimeElapsed,
	} = require('tasks/statemanager/redux/slices/tasks');
	const { Moment } = require('utils/date/moment');
	const { DynamicDateFormatter } = require('utils/date/dynamic-date-formatter');
	const { date, dayShortMonth, shortTime } = require('utils/date/formats');
	const { withPressed } = require('utils/color');
	const { TaskStatus, TaskCounter, TimerState } = require('tasks/enum');
	const { Text3, Text5 } = require('ui-system/typography/text');
	const { TimeTrackingTimerIcon } = require('tasks/layout/fields/time-tracking/timer');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Loc } = require('loc');

	class TaskContent extends PureComponent
	{
		containerRef = null;
		isBlinking = false;

		shouldComponentUpdate(nextProps, nextState)
		{
			if (this.props.id !== nextProps.id)
			{
				return true;
			}

			return super.shouldComponentUpdate(nextProps, nextState);
		}

		get task()
		{
			return this.props.task;
		}

		get backgroundColor()
		{
			const isPinned = this.task?.isPinned && this.props.itemLayoutOptions.canBePinned;

			return isPinned ? Color.bgContentSecondary.toHex() : Color.bgContentPrimary.toHex();
		}

		get showBorder()
		{
			return this.props?.showBorder ?? false;
		}

		get showDelimiter()
		{
			if (this.showLastPinnedDelimiter)
			{
				return false;
			}

			return this.showBorder;
		}

		get showLastPinnedDelimiter()
		{
			if (!this.showBorder)
			{
				return false;
			}

			return this.props?.isLastPinned ?? false;
		}

		async blink()
		{
			if (this.isBlinking)
			{
				return;
			}

			this.isBlinking = true;

			await chain(
				transition(this.containerRef, {
					duration: 500,
					backgroundColor: Color.accentSoftOrange3.toHex(),
				}),
				pause(1600),
				transition(this.containerRef, {
					duration: 500,
					backgroundColor: this.backgroundColor,
				}),
			)();

			this.isBlinking = false;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						backgroundColor: withPressed(this.backgroundColor),
					},
					testId: `${this.props.testId}_ITEM_${this.props.id}`,
					ref: (ref) => {
						this.containerRef = ref;
					},
				},
				View({
					style: this.styles.itemWrapper(this.showLastPinnedDelimiter, this.backgroundColor),
				}),
				View(
					{
						style: this.styles.itemContent(this.showDelimiter, this.backgroundColor, this.task?.isPinned),
					},
					this.renderHeader(),
					this.renderBody(),
				),
			);
		}

		renderHeader()
		{
			return View(
				{
					testId: `${this.props.testId}_SECTION`,
					style: this.styles.header(this.task),
				},
				Text3({
					accent: true,
					testId: `${this.props.testId}_SECTION_TITLE`,
					style: this.styles.title(this.task?.isCompleted, this.task?.status),
					text: this.task?.name || this.props.id,
					numberOfLines: 2,
					ellipsize: 'end',
				}),
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					this.renderImportantIcon(),
					this.renderMuteIcon(),
					this.renderLastActivityDate(),
				),
			);
		}

		renderMuteIcon()
		{
			return this.renderStateTopIcon({
				icon: Icon.SOUND_OFF,
				color: Color.base3,
				testId: `${this.props.testId}_MUTE`,
				shouldShow: this.task?.isMuted,
			});
		}

		renderImportantIcon()
		{
			return this.renderStateTopIcon({
				icon: Icon.FIRE,
				color: Color.accentMainWarning,
				testId: `${this.props.testId}_IMPORTANT`,
				shouldShow: this.task?.priority === 2,
			});
		}

		renderStateTopIcon({ icon, color, testId, shouldShow = true })
		{
			return View(
				{
					testId,
					style: this.styles.stateIconWrapper(shouldShow),
				},
				IconView({ size: 16, color, icon }),
			);
		}

		renderLastActivityDate()
		{
			if (!this.task?.activityDate)
			{
				return null;
			}

			const formatter = new DynamicDateFormatter({
				config: {
					[DynamicDateFormatter.periods.DAY]: shortTime(),
					[DynamicDateFormatter.deltas.WEEK]: 'E',
					[DynamicDateFormatter.periods.YEAR]: dayShortMonth(),
				},
				defaultFormat: date(),
			});

			const formattedTime = formatter.format(new Moment(this.task.activityDate * 1000));

			return Text5({
				testId: `${this.props.testId}_LAST_ACTIVITY_DATE`,
				text: formattedTime,
				style: this.styles.date,
			});
		}

		renderBody()
		{
			if (this.task?.isCreationErrorExist)
			{
				return View(
					{
						style: this.styles.body(this.task),
					},
					this.renderResponsible(),
					this.renderCreationError(),
				);
			}

			return View(
				{
					style: this.styles.body(this.task),
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'flex-start',
							flex: 1,
						},
					},
					View(
						{
							style: {
								...this.styles.bodySection,
								flexGrow: 1,
								marginLeft: 0,
							},
						},
						this.renderResponsible(),
						this.renderDeadline(),
					),
					View(
						{
							style: {
								...this.styles.bodySection,
								width: 80,
							},
						},
						this.renderChecklist(),
						this.renderRepetition(),
					),
				),
				View(
					{
						style: {
							...this.styles.bodySection,
							minWidth: 72,
							justifyContent: 'flex-end',
						},
					},
					this.renderTimeTrackingIcon(),
					this.renderCounter(),
					this.renderPinIcon(),
				),
			);
		}

		renderResponsible()
		{
			return Avatar({
				id: this.task?.responsible,
				testId: `${this.testId}_RESPONSIBLE`,
				size: 28,
				withRedux: true,
			});
		}

		renderDeadline()
		{
			return DeadlinePill({
				id: this.task?.id,
				testId: `${this.props.testId}_DEADLINE`,
				backgroundColor: this.backgroundColor,
			});
		}

		renderChecklist()
		{
			const checklistItems = this.task?.checklist;
			if (!checklistItems)
			{
				return null;
			}

			const checklistItemsCount = checklistItems.completed + checklistItems.uncompleted;

			if (checklistItemsCount === 0)
			{
				return null;
			}

			const completedValue = checklistItems.completed > 99 ? '99+' : checklistItems.completed;
			const checklistItemsCountValue = checklistItemsCount > 99 ? '99+' : checklistItemsCount;

			return View(
				{
					testId: `${this.props.testId}_CHECKLIST`,
					style: {
						flexDirection: 'row',
						marginRight: 10,
						position: 'absolute',
						left: 0,
					},
				},
				IconView({
					size: 20,
					color: Color.base3,
					icon: Icon.COMPLETE_TASK_LIST,
					style: {
						marginRight: Indent.XS2.toNumber(),
					},
				}),
				Text5({
					style: {
						color: Color.base3.toHex(),
					},
					text: `${completedValue}/${checklistItemsCountValue}`,
				}),
			);
		}

		renderRepetition()
		{
			// todo: tmp
			return null;

			return IconView({
				size: 20,
				icon: Icon.REPEAT,
				color: Color.base3,
				style: {
					position: 'absolute',
					right: 0,
				},
			});
		}

		renderCounter()
		{
			const counter = this.task?.counter;

			if (counter && counter.value > 0)
			{
				let counterColor = Color.base5.toHex();

				if (counter.type === TaskCounter.ALERT)
				{
					counterColor = Color.accentMainAlert.toHex();
				}
				else if (counter.type === TaskCounter.SUCCESS)
				{
					counterColor = Color.accentMainSuccess.toHex();
				}

				return View(
					{
						style: {
							marginLeft: Indent.M.getValue(),
						},
					},
					CounterView(
						counter.value,
						{
							isDouble: counter.isDouble,
							firstColor: counterColor,
							secondColor: Color.accentMainSuccess.toHex(),
						},
					),
				);
			}

			return null;
		}

		renderPinIcon()
		{
			const counter = this.task?.counter;
			const isPinned = this.task?.isPinned;

			if (counter && counter.value > 0)
			{
				return null;
			}

			if (isPinned && this.props.itemLayoutOptions.canBePinned)
			{
				return IconView({
					testId: `${this.props.testId}_PIN`,
					icon: Icon.PIN,
					color: Color.base3,
					style: {
						marginLeft: Indent.M.getValue(),
					},
				});
			}

			return null;
		}

		renderTimeTrackingIcon()
		{
			const allowTimeTracking = this.task?.allowTimeTracking;
			const timerState = this.task?.timerState;
			const seconds = this.task?.timeElapsed;
			const timeEstimate = this.task?.timeEstimate;
			const isActive = this.task?.isTimerRunningForCurrentUser;
			const Colors = {
				[TimerState.OVERDUE]: Color.accentMainAlert.toHex(),
				[TimerState.RUNNING]: Color.accentMainPrimaryalt.toHex(),
				[TimerState.PAUSED]: Color.base3.toHex(),
			};

			if (!allowTimeTracking)
			{
				return null;
			}

			return new TimeTrackingTimerIcon({
				timeEstimate,
				seconds,
				isActive,
				testId: `${this.props.testId}_TIME_TRACKING_${timerState}`,
				color: Colors[timerState],
				onTimeOver: this.#onTimeOver,
			});
		}

		#onTimeOver = (timeElapsed) => {
			dispatch(setTimeElapsed({
				timeElapsed,
				taskId: this.task?.id,
			}));
		};

		renderCreationError()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				Text5({
					style: {
						color: Color.accentMainAlert.toHex(),
					},
					text: Loc.getMessage('M_TASKS_TASK_ITEM_ERROR'),
				}),
				IconView({
					style: {
						marginLeft: Indent.XS.toNumber(),
					},
					icon: Icon.ALERT,
					color: Color.accentMainAlert,
				}),
			);
		}

		get styles()
		{
			return {
				itemWrapper: (showBorder, backgroundColor) => ({
					flexGrow: 1,
					height: 0.5,
					width: '100%',
					position: 'absolute',
					bottom: 0,
					backgroundColor: showBorder ? Color.bgSeparatorPrimary.toHex() : backgroundColor,
				}),
				itemContent: (showBorder, backgroundColor, isPinned) => ({
					marginHorizontal: 18,
					paddingTop: 4,
					flexGrow: 1,
					borderBottomWidth: showBorder ? 0.5 : 0,
					borderBottomColor: (
						showBorder
							? (isPinned ? Color.bgSeparatorPrimary.toHex() : Color.bgSeparatorSecondary.toHex())
							: backgroundColor
					),
				}),
				header: (shouldShow) => ({
					display: shouldShow ? 'flex' : 'none',
					flexDirection: 'row',
					alignItems: 'flex-start',
					marginTop: 8,
					marginBottom: 14,
					flexGrow: 1,
				}),
				title: (isCompleted, status) => ({
					flex: 1,
					marginRight: 10,
					marginBottom: -0.5,
					color: isCompleted && status !== TaskStatus.SUPPOSEDLY_COMPLETED ? Color.base4.toHex() : Color.base1.toHex(),
					textDecorationLine: isCompleted && status !== TaskStatus.SUPPOSEDLY_COMPLETED ? 'line-through' : 'none',
					marginTop: 0,
				}),
				iconSmall: {
					width: 18,
					height: 18,
				},
				iconSmaller: {
					height: 16,
					width: 16,
				},
				stateIconWrapper: (shouldShow) => ({
					display: shouldShow ? 'flex' : 'none',
					marginRight: 9,
					marginTop: 2,
				}),
				date: {
					color: Color.base3.toHex(),
					textAlign: 'right',
					marginLeft: 2,
					minWidth: 32,
					marginBottom: 0,
					paddingTop: 0,
					marginTop: 2,
				},
				body: (shouldShow) => ({
					display: shouldShow ? 'flex' : 'none',
					flexDirection: 'row',
					justifyContent: 'space-between',
					alignItems: 'center',
					flexGrow: 1,
					marginBottom: 16,
				}),
				bodySection: {
					flexDirection: 'row',
					alignItems: 'center',
				},
			};
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const taskId = ownProps.id;
		const task = selectByTaskIdOrGuid(state, taskId);

		if (!task)
		{
			return { task };
		}

		const {
			id,
			name,
			responsible,
			priority,
			isPinned,
			isMuted,
			isRepeatable,
			checklist,
			activityDate,
			status,
			allowTimeTracking,
			timeElapsed,
			timeEstimate,
			isTimerRunningForCurrentUser,
			isCreationErrorExist,
		} = task;

		return {
			task: {
				id,
				name,
				responsible,
				priority,
				isPinned,
				isMuted,
				isRepeatable,
				checklist,
				status,
				allowTimeTracking,
				timeElapsed,
				timeEstimate,
				isTimerRunningForCurrentUser,
				isCreationErrorExist,
				timerState: selectTimerState(task),
				activityDate: activityDate - (activityDate % 60),
				counter: selectCounter(task),
				isCompleted: selectIsCompleted(task),
			},
		};
	};

	module.exports = {
		TaskContentView: connect(mapStateToProps)(TaskContent),
	};
});
