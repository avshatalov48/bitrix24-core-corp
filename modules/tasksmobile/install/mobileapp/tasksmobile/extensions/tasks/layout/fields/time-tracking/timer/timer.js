/**
 * @module tasks/layout/fields/time-tracking/timer/timer
 */
jn.define('tasks/layout/fields/time-tracking/timer/timer', (require, exports, module) => {
	const { Text4 } = require('ui-system/typography/text');
	const { toTimer } = require('tasks/layout/fields/time-tracking/time-utils');

	class TimeTrackingTimer extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				prevSeconds: props.seconds,
				seconds: props.seconds,
				isActive: props.isActive,
			};

			this.timeoutId = null;
		}

		componentDidMount()
		{
			this.timeoutId = setInterval(() => this.#tick(), 1000);
		}

		componentWillUnmount()
		{
			if (this.timeoutId)
			{
				clearTimeout(this.timeoutId);
			}
		}

		componentWillReceiveProps(props)
		{
			this.state.isActive = props.isActive;

			// avoid setting prev value on pending redux transaction
			if (props.seconds !== this.state.prevSeconds)
			{
				this.state.seconds = props.seconds;
				this.state.prevSeconds = props.seconds;
			}
		}

		#tick()
		{
			const { isActive, seconds: prevSeconds } = this.state;
			const { timeEstimate, onTimeOver } = this.props;

			if (!isActive)
			{
				return;
			}

			const seconds = prevSeconds + 1;

			this.setState({ seconds }, () => {
				const isTimeOver = timeEstimate > 0 && prevSeconds <= timeEstimate && seconds > timeEstimate;

				if (isTimeOver && onTimeOver)
				{
					onTimeOver(seconds);
				}
			});
		}

		render()
		{
			return Text4({
				testId: this.props.testId,
				text: toTimer(this.state.seconds),
				numberOfLines: 1,
				style: {
					color: this.props.color,
				},
			});
		}
	}

	module.exports = { TimeTrackingTimer };
});
