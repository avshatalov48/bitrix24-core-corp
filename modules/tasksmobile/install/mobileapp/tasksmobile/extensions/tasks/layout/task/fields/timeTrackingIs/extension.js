/**
 * @module tasks/layout/task/fields/timeTrackingIs
 */
jn.define('tasks/layout/task/fields/timeTrackingIs', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BooleanField, BooleanMode } = require('layout/ui/fields/boolean');

	class TimeTrackingIs extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				isTimeTracking: props.isTimeTracking,
			};

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				isTimeTracking: props.isTimeTracking,
			};
		}

		handleOnChange(value)
		{
			this.setState({ isTimeTracking: value });
			this.props.onChange(value);
		}

		render()
		{
			return BooleanField({
				readOnly: this.props.readOnly,
				showEditIcon: !this.props.readOnly,
				showTitle: false,
				value: this.state.isTimeTracking,
				config: {
					deepMergeStyles: {
						...this.props.deepMergeStyles,
						externalWrapper: {
							...this.props.deepMergeStyles.externalWrapper,
							height: 52,
						},
					},
					mode: BooleanMode.SWITCHER,
					description: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_TIME_TRACKING_IS_MSGVER_1'),
					showSwitcher: true,
				},
				testId: 'timeTrackingIs',
				onChange: this.handleOnChange,
			});
		}
	}

	module.exports = { TimeTrackingIs };
});
