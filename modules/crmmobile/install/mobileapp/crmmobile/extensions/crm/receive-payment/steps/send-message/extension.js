/**
 * @module crm/receive-payment/steps/send-message
 */
jn.define('crm/receive-payment/steps/send-message', (require, exports, module) => {
	const { Loc } = require('loc');
	const { SmsServiceSelector } = require('crm/receive-payment/steps/send-message/sms-service-selector');
	const { EditButton } = require('crm/receive-payment/steps/send-message/edit-button');
	const { MessageField } = require('crm/receive-payment/steps/send-message/message-field');
	const { Instruction } = require('crm/receive-payment/steps/send-message/instruction');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ProgressBarNumber } = require('crm/receive-payment/progress-bar-number');
	const { SenderCodes } = require('crm/receive-payment/steps/send-message/sender-codes');
	const { AnalyticsLabel } = require('analytics-label');

	const pathToExtension = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/receive-payment/steps/send-message`;

	/**
	 * @class SendMessageStep
	 */
	class SendMessageStep extends WizardStep
	{
		constructor(props)
		{
			super();
			this.props = props;
			this.contactPhone = props.contactPhone;
			this.entityResponsible = props.entityResponsible;
			this.orderPublicUrl = props.orderPublicUrl;
			this.currentSenderCode = props.currentSenderCode;
			this.senders = props.senders;
			this.sendingMethod = props.sendingMethod;
			this.sendingMethodDesc = props.sendingMethodDesc;

			this.areAnalyticsSent = false;
		}

		onEnterStep()
		{
			super.onEnterStep();

			if (!this.areAnalyticsSent)
			{
				AnalyticsLabel.send({
					event: 'onReceivePaymentSendMessageStepOpen',
				});

				this.areAnalyticsSent = true;
			}
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
				title: {
					text: Loc.getMessage('M_RP_SM_PROGRESS_BAR_TITLE', { '#CONTACT_PHONE#': this.contactPhone }),
				},
				number: 3,
				count: 3,
			};
		}

		renderNumberBlock()
		{
			return new ProgressBarNumber({
				number: '3',
				isCompleted: !!this.currentSenderCode,
			});
		}

		getTitle()
		{
			return Loc.getMessage('M_RP_SM_TITLE');
		}

		createLayout(props)
		{
			return View(
				{
					backgroundColor: '#eef2f4',
				},
				this.renderMainBlock(),
				// this.renderInstructionText(),
			);
		}

		renderMainBlock()
		{
			return View(
				{
					style: {
						backgroundColor: '#ffffff',
						borderRadius: 12,
						marginTop: 12,
					},
				},
				this.renderMessageEditingBlockLayout(),
				this.renderSendingMethodBlockLayout(),
			);
		}

		renderMessageEditingBlockLayout()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						width: '100%',
						paddingRight: 16,
					},
				},
				this.renderPersonIconBlockLayout(),
				this.renderMessageFieldLayout(),
				// this.renderMessageFieldEditButtonLayout(),
			);
		}

		renderSendingMethodBlockLayout()
		{
			if (this.currentSenderCode === SenderCodes.BITRIX24)
			{
				const sendingMethodText = Loc.getMessage('M_RP_SM_SENDING_VIA_BITRIX24');
				const helpdeskLinkText = Loc.getMessage('M_RP_SM_HELPDESK');

				return View(
					{
						style: {
							flexDirection: 'row',
							width: '100%',
							marginTop: 14,
							marginBottom: 20,
							justifyContent: 'center',
							flexWrap: 'wrap',
							paddingHorizontal: 16,
						},
					},
					Text({
						style: {
							color: '#a8adb4',
							fontSize: 12,
						},
						text: `${sendingMethodText} `,
					}),
					View(
						{
							onClick: () => {
								helpdesk.openHelpArticle('17537000', 'helpdesk');
							},
						},
						Text({
							style: {
								color: '#828B95',
								fontSize: 12,
								borderBottomWidth: 1,
								borderBottomColor: '#cdd1d5',
								borderStyle: 'dash',
								borderDashSegmentLength: 3,
								borderDashGapLength: 3,
							},
							text: helpdeskLinkText,
						}),
					),
				);
			}
			if (this.currentSenderCode === SenderCodes.SMS_PROVIDER)
			{
				const smsProviderData = this.senders.find((sender) => sender.code === SenderCodes.SMS_PROVIDER);
				const smsSenders = smsProviderData.smsSenders;
				const selectedSmsSenderId = this.sendingMethodDesc.provider;

				return new SmsServiceSelector({
					smsSenders,
					selectedSmsSenderId,
					onSelect: (senderId) => {
						this.sendingMethodDesc.provider = senderId;
						this.saveLatestSelectedProvider(senderId);
					},
				});
			}
		}

		saveLatestSelectedProvider(provider)
		{
			BX.ajax.runAction(
				'crmmobile.ReceivePayment.Option.saveLatestSelectedProvider',
				{
					json: {
						provider,
					},
				},
			);
		}

		renderPersonIconBlockLayout()
		{
			return View(
				{
					style: {
						width: 64,
					},
				},
				Image({
					uri: this.getPersonIconUri(),
					svg: this.entityResponsible.photo ? null : { uri: `${pathToExtension}/images/no-photo.svg` },
					style: {
						width: 42,
						height: 42,
						borderRadius: 100,
						marginTop: 23,
						marginBottom: 4,
						alignSelf: 'center',
						backgroundColor: '#7b8691',
					},
				}),
				Text({
					text: this.entityResponsible.name,
					style: {
						fontSize: 11,
						textAlign: 'center',
						color: '#828b95',
					},
				}),
			);
		}

		getPersonIconUri()
		{
			if (!this.entityResponsible.photo)
			{
				return null;
			}

			if (this.entityResponsible.photo.indexOf('http') === 0)
			{
				return this.entityResponsible.photo;
			}

			return currentDomain + this.entityResponsible.photo;
		}

		renderMessageFieldLayout()
		{
			return View(
				{
					style: {
						marginTop: 20,
						flexShrink: 1,
						width: '100%',
					},
				},
				new MessageField({
					value: this.sendingMethodDesc.text === this.sendingMethodDesc.defaultText
						? this.sendingMethodDesc.defaultTextWrapped
						: this.sendingMethodDesc.text,
					orderPublicUrl: this.orderPublicUrl,
					currentSenderCode: this.currentSenderCode,
					ref: (ref) => this.messageFieldRef = ref,
				}),
			);
		}

		renderMessageFieldEditButtonLayout()
		{
			if (this.currentSenderCode === SenderCodes.BITRIX24)
			{
				return null;
			}
			if (this.currentSenderCode === SenderCodes.SMS_PROVIDER)
			{
				return new EditButton({
					ref: (ref) => this.editButtondRef = ref,
					onChange: (isEditing) => {
						this.editButtondRef.setState({ isEditing });
						this.instructionRef.setState({ isEditing });
						if (isEditing)
						{
							this.messageFieldRef.setState(
								{
									isEditing,
									value: this.sendingMethodDesc.text,
								},
								() => {
									this.messageFieldRef.textInputRef.focus();
								},
							);
						}
						else
						{
							let textValue = this.messageFieldRef.textInputRef.getTextValue();
							if (!textValue.includes('#LINK#'))
							{
								textValue += ' #LINK#';
							}
							this.sendingMethodDesc.text = textValue;
							this.sendingMethodDesc.text_modes.payment = this.sendingMethodDesc.text;
							this.saveSmsTemplate(this.sendingMethodDesc.text);

							this.messageFieldRef.setState({
								isEditing,
								value: this.sendingMethodDesc.text,
							});
							FocusManager.blurFocusedFieldIfHas();
						}
					},
				});
			}
		}

		saveSmsTemplate(smsText)
		{
			BX.ajax.runComponentAction(
				'bitrix:salescenter.app',
				'saveSmsTemplate',
				{
					mode: 'class',
					data: {
						smsTemplate: smsText,
					},
				},
			);
		}

		renderInstructionText()
		{
			if (this.currentSenderCode === SenderCodes.BITRIX24)
			{
				return null;
			}

			return new Instruction({
				ref: (ref) => this.instructionRef = ref,
			});
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('M_RP_SM_SEND');
		}

		onMoveToNextStep()
		{
			if (this.props.parent)
			{
				this.props.parent.saveSendingMethodForSending({
					sendingMethod: this.sendingMethod,
					sendingMethodDesc: this.sendingMethodDesc,
				});
			}

			return Promise.resolve();
		}
	}

	module.exports = { SendMessageStep };
});
