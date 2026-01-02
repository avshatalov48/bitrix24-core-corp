jn.define('call:followup/audioplayer', (require, exports, module) => {
	const AudioPlayer = require('native/media').AudioPlayer;
	const {
		Button,
		ButtonSize,
		ButtonDesign,
		Icon,
		Ellipsize,
	} = require('ui-system/form/buttons/button');
	const { Color } = require('tokens');

	class Audioplayer extends LayoutComponent
	{
		constructor(props = {})
		{
			super(props);

			this.trackUrl = props.trackUrl;
			this.player = null;

			this.state = {
				currentSeconds: 0,
				trackWidth: null,
				duration: 0,
				currentSpeed: 1,
				isPlaying: false,
				isReady: false,
			};

			this.player = new AudioPlayer({
				uri: this.trackUrl,
			});

			this.player
				.on('timeupdate', (data) => {
					this.setState({
						currentSeconds: data.currentTime,
					})
				})
				.on('ready', (data) => {
					this.setState({
						isReady: true,
						duration: data.duration,
					})
				})
		}

		formatTime(value) {
			const minutes = Math.floor(value / 60);
			const seconds = value % 60;

			return `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
		}

		setTime(value)
		{
			this.player.setSeek(value);
		}

		renderProgressWave()
		{
			const progress = (this.state.currentSeconds / this.state.duration) * 100;
			return `<svg width="188" height="17" viewBox="0 0 188 17" fill="none" xmlns="http://www.w3.org/2000/svg"><linearGradient id="halfGradient" x1="0" x2="1" y1="0" y2="0"><stop offset='${Number.isNaN(progress) ? 0 : progress}%' stop-color="#11A9D9"/><stop stop-color="#D1D1D1"/></linearGradient><path d="M0 7a1 1 0 0 1 2 0v3a1 1 0 1 1-2 0V7ZM3 7a1 1 0 0 1 2 0v3a1 1 0 1 1-2 0V7ZM6 7a1 1 0 0 1 2 0v3a1 1 0 1 1-2 0V7ZM9 5a1 1 0 0 1 2 0v7a1 1 0 1 1-2 0V5ZM12 2.5a1 1 0 1 1 2 0v12a1 1 0 1 1-2 0v-12ZM15 5a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0V5ZM18 4.5a1 1 0 1 1 2 0v8a1 1 0 1 1-2 0v-8ZM21 5a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0V5ZM24 7a1 1 0 1 1 2 0v3a1 1 0 1 1-2 0V7ZM27 7a1 1 0 1 1 2 0v3a1 1 0 1 1-2 0V7ZM30 2a1 1 0 1 1 2 0v13a1 1 0 1 1-2 0V2ZM33 4.5a1 1 0 1 1 2 0v8a1 1 0 1 1-2 0v-8ZM36 5a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0V5ZM39 2.5a1 1 0 1 1 2 0v12a1 1 0 1 1-2 0v-12ZM42 7a1 1 0 1 1 2 0v3a1 1 0 1 1-2 0V7ZM45 6a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0V6ZM48 5a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0V5ZM51 6a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0V6ZM54 4a1 1 0 1 1 2 0v9a1 1 0 1 1-2 0V4ZM57 8a1 1 0 1 1 2 0v1a1 1 0 1 1-2 0V8ZM60 7a1 1 0 1 1 2 0v3a1 1 0 1 1-2 0V7ZM63 5a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0V5ZM66 7a1 1 0 1 1 2 0v3a1 1 0 1 1-2 0V7ZM69 6a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0V6ZM72 7a1 1 0 1 1 2 0v3a1 1 0 1 1-2 0V7ZM75 5a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0V5ZM78 4.5a1 1 0 1 1 2 0v8a1 1 0 1 1-2 0v-8ZM81 8a1 1 0 1 1 2 0v1a1 1 0 1 1-2 0V8ZM84 6.5a1 1 0 1 1 2 0v4a1 1 0 1 1-2 0v-4ZM87 5a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0V5ZM90 1a1 1 0 1 1 2 0v15a1 1 0 1 1-2 0V1ZM93 1a1 1 0 1 1 2 0v15a1 1 0 1 1-2 0V1ZM96 7a1 1 0 1 1 2 0v3a1 1 0 1 1-2 0V7ZM99 7a1 1 0 1 1 2 0v3a1 1 0 0 1-2 0V7ZM102 5a1 1 0 0 1 2 0v7a1 1 0 0 1-2 0V5ZM105 5a1 1 0 0 1 2 0v7a1 1 0 0 1-2 0V5ZM108 7a1 1 0 0 1 2 0v3a1 1 0 0 1-2 0V7ZM111 2.5a1 1 0 0 1 2 0v12a1 1 0 0 1-2 0v-12ZM114 2.5a1 1 0 0 1 2 0v12a1 1 0 0 1-2 0v-12ZM117 3a1 1 0 0 1 2 0v11a1 1 0 0 1-2 0V3ZM120 3a1 1 0 0 1 2 0v11a1 1 0 0 1-2 0V3ZM123 7a1 1 0 0 1 2 0v3a1 1 0 0 1-2 0V7ZM126 2.5a1 1 0 0 1 2 0v12a1 1 0 0 1-2 0v-12ZM129 4.5a1 1 0 0 1 2 0v8a1 1 0 0 1-2 0v-8ZM132 2.5a1 1 0 0 1 2 0v12a1 1 0 0 1-2 0v-12ZM135 5a1 1 0 0 1 2 0v7a1 1 0 0 1-2 0V5ZM138 8a1 1 0 0 1 2 0v1a1 1 0 0 1-2 0V8ZM141 5a1 1 0 0 1 2 0v7a1 1 0 0 1-2 0V5ZM144 6.5a1 1 0 0 1 2 0v4a1 1 0 0 1-2 0v-4ZM147 3a1 1 0 0 1 2 0v11a1 1 0 0 1-2 0V3ZM150 1a1 1 0 0 1 2 0v15a1 1 0 0 1-2 0V1ZM153 5a1 1 0 0 1 2 0v7a1 1 0 0 1-2 0V5ZM156 4.5a1 1 0 0 1 2 0v8a1 1 0 0 1-2 0v-8ZM159 5a1 1 0 0 1 2 0v7a1 1 0 0 1-2 0V5ZM162 7a1 1 0 0 1 2 0v3a1 1 0 0 1-2 0V7ZM165 4a1 1 0 0 1 2 0v9a1 1 0 0 1-2 0V4ZM168 8a1 1 0 0 1 2 0v1a1 1 0 0 1-2 0V8ZM171 5a1 1 0 0 1 2 0v7a1 1 0 0 1-2 0V5ZM174 5a1 1 0 0 1 2 0v7a1 1 0 0 1-2 0V5ZM177 8a1 1 0 0 1 2 0v1a1 1 0 0 1-2 0V8ZM180 5a1 1 0 0 1 2 0v7a1 1 0 0 1-2 0V5ZM183 6.5a1 1 0 0 1 2 0v4a1 1 0 0 1-2 0v-4ZM186 3a1 1 0 0 1 2 0v11a1 1 0 0 1-2 0V3Z" fill="url(#halfGradient)"/></svg>`;
		}

		render()
		{
			return View(
				{
					style: {
						display: 'flex',
						flexDirection: 'row',
						alignItems: 'flex-start',
						justifyContent: 'space-between',
					}
				},
				this.state.isReady
					? Button({
						testId: 'button-play',
						onClick: () => {
							if (this.state.isPlaying)
							{
								this.player.pause();
								this.setState({ isPlaying: false });
								return;
							}
							this.player.play();
							this.setState({ isPlaying: true });
						},
						disabled: false,
						rightIcon: this.state.isPlaying ? Icon.PAUSE : Icon.PLAY,
						size: ButtonSize.M,
						design: ButtonDesign.FILLED,
						rounded: true,
						ellipsize: Ellipsize.END,
					})
					: Loader({
						style: {
							width: 40,
							height: 40,
						},
						tintColor: Color.accentMainPrimaryalt.toHex(),
						animating: true,
						size: 'large',
					}),
				View(
					{
						style: {
							width: '60%',
						},
					},
					View(
						{
							style: {
								position: 'relative',
								height: 17,
							},
							ref: (ref) => {
								this.audioTrack = ref;
							},
							onLayout: (params) => {
								this.state.trackWidth = params.width;
							},
							onTouchesBegan: () => {},
							onTouchesEnded: (props) => {
								if (this.state.isReady)
								{
									const newVal = props.x / this.state.trackWidth * this.state.duration;
									this.player.setSeek(newVal);
								}
							},
						},
						Image(
							{
								style: {
									position: 'absolute',
									width: '100%',
									height: 17,
									top: 0,
									left: 0,
									zIndex: 1,
								},
								svg: { content: this.renderProgressWave() },
							},
						),
					),
					View(
						{
							style: {
								marginTop: 5,
							}
						},
						Text({
							style: { fontSize: 10, color: Color.chatOtherBase1_2.toHex(), fontWeight: '400' },
							text: this.formatTime(this.state.currentSeconds),
						}),
					)
				),
				View(
					{
						style: {
							width: 60,
						},
					},
					Button({
						testId: 'button-speed',
						onClick: () => {
							if (this.state.currentSpeed === 1)
							{
								this.setState({
									currentSpeed: 1.5,
								});
								this.player.setSpeed(this.state.currentSpeed)
								return;
							}
							if (this.state.currentSpeed === 1.5)
							{
								this.setState({
									currentSpeed: 2,
								});
								this.player.setSpeed(this.state.currentSpeed)
								return;
							}
							this.setState({
								currentSpeed: 1,
							});
							this.player.setSpeed(this.state.currentSpeed)

						},
						text: `${this.state.currentSpeed}x`,
						disabled: false,
						size: ButtonSize.S,
						design: ButtonDesign.OUTLINE,
						rounded: true,
						ellipsize: Ellipsize.END,
					}),
				)
			)
		}
	}

	module.exports = {
		Audioplayer,
	};
});
