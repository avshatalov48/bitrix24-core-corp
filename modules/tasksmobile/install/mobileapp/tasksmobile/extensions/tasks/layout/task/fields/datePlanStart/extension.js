/**
 * @module tasks/layout/task/fields/datePlanStart
 */
jn.define('tasks/layout/task/fields/datePlanStart', (require, exports, module) => {
	const { Loc } = require('loc');
	const { DateTimeField } = require('layout/ui/fields/datetime');
	const { dayMonth, shortTime } = require('utils/date/formats');

	class DatePlanStart extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				startDatePlan: props.startDatePlan,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				startDatePlan: props.startDatePlan,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				startDatePlan: newState.startDatePlan,
			});
		}

		handleOnChange(date)
		{
			this.props.datesResolver.updateStartDate(date);
		}

		render()
		{
			return DateTimeField({
				readOnly: this.state.readOnly,
				showEditIcon: !this.state.readOnly,
				title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_DATE_PLAN_START'),
				value: ((this.state.startDatePlan / 1000) || ''),
				config: {
					deepMergeStyles: this.props.deepMergeStyles,
					enableTime: true,
					dateFormat: `${dayMonth()} ${shortTime()}`,
				},
				testId: 'datePlanStart',
				onChange: this.handleOnChange,
			});
		}
	}

	module.exports = { DatePlanStart };
});
