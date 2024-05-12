/**
 * @module tasks/layout/task/fields/deadline
 */
jn.define('tasks/layout/task/fields/deadline', (require, exports, module) => {
	const { Loc } = require('loc');
	const { chevronDown } = require('assets/common');
	const AppTheme = require('apptheme');
	const { DateTimeFieldClass } = require('layout/ui/fields/datetime');
	const { dayMonth, shortTime } = require('utils/date/formats');

	class Deadline extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				deadline: props.deadline,
				taskState: props.taskState,
				deadlines: props.deadlines,
				showBalloonDate: props.showBalloonDate,
				counter: props.counter,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				deadline: props.deadline,
				taskState: props.taskState,
				deadlines: props.deadlines,
				showBalloonDate: props.showBalloonDate,
				counter: props.counter,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				deadline: newState.deadline,
				taskState: newState.taskState,
				deadlines: newState.deadlines,
				showBalloonDate: newState.showBalloonDate,
				counter: newState.counter,
			});
		}

		handleOnChange(date)
		{
			const { datesResolver } = this.props;

			if (datesResolver)
			{
				datesResolver.updateDeadline(date);
			}
		}

		render()
		{
			return View(
				{
					style: (this.props.style || {}),
				},
				new DeadlineField({
					readOnly: this.state.readOnly,
					showEditIcon: !this.state.readOnly,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_DEADLINE'),
					value: ((this.state.deadline / 1000) || ''),
					emptyValue: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_DEADLINE_EMPTY_VALUE'),
					titlePosition: 'left',
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						enableTime: true,
						dateFormat: `${dayMonth()} ${shortTime()}`,
						items: this.state.deadlines,
						taskState: this.state.taskState,
						showBalloonDate: this.state.showBalloonDate,
						counter: this.state.counter,
					},
					testId: 'deadline',
					onChange: this.handleOnChange,
				}),
			);
		}

		openPicker()
		{
			dialogs.showDatePicker(
				{
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_VIEW_DEADLINE_PICKER_TITLE'),
					type: 'datetime',
					value: this.state.deadline,
					items: this.state.deadlines,
				},
				(eventName, date) => {
					if (date)
					{
						this.props.datesResolver.updateDeadline(date / 1000, true);
					}
				},
			);
		}
	}

	class DeadlineField extends DateTimeFieldClass
	{
		renderContent()
		{
			const { counter } = this.getConfig();

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							...this.styles.balloonStyle,
						},
					},
					Text({
						style: this.styles.value,
						text: (this.isEmpty() ? this.getEditableEmptyValue() : this.getDisplayedValue()),
					}),
					(!this.isReadOnly() && this.renderBalloonArrowDown()),
				),
				(counter && counter.value > 0 && this.renderCounter()),
			);
		}

		renderBalloonArrowDown()
		{
			let tintColor = this.isEmpty() ? AppTheme.colors.base3 : AppTheme.colors.baseWhiteFixed;

			if (this.styles.chevronDownColor)
			{
				tintColor = this.styles.chevronDownColor;
			}

			return Image({
				style: {
					width: 14,
					height: 14,
					alignSelf: 'center',
					marginLeft: 2,
				},
				tintColor,
				svg: {
					content: chevronDown(tintColor, { box: true }),
				},
			});
		}

		renderCounter()
		{
			const { counter } = this.getConfig();

			return View(
				{
					style: {
						alignSelf: 'center',
						alignItems: 'center',
						justifyContent: 'center',
						width: 18,
						height: 18,
						marginLeft: 6,
						backgroundColor: counter.color,
						borderRadius: 9,
					},
					testId: 'deadlineCounter',
				},
				Text({
					style: {
						fontSize: 12,
						fontWeight: '500',
						color: AppTheme.colors.base8,
					},
					text: counter.value.toString(),
				}),
			);
		}

		getDefaultStyles()
		{
			const config = this.getConfig();

			if (!config.showBalloonDate)
			{
				return super.getDefaultStyles();
			}

			const taskState = config.taskState;
			const styles = super.getDefaultStyles();

			return {
				...styles,
				balloonStyle: {
					height: 22,
					backgroundColor: taskState.backgroundColor,
					borderRadius: 15,
					paddingLeft: 9,
					paddingRight: (this.isReadOnly() ? 9 : 5),
					paddingVertical: 2,
				},
				value: {
					...styles.value,
					flex: null,
					fontSize: 12,
					fontWeight: '600',
					fontColor: taskState.fontColor,
					color: taskState.fontColor,
				},
				chevronDownColor: taskState.fontColor,
			};
		}
	}

	module.exports = { Deadline };
});
