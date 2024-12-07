/**
 * @module crm/entity-detail/toolbar/activity/templates/call
 */
jn.define('crm/entity-detail/toolbar/activity/templates/call', (require, exports, module) => {
	const { ActivityPinnedBase } = require('crm/entity-detail/toolbar/activity/templates/base');
	const { EventEmitter } = require('event-emitter');
	const { throttle, debounce } = jn.require('utils/function');

	const MARKER_SIZE = 20;
	const nothing = () => {};

	/**
	 * @class ActivityPinnedCall
	 */
	class ActivityPinnedCall extends ActivityPinnedBase
	{
		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						top: -80,
						position: 'absolute',
						width: '100%',
					},
					ref: (ref) => this.ref = ref,
					interactable: true,
					clickable: false,
				},
				new ActivityPinnedCallWrapper({
					...this.props,
					clientName: this.model.getClientName(),
					ref: ref => this.wrapperRef = ref,
					clickable: this.state.visible,
				}),
				View(
					{
						style: {
							position: 'absolute',
							bottom: 3,
							left: 0,
							width: '100%',
							height: 30,
						},
						clickable: this.state.visible,
						onPan: this.state.visible && nothing,
						onTouchesBegan: ({x}) => {
							if (this.props.actionParams && this.props.actionParams.duration)
							{
								this.wrapperRef.onTouchesBegan(x);
							}
						},
						onTouchesMoved: ({ x}) => {
							if (this.props.actionParams && this.props.actionParams.duration)
							{
								this.wrapperRef.onTouchesMoved(x);
							}
						},
						onTouchesEnded: ({ x}) => {
							if (this.props.actionParams && this.props.actionParams.duration)
							{
								this.wrapperRef.onTouchesEnded(x);
							}
						},
					},
				),
			);
		}

		shouldHighlightOnShow()
		{
			return false;
		}
	}

	/**
	 * @class ActivityPinnedCallWrapper
	 */
	class ActivityPinnedCallWrapper extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				speed:  this.props.actionParams.speed,
				currentTime: this.props.actionParams.currentTime,
				x: this.getPositionByValue(this.props.actionParams.currentTime),
				play: true,
				isTouchEnd: true,
				duration: this.props.actionParams.duration,
			};

			this.currentTimeWidth = 27;

			this.updatePosition = throttle((x) => {
				this.setState({
					x,
					currentTime: this.getValueByPosition(x),
				});
			}, 25);

			this.freezeTouchEnd = debounce((value) => {
				this.setState({
					...this.state,
					isTouchEnd: value,
				});
			}, 200);

			this.enableToUpdateCurrentTime = true;

			this.onUpdateAudioPlayer = this.onUpdateAudioPlayer.bind(this);
			this.onPlayAudio = this.onPlayAudio.bind(this);
			this.onPauseAudio = this.onPauseAudio.bind(this);
			this.onFinishAudio = this.onFinishAudio.bind(this);
			this.onChangeSpeed = this.onChangeSpeed.bind(this);
		}

		get clickable()
		{
			return BX.prop.getBoolean(this.props, 'clickable', false);
		}

		componentDidMount()
		{
			if (this.props.actionParams.uid)
			{
				this.customEventEmitter = EventEmitter.createWithUid(this.props.actionParams.uid);
				this.bindPlayerEvents();
			}
		}

		componentWillReceiveProps(props)
		{
			if (this.props.actionParams.uid !== props.actionParams.uid)
			{
				this.removePlayerEvents();
				this.customEventEmitter.setUid(props.actionParams.uid);
				this.bindPlayerEvents();
				this.setState({
					play: true,
					currentTime: props.actionParams.currentTime,
					x: this.getPositionByValue(props.actionParams.currentTime),
					speed: props.actionParams.speed,
				});
			}
		}

		bindPlayerEvents()
		{
			this.customEventEmitter
				.on('AudioPlayer::onUpdate', this.onUpdateAudioPlayer)
				.on('AudioPlayer::onPlay', this.onPlayAudio)
				.on('AudioPlayer::onPause', this.onPauseAudio)
				.on('AudioPlayer::onFinish', this.onFinishAudio)
				.on('AudioPlayer::onChangeSpeed', this.onChangeSpeed);
		}

		removePlayerEvents()
		{
			this.customEventEmitter
				.off('AudioPlayer::onUpdate', this.onUpdateAudioPlayer)
				.off('AudioPlayer::onPlay', this.onPlayAudio)
				.off('AudioPlayer::onPause', this.onPauseAudio)
				.off('AudioPlayer::onFinish', this.onFinishAudio)
				.off('AudioPlayer::onChangeSpeed', this.onChangeSpeed);
		}

		onUpdateAudioPlayer({currentTime, duration, uid})
		{
			if (this.state.isTouchEnd && this.enableToUpdateCurrentTime && uid === this.props.actionParams.uid)
			{
				const position = this.getPositionByValue(currentTime);

				this.setState({
					x: position,
					currentTime,
					duration
				});
			}
		}

		onPlayAudio()
		{
			this.enableToUpdateCurrentTime = false;

			setTimeout(() => {
				this.enableToUpdateCurrentTime = true;
			}, 100);

			this.setState({
				play: true
			});
		}

		onPauseAudio()
		{
			this.setState({
				play: false
			});
		}

		onFinishAudio()
		{
			this.setState({
				play: false,
			});
		}

		onChangeSpeed({speed})
		{
			this.setState({
				speed,
			});
		}

		convertSecondsToTime(totalSeconds)
		{
			const padTo2Digits = (num) => {
				return num.toString().padStart(2, '0');
			};

			const minutes = Math.floor(totalSeconds / 60);
			const seconds = Math.floor(totalSeconds % 60);

			return `${minutes}:${padTo2Digits(seconds)}`;
		}

		getPositionByValue(value)
		{
			if (!this.state.duration)
			{
				return 0;
			}

			return Math.floor(value * device.screen.width / this.state.duration);
		}

		setPosition(x)
		{
			x = Math.max(x, 0);
			x = Math.min(x, device.screen.width);
			if (x !== this.state.x)
			{
				this.updatePosition(x);
			}
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						height: 80,
					},
					clickable: false,
					onLayout: ({ width}) => {
						this.progressWidth = width;
					},
				},
				View(
					{
						style: {
							width: '100%',
							height: 62,
						},
						clickable: false,
					},
					View(
						{
							style: {
								backgroundColor: '#fff',
								flex: 1,
								flexDirection: 'row',
								paddingHorizontal: 12,
								paddingVertical: 5,
								justifyContent: 'center',

								borderBottomWidth: 1,
								borderBottomColor: '#DFE0E3',
							},
							clickable: false,
						},
						this.renderPlayButton(),
						this.renderTrackInfo(),
						this.renderButtons(),
					),
				),
				this.renderProgressBar(),
			);
		}

		renderPlayButton()
		{
			return Image(
				{
					style: {
						width: 47,
						height: 47,
					},
					onClick: this.clickable && this.onPlay.bind(this),
					svg: {
						content: this.state.play ? icons.play : icons.pause,
					},
				},
			);
		}

		onPlay()
		{
			if (this.state.duration)
			{
				this.customEventEmitter.emit('TopPanelAudioPlayer::onChangePlay', []);
			}
		}

		renderTrackInfo()
		{
			const currentTime = this.state.isTouchEnd ? this.state.currentTime : this.getValueByPosition(this.state.x);

			return View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						flex: 1,
					},
					clickable: false,
				},
				Text({
					style: {
						color: '#000',
						fontSize: 15,
					},
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.props.clientName,
				}),
				View(
					{
						style: {
							flexDirection: 'row',
						},
						clickable: false,
					},
					Text({
						style: {
							color: this.state.play ? '#2FC6F6' : '#A8ADB4',
							fontSize: 12,
							width: this.currentTimeWidth,
							textAlign: 'right',
						},
						text: `${this.convertSecondsToTime(currentTime)}`,
						onLayout: ({width}) => {
							this.currentTimeWidth = Math.max(this.currentTimeWidth, width);
						},
					}),
					Text({
						style: {
							color: '#A8ADB4',
							fontSize: 12,
						},
						text: ` / ${this.convertSecondsToTime(this.state.duration)}`,
					}),
				),
			);
		}

		renderButtons()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
					clickable: false,
				},
				this.renderSpeedButton(),
				this.renderBackButton(),
				this.renderFrontButton(),
				this.renderCancelButton(),
			);
		}

		renderSpeedButton()
		{
			return View(
				{
					style: {
						width: 33,
						height: 33,
						marginRight: 8,
						marginLeft: 8,
					},
					clickable: this.clickable,
					onClick: () => {
						if (this.state.duration)
						{
							this.menu = new ContextMenu({
								actions: this.getSpeedMenuActions(),
								params: {
									title: BX.message('M_CRM_E_D_TOOLBAR_TITLE_AUDIO_PLAYER_SPEED'),
								},
							});
							this.menu.show();
						}
					},
				},
				Image({
					style: {
						width: 33,
						height: 33,
					},
					svg: {
						content: icons.speed[(this.state.speed).toFixed(1)],
					},
				}),
			);
		}

		renderBackButton()
		{
			return View(
				{
					style: {
						width: 33,
						height: 33,
						marginRight: 8,
					},
					clickable: this.clickable,
					onClick: () => {
						this.state.duration && this.setPlayerSeek(this.state.currentTime - 15)
					},
				},
				Image({
					style: {
						width: 33,
						height: 33,
					},
					svg: {
						content: icons.back,
					},
				}),
			);
		}

		renderFrontButton()
		{
			return View(
				{
					style: {
						width: 33,
						height: 33,
						marginRight: 8,
					},
					clickable: this.clickable,
					onClick: () => {
						this.state.duration && this.setPlayerSeek(this.state.currentTime + 15);
					},
				},
				Image({
					style: {
						width: 33,
						height: 33,
					},
					svg: {
						content: icons.front,
					},
				}),
			);
		}

		renderCancelButton()
		{
			return View(
				{
					style: {
						width: 33,
						height: 33,
					},
					clickable: this.clickable,
					onClick: () => {
						this.customEventEmitter.emit('TopPanelAudioPlayer::onCancel', []);
						this.props.onHide();
					},
				},
				Image({
					style: {
						width: 33,
						height: 33,
					},
					svg: {
						content: icons.cancel,
					},
				}),
			);
		}

		getSpeedMenuActions()
		{
			return Object.keys(icons.speed).map(item => this.getMenuAction(item));
		}

		getMenuAction(value)
		{
			return {
				id: String(value),
				title: `${value}x`,
				onClickCallback: () => {
					this.setState({
						speed: parseFloat(value),
					}, () => {
						this.customEventEmitter.emit('TopPanelAudioPlayer::onChangeSpeed', [{speed: parseFloat(value)}])
					});
				},
				isSelected: this.state.speed === parseFloat(value),
			};
		}

		renderProgressBar()
		{
			const markerSize = this.state.isTouchEnd ? 20 : 30;
			const leftOffsetByPosition = markerSize * this.state.x / device.screen.width;

			return View(
				{
					style: {
						position: 'absolute',
						top: 43,
						left: 0,
						width: '100%',
						height: 35,
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'flex-start',
					},
					clickable: false,
				},
				//progress
				View(
					{
						style: {
							width: this.state.x,
							height: this.state.isTouchEnd ? 3 : 11,
							flexDirection: 'row',
						},
						clickable: false,
					},
					View(
						{
							style: {
								backgroundColor: '#2FC6F6',
								flex: 1,
							},
							clickable: false,
						},
					),
					//hide part of progress because marker is rounded
					View(
						{
							style: {
								height: this.state.isTouchEnd ? 3 : 11,
								width: this.state.x > device.screen.width / 2 ? (leftOffsetByPosition / 2) : 0,
							},
							clickable: false,
						},
					),
				),
				this.renderMarker(),
			);
		}

		renderMarker()
		{
			const markerSize = this.state.isTouchEnd ? 20 : 30;
			const leftOffsetByPosition = markerSize * this.state.x / device.screen.width;

			const markerOffset = {
				top: this.state.isTouchEnd ? 7 : 0,
				left: this.state.x - leftOffsetByPosition,
			};

			//it's hard to render shadows on android
			if (Application.getPlatform() === 'android')
			{
				return View(
					{
						style: {
							width: this.state.isTouchEnd ? MARKER_SIZE : 30,
							height: this.state.isTouchEnd ? MARKER_SIZE : 30,
							borderRadius: 30,
							backgroundColor: '#ffffff',
							justifyContent: 'center',
							alignItems: 'center',

							position: 'absolute',
							top: markerOffset.top,
							left: markerOffset.left,

							borderBottomWidth: 0.5,
							borderBottomColor: '#DFE0E3',
						},
						clickable: false,
					},
					View(
						{
							style: {
								width: this.state.isTouchEnd ? 8 : 14,
								height: this.state.isTouchEnd ? 8 : 14,
								borderRadius: 7,
								backgroundColor: '#2FC6F6',
							},
							clickable: false,
						},
					),
				);
			}

			return Shadow(
				{
					offset: {
						x: 0,
						y: 2,
					},
					radius: 4,
					color: '#e0e0e0',
					style: {
						position: 'absolute',
						top: markerOffset.top,
						left: markerOffset.left,
						borderRadius: 30,
					},
				},
				View(
					{
						style: {
							width: this.state.isTouchEnd ? MARKER_SIZE : 30,
							height: this.state.isTouchEnd ? MARKER_SIZE : 30,
							borderRadius: 30,
							backgroundColor: '#ffffff',
							justifyContent: 'center',
							alignItems: 'center',
						},
						clickable: false,
					},
					View(
						{
							style: {
								width: this.state.isTouchEnd ? 8 : 14,
								height: this.state.isTouchEnd ? 8 : 14,
								borderRadius: 7,
								backgroundColor: '#2FC6F6',
							},
							clickable: false,
						},
					),
				),
			)
		}

		onTouchesBegan(x)
		{
			this.state.isTouchEnd = false;
		}

		onTouchesMoved(x)
		{
			//to enable reach end of the screen
			const offset = 10 * (x - device.screen.width / 2) / (device.screen.width / 2);

			this.setPosition(x + offset);
		}

		getValueByPosition(position)
		{
			return Math.round(position * this.state.duration / device.screen.width);
		}

		onTouchesEnded(x)
		{
			if (this.state.play)
			{
				this.freezeTouchEnd(true);
			}
			else
			{
				this.setState({
					...this.state,
					isTouchEnd: true,
				});
			}

			const currentTime = this.getValueByPosition(this.state.x);
			this.setPlayerSeek(Math.ceil(currentTime));
		}

		setPlayerSeek(currentTime)
		{
			if (this.customEventEmitter)
			{
				this.customEventEmitter.emit('TopPanelAudioPlayer::onSetSeek', [{currentTime}])
			}
		}
	}

	const icons = {
		pause: `<svg width="47" height="47" viewBox="0 0 47 47" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M40.5909 23.5C40.5909 32.939 32.9391 40.5909 23.5 40.5909C14.061 40.5909 6.40912 32.939 6.40912 23.5C6.40912 14.0609 14.061 6.40909 23.5 6.40909C32.9391 6.40909 40.5909 14.0609 40.5909 23.5Z" fill="#2FC6F6"/><path fill-rule="evenodd" clip-rule="evenodd" d="M29.5963 22.8739L20.3435 16.1573C20.1253 15.9967 19.8411 15.9785 19.6063 16.1101C19.3716 16.2417 19.2255 16.5012 19.2273 16.7831V30.2157C19.2246 30.498 19.3706 30.7582 19.6057 30.8901C19.8408 31.0219 20.1255 31.0032 20.3435 30.8416L29.5963 24.1249C29.7919 23.9845 29.9091 23.7501 29.9091 23.4994C29.9091 23.2487 29.7919 23.0143 29.5963 22.8739Z" fill="white"/><rect width="47" height="47" rx="23.5" fill="white" fill-opacity="0.01"/></svg>`,
		play: `<svg width="47" height="47" viewBox="0 0 47 47" fill="none" xmlns="http://www.w3.org/2000/svg"><ellipse cx="22.9778" cy="23.5" rx="16.7111" ry="16.7111" fill="#C1EEFD"/><path d="M21.0788 18.2333C21.0788 17.6811 20.6311 17.2333 20.0788 17.2333H18.7556C18.2033 17.2333 17.7556 17.6811 17.7556 18.2333V28.7667C17.7556 29.319 18.2033 29.7667 18.7556 29.7667H20.0788C20.6311 29.7667 21.0788 29.319 21.0788 28.7667V18.2333Z" fill="#2FC6F6"/><path d="M28.2 18.2333C28.2 17.6811 27.7523 17.2333 27.2 17.2333H25.8768C25.3245 17.2333 24.8768 17.6811 24.8768 18.2333V28.7667C24.8768 29.319 25.3245 29.7667 25.8768 29.7667H27.2C27.7523 29.7667 28.2 29.319 28.2 28.7667V18.2333Z" fill="#2FC6F6"/><rect y="0.522217" width="45.9556" height="45.9556" rx="22.9778" fill="white" fill-opacity="0.01"/></svg>`,
		back: `<svg width="33" height="33" viewBox="0 0 33 33" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M24.0465 16.5C24.0465 12.4682 20.7853 9.25467 16.8311 9.25467C15.3313 9.25467 13.9368 9.71347 12.7795 10.5038L13.5707 11.273C13.8178 11.5133 13.6428 11.924 13.2933 11.924H9.92557C9.70894 11.924 9.53333 11.7533 9.53333 11.5427V8.26863C9.53333 7.9289 9.95583 7.75876 10.2029 7.99899L11.0964 8.86763C12.6918 7.67631 14.6774 6.96667 16.8311 6.96667C22.1466 6.96667 26.4 11.2652 26.4 16.5C26.4 21.7348 22.1466 26.0333 16.8311 26.0333C14.6027 26.0333 12.5525 25.2733 10.9294 24.0048C10.4229 23.609 10.3423 22.8889 10.7495 22.3964C11.1567 21.904 11.8974 21.8257 12.4039 22.2216C13.6289 23.1789 15.1641 23.7453 16.8311 23.7453C20.7853 23.7453 24.0465 20.5318 24.0465 16.5ZM14.7657 19.6418H13.1299V15.3694H13.0991L11.7333 16.295V14.8907L13.1299 13.9333H14.7657V19.6418ZM20.5333 17.6875C20.5333 18.9574 19.6074 19.8 18.2147 19.8C16.8914 19.8 15.9539 19.0286 15.9462 17.9368H17.4161C17.4817 18.2888 17.8135 18.5341 18.2263 18.5341C18.6892 18.5341 19.0017 18.2018 19.0017 17.7271C19.0017 17.2286 18.6931 16.8924 18.2263 16.8924C17.9099 16.8924 17.6283 17.0467 17.4663 17.3078H16.035L16.2819 13.9333H20.1745V15.1992H17.5396L17.4547 16.4651H17.4855C17.6862 16.0419 18.1144 15.7689 18.7162 15.7689C19.7733 15.7689 20.5333 16.572 20.5333 17.6875Z" fill="#BDC1C6"/><rect width="33" height="33" rx="16.5" fill="white" fill-opacity="0.01"/></svg>`,
		front: `<svg width="33" height="33" viewBox="0 0 33 33" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.6868 16.5C9.6868 12.4682 12.948 9.25467 16.9022 9.25467C18.402 9.25467 19.7965 9.71347 20.9538 10.5038L20.1626 11.273C19.9155 11.5133 20.0905 11.924 20.44 11.924H23.8077C24.0244 11.924 24.2 11.7533 24.2 11.5427V8.26863C24.2 7.9289 23.7775 7.75876 23.5304 7.99899L22.6369 8.86763C21.0415 7.67631 19.0559 6.96667 16.9022 6.96667C11.5868 6.96667 7.33331 11.2652 7.33331 16.5C7.33331 21.7348 11.5868 26.0333 16.9022 26.0333C19.1306 26.0333 21.1808 25.2733 22.8039 24.0048C23.3105 23.609 23.391 22.8889 22.9838 22.3964C22.5766 21.904 21.8359 21.8257 21.3294 22.2216C20.1044 23.1789 18.5692 23.7453 16.9022 23.7453C12.948 23.7453 9.6868 20.5318 9.6868 16.5ZM15.499 19.2751H13.8632V15.0027H13.8323L12.4666 15.9284V14.524L13.8632 13.5667H15.499V19.2751ZM21.2666 17.3209C21.2666 18.5907 20.3407 19.4333 18.948 19.4333C17.6247 19.4333 16.6872 18.6619 16.6795 17.5701H18.1494C18.215 17.9222 18.5468 18.1674 18.9596 18.1674C19.4225 18.1674 19.735 17.8351 19.735 17.3604C19.735 16.862 19.4264 16.5257 18.9596 16.5257C18.6432 16.5257 18.3616 16.68 18.1996 16.9411H16.7683L17.0152 13.5667H20.9078V14.8326H18.2729L18.188 16.0985H18.2188C18.4195 15.6752 18.8477 15.4022 19.4495 15.4022C20.5066 15.4022 21.2666 16.2053 21.2666 17.3209Z" fill="#BDC1C6"/><rect width="33" height="33" rx="16.5" fill="white" fill-opacity="0.01"/></svg>`,
		cancel: `<svg width="33" height="33" viewBox="0 0 33 33" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.6166 10.1138L10.114 11.6164L14.9976 16.5L10.114 21.3836L11.6166 22.8862L16.5002 18.0026L21.3835 22.8859L22.8861 21.3833L18.0028 16.5L22.8861 11.6167L21.3835 10.1141L16.5002 14.9974L11.6166 10.1138Z" fill="#BDC1C6"/><rect width="33" height="33" rx="16.5" fill="white" fill-opacity="0.01"/></svg>`,
		speed: {
			'0.5': `<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.564 27.625C15.2446 27.625 16.8706 25.3335 16.8706 21.6639C16.8706 17.9943 15.23 15.75 12.564 15.75C9.89795 15.75 8.25 18.0022 8.25 21.6718C8.25 25.3492 9.8833 27.625 12.564 27.625ZM12.564 25.7193C11.2603 25.7193 10.4985 24.2783 10.4985 21.6718C10.4985 19.0889 11.2749 17.6557 12.564 17.6557C13.8604 17.6557 14.6221 19.081 14.6221 21.6718C14.6221 24.2783 13.8677 25.7193 12.564 25.7193Z" fill="#BDC1C6"/><path d="M18.307 27.4675C18.9589 27.4675 19.4862 26.9005 19.4862 26.1997C19.4862 25.491 18.9589 24.9319 18.307 24.9319C17.6479 24.9319 17.1278 25.491 17.1278 26.1997C17.1278 26.9005 17.6479 27.4675 18.307 27.4675Z" fill="#BDC1C6"/><path d="M24.2405 27.625C26.6502 27.625 28.3274 25.9871 28.3274 23.5617C28.3274 21.3961 26.8919 19.8685 24.8704 19.8685C23.7937 19.8685 22.9295 20.3252 22.4974 21.0575H22.4534L22.7024 17.8998H26.8772C27.4339 17.8998 27.7854 17.5297 27.7854 16.9548C27.7854 16.38 27.4266 16.0099 26.8772 16.0099H22.241C21.3841 16.0099 20.9227 16.3957 20.8494 17.3564L20.5418 21.5221C20.5345 21.5615 20.5345 21.5851 20.5345 21.6166C20.5052 22.2545 20.8494 22.7978 21.5745 22.7978C22.0652 22.7978 22.285 22.6797 22.7171 22.2466C23.0906 21.8607 23.618 21.5694 24.2479 21.5694C25.3978 21.5694 26.2181 22.4198 26.2181 23.6168C26.2181 24.8452 25.3904 25.7351 24.2405 25.7351C23.2884 25.7351 22.6658 25.239 22.285 24.42C22.0359 23.9948 21.7576 23.8294 21.3182 23.8294C20.7469 23.8294 20.3953 24.1916 20.3953 24.798C20.3953 25.0815 20.4612 25.3256 20.5711 25.5776C21.0984 26.7824 22.5633 27.625 24.2405 27.625Z" fill="#BDC1C6"/><path d="M29.3024 27.5384C29.6833 27.5384 29.8957 27.4045 30.1887 26.9163L31.7194 24.4436H31.7634L33.3234 26.9557C33.5651 27.3494 33.7702 27.5384 34.1877 27.5384C34.7663 27.5384 35.2058 27.1446 35.2058 26.5225C35.2058 26.2706 35.1325 26.0501 34.9714 25.8138L33.1477 23.1049L34.9421 20.5299C35.1472 20.2464 35.2277 20.0181 35.2277 19.7346C35.2277 19.1519 34.8249 18.7581 34.2243 18.7581C33.8288 18.7581 33.6018 18.9471 33.3161 19.4275L31.8806 21.782H31.8366L30.3718 19.4117C30.0861 18.9235 29.8591 18.7581 29.427 18.7581C28.841 18.7581 28.4016 19.1912 28.4016 19.7661C28.4016 20.0338 28.4748 20.2622 28.6286 20.4827L30.4597 23.1679L28.6213 25.8138C28.4162 26.1052 28.3356 26.3257 28.3356 26.6013C28.3356 27.1525 28.7385 27.5384 29.3024 27.5384Z" fill="#BDC1C6"/><rect width="44" height="44" rx="22" fill="white" fill-opacity="0.01"/></svg>`,
			'0.7': `<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.8882 27.625C15.7703 27.625 17.5185 25.3335 17.5185 21.6639C17.5185 17.9943 15.7546 15.75 12.8882 15.75C10.0218 15.75 8.25 18.0022 8.25 21.6718C8.25 25.3492 10.0061 27.625 12.8882 27.625ZM12.8882 25.7193C11.4865 25.7193 10.6675 24.2783 10.6675 21.6718C10.6675 19.0889 11.5022 17.6557 12.8882 17.6557C14.282 17.6557 15.101 19.081 15.101 21.6718C15.101 24.2783 14.2899 25.7193 12.8882 25.7193Z" fill="#BDC1C6"/><path d="M19.0629 27.4675C19.7637 27.4675 20.3307 26.9005 20.3307 26.1997C20.3307 25.491 19.7637 24.9319 19.0629 24.9319C18.3541 24.9319 17.795 25.491 17.795 26.1997C17.795 26.9005 18.3541 27.4675 19.0629 27.4675Z" fill="#BDC1C6"/><path d="M22.8358 27.5384C23.3476 27.5384 23.639 27.3494 23.8989 26.8454L28.1118 18.7109C28.4189 18.1124 28.5607 17.7344 28.5607 17.3092C28.5607 16.4981 27.9701 16.0099 27.1432 16.0099H21.2608C20.6939 16.0099 20.2765 16.38 20.2765 16.9469C20.2765 17.5218 20.6939 17.8998 21.2608 17.8998H26.1589V17.947L21.9538 25.8217C21.8121 26.0816 21.7491 26.2942 21.7491 26.5698C21.7491 27.121 22.1664 27.5384 22.8358 27.5384Z" fill="#BDC1C6"/><path d="M29.6247 27.5384C30.0342 27.5384 30.2625 27.4045 30.5775 26.9163L32.2233 24.4436H32.2706L33.9479 26.9557C34.2077 27.3494 34.4282 27.5384 34.8771 27.5384C35.4992 27.5384 35.9717 27.1446 35.9717 26.5225C35.9717 26.2706 35.8929 26.0501 35.7197 25.8138L33.7589 23.1049L35.6882 20.5299C35.9087 20.2464 35.9953 20.0181 35.9953 19.7346C35.9953 19.1519 35.5622 18.7581 34.9165 18.7581C34.4912 18.7581 34.2471 18.9471 33.94 19.4275L32.3966 21.782H32.3493L30.7744 19.4117C30.4673 18.9235 30.2232 18.7581 29.7586 18.7581C29.1286 18.7581 28.6561 19.1912 28.6561 19.7661C28.6561 20.0338 28.7348 20.2622 28.9002 20.4827L30.8689 23.1679L28.8923 25.8138C28.6719 26.1052 28.5852 26.3257 28.5852 26.6013C28.5852 27.1525 29.0183 27.5384 29.6247 27.5384Z" fill="#BDC1C6"/><rect width="44" height="44" rx="22" fill="white" fill-opacity="0.01"/></svg>`,
			'1.0': `<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.0338 27.9198C13.683 27.9198 14.1207 27.4821 14.1207 26.8183V18.313C14.1207 17.5544 13.6538 17.0802 12.8733 17.0802C12.4211 17.0802 12.071 17.1459 11.5603 17.496L9.47414 18.9403C9.1313 19.181 9 19.429 9 19.7646C9 20.2314 9.32825 20.5524 9.77321 20.5524C9.99934 20.5524 10.1525 20.5013 10.3568 20.3554L11.8959 19.2832H11.9397V26.8183C11.9397 27.4821 12.3846 27.9198 13.0338 27.9198Z" fill="#BDC1C6"/><path d="M17.0833 27.8541C17.7325 27.8541 18.2577 27.3289 18.2577 26.6797C18.2577 26.0232 17.7325 25.5053 17.0833 25.5053C16.4268 25.5053 15.9089 26.0232 15.9089 26.6797C15.9089 27.3289 16.4268 27.8541 17.0833 27.8541Z" fill="#BDC1C6"/><path d="M23.2919 28C25.9617 28 27.5811 25.8773 27.5811 22.4781C27.5811 19.0789 25.9471 17 23.2919 17C20.6368 17 18.9955 19.0862 18.9955 22.4854C18.9955 25.8919 20.6222 28 23.2919 28ZM23.2919 26.2347C21.9935 26.2347 21.2349 24.8999 21.2349 22.4854C21.2349 20.0928 22.0081 18.7653 23.2919 18.7653C24.5831 18.7653 25.3417 20.0855 25.3417 22.4854C25.3417 24.8999 24.5904 26.2347 23.2919 26.2347Z" fill="#BDC1C6"/><path d="M29.0994 27.9198C29.4787 27.9198 29.6902 27.7958 29.982 27.3435L31.5066 25.0531H31.5503L33.104 27.38C33.3448 27.7447 33.549 27.9198 33.9648 27.9198C34.541 27.9198 34.9787 27.555 34.9787 26.9788C34.9787 26.7454 34.9058 26.5411 34.7453 26.3223L32.929 23.813L34.7161 21.4277C34.9204 21.1651 35.0006 20.9536 35.0006 20.691C35.0006 20.1512 34.5994 19.7865 34.0013 19.7865C33.6074 19.7865 33.3812 19.9615 33.0967 20.4065L31.667 22.5875H31.6233L30.1644 20.3919C29.8799 19.9397 29.6538 19.7865 29.2234 19.7865C28.6398 19.7865 28.2022 20.1877 28.2022 20.7202C28.2022 20.9682 28.2751 21.1797 28.4283 21.384L30.2519 23.8714L28.421 26.3223C28.2168 26.5922 28.1365 26.7964 28.1365 27.0517C28.1365 27.5623 28.5377 27.9198 29.0994 27.9198Z" fill="#BDC1C6"/><rect width="44" height="44" rx="22" fill="white" fill-opacity="0.01"/><rect width="44" height="44" rx="22" fill="white" fill-opacity="0.01"/></svg>`,
			'1.2': `<svg width="44" height="45" viewBox="0 0 44 45" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.0662 28C13.7206 28 14.1618 27.5588 14.1618 26.8897V18.3162C14.1618 17.5515 13.6912 17.0735 12.9044 17.0735C12.4485 17.0735 12.0956 17.1397 11.5809 17.4926L9.47794 18.9485C9.13235 19.1912 9 19.4412 9 19.7794C9 20.25 9.33088 20.5735 9.77941 20.5735C10.0074 20.5735 10.1618 20.5221 10.3676 20.375L11.9191 19.2941H11.9632V26.8897C11.9632 27.5588 12.4118 28 13.0662 28Z" fill="#BDC1C6"/><path d="M17.1481 27.9338C17.8026 27.9338 18.332 27.4044 18.332 26.75C18.332 26.0882 17.8026 25.5662 17.1481 25.5662C16.4864 25.5662 15.9643 26.0882 15.9643 26.75C15.9643 27.4044 16.4864 27.9338 17.1481 27.9338Z" fill="#BDC1C6"/><path d="M20.4654 27.8456H26.3404C26.9139 27.8456 27.2595 27.4853 27.2595 26.9632C27.2595 26.4265 26.9139 26.0809 26.3404 26.0809H22.3404V26.0368L24.6345 23.8309C26.3698 22.1912 27.0022 21.3824 27.0022 20.0882C27.0022 18.2647 25.4875 17 23.2375 17C21.2301 17 19.8992 18.0956 19.5389 19.2721C19.4875 19.4265 19.4581 19.5809 19.4581 19.75C19.4581 20.3015 19.811 20.6544 20.4066 20.6544C20.8845 20.6544 21.1419 20.4485 21.3919 20.0074C21.7742 19.1544 22.3625 18.7279 23.2154 18.7279C24.1639 18.7279 24.8404 19.3529 24.8404 20.2279C24.8404 20.9926 24.5022 21.5074 23.1934 22.7647L20.0316 25.7868C19.5831 26.1985 19.4213 26.4779 19.4213 26.8971C19.4213 27.4559 19.7669 27.8456 20.4654 27.8456Z" fill="#BDC1C6"/><path d="M28.6871 28C29.0694 28 29.2827 27.875 29.5768 27.4191L31.1136 25.1103H31.1577L32.7239 27.4559C32.9665 27.8235 33.1724 28 33.5915 28C34.1724 28 34.6136 27.6324 34.6136 27.0515C34.6136 26.8162 34.54 26.6103 34.3783 26.3897L32.5474 23.8603L34.3489 21.4559C34.5547 21.1912 34.6356 20.9779 34.6356 20.7132C34.6356 20.1691 34.2312 19.8015 33.6283 19.8015C33.2312 19.8015 33.0033 19.9779 32.7165 20.4265L31.2753 22.625H31.2312L29.7606 20.4118C29.4739 19.9559 29.2459 19.8015 28.8121 19.8015C28.2239 19.8015 27.7827 20.2059 27.7827 20.7426C27.7827 20.9926 27.8562 21.2059 28.0106 21.4118L29.8489 23.9191L28.0033 26.3897C27.7974 26.6618 27.7165 26.8676 27.7165 27.125C27.7165 27.6397 28.1209 28 28.6871 28Z" fill="#BDC1C6"/><rect y="0.5" width="44" height="44" rx="22" fill="white" fill-opacity="0.01"/></svg>`,
			'1.5': `<svg width="44" height="45" viewBox="0 0 44 45" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.0635 27.9192C13.7174 27.9192 14.1583 27.4783 14.1583 26.8096V18.2418C14.1583 17.4776 13.688 17 12.9018 17C12.4462 17 12.0935 17.0661 11.5792 17.4188L9.47762 18.8737C9.13226 19.1162 9 19.3661 9 19.7041C9 20.1743 9.33066 20.4977 9.77889 20.4977C10.0067 20.4977 10.161 20.4462 10.3667 20.2993L11.9172 19.2191H11.9613V26.8096C11.9613 27.4783 12.4095 27.9192 13.0635 27.9192Z" fill="#BDC1C6"/><path d="M17.1427 27.853C17.7967 27.853 18.3257 27.324 18.3257 26.67C18.3257 26.0087 17.7967 25.487 17.1427 25.487C16.4814 25.487 15.9597 26.0087 15.9597 26.67C15.9597 27.324 16.4814 27.853 17.1427 27.853Z" fill="#BDC1C6"/><path d="M23.4411 28C25.8586 28 27.5413 26.4716 27.5413 24.2084C27.5413 22.1877 26.101 20.7622 24.073 20.7622C22.9928 20.7622 22.1258 21.1884 21.6922 21.8717H21.6481L21.898 18.9252H26.0863C26.6448 18.9252 26.9975 18.5798 26.9975 18.0434C26.9975 17.507 26.6374 17.1617 26.0863 17.1617H21.435C20.5753 17.1617 20.1124 17.5217 20.0389 18.4182L19.7303 22.3053C19.723 22.342 19.723 22.3641 19.723 22.3935C19.6936 22.9886 20.0389 23.4957 20.7664 23.4957C21.2587 23.4957 21.4791 23.3854 21.9127 22.9813C22.2874 22.6212 22.8165 22.3494 23.4484 22.3494C24.602 22.3494 25.425 23.143 25.425 24.2599C25.425 25.4061 24.5947 26.2365 23.4411 26.2365C22.4858 26.2365 21.8612 25.7735 21.4791 25.0094C21.2293 24.6126 20.9501 24.4583 20.5092 24.4583C19.936 24.4583 19.5833 24.7963 19.5833 25.3621C19.5833 25.6266 19.6495 25.8544 19.7597 26.0895C20.2888 27.2138 21.7584 28 23.4411 28Z" fill="#BDC1C6"/><path d="M28.865 27.9192C29.2471 27.9192 29.4602 27.7943 29.7541 27.3387L31.2898 25.0314H31.3339L32.8991 27.3754C33.1415 27.7428 33.3473 27.9192 33.7661 27.9192C34.3466 27.9192 34.7875 27.5518 34.7875 26.9713C34.7875 26.7361 34.714 26.5304 34.5524 26.31L32.7227 23.7822L34.523 21.3794C34.7287 21.1149 34.8095 20.9018 34.8095 20.6373C34.8095 20.0935 34.4054 19.7261 33.8029 19.7261C33.4061 19.7261 33.1783 19.9025 32.8917 20.3507L31.4515 22.5478H31.4074L29.9378 20.336C29.6512 19.8804 29.4234 19.7261 28.9899 19.7261C28.4021 19.7261 27.9612 20.1303 27.9612 20.6667C27.9612 20.9165 28.0347 21.1296 28.189 21.3353L30.026 23.841L28.1816 26.31C27.9759 26.5818 27.8951 26.7876 27.8951 27.0448C27.8951 27.5591 28.2992 27.9192 28.865 27.9192Z" fill="#BDC1C6"/><rect y="0.5" width="44" height="44" rx="22" fill="white" fill-opacity="0.01"/></svg>`,
			'1.7': `<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.0935 28C14.7524 28 15.1965 27.5559 15.1965 26.8822V18.251C15.1965 17.4812 14.7227 17 13.9307 17C13.4717 17 13.1164 17.0666 12.5983 17.4219L10.4812 18.8876C10.1332 19.1319 10 19.3836 10 19.7241C10 20.1978 10.3331 20.5236 10.7847 20.5236C11.0141 20.5236 11.1696 20.4717 11.3769 20.3237L12.9388 19.2355H12.9832V26.8822C12.9832 27.5559 13.4347 28 14.0935 28Z" fill="#BDC1C6"/><path d="M18.203 27.9334C18.8618 27.9334 19.3948 27.4004 19.3948 26.7416C19.3948 26.0754 18.8618 25.5498 18.203 25.5498C17.5368 25.5498 17.0112 26.0754 17.0112 26.7416C17.0112 27.4004 17.5368 27.9334 18.203 27.9334Z" fill="#BDC1C6"/><path d="M22.0978 28C22.5789 28 22.8528 27.8223 23.0971 27.3486L27.0574 19.7019C27.3461 19.1393 27.4793 18.784 27.4793 18.3843C27.4793 17.6218 26.9241 17.1629 26.1469 17.1629H20.6173C20.0843 17.1629 19.692 17.5108 19.692 18.0437C19.692 18.5841 20.0843 18.9394 20.6173 18.9394H25.2216V18.9838L21.2687 26.3863C21.1354 26.6306 21.0762 26.8304 21.0762 27.0895C21.0762 27.6077 21.4685 28 22.0978 28Z" fill="#BDC1C6"/><path d="M28.8276 28C29.2126 28 29.4272 27.8742 29.7233 27.4152L31.2704 25.0908H31.3149L32.8916 27.4522C33.1359 27.8223 33.3431 28 33.7651 28C34.3499 28 34.794 27.6299 34.794 27.0451C34.794 26.8082 34.72 26.6009 34.5571 26.3789L32.7139 23.8324L34.5275 21.4118C34.7348 21.1454 34.8162 20.9307 34.8162 20.6642C34.8162 20.1164 34.4091 19.7463 33.8021 19.7463C33.4023 19.7463 33.1729 19.924 32.8842 20.3755L31.4333 22.5888H31.3889L29.9084 20.3607C29.6197 19.9017 29.3902 19.7463 28.9535 19.7463C28.3613 19.7463 27.9172 20.1534 27.9172 20.6938C27.9172 20.9455 27.9912 21.1602 28.1466 21.3674L29.9972 23.8917L28.1392 26.3789C27.932 26.6528 27.8505 26.86 27.8505 27.1191C27.8505 27.6373 28.2577 28 28.8276 28Z" fill="#BDC1C6"/><rect width="44" height="44" rx="22" fill="white" fill-opacity="0.01"/></svg>`,
			'2.0': `<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.0358 27.7666H15.8641C16.433 27.7666 16.7759 27.4092 16.7759 26.8912C16.7759 26.3588 16.433 26.0159 15.8641 26.0159H11.8959V25.9721L14.1718 23.7838C15.8932 22.1572 16.5206 21.3548 16.5206 20.071C16.5206 18.2619 15.0179 17.0073 12.7858 17.0073C10.7944 17.0073 9.47414 18.0942 9.11671 19.2613C9.06565 19.4145 9.03647 19.5676 9.03647 19.7354C9.03647 20.2825 9.3866 20.6326 9.97745 20.6326C10.4516 20.6326 10.7069 20.4284 10.9549 19.9907C11.3342 19.1446 11.9178 18.7215 12.7639 18.7215C13.7049 18.7215 14.376 19.3415 14.376 20.2095C14.376 20.9682 14.0405 21.4788 12.742 22.7261L9.60544 25.7241C9.16048 26.1326 9 26.4098 9 26.8256C9 27.38 9.34284 27.7666 10.0358 27.7666Z" fill="#BDC1C6"/><path d="M18.2588 27.8541C18.908 27.8541 19.4332 27.3289 19.4332 26.6797C19.4332 26.0232 18.908 25.5053 18.2588 25.5053C17.6023 25.5053 17.0844 26.0232 17.0844 26.6797C17.0844 27.3289 17.6023 27.8541 18.2588 27.8541Z" fill="#BDC1C6"/><path d="M23.9141 28C26.5839 28 28.2033 25.8773 28.2033 22.4781C28.2033 19.0789 26.5693 17 23.9141 17C21.259 17 19.6177 19.0862 19.6177 22.4854C19.6177 25.8919 21.2444 28 23.9141 28ZM23.9141 26.2347C22.6157 26.2347 21.8571 24.8999 21.8571 22.4854C21.8571 20.0928 22.6303 18.7653 23.9141 18.7653C25.2053 18.7653 25.9639 20.0855 25.9639 22.4854C25.9639 24.8999 25.2125 26.2347 23.9141 26.2347Z" fill="#BDC1C6"/><path d="M29.1683 27.9198C29.5476 27.9198 29.7591 27.7958 30.0509 27.3435L31.5754 25.0531H31.6192L33.1729 27.38C33.4136 27.7447 33.6179 27.9198 34.0337 27.9198C34.6099 27.9198 35.0476 27.555 35.0476 26.9788C35.0476 26.7454 34.9747 26.5411 34.8142 26.3223L32.9979 23.813L34.785 21.4277C34.9892 21.1651 35.0695 20.9536 35.0695 20.691C35.0695 20.1512 34.6683 19.7865 34.0701 19.7865C33.6762 19.7865 33.4501 19.9615 33.1656 20.4065L31.7359 22.5875H31.6922L30.2333 20.3919C29.9488 19.9397 29.7227 19.7865 29.2923 19.7865C28.7087 19.7865 28.2711 20.1877 28.2711 20.7202C28.2711 20.9682 28.344 21.1797 28.4972 21.384L30.3208 23.8714L28.4899 26.3223C28.2857 26.5922 28.2054 26.7964 28.2054 27.0517C28.2054 27.5623 28.6066 27.9198 29.1683 27.9198Z" fill="#BDC1C6"/><rect width="44" height="44" rx="22" fill="white" fill-opacity="0.01"/></svg>`,
		},
	};

	module.exports = { ActivityPinnedCall };
});