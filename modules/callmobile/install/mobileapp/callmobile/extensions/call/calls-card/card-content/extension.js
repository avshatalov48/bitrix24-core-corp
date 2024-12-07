/**
 * @module call/calls-card/card-content
 */
jn.define('call/calls-card/card-content', (require, exports, module) => {
	const { PhoneField } = require('layout/ui/fields/phone');
	const { Alert } = require('alert');

	const { Overlay } = require('call/calls-card/card-content/elements');
	const { CollapseButton } = require('call/calls-card/card-content/elements');
	const { Avatar } = require('call/calls-card/card-content/elements');
	const { Status } = require('call/calls-card/card-content/elements');
	const { CrmButton } = require('call/calls-card/card-content/elements');
	const { Button } = require('call/calls-card/card-content/elements');
	const { Timer } = require('call/calls-card/card-content/elements');
	const { CallControls } = require('call/calls-card/card-content/elements');
	const { PureComponent } = require('layout/pure-component');
	const { CallsCardType, TelephonyUiEvent } = require('call/calls-card/card-content/enum');

	/**
	 * @class CardContent
	 */
	class CardContent extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				status: this.props.status,
				statusColor: this.props.statusColor,
				errorText: null,
				type: this.props.type,
				crmData: this.props.crmData,
				avatarUrl: this.props.avatarUrl,
				crmContactName: this.props.crmContactName,
				crmCompanyName: this.props.crmCompanyName,
				recordText: this.props.recordText,
				startTime: null,
				pauseTime: null,
				paused: false,
				crmStatus: this.props.crmStatus,
				showName: this.props.showName,
				isNumberHidden: this.props.isNumberHidden,
			};

			this.onCloseHandler = this.onClose.bind(this);
			this.onUiEventHandler = this.onUiEvent.bind(this);
			this.onPauseChangedhandler = this.onPauseChanged.bind(this);
			this.onRollUpHandler = this.onRollUp.bind(this);
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			if (nextProps.crmContactName !== '' || nextProps.crmCompanyName !== '')
			{
				nextState.showName = true;
			}

			return super.shouldComponentUpdate(nextProps, nextState);
		}

		get phoneNumber()
		{
			return BX.prop.getString(this.props, 'phoneNumber', null);
		}

		get layoutWidget()
		{
			return BX.prop.get(this.props, 'layoutWidget', null);
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				Overlay({
					imagePath: this.state.avatarUrl,
				}),
				this.renderContent(),
			);
		}

		renderContent()
		{
			return View(
				{
					style: {
						flex: 1,
						paddingTop: device.screen.safeArea.top,
						paddingBottom: device.screen.safeArea.bottom,
					},
				},
				View(
					{
						style: {
							flex: 1,
							paddingTop: 12,
							paddingBottom: 10,
							flexDirection: 'column',
							justifyContent: 'space-around'
						},
					},
					this.renderHeader(),
					CrmButton({
						layoutWidget: this.layoutWidget,
						crmData: this.state.crmData,
						crmContactName: this.state.crmContactName,
						crmCompanyName: this.state.crmCompanyName,
						onUiEvent: this.onUiEventHandler,
					}),
					View(
						{
							style: {
								minHeight: 30,
								justifyContent: 'center',
								alignItems: 'center',
							},
						},
						Text({
							style: {
								color: '#FFFFFF',
								fontSize: 13,
								opacity: 0.4,
								marginBottom: 13,
								textAlign: 'center',
							},
							text: this.state.recordText,
						}),
					),
					View(
						{
							style: {
								flexDirection: 'column',
							},
						},
						this.renderButtons(),
						new CallControls({
							showAcceptButton: this.state.type === CallsCardType.incoming,
							isFinished: this.state.type === CallsCardType.finished,
							onClose: this.onCloseHandler,
							onUiEvent: this.onUiEventHandler,
							type: this.state.type,
						}),
					),
				),
			);
		}

		renderHeader()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				Avatar({
					avatarUrl: this.state.avatarUrl,
					type: this.state.type,
				}),
				CollapseButton({
					onRollUp: this.onRollUpHandler,
					eventName: TelephonyUiEvent.onFormFolded,
					onUiEvent: this.onUiEventHandler,
				}),
				this.renderName(),
				!this.state.isNumberHidden && PhoneField({
					readOnly: true,
					value: {
						phoneNumber: this.phoneNumber,
					},
					showTitle: false,
					showDefaultIcon: false,
					config: {
						ellipsize: false,
						deepMergeStyles: {
							externalWrapper: {
								marginBottom: 2,
							},
							contentWrapper: {
								justifyContent: 'center',
							},
							wrapper: {
								flex: null,
							},
							innerWrapper: {
								flex: null,
							},
							value: {
								color: '#A8ADB4',
								flex: null,
							},
							phoneFieldContainer: {
								flex: null,
							},
						},
					},
				}),
				View(
					{
						style: {
							flexDirection: 'row',
							height: 30,
							justifyContent: 'center',
							alignItems: 'center',
							marginBottom: 10,
						},
					},
					this.state.crmStatus && Status({
						statusText: this.state.crmStatus.text,
						statusColor: this.state.crmStatus.color,
					}),
					this.state.startTime && new Timer({
						paused: this.state.paused,
						startTime: this.state.startTime,
						pauseTime: this.state.pauseTime,
					}),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							height: 30,
							justifyContent: 'center',
							alignItems: 'center',
						},
					},
					this.state.status && Status({
						statusText: this.state.status,
						statusColor: this.state.statusColor,
						showBalloon: false,
					}),
				),
			);
		}

		renderName()
		{
			let name = this.state.crmContactName ? this.state.crmContactName : this.state.crmCompanyName;
			if (!this.state.showName)
			{
				name = '';
			}

			return View(
				{
					style: {
						height: 27,
						marginBottom: 10,
						marginTop: 200,
					}
				},
				Text({
					style: {
						fontSize: 23,
						color: '#FFFFFF',
					},
					text: name,
				})
			);
		}

		renderButtons()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'center',
						alignItems: 'center',
						marginBottom: 6,
					},
				},
				new Button({
					icon: icons.microphone,
					text: BX.message('MOBILE_CALLS_CARD_MICROPHONE_BUTTON'),
					isSwitchable: true,
					eventName: TelephonyUiEvent.onMuteChanged,
					onUiEvent: this.onUiEventHandler,
					testId: 'calls-card-microphone-button',
				}),
				new Button({
					icon: icons.keyboard,
					text: BX.message('MOBILE_CALLS_CARD_KEYBOARD_BUTTON'),
					eventName: TelephonyUiEvent.onNumpadOpen,
					onUiEvent: this.onUiEventHandler,
					enabled: this.state.type === CallsCardType.started,
					testId: 'calls-card-keyboard-button',
				}),
				new Button({
					icon: icons.pause,
					text: BX.message('MOBILE_CALLS_CARD_PAUSE_BUTTON'),
					isSwitchable: true,
					eventName: TelephonyUiEvent.onPauseChanged,
					onUiEvent: this.onUiEventHandler,
					onClick: this.onPauseChangedhandler,
					enabled: this.state.type === CallsCardType.started,
					testId: 'calls-card-pause-button',
				}),
				new Button({
					icon: icons.dynamic,
					text: BX.message('MOBILE_CALLS_CARD_DYNAMIC_BUTTON'),
					isSwitchable: true,
					eventName: TelephonyUiEvent.onSpeakerphoneChanged,
					onUiEvent: this.onUiEventHandler,
					testId: 'calls-card-dynamic-button',
				}),
			);
		}

		onPauseChanged(params)
		{
			if (this.props.onPauseChanged)
			{
				this.props.onPauseChanged(params);
			}
		}

		setStartTime(time)
		{
			this.setState({
				startTime: time,
			});
		}

		updateTimerData({paused, pauseTime})
		{
			this.setState({
				paused,
				pauseTime,
			});
		}

		onClose()
		{
			if (this.props.onClose)
			{
				this.props.onClose();
			}
		}

		onRollUp()
		{
			if (this.props.onRollUp)
			{
				this.props.onRollUp();
			}
		}

		onUiEvent(params)
		{
			if (this.props.onUiEvent)
			{
				this.props.onUiEvent(params);
			}
		}

		update(props)
		{
			this.setState({
				...this.state,
				...props,
			}, () => {
				if (props.errorText)
				{
					this.showError();
				}
			});
		}

		showError()
		{
			Alert.alert(BX.message('MOBILE_CALLS_CARD_ERROR_TITLE'), this.state.errorText);
		}
	}

	const icons = {
		microphone: `<svg width="35" height="36" viewBox="0 0 35 36" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.0648 18.0326L14.0648 17.6617L18.1521 21.686C18.0338 21.6972 17.9138 21.7029 17.7925 21.7029C15.7338 21.7029 14.0648 20.0597 14.0648 18.0326ZM17.8071 24.005C18.691 24.005 19.4682 23.8835 20.1498 23.6529L22.2255 25.6967C21.3597 26.1369 20.371 26.4534 19.262 26.6018L19.261 28.3716L19.8531 28.372C20.5334 28.372 21.0848 28.9149 21.0848 29.5847C21.0848 30.2544 20.5334 30.7973 19.8531 30.7973L15.7321 30.7973C15.0518 30.7973 14.5004 30.2544 14.5004 29.5847C14.5004 28.9149 15.0518 28.372 15.7321 28.372L16.3223 28.3716L16.3222 26.6006C11.381 25.9437 8.85512 22.0302 8.89351 18.5301C8.90166 17.7869 9.52018 17.1909 10.275 17.1989C10.9795 17.2065 11.5538 17.7375 11.6206 18.4125L11.627 18.5592C11.6193 19.2573 12.0154 20.598 12.7463 21.6406C13.7958 23.1375 15.4091 24.005 17.8071 24.005ZM24.052 18.0534L26.4677 20.432C26.6298 19.7896 26.7049 19.1428 26.6914 18.5161C26.6753 17.773 26.0505 17.1834 25.2958 17.1992C24.7274 17.2111 24.2474 17.563 24.052 18.0534ZM14.4736 8.62254L21.5202 15.5606L21.5202 10.2953C21.5202 8.26824 19.8512 6.625 17.7925 6.625C16.3457 6.625 15.0914 7.43656 14.4736 8.62254ZM8.53316 7.80031C8.16587 8.16195 8.16587 8.74827 8.53316 9.1099L26.43 26.7312C26.7973 27.0928 27.3928 27.0928 27.7601 26.7312L28.07 26.426C28.4373 26.0644 28.4373 25.4781 28.07 25.1164L10.1732 7.49514C9.80588 7.13351 9.21039 7.13351 8.8431 7.49514L8.53316 7.80031Z" fill="white" fill-opacity="0.8"/></svg>`,
		keyboard: `<svg width="35" height="36" viewBox="0 0 35 36" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.6485 10.5352C12.6485 11.9094 11.5344 13.0235 10.1602 13.0235C8.78592 13.0235 7.67188 11.9094 7.67188 10.5352C7.67188 9.16092 8.78592 8.04688 10.1602 8.04688C11.5344 8.04688 12.6485 9.16092 12.6485 10.5352ZM12.6485 18C12.6485 19.3743 11.5344 20.4883 10.1602 20.4883C8.78592 20.4883 7.67187 19.3743 7.67187 18C7.67187 16.6258 8.78592 15.5117 10.1602 15.5117C11.5344 15.5117 12.6485 16.6258 12.6485 18ZM10.1602 27.9531C11.5344 27.9531 12.6485 26.8391 12.6485 25.4649C12.6485 24.0906 11.5344 22.9766 10.1602 22.9766C8.78592 22.9766 7.67187 24.0906 7.67187 25.4649C7.67187 26.8391 8.78592 27.9531 10.1602 27.9531ZM20.1133 10.5352C20.1133 11.9094 18.9993 13.0235 17.625 13.0235C16.2508 13.0235 15.1367 11.9094 15.1367 10.5352C15.1367 9.16092 16.2508 8.04688 17.625 8.04688C18.9993 8.04688 20.1133 9.16092 20.1133 10.5352ZM17.625 20.4883C18.9993 20.4883 20.1133 19.3743 20.1133 18C20.1133 16.6258 18.9993 15.5117 17.625 15.5117C16.2508 15.5117 15.1367 16.6258 15.1367 18C15.1367 19.3743 16.2508 20.4883 17.625 20.4883ZM20.1133 25.4649C20.1133 26.8391 18.9993 27.9532 17.625 27.9532C16.2508 27.9532 15.1367 26.8391 15.1367 25.4649C15.1367 24.0906 16.2508 22.9766 17.625 22.9766C18.9993 22.9766 20.1133 24.0906 20.1133 25.4649ZM25.0899 13.0235C26.4641 13.0235 27.5782 11.9094 27.5782 10.5352C27.5782 9.16092 26.4641 8.04688 25.0899 8.04688C23.7156 8.04688 22.6016 9.16092 22.6016 10.5352C22.6016 11.9094 23.7156 13.0235 25.0899 13.0235ZM27.5782 18C27.5782 19.3743 26.4641 20.4883 25.0899 20.4883C23.7156 20.4883 22.6016 19.3743 22.6016 18C22.6016 16.6258 23.7156 15.5117 25.0899 15.5117C26.4641 15.5117 27.5782 16.6258 27.5782 18ZM25.0899 27.9532C26.4641 27.9532 27.5782 26.8391 27.5782 25.4649C27.5782 24.0906 26.4641 22.9766 25.0899 22.9766C23.7156 22.9766 22.6016 24.0906 22.6016 25.4649C22.6016 26.8391 23.7156 27.9532 25.0899 27.9532Z" fill="white" fill-opacity="0.8"/></svg>`,
		pause: `<svg width="35" height="36" viewBox="0 0 35 36" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.583 8.95239C14.583 8.67625 14.3592 8.45239 14.083 8.45239L10.1628 8.45239C9.88668 8.45239 9.66283 8.67625 9.66283 8.95239L9.66282 27.0477C9.66282 27.3239 9.88668 27.5477 10.1628 27.5477L14.083 27.5477C14.3592 27.5477 14.583 27.3239 14.583 27.0477L14.583 8.95239Z" fill="white" fill-opacity="0.8"/><path d="M25.1263 8.95239C25.1263 8.67625 24.9025 8.4524 24.6263 8.4524L20.7061 8.45239C20.43 8.45239 20.2061 8.67625 20.2061 8.95239L20.2061 27.0477C20.2061 27.3239 20.43 27.5477 20.7061 27.5477L24.6263 27.5477C24.9025 27.5477 25.1263 27.3239 25.1263 27.0477L25.1263 8.95239Z" fill="white" fill-opacity="0.8"/></svg>`,
		dynamic: `<svg width="35" height="36" viewBox="0 0 35 36" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.357 7.19776L18.357 28.8023L10.9327 23.886L10.9339 23.5501C10.8837 23.5568 10.8325 23.5603 10.7805 23.5603L6.32287 23.5603C5.69488 23.5603 5.18579 23.0512 5.18579 22.4232L5.18579 13.5768C5.18579 12.9488 5.69488 12.4397 6.32287 12.4397L10.7805 12.4397C10.8325 12.4397 10.8837 12.4432 10.9339 12.45L10.9327 12.2429L18.357 7.19776ZM25.4864 9.44008C27.7995 11.709 29.0642 14.7467 29.0642 17.9569C29.0642 21.1671 27.7995 24.2069 25.4864 26.4737C25.0944 26.8583 24.4398 26.8583 24.0035 26.4737C23.8279 26.2597 23.6967 26.0039 23.6967 25.7464C23.6967 25.4893 23.7836 25.2335 24.0018 25.0195C25.9671 23.1369 27.0137 20.6541 27.0137 17.9571C27.0137 15.2601 25.9671 12.7771 24.0035 10.8947C23.6114 10.5101 23.6114 9.86811 24.0035 9.44008C24.3955 9.05553 25.05 9.05553 25.4864 9.44008ZM22.7753 12.993C24.1287 14.3189 24.8701 16.0744 24.8701 17.9572C24.8701 19.84 24.1287 21.5954 22.7752 22.9231C22.3832 23.3077 21.7287 23.3077 21.2923 22.9231C21.146 22.7448 21.0317 22.5363 20.9976 22.3227L20.9872 22.1938C20.9872 21.9363 21.0741 21.6805 21.2923 21.4665C22.252 20.5252 22.7753 19.2829 22.7753 17.957C22.7753 16.6295 22.252 15.3889 21.2923 14.4476C20.9003 14.063 20.9003 13.421 21.2923 12.993C21.6844 12.6084 22.3389 12.6084 22.7753 12.993Z" fill="white" fill-opacity="0.8"/></svg>`,
	}

	module.exports = { CardContent };
});
