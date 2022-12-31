/**
 * @module layout/ui/audio-player
 */
jn.define('layout/ui/audio-player', (require, exports, module) => {
	const { AudioPlayer: Player } = require('native/media');
	const { PlayButton } = require('layout/ui/audio-player/play-button');
	const { SpeedButton } = require('layout/ui/audio-player/speed-button');
	const { EventEmitter } = require('event-emitter');
	const { RangeSlider } = require('layout/ui/range-slider');

	const { Alert } = require('alert');

	class AudioPlayer extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.player = null;

			this.state = {
				duration: 0,
				play: false,
				isLoading: false,
			};

			this.currentTime = 0;
			this.speed = 1;
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
		}

		get uid()
		{
			return BX.prop.get(this.props, 'uid', Random.getString());
		}

		get title()
		{
			return BX.prop.getString(this.props, 'title', null);
		}

		get imageUri()
		{
			return BX.prop.getString(this.props, 'imageUri', null);
		}

		componentDidMount()
		{
			this.customEventEmitter.on('onTimelineIconClicked', () => {
				this.onPlay.bind(this)

				if (!this.state.isLoading && !this.state.duration)
				{
					this.onLoadAudio();
				}
				else
				{
					this.onPlay();
				}
			});
			this.customEventEmitter.on('TopPanelAudioPlayer::onChangePlay', () => {
				this.onPlay();
			});
			this.customEventEmitter.on('TopPanelAudioPlayer::onSetSeek', ({currentTime}) => {
				currentTime = Math.min(currentTime, this.state.duration);
				currentTime = Math.max(currentTime, 0);

				this.player.setSeek(currentTime);
			});
		}

		onPlayerReady(duration)
		{
			this.customEventEmitter.emit('AudioPlayer::onReady', [{duration}]);
		}

		onPlayerPlay()
		{
			this.customEventEmitter.emit(
				'AudioPlayer::onPlay',
				[{
					duration: this.state.duration,
					uid: this.uid,
					currentTime: this.currentTime,
					speed: this.speed
				}]);
		}

		onPlayerChangeSpeed(speed)
		{
			this.customEventEmitter.emit('AudioPlayer::onChangeSpeed', [{speed}]);
		}

		onPlayerPause()
		{
			this.customEventEmitter.emit('AudioPlayer::onPause', [])
		}

		onPlayerFinish()
		{
			this.customEventEmitter.emit('AudioPlayer::onFinish', []);
		}

		onPlayerUpdate(currentTime)
		{
			this.customEventEmitter.emit('AudioPlayer::onUpdate', [{currentTime, duration: this.state.duration, uid: this.uid}]);
		}

		onPlay()
		{
			if (this.state.duration)
			{
				this.state.play ? this.player.pause() : this.player.play();
			}
		}

		render()
		{
			const { duration, play, isLoading } = this.state;

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				PlayButton({
					play,
					onClick: this.onPlay.bind(this),
					onLoadAudio: this.onLoadAudio.bind(this),
					isLoading,
					duration,
				}),
				new RangeSlider({
					uid: this.uid,
					value: this.state.currentTime,
					maximumValue: duration,
					enabled: duration,
					active: play,
					showValues: duration,
					onSlidingComplete: (currentTime) => {
						if (this.player)
						{
							this.player.setSeek(currentTime);
							this.onPlayerUpdate(currentTime);
						}
					},
					player: this.player,
				}),
				new SpeedButton({
					onChangeSpeed: (speed) => {
						this.onPlayerChangeSpeed(speed);
						this.speed = speed;
						if (this.player)
						{
							this.player.setSpeed(speed);
						}
					},
					customEventEmitter: this.customEventEmitter,
				}),
			);
		}

		onLoadAudio()
		{
			this.setState({
				isLoading: true,
			}, () => {
				this.player = new Player({
					uri: this.props.uri,
					speed: this.speed,
					title: this.title,
					imageUri: this.imageUri,
				});

				this.bindPlayerEvents();
			});
		}

		bindPlayerEvents()
		{
			this.player.on('ready', ({duration}) => {
				if (duration !== this.state.duration)
				{
					this.onPlayerReady(duration);
					this.setState({
						duration,
					}, this.onPlay.bind(this));
				}
			});

			this.player.on('error', (data) => {
				console.log(data);
				Alert.alert(
					'',
					BX.message('AUDIO_PLAYER_ERROR'),
					[
						{
							type: 'default',
						},
					],
				);
			});

			this.player.on('timeupdate', ({currentTime}) => {
				this.currentTime = currentTime;
				this.onPlayerUpdate(currentTime);
			});

			this.player.on("play", (data) => {
				if (!this.state.play)
				{
					this.onPlayerPlay();
					this.setState({
						play: true,
						isLoading: false,
					});
				}
			});

			this.player.on('pause', (data) => {
				if (this.state.play)
				{
					this.onPlayerPause();
					this.setState({
						play: false,
					});
				}
			});

			this.player.on('finish', (data) => {
				this.onPlayerFinish();
				this.setState({
					play: false,
				});
			});
		}
	}

	module.exports = { AudioPlayer };
});