/**
 * @module crm/terminal/payment-pay/components/payment-result/success
 */
jn.define('crm/terminal/payment-pay/components/payment-result/success', (require, exports, module) => {
	const AppTheme = require('apptheme');
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
			return AppTheme.colors.accentMainSuccess;
		}

		getImage()
		{
			return 'circled-check';
		}

		getDefaultButtonStyle()
		{
			return {
				button: {
					borderColor: AppTheme.colors.accentSoftBlue1,
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
