/**
 * @module crm/terminal/payment-pay/components/payment-result/failure
 */
jn.define('crm/terminal/payment-pay/components/payment-result/failure', (require, exports, module) => {
	const AppTheme = require('apptheme');
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
			return AppTheme.colors.accentMainAlert;
		}

		getImage()
		{
			return 'exclamation-mark';
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
			return {
				button: {
					backgroundColor: withPressed(AppTheme.colors.bgContentPrimary),
				},
				buttonText: {
					color: AppTheme.colors.base1,
				},
			};
		}
	}

	module.exports = { PaymentResultFailure };
});
