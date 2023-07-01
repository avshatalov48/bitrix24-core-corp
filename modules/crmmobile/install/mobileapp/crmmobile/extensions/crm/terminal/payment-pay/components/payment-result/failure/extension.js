/**
 * @module crm/terminal/payment-pay/components/payment-result/failure
 */
jn.define('crm/terminal/payment-pay/components/payment-result/failure', (require, exports, module) => {
	const { PaymentResult } = require('crm/terminal/payment-pay/components/payment-result');
	const { withPressed } = require('utils/color');

	/**
	 * @class PaymentResultFailure
	 */
	class PaymentResultFailure extends PaymentResult
	{
		getImageTestId()
		{
			return 'TerminalPaymentPayFailureImage';
		}

		getTextTestId()
		{
			return 'TerminalPaymentPayFailureText';
		}

		getBackgroundColor()
		{
			return '#F4433E';
		}

		getImage()
		{
			return 'exclamation-mark';
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
			return {
				button: {
					backgroundColor: withPressed('#FFFFFF'),
				},
				buttonText: {
					color: '#333333',
				},
			};
		}
	}

	module.exports = { PaymentResultFailure };
});
