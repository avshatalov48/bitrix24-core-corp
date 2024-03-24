/**
 * @module tasks/layout/deadline-pill
 */
jn.define('tasks/layout/deadline-pill', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { connect } = require('statemanager/redux/connect');
	const { DeadlinePicker } = require('tasks/deadline-picker');
	const { DeadlineFriendlyDate } = require('tasks/layout/deadline-friendly-date');
	const { executeIfOnline } = require('tasks/layout/online');
	const { withPressed, transparent } = require('utils/color');
	const { Moment } = require('utils/date');
	const { PropTypes } = require('utils/validation');
	const { showToast } = require('toast');
	const { Haptics } = require('haptics');
	const {
		selectById,
		selectIsCompleted,
		selectActions,
		updateDeadline,
	} = require('tasks/statemanager/redux/slices/tasks');
	const { TaskStatus } = require('tasks/enum');

	/**
	 * @class DeadlinePillView
	 */
	class DeadlinePillView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.init(props);

			this.onDeadlineClick = this.onDeadlineClick.bind(this);
		}

		componentWillReceiveProps(props)
		{
			super.componentWillReceiveProps(props);
			this.init(props);
		}

		init(props)
		{
			const { deadline = Date.now() / 1000 } = props;

			this.moment = new Moment(deadline * 1000);
		}

		render()
		{
			const style = Styles.wrapper(this.props.isExpired, this.props.isCompleted, this.props.backgroundColor);

			return View(
				{
					style,
					testId: this.props.testId,
					onClick: this.onDeadlineClick,
				},
				this.renderDeadlineText(),
				this.renderChevron(),
			);
		}

		renderDeadlineText()
		{
			if (this.props.status === TaskStatus.DEFERRED)
			{
				return Text({
					text: Loc.getMessage('TASKSMOBILE_DEADLINE_PILL_DEFERRED'),
					style: Styles.deadlineText(false, this.props.isCompleted),
				});
			}

			if (this.props.status === TaskStatus.SUPPOSEDLY_COMPLETED)
			{
				return Text({
					text: Loc.getMessage('TASKSMOBILE_DEADLINE_PILL_SUPPOSEDLY_COMPLETED'),
					style: Styles.deadlineText(false, false),
				});
			}

			if (this.props.deadline > 0)
			{
				return new DeadlineFriendlyDate({
					moment: this.moment,
					style: Styles.deadlineText(this.props.isExpired, this.props.isCompleted),
				});
			}

			return Text({
				text: Loc.getMessage('TASKSMOBILE_DEADLINE_PILL_NO_DEADLINE'),
				style: Styles.deadlineText(false, this.props.isCompleted),
			});
		}

		renderChevron()
		{
			if (!this.props.canChange)
			{
				return null;
			}

			return Image({
				style: {
					height: 12,
					width: 12,
					marginLeft: 5,
					marginRight: -3,
				},
				tintColor: Styles.deadlineTextColor(this.props.isExpired, this.props.isCompleted),
				svg: {
					content: '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path id="Icon" fill-rule="evenodd" clip-rule="evenodd" d="M8.83365 4.0752L6.57013 6.33872L5.99987 6.90015L5.44042 6.33872L3.1769 4.0752L2.37817 4.87393L6.00524 8.50101L9.63232 4.87393L8.83365 4.0752Z" fill="#909090"/></svg>',
				},
			});
		}

		onDeadlineClick()
		{
			if (!this.props.canChange)
			{
				Haptics.notifyWarning();
				showToast({ message: Loc.getMessage('TASKSMOBILE_DEADLINE_PILL_CANT_CHANGE') }, layout);

				return;
			}

			const currentDeadline = (this.props.deadline ? this.props.deadline * 1000 : null);

			(new DeadlinePicker()).show(currentDeadline)
				.then((deadline) => {
					if (deadline > 0 && deadline !== currentDeadline)
					{
						executeIfOnline(() => {
							this.props.updateDeadline({ deadline, taskId: this.props.id });
							if (this.props.onChange)
							{
								this.props.onChange(deadline);
							}
						});
					}
				})
				.catch(console.error)
			;
		}
	}

	DeadlinePillView.propTypes = {
		id: PropTypes.number,
		testId: PropTypes.string,
		backgroundColor: PropTypes.string,
		deadline: PropTypes.number,
		isCompleted: PropTypes.bool,
		isExpired: PropTypes.bool,
		canChange: PropTypes.bool,
	};

	const Styles = {
		wrapper: (isExpired, isCompleted, defaultBackgroundColor) => {
			return {
				borderWidth: 1,
				borderColor: isExpired && !isCompleted
					? transparent(AppTheme.colors.accentMainAlert, 0.3)
					: AppTheme.colors.bgSeparatorPrimary,
				backgroundColor: isExpired && !isCompleted
					? {
						default: defaultBackgroundColor,
						pressed: AppTheme.colors.accentSoftRed1,
					}
					: withPressed(defaultBackgroundColor),
				flexDirection: 'row',
				alignItems: 'center',
				justifyContent: 'center',
				borderRadius: 14,
				marginLeft: 10,
				paddingHorizontal: 8,
				paddingVertical: 1,
			};
		},
		deadlineText: (isExpired, isCompleted) => {
			return {
				color: Styles.deadlineTextColor(isExpired, isCompleted),
				fontSize: 12,
				marginVertical: 3,
			};
		},
		deadlineTextColor: (isExpired, isCompleted) => {
			let color = AppTheme.colors.base6;

			if (!isCompleted)
			{
				color = isExpired ? AppTheme.colors.accentMainAlert : AppTheme.colors.base3;
			}

			return color;
		},
	};

	const mapStateToProps = (state, ownProps) => {
		const taskId = Number(ownProps.id);
		const task = selectById(state, taskId);

		if (!task)
		{
			return { task };
		}

		return {
			deadline: task.deadline,
			isExpired: task.isExpired,
			isCompleted: selectIsCompleted(task),
			canChange: selectActions(task).updateDeadline,
			status: task.status,
		};
	};

	const mapDispatchToProps = ({
		updateDeadline,
	});

	module.exports = {
		DeadlinePill: connect(mapStateToProps, mapDispatchToProps)(DeadlinePillView),
	};
});
