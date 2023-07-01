/**
 * @module crm/receive-payment/steps/finish
 */
jn.define('crm/receive-payment/steps/finish', (require, exports, module) => {
	const { Loc } = require('loc');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { StatusBlock } = require('crm/receive-payment/steps/finish/status-block');
	const { Statuses } = require('crm/receive-payment/steps/finish/statuses');
	const { AnalyticsLabel } = require('analytics-label');

	const pathToExtension = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/receive-payment/steps/finish`;

	/**
	 * @class FinishStep
	 */
	class FinishStep extends WizardStep
	{
		constructor(props)
		{
			super();
			this.uid = props.uid || Random.getString();
			this.props = props;
			this.sendingStatus = Statuses.NONE;
			this.startingAnimationFinished = false;
			this.isMessageSent = false;
			this.error = false;
			this.errorCode = null;

			this.areAnalyticsSent = false;
		}

		onEnterStep()
		{
			super.onEnterStep();

			if (!this.areAnalyticsSent)
			{
				AnalyticsLabel.send({
					event: 'onReceivePaymentSendMessage',
				});

				this.areAnalyticsSent = true;
			}
		}

		get sendMessageProps()
		{
			return BX.prop.getObject(this.props, 'sendMessageProps', {});
		}

		get sendMessage()
		{
			return BX.prop.getFunction(this.props, 'sendMessage', null);
		}

		get parentLayout()
		{
			return BX.prop.getObject(this.props, 'parentLayout', {});
		}

		getTitle()
		{
			return Loc.getMessage('M_RP_F_TITLE');
		}

		isNeedToShowNextStep()
		{
			return false;
		}

		isPrevStepEnabled()
		{
			return false;
		}

		createLayout(props)
		{
			if (this.sendingStatus === Statuses.NONE && this.sendMessage)
			{
				this.sendMessage()
					.then(() => {
						this.isMessageSent = true;
						if (this.startingAnimationFinished)
						{
							this.statusBlockRef.setState({ sendingStatus: Statuses.FINISHING });
							this.sendingStatus = Statuses.FINISHING;
						}
					})
					.catch((response) => {
						this.error = true;
						this.errorCode = response.errors[0].code;
						if (this.startingAnimationFinished)
						{
							this.statusBlockRef.setState({
								sendingStatus: Statuses.ERROR,
								errorCode: this.errorCode,
							});
							this.sendingStatus = Statuses.ERROR;
						}
					})
				;
				this.sendingStatus = Statuses.STARTED;
			}
			else if (this.sendingStatus === Statuses.FINISHING)
			{
				this.sendingStatus = Statuses.FINISHED;
			}

			return View(
				{
					style: {
						backgroundColor: '#eef2f4',
					},
				},
				new StatusBlock({
					uid: this.uid,
					sendMessageProps: this.sendMessageProps,
					sendingStatus: this.sendingStatus,
					parentLayout: this.parentLayout,
					ref: (ref) => {
						this.statusBlockRef = ref;
						setTimeout(() => {
							this.startingAnimationFinished = true;
							if (this.isMessageSent)
							{
								this.statusBlockRef.setState({ sendingStatus: Statuses.FINISHING });
							}
							else if (this.error)
							{
								this.statusBlockRef.setState({
									sendingStatus: Statuses.ERROR,
									errorCode: this.errorCode,
								});
							}
						}, 2000);
					},
				}),
			);
		}
	}

	module.exports = { FinishStep };
});
