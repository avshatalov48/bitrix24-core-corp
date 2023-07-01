/**
 * @module im/calls-card/card-content/elements/call-controls
 */
jn.define('im/calls-card/card-content/elements/call-controls', (require, exports, module) => {
	const { transition, parallel } = require('animation');
	const { CallsCardType, TelephonyUiEvent } = require('im/calls-card/card-content/enum');
	const buttonSize = 100;

	/**
	 * @class CallControls
	 */
	class CallControls extends LayoutComponent
	{
		constructor(props) {
			super(props);
			this.containerWidth = null;
			this.contentRef = null;
			this.isAcceptClicked = false;
		}

		get showAcceptButton()
		{
			return BX.prop.getBoolean(this.props, 'showAcceptButton', false);
		}

		get type()
		{
			return BX.prop.getString(this.props, 'type', null);
		}

		shouldComponentUpdate(nextProps, nextState) {
			//fix offset after animation
			return !this.isAcceptClicked;
		}

		render()
		{
			return View(
				{
					style: {
						minHeight: 100,
						alignItems: 'center',
						justifyContent: 'center',
					},
					onLayout: ({width}) => {
						this.containerWidth = width;
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
						},
					},
					View(
						{
							ref: ref => this.contentRef = ref,
							style: {
								width: 0,
							},
						},
					),
					this.renderDeclineButton(),
					this.renderAcceptButton(),
				),
			);
		}

		renderDeclineButton()
		{
			return View(
				{
					style: {
						width: buttonSize,
						height: buttonSize,
						justifyContent: 'center',
						alignItems: 'center',
						marginRight: this.showAcceptButton && buttonSize -30 || 0,
					},
				},
				View(
					{
						style: {
							width: 60,
							height: 60,
							borderRadius: 30,
							backgroundColor: '#FF5752',
						},
						testId: 'calls-card-decline-button',
						onClick: () => {
							switch (this.type)
							{
								case CallsCardType.started:
									this.onUiEvent(TelephonyUiEvent.onHangup);
									break;
								case CallsCardType.incoming:
									this.onUiEvent(TelephonyUiEvent.onSkipClicked);
									break;
								case CallsCardType.outgoing:
									this.onUiEvent(TelephonyUiEvent.onCloseClicked);
									break;
								case CallsCardType.finished:
									this.onUiEvent(TelephonyUiEvent.onCloseClicked);
									if (this.props.onClose)
									{
										this.props.onClose();
									}
									break;
							}
						}
					},
					Image({
						style: {
							width: 60,
							height: 60,
						},
						svg: {
							content: icons.decline,
						},
					}),
				),
			);
		}

		onUiEvent(eventName)
		{
			if (this.props.onUiEvent)
			{
				this.props.onUiEvent({
					eventName,
				});
			}
		}

		renderAcceptButton()
		{
			if (!this.showAcceptButton)
			{
				return null;
			}

			return View(
				{
					ref: ref => this.acceptButtonRef = ref,
					style: {
						width: buttonSize,
						height: buttonSize,
					},
					testId: 'calls-card-accept-button',
					onClick: () => {
						this.animateOnAnswerCall().then(() => {
							this.isAcceptClicked = true;
							this.onUiEvent(TelephonyUiEvent.onAnswerClicked);
						});
					},
				},
				LottieView(
					{
						style: {
							height: buttonSize,
							width: buttonSize,
						},
						data: {
							content: lottie,
						},
						params: {
							loopMode: "loop"
						},
						autoPlay: true,
					},
				),
				View(
					{
						style: {
							width: buttonSize,
							height: buttonSize,
							justifyContent: 'center',
							alignItems: 'center',
							marginTop: -buttonSize,
						},
					},
					View(
						{
							style: {
								width: 60,
								height: 60,
								backgroundColor: '#9DCF00',
								justifyContent: 'center',
								alignItems: 'center',
								borderRadius: 30,
							}
						},
						Image({
							style: {
								width: 45,
								height: 45,
							},
							svg: {
								content: icons.accept,
							},
						}),
					),
				),
			);
		}

		animateOnAnswerCall()
		{
			const moveControls = transition(this.contentRef, {
				duration: 400,
				width: (buttonSize - 15) * 2,
			});

			const hideAcceptButton = transition(this.acceptButtonRef, {
				duration: 400,
				opacity: 0,
			});

			const animate =  parallel(
				moveControls,
				hideAcceptButton,
			);

			return animate();
		}
	}

	const lottie = `{"v": "5.9.0","fr": 25,"ip": 0,"op": 41,"w": 243,"h": 243,"nm": "Call_User","ddd": 0,"assets": [],"layers": [{"ddd": 0,"ind": 4,"ty": 4,"nm": "Vector 2","sr": 1,"ks": {"o": {"a": 1,"k": [{"i": {"x": [0.833], "y": [0.833]},"o": {"x": [0.167], "y": [0.167]},"t": 0,"s": [100]}, {"t": 26, "s": [0]}],"ix": 11},"r": {"a": 0, "k": 0, "ix": 10},"p": {"a": 0, "k": [121.5, 121.5, 0], "ix": 2, "l": 2},"a": {"a": 0, "k": [0, 0, 0], "ix": 1, "l": 2},"s": {"a": 1,"k": [{"i": {"x": [0.667, 0.667, 0.667], "y": [1, 1, 1]},"o": {"x": [0.775, 0.775, 0.333], "y": [0, 0, 0]},"t": 0,"s": [101.21, 101.21, 100]}, {"t": 26, "s": [191.575, 191.575, 100]}],"ix": 6,"l": 2}},"ao": 0,"sy": [{"c": {"a": 0, "k": [1, 1, 1, 0.639999985695], "ix": 2},"o": {"a": 0, "k": 64, "ix": 3},"a": {"a": 0, "k": 135, "ix": 5},"s": {"a": 0, "k": 32, "ix": 8},"d": {"a": 0, "k": 2.828, "ix": 6},"ch": {"a": 0, "k": 0, "ix": 7},"bm": {"a": 0, "k": 1, "ix": 1},"no": {"a": 0, "k": 0, "ix": 9},"ty": 2,"nm": "Inner Shadow"}],"shapes": [{"ty": "gr","it": [{"ind": 0,"ty": "sh","ix": 1,"ks": {"a": 0,"k": {"i": [[0, -29.823], [29.824, 0], [0, 29.824], [-29.823, 0]],"o": [[0, 29.824], [-29.823, 0], [0, -29.823], [29.824, 0]],"v": [[54, 0], [0, 54], [-54, 0], [0, -54]],"c": true},"ix": 2},"nm": "Path 1","mn": "ADBE Vector Shape - Group","hd": false}, {"ty": "gf","o": {"a": 0, "k": 100, "ix": 10},"r": 1,"bm": 0,"g": {"p": 5,"k": {"a": 0,"k": [0, 0.184, 0.776, 0.965, 0.267, 0.244, 0.803, 0.698, 0.533, 0.304, 0.829, 0.431, 0.767, 0.519, 0.85, 0.367, 1, 0.733, 0.871, 0.302],"ix": 9}},"s": {"a": 0, "k": [-13.897, -15.484], "ix": 5},"e": {"a": 0, "k": [62.474, -15.484], "ix": 6},"t": 2,"h": {"a": 0, "k": 0, "ix": 7},"a": {"a": 0, "k": 0, "ix": 8},"nm": "Gradient Fill 1","mn": "ADBE Vector Graphic - G-Fill","hd": false}, {"ty": "tr","p": {"a": 0, "k": [0, 0], "ix": 2},"a": {"a": 0, "k": [0, 0], "ix": 1},"s": {"a": 0, "k": [100, 100], "ix": 3},"r": {"a": 0, "k": 0, "ix": 6},"o": {"a": 0, "k": 100, "ix": 7},"sk": {"a": 0, "k": 0, "ix": 4},"sa": {"a": 0, "k": 0, "ix": 5},"nm": "Transform"}],"nm": "Vector","np": 2,"cix": 2,"bm": 0,"ix": 1,"mn": "ADBE Vector Group","hd": false}],"ip": 0,"op": 26,"st": 0,"bm": 0}],"markers": []}`;

	const icons = {
		decline: `<svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M21.1805 34.9057L16.5539 35.6381C15.1478 35.8512 13.9061 34.7352 13.9808 33.3158C14.1858 27.918 17.8457 22.2423 30.2913 22.3981C42.737 22.5537 46.0253 28.2862 45.8204 33.6841C45.7463 35.1024 44.4464 36.1799 43.0598 35.9292L38.5277 35.1452C36.8957 34.8258 35.9809 33.0818 36.3361 31.4231L36.7328 29.57C36.8316 29.1518 36.569 28.7452 36.1739 28.6274C32.2427 27.6058 27.9707 27.5374 23.9574 28.4404C23.575 28.5483 23.2939 28.9714 23.3492 29.3684L23.6468 31.2548C23.904 32.9012 22.8255 34.6476 21.1805 34.9057Z" fill="white"/></svg>`,
		accept: `<svg width="46" height="46" viewBox="0 0 46 46" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M31.3561 26.9997L35.1455 29.7533C36.2905 30.5969 36.3793 32.264 35.3228 33.2149C31.361 36.8868 24.7598 38.3122 16.0695 29.4016C7.37917 20.4911 9.10749 14.1124 13.0693 10.4405C14.1246 9.48999 15.8057 9.64725 16.6088 10.805L19.2591 14.564C20.1873 15.9439 19.601 17.8239 18.1769 18.7457L16.5861 19.7755C16.2205 20.0013 16.1187 20.4746 16.3148 20.8372C18.3722 24.3394 21.3446 27.4085 24.8209 29.6078C25.1676 29.8019 25.6655 29.7015 25.9072 29.3817L27.0306 27.8373C28.0129 26.4913 30.0104 26.0191 31.3561 26.9997Z" fill="white"/></svg>`,
	};

	module.exports = { CallControls };
});