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
			this.uid = this.props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.handleExternalChangePlay = this.handleExternalEvent(this.handleExternalChangePlay);
			this.handleExternalCancel = this.handleExternalEvent(this.handleExternalCancel);
			this.handleExternalSetSeek = this.handleExternalEvent(this.handleExternalSetSeek);
			this.onPlay = this.onPlay.bind(this);
			this.onLoadAudio = this.onLoadAudio.bind(this);
		}

		get title()
		{
			return BX.prop.getString(this.props, 'title', null);
		}

		get fileName()
		{
			return BX.prop.getString(this.props, 'fileName', null);
		}

		get uri()
		{
			return BX.prop.getString(this.props, 'uri', null);
		}

		get imageUri()
		{
			return BX.prop.getString(this.props, 'imageUri', null);
		}

		componentDidMount()
		{
			this.customEventEmitter.on('TimelineIconAudioPlayer::onChangePlay', this.handleExternalChangePlay);
			this.customEventEmitter.on('TopPanelAudioPlayer::onChangePlay', this.handleExternalChangePlay);
			this.customEventEmitter.on('TopPanelAudioPlayer::onCancel', this.handleExternalCancel);
			this.customEventEmitter.on('TopPanelAudioPlayer::onSetSeek', this.handleExternalSetSeek);
		}

		/**
		 * @private
		 * @param {function} handler
		 */
		handleExternalEvent(handler)
		{
			return (params = {}) => {
				if (params.uri && params.uri === this.uri)
				{
					handler.call(this, params);
				}
			};
		}

		/**
		 * @private
		 */
		handleExternalChangePlay()
		{
			if (!this.state.isLoading && !this.state.duration)
			{
				this.onLoadAudio();
			}
			else
			{
				this.onPlay();
			}
		}

		/**
		 * @private
		 */
		handleExternalCancel()
		{
			this.onCancel();
		}

		/**
		 * @private
		 */
		handleExternalSetSeek({ currentTime })
		{
			currentTime = Math.min(currentTime, this.state.duration);
			currentTime = Math.max(currentTime, 0);

			this.player.setSeek(currentTime);
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
					speed: this.speed,
					uri: this.uri,
					title: this.title,
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

		onCancel()
		{
			if (this.state.play)
			{
				this.player.pause();
			}

			this.player.setSeek(0);
			this.onPlayerUpdate(0);
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
					onClick: this.onPlay,
					onLoadAudio: this.onLoadAudio,
					isLoading,
					duration,
				}),
				new RangeSlider({
					uid: this.uid,
					value: this.currentTime,
					maximumValue: duration,
					enabled: duration,
					active: play,
					showTimings: duration,
					fileName: this.fileName,
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
					uri: this.uri,
				}),
			);
		}

		onLoadAudio()
		{
			this.setState({
				isLoading: true,
			}, () => {
				this.player = new Player({
					uri: this.uri,
					speed: this.speed,
					title: this.title,
					imageUri: this.imageUri,
				});

				this.bindPlayerEvents();
			});
		}

		bindPlayerEvents()
		{
			this.player.on('ready', ({ duration }) => {
				if (duration !== this.state.duration)
				{
					this.onPlayerReady(duration);
					this.setState({ duration }, this.onPlay);
				}
			});

			this.player.on('error', (data) => {
				console.error('AudioPlayer error', data);

				this.onPlayerPause();
				this.setState({
					play: false,
					isLoading: false,
				});

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

			this.player.on('timeupdate', ({ currentTime }) => {
				this.currentTime = currentTime;
				this.onPlayerUpdate(currentTime);
			});

			this.player.on('play', () => {
				if (!this.state.play)
				{
					this.onPlayerPlay();
					this.setState({
						play: true,
						isLoading: false,
					});
				}
			});

			this.player.on('pause', () => {
				if (this.state.play)
				{
					this.onPlayerPause();
					this.setState({
						play: false,
					});
				}
			});

			this.player.on('finish', () => {
				this.onPlayerFinish();
				this.setState({
					play: false,
				});
			});
		}
	}

	module.exports = { AudioPlayer };
});