/**
 * @module crm/terminal/payment-details
 */
jn.define('crm/terminal/payment-details', (require, exports, module) => {
	const { PhoneType } = require('layout/ui/fields');
	const { PureComponent } = require('layout/pure-component');
	const { CommunicationEvents } = require('communication/events');
	const { Loc } = require('loc');
	const {
		FieldManagerService,
		FieldNameSum,
		FieldNamePhone,
		FieldNameClient,
		FieldNameDatePaid,
		FieldNameStatus,
		FieldNamePaymentSystem,
		FieldNameSlipLink,
	} = require('crm/terminal/services/field-manager');

	/**
	 * @class PaymentDetails
	 */
	class PaymentDetails extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.fieldManagerService = new FieldManagerService(
				this.payment.fields,
				{
					renderIfEmpty: false,
				},
			);
			this.onPhoneFieldClick = this.phoneFieldClickHandler.bind(this);
		}

		render()
		{
			return ScrollView(
				{
					style: styles.container,
				},
				View(
					{
						style: styles.fieldsContainer,
					},
					this.fieldManagerService.renderField(FieldNameSum, {
						testId: 'TerminalPaymentDetailsFieldSum',
						readOnly: true,
					}),
					this.fieldManagerService.renderField(FieldNamePhone, {
						testId: 'TerminalPaymentDetailsFieldPhone',
						readOnly: true,
						config: {
							deepMergeStyles: {
								value: {
									color: '#2066b0',
								},
							},
						},
						ref: (ref) => this.phoneFieldRef = ref,
						onContentClick: this.onPhoneFieldClick,
					}),
					this.fieldManagerService.renderField(FieldNameClient, {
						testId: 'TerminalPaymentDetailsFieldClient',
						readOnly: true,
						config: {
							parentWidget: this.layout,
						},
					}),
					this.fieldManagerService.renderField(FieldNameDatePaid, {
						testId: 'TerminalPaymentDetailsFieldDatePaid',
						readOnly: true,
					}),
					this.fieldManagerService.renderField(FieldNameStatus, {
						testId: 'TerminalPaymentDetailsFieldStatus',
						readOnly: true,
					}),
					this.fieldManagerService.renderField(FieldNamePaymentSystem, {
						testId: 'TerminalPaymentDetailsFieldPaymentSystem',
						readOnly: true,
					}),
					this.fieldManagerService.renderField(FieldNameSlipLink, {
						testId: 'TerminalPaymentDetailsFieldSlipLink',
						value: this.payment.slipLink ? Loc.getMessage('M_CRM_TL_PAYMENT_DETAILS_OPEN') : '',
						readOnly: true,
						onContentClick: () => {
							Application.openUrl(currentDomain + this.payment.slipLink);
						},
						config: {
							deepMergeStyles: {
								value: {
									color: '#0B66C3',
								},
							},
						},
					}),
				),
			);
		}

		phoneFieldClickHandler()
		{
			if (!this.payment.phoneNumber)
			{
				return;
			}

			CommunicationEvents.execute({
				type: PhoneType,
				props: {
					alert: true,
					number: this.payment.phoneNumber,
					layoutWidget: this.layout,
				},
			});
		}

		componentDidMount()
		{
			if (this.layout)
			{
				this.layout.enableNavigationBarBorder(false);
			}
		}

		get layout()
		{
			return this.props.layout || {};
		}

		get payment()
		{
			return this.props.payment || {};
		}
	}

	const styles = {
		container: {
			backgroundColor: '#EEF2F4',
			flexDirection: 'column',
			flexGrow: 1,
		},
		fieldsContainer: {
			backgroundColor: '#FFFFFF',
			borderRadius: 12,
			paddingTop: 14,
			paddingBottom: 8,
			paddingHorizontal: 16,
		},
	};

	module.exports = { PaymentDetails };
});
