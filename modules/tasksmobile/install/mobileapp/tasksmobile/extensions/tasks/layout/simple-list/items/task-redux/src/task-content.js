/**
 * @module tasks/layout/simple-list/items/task-redux/task-content
 */
jn.define('tasks/layout/simple-list/items/task-redux/task-content', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { CounterView } = require('layout/ui/counter-view');
	const { Avatar } = require('layout/ui/user/avatar');
	const { connect } = require('statemanager/redux/connect');
	const { DeadlinePill } = require('tasks/layout/deadline-pill');
	const { selectById, selectCounter, selectIsCompleted } = require('tasks/statemanager/redux/slices/tasks');
	const { ConfigurableDateByTimeDeltaTokens } = require('utils/date');
	const { date, dayShortMonth, shortTime } = require('utils/date/formats');
	const { withPressed } = require('utils/color');
	const { get } = require('utils/object');

	class TaskContent extends PureComponent
	{
		constructor(props)
		{
			super(props);
		}

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

			return isPinned ? AppTheme.colors.bgContentSecondary : AppTheme.colors.bgContentPrimary;
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

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						backgroundColor: withPressed(this.backgroundColor),
					},
				},
				View({
					style: Styles.itemWrapper(this.showLastPinnedDelimiter, this.backgroundColor),
				}),
				View(
					{
						style: Styles.itemContent(this.showDelimiter, this.backgroundColor),
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
					style: Styles.header(this.task),
				},
				Text({
					testId: `${this.props.testId}_SECTION_TITLE`,
					style: Styles.title(this.task?.isCompleted),
					text: this.task?.name || this.props.id,
					numberOfLines: 2,
					ellipsize: 'end',
				}),
				View(
					{
						style: {
							flexDirection: 'row',
							marginTop: 2,
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
			return this.renderStateTopIcon(Icons.mute, `${this.props.testId}_MUTE`, this.task?.isMuted);
		}

		renderImportantIcon()
		{
			return this.renderStateTopIcon(
				Icons.important,
				`${this.props.testId}_IMPORTANT`,
				this.task?.priority === 2,
			);
		}

		renderStateTopIcon(iconContent, testId, shouldShow = true)
		{
			return View(
				{
					testId,
					style: Styles.stateIconWrapper(shouldShow),
				},
				Image({
					style: Styles.iconSmaller,
					svg: {
						content: iconContent,
					},
				}),
			);
		}

		renderLastActivityDate()
		{
			if (!this.task?.activityDate)
			{
				return null;
			}

			const formattedTime = ConfigurableDateByTimeDeltaTokens({
				timestamp: this.task.activityDate,
				deltas: {
					day: shortTime(),
					week: 'E',
					year: dayShortMonth(),
				},
				defaultFormat: date(),
			});

			return Text({
				testId: `${this.props.testId}_LAST_ACTIVITY_DATE`,
				text: formattedTime,
				style: Styles.date,
			});
		}

		renderBody()
		{
			return View(
				{
					style: Styles.body(this.task),
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
								...Styles.bodySection,
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
								...Styles.bodySection,
								width: 100,
							},
						},
						this.renderChecklist(),
						this.renderRepetition(),
					),
				),
				View(
					{
						style: {
							...Styles.bodySection,
							minWidth: 72,
							justifyContent: 'flex-end',
						},
					},
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
				Image({
					style: {
						width: 20,
						height: 20,
					},
					resizeMode: 'contain',
					svg: {
						content: Icons.checklist,
					},
				}),
				Text({
					style: {
						fontSize: 12,
						fontWeight: '400',
						color: AppTheme.colors.base4,
					},
					text: `${completedValue}/${checklistItemsCountValue}`,
				}),
			);
		}

		renderRepetition()
		{
			// todo: tmp
			return null;

			if (!this.task?.isRepeatable)
			{
				return null;
			}

			return Image({
				style: {
					width: 20,
					height: 20,
					position: 'absolute',
					right: 0,
				},
				svg: {
					content: Icons.repetition,
				},
			});
		}

		renderCounter()
		{
			const counter = this.task?.counter;

			if (counter && counter.value > 0)
			{
				return View(
					{},
					CounterView(
						counter.value,
						{
							isDouble: counter.isDouble,
							firstColor: counter.color,
							secondColor: AppTheme.colors.accentMainSuccess,
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
				return Image({
					testId: `${this.props.testId}_PIN`,
					svg: {
						content: Icons.pin,
					},
					style: Styles.iconSmaller,
				});
			}

			return null;
		}
	}

	const Styles = {
		itemWrapper: (showBorder, backgroundColor) => ({
			flexGrow: 1,
			height: 1,
			width: '100%',
			position: 'absolute',
			bottom: 0,
			backgroundColor: showBorder ? AppTheme.colors.bgSeparatorPrimary : backgroundColor,
		}),
		itemContent: (showBorder, backgroundColor) => ({
			marginHorizontal: 18,
			paddingTop: 4,
			flexGrow: 1,
			borderBottomWidth: showBorder ? 1 : 0,
			borderBottomColor: showBorder ? AppTheme.colors.bgSeparatorPrimary : backgroundColor,
		}),
		header: (shouldShow) => ({
			display: shouldShow ? 'flex' : 'none',
			flexDirection: 'row',
			alignItems: 'flex-start',
			marginTop: 8,
			marginBottom: 14,
			flexGrow: 1,
		}),
		title: (isCompleted) => ({
			flex: 1,
			fontWeight: '500',
			fontSize: 16,
			marginRight: 10,
			marginBottom: -0.5,
			color: isCompleted ? AppTheme.colors.base4 : AppTheme.colors.base1,
			textDecorationLine: isCompleted ? 'line-through' : 'none',
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
		}),
		date: {
			color: AppTheme.colors.base3,
			textAlign: 'right',
			fontSize: 12,
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

	const Icons = {
		mute: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M8.28842 3.3L4.78471 5.72726V5.82631C4.76236 5.82346 4.73958 5.82198 4.71645 5.82198H2.60583C2.31139 5.82198 2.07269 6.06068 2.07269 6.35512V10.6391C2.07269 10.9335 2.31139 11.1722 2.60583 11.1722H4.71645C4.73958 11.1722 4.76236 11.1708 4.78471 11.1679V11.3289L8.28842 13.6942V3.3Z" fill="#A8ADB4"/><path d="M13.9273 9.85094L13.1851 10.5931L11.7007 9.10875L10.2163 10.5931L9.47412 9.85094L10.9585 8.36656L9.47412 6.88218L10.2163 6.14L11.7007 7.62437L13.1851 6.14L13.9273 6.88218L12.4429 8.36656L13.9273 9.85094Z" fill="#A8ADB4"/></svg>',
		important: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.8333 16.6673C11.8805 15.8787 12.4974 14.6449 12.5 13.334C12.5 11.5412 11.2888 11.7789 10.0819 9.33722C10.0377 9.2479 9.92551 9.21323 9.84271 9.26866C8.47548 10.184 7.60697 11.6851 7.5 13.334C7.58028 14.6252 8.18185 15.8284 9.16667 16.6673H8.575C6.46795 15.8951 5.0492 13.9109 5 11.6673C5 8.18602 8.16809 4.91146 10.3036 3.62326C10.5285 3.48759 10.7996 3.67666 10.8131 3.93896C10.9674 6.94986 15 8.01431 15 12.2923C15 15.5232 11.425 16.6673 11.425 16.6673H10.8333Z" fill="#FFA900"/></svg>',
		pin: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M13.4756 5.53159C13.7704 5.82703 13.7551 6.32027 13.4415 6.63326C13.1278 6.94626 12.6346 6.96049 12.3399 6.66505L11.79 6.11519L8.29195 10.7015L8.82877 11.2383C9.10414 11.5368 9.08082 12.0141 8.77598 12.3189C8.47114 12.6237 7.99385 12.6471 7.69538 12.3717L6.38413 11.0629L3.00569 13.6073C2.95545 13.6634 2.87984 13.6889 2.80936 13.6734C2.73888 13.6579 2.68516 13.604 2.66987 13.5335C2.65459 13.463 2.68026 13.3874 2.73653 13.3373L5.24338 9.92051L3.95827 8.63621C3.6773 8.33855 3.69834 7.85666 4.00573 7.54948C4.31312 7.2423 4.79501 7.2216 5.09245 7.50279L5.62928 8.03962L10.2164 4.54153L9.67032 3.9955C9.38935 3.69784 9.4104 3.21595 9.71779 2.90877C10.0252 2.60159 10.5071 2.58089 10.8045 2.86208L13.4756 5.53159Z" fill="#A7A7A7"/></svg>',
		repetition: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.25675 9.07471L8.01789 11.8359L6.10394 11.8356L6.10464 13.039C6.10464 13.4214 6.39075 13.7369 6.76056 13.7832L6.85464 13.789H12.9333C13.3156 13.789 13.6312 13.5029 13.6774 13.1331L13.6833 13.039L13.6832 12.619L15.5574 12.7823L15.558 12.9971C15.558 14.4699 14.3641 15.6638 12.8914 15.6638H6.89655C5.42379 15.6638 4.22989 14.4699 4.22989 12.9971L4.22977 11.8356L2.49561 11.8359L5.25675 9.07471ZM12.8914 4.33594C14.3641 4.33594 15.558 5.52984 15.558 7.0026L15.5573 8.18677L17.4007 8.18746L14.6395 10.9486L11.8784 8.18746L13.6831 8.18677L13.6833 6.96069C13.6833 6.54647 13.3475 6.21069 12.9333 6.21069H6.85464C6.47228 6.21069 6.15676 6.4968 6.11048 6.86661L6.10464 6.96069L6.10405 7.27594L4.22989 7.11177V7.0026C4.22989 5.52984 5.42379 4.33594 6.89655 4.33594H12.8914Z" fill="#A8ADB4"/></svg>',
		checklist: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M8.75459 13.5459C8.63809 13.6595 8.45232 13.6595 8.33582 13.5459L5.29393 10.5814C5.13284 10.4244 5.13284 10.1655 5.29393 10.0085L5.96164 9.35771C6.11698 9.20632 6.36467 9.20632 6.52 9.35771L8.54521 11.3314L13.48 6.52208C13.6353 6.3707 13.883 6.3707 14.0384 6.52208L14.7061 7.17282C14.8672 7.32982 14.8672 7.58874 14.7061 7.74574L8.75459 13.5459Z" fill="#A8ADB4"/> </svg>',
	};

	const mapStateToProps = (state, ownProps) => {
		const taskId = Number(ownProps.id);
		const task = selectById(state, taskId);

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
