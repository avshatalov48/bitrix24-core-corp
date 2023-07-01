/**
 * @module tasks/layout/task/fields/timeTracking
 */
jn.define('tasks/layout/task/fields/timeTracking', (require, exports, module) => {
	const {TimeTrackingIs} = require('tasks/layout/task/fields/timeTrackingIs');
	const {TimeTrackingTime} = require('tasks/layout/task/fields/timeTrackingTime');

	class TimeTracking extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				isTimeTracking: props.isTimeTracking,
				timeEstimate: props.timeEstimate,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				isTimeTracking: props.isTimeTracking,
				timeEstimate: props.timeEstimate,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				isTimeTracking: newState.isTimeTracking,
				timeEstimate: newState.timeEstimate,
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

		render()
		{
			return View(
				{
					style: (this.props.style || {}),
					testId: 'timeTrackingField',
				},
				new TimeTrackingIs({
					readOnly: this.state.readOnly,
					isTimeTracking: this.state.isTimeTracking,
					deepMergeStyles: this.getDeepMergeStyles(),
					onChange: (value) => {
						this.timeTrackingBlockRef.animate({
							duration: 200,
							height: (!value ? 0 : 66),
						});
						this.timeTrackingBlockRef.animate({
							duration: 600,
							opacity: (!value ? 0 : 1),
						});
						this.props.onChange({allowTimeTracking: (value ? 'Y' : 'N')});
					},
				}),
				View(
					{
						style: {
							height: (this.state.isTimeTracking ? 66 : 0),
							opacity: (this.state.isTimeTracking ? 1 : 0),
						},
						ref: ref => this.timeTrackingBlockRef = ref,
					},
					this.renderWithTopBorder(
						new TimeTrackingTime({
							readOnly: this.state.readOnly,
							timeEstimate: this.state.timeEstimate,
							deepMergeStyles: this.getDeepMergeStyles(),
							onChange: timeEstimate => this.props.onChange({timeEstimate}),
						})
					),
				),
			);
		}

		renderWithTopBorder(field)
		{
			return View(
				{},
				View({
					style: {
						height: 0.5,
						backgroundColor: '#e6e7e9',
					},
				}),
				field,
			);
		}
	}

	module.exports = {TimeTracking};
});