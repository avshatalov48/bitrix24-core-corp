/**
 * @module tasks/layout/task/fields/datePlanEnd
 */
jn.define('tasks/layout/task/fields/datePlanEnd', (require, exports, module) => {
	const {Loc} = require('loc');
	const {DateTimeField} = require('layout/ui/fields/datetime');

	class DatePlanEnd extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				endDatePlan: props.endDatePlan,
			};
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
					dateFormat: 'd MMMM, HH:mm',
				},
				testId: 'datePlanEnd',
				onChange: date => this.props.datesResolver.updateEndDate(date),
			});
		}
	}

	module.exports = {DatePlanEnd};
});