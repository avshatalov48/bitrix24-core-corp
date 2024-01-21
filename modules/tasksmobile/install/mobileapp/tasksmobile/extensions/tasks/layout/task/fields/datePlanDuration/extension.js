/**
 * @module tasks/layout/task/fields/datePlanDuration
 */
jn.define('tasks/layout/task/fields/datePlanDuration', (require, exports, module) => {
	const { Loc } = require('loc');
	const { CombinedField } = require('layout/ui/fields/combined');
	const { NumberField, NumberPrecision } = require('layout/ui/fields/number');
	const { SelectField } = require('layout/ui/fields/select');

	class DatePlanDuration extends LayoutComponent
	{
		static get type()
		{
			return {
				days: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_DATE_PLAN_DURATION_TYPE_DAYS'),
				hours: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_DATE_PLAN_DURATION_TYPE_HOURS'),
				mins: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_DATE_PLAN_DURATION_TYPE_MINUTES'),
			};
		}

		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				duration: props.datesResolver.durationByType,
				durationType: props.datesResolver.durationType,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				duration: props.datesResolver.durationByType,
				durationType: props.datesResolver.durationType,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				duration: (newState.duration || ''),
				durationType: newState.durationType,
			});
		}

		handleOnChange({ duration, durationType })
		{
			if (Number(duration) !== this.state.duration)
			{
				if (this.timerId)
				{
					clearTimeout(this.timerId);
					this.timerId = null;
				}
				this.timerId = setTimeout(() => {
					this.props.datesResolver.updateDuration(Number(duration));
				}, 1000);
			}

			if (durationType !== this.state.durationType)
			{
				this.props.datesResolver.updateDurationType(durationType);
			}
		}

		render()
		{
			return CombinedField({
				value: {
					duration: this.state.duration,
					durationType: this.state.durationType,
				},
				config: {
					deepMergeStyles: {
						combinedContainer: {
							alignItems: 'flex-end',
							paddingTop: undefined,
							paddingBottom: undefined,
						},
						primaryFieldWrapper: this.props.deepMergeStyles.externalWrapper,
						secondaryFieldWrapper: this.props.deepMergeStyles.externalWrapper,
						secondaryFieldContainer: {
							flex: 1,
							width: undefined,
						},
					},
					primaryField: {
						id: 'duration',
						renderField: NumberField,
						readOnly: this.state.readOnly,
						title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_DATE_PLAN_DURATION'),
						placeholder: '0',
						config: {
							type: NumberPrecision.INTEGER,
							selectionOnFocus: false,
						},
					},
					secondaryField: {
						id: 'durationType',
						renderField: SelectField,
						readOnly: this.state.readOnly,
						required: true,
						showRequired: false,
						showTitle: false,
						config: {
							items: Object.entries(DatePlanDuration.type).map(([value, name]) => ({
								name,
								value,
							})),
						},
					},
				},
				testId: 'datePlanDuration',
				onChange: this.handleOnChange,
			});
		}
	}

	module.exports = { DatePlanDuration };
});
