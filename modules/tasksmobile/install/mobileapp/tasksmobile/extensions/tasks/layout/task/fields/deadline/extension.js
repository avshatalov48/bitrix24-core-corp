/**
 * @module tasks/layout/task/fields/deadline
 */
jn.define('tasks/layout/task/fields/deadline', (require, exports, module) => {
	const { Loc } = require('loc');
	const { DateTimeFieldClass } = require('layout/ui/fields/datetime');

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
						dateFormat: 'd MMMM, HH:mm',
						items: this.state.deadlines,
						taskState: this.state.taskState,
						showBalloonDate: this.state.showBalloonDate,
						counter: this.state.counter,
						balloonArrowDownUri: `${this.props.pathToImages}/tasksmobile-layout-task-balloon-arrow-down.png`,
					},
					testId: 'deadline',
					onChange: (date) => this.props.datesResolver.updateDeadline(date),
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
		static getImageUrl(imageUrl)
		{
			let result = imageUrl;

			if (result.indexOf(currentDomain) !== 0)
			{
				result = result.replace(`${currentDomain}`, '');
				result = (result.indexOf('http') === 0 ? result : `${currentDomain}${result}`);
			}

			return encodeURI(result);
		}

		// eslint-disable-next-line no-useless-constructor
		constructor(props)
		{
			super(props);
		}

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
						text: (this.isEmpty() ? this.getEditableEmptyValue() : this.getFormattedDate()),
					}),
					(!this.isReadOnly() && this.renderBalloonArrowDown()),
				),
				(counter && counter.value > 0 && this.renderCounter()),
			);
		}

		renderBalloonArrowDown()
		{
			return Image({
				style: {
					width: 14,
					height: 14,
					alignSelf: 'center',
					marginLeft: 2,
				},
				uri: DeadlineField.getImageUrl(this.getConfig().balloonArrowDownUri),
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
						color: '#ffffff',
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
			};
		}
	}

	module.exports = { Deadline };
});
