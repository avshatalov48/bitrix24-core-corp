/**
 * @module tasks/layout/task/fields/datePlanStart
 */
jn.define('tasks/layout/task/fields/datePlanStart', (require, exports, module) => {
	const {Loc} = require('loc');
	const {DateTimeField} = require('layout/ui/fields/datetime');

	class DatePlanStart extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				startDatePlan: props.startDatePlan,
			};
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
					dateFormat: 'd MMMM, HH:mm',
				},
				testId: 'datePlanStart',
				onChange: date => this.props.datesResolver.updateStartDate(date),
			});
		}
	}

	module.exports = {DatePlanStart};
});