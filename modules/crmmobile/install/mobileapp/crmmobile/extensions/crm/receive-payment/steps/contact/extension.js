/**
 * @module crm/receive-payment/steps/contact
 */
jn.define('crm/receive-payment/steps/contact', (require, exports, module) => {
	const { Loc } = require('loc');
	const { FieldEditorStep } = require('layout/ui/wizard/step/field-editor');
	const { WarningLayout } = require('crm/receive-payment/steps/contact/warning-layout');
	const { ProgressBarNumber } = require('crm/salescenter/progress-bar-number');
	const { ClientType } = require('layout/ui/fields/client');
	const { AnalyticsLabel } = require('analytics-label');
	const { EventEmitter } = require('event-emitter');

	/**
	 * @class ContactStep
	 */
	class ContactStep extends FieldEditorStep
	{
		constructor(props)
		{
			super();
			this.props = props;
			this.areAnalyticsSent = false;

			this.contactFieldRef = null;
			this.selectedContact = null;

			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.customEventEmitter.on('ReceivePayment::onContactPhoneChecked', this.onContactPhoneChecked.bind(this));
		}

		isNextStepEnabled()
		{
			return this.props.hasSmsProviders && this.selectedContact !== null;
		}

		createLayout(props)
		{
			return View(
				{},
				new WarningLayout({
					uid: this.uid,
					hasSmsProviders: this.props.hasSmsProviders,
				}),
				super.createLayout(props),
			);
		}

		prepareFields()
		{
			this.clearFields();

			this.addField(
				'CONTACT',
				ClientType,
				Loc.getMessage('MOBILE_RECEIVE_PAYMENT_CONTACT'),
				{},
				{
					required: true,
					config: {
						...this.props.data,
						multiple: false,
						selectionOnFocus: true,
					},
					ref: (ref) => this.contactFieldRef = ref,
					uid: this.uid,
				},
			);
		}

		onChange(fieldId, fieldValue, options)
		{
			super.onChange(fieldId, fieldValue, options);
			this.selectedContact = fieldValue.contact[0] ?? null;
			this.customEventEmitter.emit('ReceivePayment::onContactSelected', this.selectedContact);
		}

		onEnterStep()
		{
			super.onEnterStep();

			if (!this.areAnalyticsSent)
			{
				AnalyticsLabel.send({
					event: 'onReceivePaymentContactStepOpen',
				});

				this.areAnalyticsSent = true;
			}
		}

		renderNumberBlock()
		{
			return new ProgressBarNumber({
				number: this.getProgressBarSettings().number.toString(),
				isCompleted: this.selectedContact !== null,
				ref: (ref) => this.progressBarNumberRef = ref,
			});
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
				title: {
					text: Loc.getMessage('MOBILE_RECEIVE_PAYMENT_CONTACT_STEP_TITLE'),
				},
			};
		}

		getTitle()
		{
			return Loc.getMessage('M_RP_PS_TITLE');
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('M_RP_PS_NEXT_STEP');
		}

		onMoveToNextStep()
		{
			const onMoveToNextStep = BX.prop.getFunction(this.props, 'onMoveToNextStep', null);
			if (onMoveToNextStep)
			{
				onMoveToNextStep({
					selectedContact: this.selectedContact,
				});
			}

			return Promise.resolve();
		}

		onContactPhoneChecked(data)
		{
			if (!this.props.hasSmsProviders)
			{
				return;
			}

			this.stepAvailabilityChangeCallback(data.phoneExists);
			if (data.contact)
			{
				this.selectedContact = data.contact;
			}
			this.progressBarNumberRef.setState({ isCompleted: data.phoneExists });
		}
	}

	module.exports = { ContactStep };
});
