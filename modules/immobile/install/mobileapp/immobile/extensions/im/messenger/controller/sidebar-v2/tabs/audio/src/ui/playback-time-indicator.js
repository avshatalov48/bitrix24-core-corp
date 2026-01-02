/**
 * @module im/messenger/controller/sidebar-v2/tabs/audio/src/ui/playback-time-indicator
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/audio/src/ui/playback-time-indicator', (require, exports, module) => {
	const { Text4 } = require('ui-system/typography/text');
	const { Color } = require('tokens');
	const { PureComponent } = require('layout/pure-component');

	class PlaybackTimeIndicator extends PureComponent
	{
		/**
		 * @param props
		 * @param {string} props.testId
		 * @param {number} props.length
		 * @param {number} props.timing
		 * @param {Function} props.onRef
		 */
		constructor(props)
		{
			super(props);

			this.testId = props.testId;

			this.state = {
				timing: 0,
				length: props.length,
			};
		}

		componentDidMount()
		{
			this.props.onRef?.(this);
		}

		/**
		 * @public
		 * @param {number} timing
		 */
		updateTiming(timing)
		{
			this.setState({ timing });
		}

		render()
		{
			const { timing, length } = this.state;

			const roundedTiming = Math.floor(timing);
			const roundedLength = Math.floor(length);

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				Text4({
					text: this.toTimeText(roundedTiming),
					color: Color.base3,
					testId: `${this.testId}-timing`,
				}),
				Text4({
					text: ' / ',
					color: Color.base2,
					testId: `${this.testId}-duration-divider`,
				}),
				Text4({
					text: this.toTimeText(roundedLength),
					color: Color.base2,
					testId: `${this.testId}-duration`,
				}),
			);
		}

		/**
		 * @param {number} seconds
		 * @returns {string} - MM:SS
		 */
		toTimeText(seconds)
		{
			const minutes = Math.floor(seconds / 60);
			seconds = seconds % 60;
			const minutesText = minutes < 10 ? `0${minutes}` : minutes;
			const secondsText = seconds < 10 ? `0${seconds}` : seconds;

			return `${minutesText}:${secondsText}`;
		}
	}

	module.exports = {
		PlaybackTimeIndicator,
	};
});
