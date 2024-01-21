/**
 * @module tasks/layout/task/fields/datePlan
 */
jn.define('tasks/layout/task/fields/datePlan', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { DatePlanIs } = require('tasks/layout/task/fields/datePlanIs');
	const { DatePlanStart } = require('tasks/layout/task/fields/datePlanStart');
	const { DatePlanEnd } = require('tasks/layout/task/fields/datePlanEnd');
	const { DatePlanDuration } = require('tasks/layout/task/fields/datePlanDuration');

	class DatePlan extends LayoutComponent
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

		getDeepMergeStyles()
		{
			return {
				...this.props.deepMergeStyles,
				externalWrapper: {
					...this.props.deepMergeStyles.externalWrapper,
					marginHorizontal: 10,
				},
			};
		}

		animateBlock(isDatePlan)
		{
			this.datePlanBlockRef.animate({
				duration: 200,
				height: (isDatePlan ? 200 : 0),
			});
			this.datePlanBlockRef.animate({
				duration: 600,
				opacity: (isDatePlan ? 1 : 0),
			});
		}

		render()
		{
			return View(
				{
					style: (this.props.style || {}),
					testId: 'datePlanField',
				},
				new DatePlanIs({
					readOnly: this.state.readOnly,
					isDatePlan: this.state.isDatePlan,
					deepMergeStyles: this.getDeepMergeStyles(),
					ref: (ref) => this.props.onDatePlanIsRef(ref),
					onChange: (value) => {
						this.animateBlock(value);
						this.props.onChange(value);
					},
				}),
				View(
					{
						style: {
							height: (this.state.isDatePlan ? 200 : 0),
							opacity: (this.state.isDatePlan ? 1 : 0),
						},
						ref: (ref) => {
							this.datePlanBlockRef = ref;
						},
					},
					DatePlan.renderWithTopBorder(
						new DatePlanStart({
							readOnly: this.state.readOnly,
							startDatePlan: this.props.startDatePlan,
							datesResolver: this.props.datesResolver,
							deepMergeStyles: this.getDeepMergeStyles(),
							ref: (ref) => this.props.onDatePlanStartRef(ref),
						}),
					),
					DatePlan.renderWithTopBorder(
						new DatePlanEnd({
							readOnly: this.state.readOnly,
							endDatePlan: this.props.endDatePlan,
							datesResolver: this.props.datesResolver,
							deepMergeStyles: this.getDeepMergeStyles(),
							ref: (ref) => this.props.onDatePlanEndRef(ref),
						}),
					),
					DatePlan.renderWithTopBorder(
						new DatePlanDuration({
							readOnly: this.state.readOnly,
							datesResolver: this.props.datesResolver,
							deepMergeStyles: this.getDeepMergeStyles(),
							ref: (ref) => this.props.onDatePlanDurationRef(ref),
						}),
					),
				),
			);
		}

		static renderWithTopBorder(field)
		{
			return View(
				{},
				View({
					style: {
						height: 0.5,
						backgroundColor: AppTheme.colors.bgSeparatorSecondary,
					},
				}),
				field,
			);
		}
	}

	module.exports = { DatePlan };
});
