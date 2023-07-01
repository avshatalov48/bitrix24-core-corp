/**
 * @module im/calls-card
 */
jn.define('im/calls-card', (require, exports, module) => {
	const { CardContent } = require('im/calls-card/card-content');
	const { AudioPlayer } = require('native/media');
	const { CallsCardType, TelephonyUiEvent } = require('im/calls-card/card-content/enum');
	const { numberpad } = require('native/calls');

	/**
	 * @class CallsCardController
	 */
	class CallsCardController
	{
		constructor(props)
		{
			this.props = props;
			this.rootWidget = null;
			this.player = new AudioPlayer();
			this.cardContentRef = null;

			this.startTime = null;
			this.startPauseTime = null;
			this.pauseTime = null;

			this.indicatorTimer = null;
			this.viewPromise = null;

			this.expandHandler = this.expand.bind(this);
			this.rollUpHandler = this.rollUp.bind(this);
			this.onUiEventHandler = this.onUiEvent.bind(this);
			this.onCloseCardViewHandler = this.closeCardView.bind(this);
			this.onPauseChangedHandler = this.onPauseChanged.bind(this);
			this.onNumpadVoiceCharClickHandler = this.onNumpadVoiceCharClick.bind(this);

			numberpad.on('voiceChar', this.onNumpadVoiceCharClickHandler);
			numberpad.on('call', (number) => {
				BX.postComponentEvent("onPhoneTo", [{number}], "calls");
			});
		}

		show(props)
		{
			this.updateCardProps(props);

			if (this.viewPromise)
			{
				return this.viewPromise;
			}

			this.viewPromise = new Promise((resolve, reject) => {
				this.openWidgetLayer()
					.then(() => {
						this.rootWidget.showComponent(new CardContent({
							ref: ref => this.cardContentRef = ref,
							layoutWidget: this.rootWidget,

							crmContactName: props.crmContactName,
							crmCompanyName: props.crmCompanyName,
							phoneNumber: props.phoneNumber,
							status: props.status,
							statusColor: props.statusColor,
							avatarUrl: props.avatarUrl,
							type: props.type,crmData: props.crmData,
							recordText: props.recordText,
							crmStatus: props.crmStatus,
							showName: props.showName,

							onClose: this.onCloseCardViewHandler,
							onUiEvent: this.onUiEventHandler,
							startTime: this.startTime,
							onPauseChanged: this.onPauseChangedHandler,
							onRollUp: this.rollUpHandler,
						}));
						this.viewPromise = null;
					})
					.catch(error => {
						this.viewPromise = null;

						return reject(error);
					});
			});

			return this.viewPromise;
		}

		onUiEvent(params)
		{
			if (params.eventName === TelephonyUiEvent.onNumpadOpen)
			{
				this.showNumpadShortForm();
			}
			else if (this.props.onUiEvent)
			{
				this.props.onUiEvent(params);
			}
		}

		prepareWidgetLayer()
		{
			return new Promise((resolve, reject) => {
				if (uicomponent.widgetLayer() && this.rootWidget)
				{
					return resolve(this.rootWidget);
				}

				uicomponent.createWidgetLayer("layout", {backdrop: {}})
					.then((rootWidget) => resolve(rootWidget))
					.catch(error => reject(error));
			});
		}

		openWidgetLayer()
		{
			return new Promise((resolve, reject) => {
				this.prepareWidgetLayer()
					.then((rootWidget) => {
						this.rootWidget = rootWidget;
						return uicomponent.widgetLayer().show();
					})
					.then(() => resolve())
					.catch(error => reject(error));
			});
		}

		update(props)
		{
			this.updateCardProps(props);

			if (this.cardContentRef)
			{
				this.cardContentRef.update(props);
			}
		}

		closeCardView()
		{
			return uicomponent.widgetLayer().close()
				.then(() => {
					this.rootWidget = null;
				});
		}

		close()
		{
			if (this.indicatorView)
			{
				this.closeIndicator();
			}
			else
			{
				this.closeCardView();
			}
		}

		rollUp()
		{
			device.setProximitySensorEnabled(false);
			this.hideCardView().then(() => {
				this.showIndicator();
			});
		}

		hideCardView()
		{
			return uicomponent.widgetLayer().hide();
		}

		showCardView()
		{
			return uicomponent.widgetLayer().show();
		}

		showNumpad()
		{
			this.showNumpadFullForm();
		}

		closeNumpad()
		{

		}

		/**
		 * @param {string} soundId
		 */
		playSound({soundId})
		{
			this.player.playSound(soundId)
		}

		stopSound()
		{
			this.player.stop();
		}

		startTimer()
		{
			this.startTime = new Date();

			if (this.cardContentRef)
			{
				this.cardContentRef.setStartTime(this.startTime);

				if (this.indicatorView)
				{
					this.updateIndicator();
				}
			}
		}

		onPauseChanged(params)
		{
			this.paused = params.selected;
			if (this.paused)
			{
				this.startPauseTime = new Date();
			}
			else
			{
				this.pauseTime = this.pauseTime + (new Date() - this.startPauseTime);
			}

			this.cardContentRef.updateTimerData({
				paused: this.paused,
				pauseTime: this.pauseTime,
			});
		}

		pauseTimer()
		{
			this.startTime = null;
			this.pauseTime = null;
			this.startPauseTime = null;

			this.update({
				startTime: this.startTime,
				pauseTime: this.pauseTime,
				startPauseTime: this.startPauseTime,
			});
		}

		expand()
		{
			device.setProximitySensorEnabled(true);
			this.closeIndicator();
			this.showCardView();
		}

		cancelDelayedClosing()
		{

		}

		showIndicator()
		{
			this.indicatorView = callInterface.getIndicator(CallUI.IndicatorCode.EXTERNAL);
			this.indicatorView.on('tap', this.expandHandler);
			this.updateIndicator();
			this.indicatorView.show();
		}

		updateIndicator()
		{
			if (this.props.avatarUrl)
			{
				this.indicatorView.imageUrl = this.props.avatarUrl;
			}
			this.indicatorView.setMode(this.props.type === CallsCardType.incoming ? 'incoming' : 'outgoing');
			this.indicatorView.hold(this.paused);
			if (this.startTime === null)
			{
				this.indicatorView.setTime('00:00');
			}
			else
			{
				this.indicatorView.setTime(this.getTimerValue());

				if (!this.paused)
				{
					this.setIndicatorTimer();
				}
			}
		}

		setIndicatorTimer()
		{
			this.indicatorTimer = setInterval(() => {
				this.indicatorView.setTime(this.getTimerValue())
			}, 500);
		}

		getTimerValue()
		{
			if (this.paused)
			{
				return CallUtil.formatSeconds((this.startPauseTime - this.startTime - this.pauseTime) / 1000);
			}

			return CallUtil.formatSeconds(((new Date()).getTime() - this.startTime - this.pauseTime) / 1000);
		}

		setUiMicEnabled()
		{

		}

		updateCardProps(props)
		{
			if (props)
			{
				this.props = {...this.props, ...props};
			}
		}

		onNumpadVoiceCharClick(char)
		{
			this.onUiEvent({
				eventName: TelephonyUiEvent.onNumpadButtonClicked,
				params: char,
			});
		}

		showNumpadShortForm()
		{
			numberpad.showShortForm();
		}

		showNumpadFullForm()
		{
			numberpad.showFullForm();
		}

		closeIndicator()
		{
			this.indicatorView.close();
			this.indicatorView = null;
			clearInterval(this.indicatorTimer);
		}

		//deprecated
		updateHeader(parameters)
		{

		}

		//deprecated
		updateMiddle(parameters)
		{

		}

		//deprecated
		updateFooter(parameters)
		{

		}
	}

	module.exports = { CallsCardController, CardContent };
});
