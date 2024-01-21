/**
 * @module tasks/layout/task/fields/timeTrackingTime
 */
jn.define('tasks/layout/task/fields/timeTrackingTime', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { CombinedField } = require('layout/ui/fields/combined');
	const { NumberField, NumberPrecision } = require('layout/ui/fields/number');

	class TimeTrackingTime extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const hours = Math.floor(props.timeEstimate / 3600);
			const minutes = Math.floor((props.timeEstimate - hours * 3600) / 60);

			this.state = {
				hours,
				minutes,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			const hours = Math.floor(props.timeEstimate / 3600);
			const minutes = Math.floor((props.timeEstimate - hours * 3600) / 60);

			this.state = {
				hours,
				minutes,
			};
		}

		handleOnChange({ hours, minutes })
		{
			const resultHours = (Type.isUndefined(hours) ? 0 : Number(hours));
			const resultMinutes = (Type.isUndefined(minutes) ? 0 : Number(minutes));

			const newStateData = {};
			if (resultHours !== this.state.hours)
			{
				newStateData.hours = resultHours;
			}

			if (resultMinutes !== this.state.minutes)
			{
				newStateData.minutes = resultMinutes;
			}
			this.setState(newStateData);
			this.props.onChange(this.state.hours * 3600 + this.state.minutes * 60);
		}

		render()
		{
			return CombinedField({
				value: {
					hours: (this.state.hours || undefined),
					minutes: (this.state.minutes || undefined),
				},
				config: {
					deepMergeStyles: {
						combinedContainer: {
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
						id: 'hours',
						renderField: NumberField,
						readOnly: this.props.readOnly,
						title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_TIME_TRACKING_TIME_HOURS'),
						placeholder: '0',
						config: {
							type: NumberPrecision.INTEGER,
							selectionOnFocus: false,
						},
					},
					secondaryField: {
						id: 'minutes',
						renderField: NumberField,
						readOnly: this.props.readOnly,
						title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_TIME_TRACKING_TIME_MINUTES'),
						placeholder: '0',
						config: {
							type: NumberPrecision.INTEGER,
							selectionOnFocus: false,
						},
					},
				},
				testId: 'timeTrackingTime',
				onChange: this.handleOnChange,
			});
		}
	}

	module.exports = { TimeTrackingTime };
});
