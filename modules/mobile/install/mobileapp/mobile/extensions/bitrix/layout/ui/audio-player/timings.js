/**
 * @module layout/ui/audio-player/timings
 */
jn.define('layout/ui/audio-player/timings', (require, exports, module) => {
	const AppTheme = require('apptheme');

	/**
	 * @class AudioPlayerTimings
	 */
	class AudioPlayerTimings extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				currentTime: 0,
			};

			this.initPlayer(props.player);
		}

		get duration()
		{
			return BX.prop.getNumber(this.props, 'duration', 0);
		}

		get currentTime()
		{
			return this.state.currentTime;
		}

		get play()
		{
			return this.props.play;
		}

		initPlayer(player)
		{
			if (!player || this.player)
			{
				return;
			}

			this.player = player;
			this.player.on('timeupdate', ({ currentTime }) => this.setState({ currentTime }));
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				currentTime: props.currentTime,
			};
			this.initPlayer(props.player);
		}

		friendlyTime(totalSeconds)
		{
			const leftPad = (num) => num.toString().padStart(2, '0');
			const minutes = Math.floor(totalSeconds / 60);
			const seconds = Math.floor(totalSeconds % 60);

			return `${minutes}:${leftPad(seconds)}`;
		}

		render()
		{
			return View(
				{
					onClick: () => this.onClick(),
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Text({
					text: this.friendlyTime(this.currentTime),
					style: {
						color: this.play ? AppTheme.colors.base2 : AppTheme.colors.base4,
						fontSize: 11,
					},
				}),
				Text({
					text: '/',
					style: {
						color: AppTheme.colors.base6,
						fontSize: 11,
						marginHorizontal: 2,
					},
				}),
				Text({
					text: this.friendlyTime(this.duration),
					style: {
						color: AppTheme.colors.base4,
						fontSize: 11,
					},
				}),
			);
		}

		onClick()
		{
			if (this.props.onClick)
			{
				this.props.onClick();
			}
		}
	}

	module.exports = { AudioPlayerTimings };
});
