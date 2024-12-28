/**
 * @module crm/receive-payment/steps/finish/status-block
 */
jn.define('crm/receive-payment/steps/finish/status-block', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { LottieAnimations } = require('crm/receive-payment/steps/finish/lottie-animations');
	const { Statuses } = require('crm/receive-payment/steps/finish/statuses');
	const { getEntityMessage } = require('crm/loc');
	const { TypeId } = require('crm/type');
	const { EventEmitter } = require('event-emitter');
	const { WarningBlock } = require('layout/ui/warning-block');

	const pathToExtension = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/receive-payment/steps/finish`;

	/**
	 * @class StatusBlock
	 */
	class StatusBlock extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.state.sendingStatus = props.sendingStatus;
			this.state.errorCode = null;
			this.state.connectedSiteId = 0;
		}

		get sendMessageProps()
		{
			return BX.prop.getObject(this.props, 'sendMessageProps', {});
		}

		getErrorCode()
		{
			return this.state.errorCode;
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderRadius: 12,
						alignItems: 'center',
						paddingBottom: 32,
						paddingHorizontal: 16,
					},
				},
				this.renderCloudBlock(),
				this.renderTopText(),
				this.renderBottomText(),
				this.renderBackButton(),
			);
		}

		renderBottomText()
		{
			let text = '';
			switch (this.state.sendingStatus)
			{
				case Statuses.STARTED:
					if (this.sendMessageProps.currentSenderCode === 'bitrix24')
					{
						text = Loc.getMessage('M_RP_F_SENDING_TYPE_BITRIX24');
					}
					else if (this.sendMessageProps.currentSenderCode === 'sms_provider')
					{
						text = Loc.getMessage('M_RP_F_SENDING_TYPE_SMS', { '#NAME#': this.getSelectedSmsSenderName() });
					}
					break;

				case Statuses.FINISHING:
				case Statuses.FINISHED:
					text = Loc.getMessage('M_RP_F_MESSAGE_IS_SENT_BOTTOM');
					break;

				case Statuses.ERROR:
					if (this.getErrorCode() === 9)
					{
						return this.renderWarningBlock();
					}

					text = Loc.getMessage('M_RP_F_MESSAGE_ERROR_BOTTOM', { '#ERROR_CODE#': this.getErrorCode() });
					break;
			}

			return Text({
				text,
				style: {
					fontSize: 17,
					color: AppTheme.colors.base4,
					marginTop: 15,
					textAlign: 'center',
				},
			});
		}

		renderCloudBlock()
		{
			if (this.state.sendingStatus === Statuses.STARTED)
			{
				return this.renderLottieCloud();
			}

			if (this.state.sendingStatus === Statuses.FINISHING)
			{
				return this.renderSvgCloud('green-clouds.svg', true);
			}

			if (this.state.sendingStatus === Statuses.FINISHED)
			{
				return this.renderSvgCloud('green-clouds.svg');
			}

			if (this.state.sendingStatus === Statuses.ERROR)
			{
				return this.renderSvgCloud('error.svg', true);
			}
		}

		renderSvgCloud(image, useOpacityAnimation = false)
		{
			return Image({
				svg: { uri: `${pathToExtension}/images/${image}` },
				style: {
					height: 186,
					width: 229,
					marginTop: 27,
					marginBottom: 25,
					opacity: useOpacityAnimation ? 0.7 : 1,
				},
				ref: (ref) => {
					ref.animate({
						duration: 300,
						opacity: 1,
					});
				},
			});
		}

		renderLottieCloud()
		{
			return LottieView(
				{
					style: {
						height: 216,
						width: 256,
						marginTop: 15,
						marginBottom: 14,
						marginLeft: -3,
					},
					data: {
						content: LottieAnimations.started,
					},
					params: {
						loopMode: 'loop',
						repeatCount: 1,
					},
					autoPlay: true,
				},
			);
		}

		renderTopText()
		{
			let text = '';
			let fontSize = 18;
			let marginBottom = 2;

			switch (this.state.sendingStatus)
			{
				case Statuses.STARTED:
					text = Loc.getMessage('M_RP_F_MESSAGE_IS_SENDING');
					fontSize = 16;
					marginBottom = 4;
					break;

				case Statuses.FINISHING:
				case Statuses.FINISHED:
					text = getEntityMessage('M_RP_F_MESSAGE_IS_SENT', TypeId.OrderPayment);
					break;

				case Statuses.ERROR:
					text = Loc.getMessage('M_RP_F_MESSAGE_ERROR');
					break;
			}

			return Text({
				text,
				style: {
					fontSize,
					marginBottom,
					color: AppTheme.colors.base2,
					textAlign: 'center',
				},
			});
		}

		renderWarningBlock()
		{
			return View(
				{
					style: {
						height: 120,
						width: 360,
						marginTop: 10,
						marginHorizontal: 10,
					},
				},
				new WarningBlock({
					title: Loc.getMessage('M_RP_F_PHONE_NOT_CONFIRMED_WARNING_TITLE'),
					description: Loc.getMessage('M_RP_F_PHONE_NOT_CONFIRMED_WARNING_TEXT'),
					redirectUrl: `/shop/stores/?force_verify_site_id=${this.state.connectedSiteId}`,
					redirectTitle: Loc.getMessage('M_RP_F_PHONE_CONFIRMATION_WARNING_TITLE'),
					analyticsSection: 'crm',
				}),
			);
		}

		getSelectedSmsSenderName()
		{
			const smsProviderData = this.sendMessageProps.senders.find((sender) => sender.code === 'sms_provider');
			if (!smsProviderData)
			{
				return null;
			}

			const smsSenders = smsProviderData.smsSenders;
			const selectedSmsSender = smsSenders.find(
				(sender) => sender.id === this.sendMessageProps.sendingMethodDesc.provider,
			);
			if (!selectedSmsSender)
			{
				return null;
			}

			return selectedSmsSender.name;
		}

		renderBackButton()
		{
			let additionalStyle = {};
			if (this.state.sendingStatus === Statuses.FINISHING || this.state.sendingStatus === Statuses.FINISHED)
			{
				additionalStyle = {
					color: AppTheme.colors.baseWhiteFixed,
					backgroundColor: AppTheme.colors.accentMainPrimary,
				};
			}
			else if (this.state.sendingStatus === Statuses.ERROR)
			{
				additionalStyle = {
					color: AppTheme.colors.base1,
					backgroundColor: AppTheme.colors.bgContentPrimary,
					borderColor: AppTheme.colors.bgSeparatorPrimary,
					borderWidth: 1,
				};
			}
			else
			{
				return null;
			}

			return Button({
				testId: 'receivePaymentFinishStepBackButton',
				style: {
					height: 44,
					width: 266,
					borderRadius: 512,
					selfAlign: 'center',
					marginTop: 25,
					fontSize: 17,
					...additionalStyle,
				},
				text: getEntityMessage('M_RP_F_BACK_BUTTON_TEXT', TypeId.Deal),
				onClick: () => {
					this.customEventEmitter.emit('ReceivePayment.FinishStepButton::Click');
					layout.close();
				},
			});
		}
	}

	module.exports = { StatusBlock };
});
