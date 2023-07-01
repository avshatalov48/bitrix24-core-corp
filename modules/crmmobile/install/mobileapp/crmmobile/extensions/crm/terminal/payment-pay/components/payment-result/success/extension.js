/**
 * @module crm/terminal/payment-pay/components/payment-result/success
 */
jn.define('crm/terminal/payment-pay/components/payment-result/success', (require, exports, module) => {
	const { PaymentResult } = require('crm/terminal/payment-pay/components/payment-result');
	const { withPressed } = require('utils/color');

	/**
	 * @class PaymentResultSuccess
	 */
	class PaymentResultSuccess extends PaymentResult
	{
		getImageTestId()
		{
			return 'TerminalPaymentPaySuccessImage';
		}

		getTextTestId()
		{
			return 'TerminalPaymentPaySuccessText';
		}

		getBackgroundColor()
		{
			return '#9DCF00';
		}

		getImage()
		{
			return 'circled-check';
		}

		getDefaultButtonStyle()
		{
			return {
				button: {
					borderColor: '#99FFFFFF',
					backgroundColor: withPressed(
						this.getBackgroundColor(),
					),
				},
			};
		}

		getPrimaryButtonStyle()
		{
			return this.getDefaultButtonStyle();
		}
	}

	module.exports = { PaymentResultSuccess };
});
