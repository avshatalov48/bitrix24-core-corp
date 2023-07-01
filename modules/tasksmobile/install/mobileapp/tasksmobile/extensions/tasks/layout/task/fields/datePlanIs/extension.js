/**
 * @module tasks/layout/task/fields/datePlanIs
 */
jn.define('tasks/layout/task/fields/datePlanIs', (require, exports, module) => {
	const {Loc} = require('loc');
	const {BooleanField, BooleanMode} = require('layout/ui/fields/boolean');

	class DatePlanIs extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				isDatePlan: props.isDatePlan,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				isDatePlan: props.isDatePlan,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				isDatePlan: newState.isDatePlan,
			});
		}

		render()
		{
			return BooleanField({
				readOnly: this.state.readOnly,
				showEditIcon: !this.state.readOnly,
				showTitle: false,
				value: this.state.isDatePlan,
				config: {
					deepMergeStyles: {
						...this.props.deepMergeStyles,
						externalWrapper: {
							...this.props.deepMergeStyles.externalWrapper,
							height: 52,
						},
					},
					mode: BooleanMode.SWITCHER,
					description: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_DATE_PLAN_IS'),
					showSwitcher: true,
				},
				testId: 'datePlanIs',
				onChange: (value) => {
					this.setState({isDatePlan: value});
					this.props.onChange(value);
				},
			});
		}
	}

	module.exports = {DatePlanIs};
});