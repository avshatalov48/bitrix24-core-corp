/**
 * @module tasks/layout/task/fields/datePlanEnd
 */
jn.define('tasks/layout/task/fields/datePlanEnd', (require, exports, module) => {
	const { Loc } = require('loc');
	const { DateTimeField } = require('layout/ui/fields/datetime');
	const { dayMonth, shortTime } = require('utils/date/formats');

	class DatePlanEnd extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				endDatePlan: props.endDatePlan,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				endDatePlan: props.endDatePlan,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				endDatePlan: newState.endDatePlan,
			});
		}

		handleOnChange(date)
		{
			this.props.datesResolver.updateEndDate(date);
		}

		render()
		{
			return DateTimeField({
				readOnly: this.state.readOnly,
				showEditIcon: !this.state.readOnly,
				title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_DATE_PLAN_END'),
				value: ((this.state.endDatePlan / 1000) || ''),
				config: {
					deepMergeStyles: this.props.deepMergeStyles,
					enableTime: true,
					dateFormat: `${dayMonth()} ${shortTime()}`,
				},
				testId: 'datePlanEnd',
				onChange: this.handleOnChange,
			});
		}
	}

	module.exports = { DatePlanEnd };
});
