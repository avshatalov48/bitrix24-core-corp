/**
 * @module call/calls-card/card-content/elements/timer
 */
jn.define('call/calls-card/card-content/elements/timer', (require, exports, module) => {
	const MINUTES = 60000;
	const SECONDS = 1000;

	/**
	 * @class Timer
	 */
	class Timer extends LayoutComponent
	{
		constructor(props) {
			super(props);

			this.interval = null;

			this.state = {
				elapsed: 0,
			};

			this.startHandler = this.start.bind(this);
			this.stopHandler = this.stop.bind(this);
		}

		get startTime()
		{
			return BX.prop.get(this.props, 'startTime', null);
		}

		get pauseTime()
		{
			return BX.prop.getInteger(this.props, 'pauseTime', 0);
		}

		get paused()
		{
			return BX.prop.getBoolean(this.props, 'paused', false);
		}

		componentDidMount()
		{
			if (this.props.startTime)
			{
				this.startHandler();
			}
		}

		componentWillReceiveProps(props) {
			if (props.paused)
			{
					this.stopHandler();
			}
			else
			{
				this.startHandler();
			}
		}

		componentWillUnmount()
		{
			clearInterval(this.interval);
		}

		start()
		{
			if (!this.interval)
			{
				this.interval = setInterval(() => {
					this.setState({
						elapsed: new Date() - this.startTime - this.pauseTime,
					});
				}, 500);
			}
		}

		stop()
		{
			if (this.interval)
			{
				clearInterval(this.interval);
				this.interval = null;
			}

			this.setState({started: false});
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						marginHorizontal: 5,
						height: 27,
					},
				},
				View(
					{
						style: {
							height: 27,
							paddingHorizontal: 12,
							justifyContent: 'center',
							alignItems: 'center',
						},
					},
					Text({
						text: this.formatTime(),
						style: {
							color: '#B7B7B7',
							fontSize: 15,
						},
					}),
				),
				View(
					{
						style: {
							height: 27,
							marginTop: -27,
							borderRadius: 13.5,
							opacity: 0.2,
							backgroundColor: '#ffffff',
						},
					},
				),
			);
		}

		formatTime()
		{
			const minutes = Math.floor(this.state.elapsed / MINUTES);
			const seconds = Math.floor(this.state.elapsed / SECONDS) - minutes * 60;

			return `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
		}
	}

	module.exports = { Timer };
});