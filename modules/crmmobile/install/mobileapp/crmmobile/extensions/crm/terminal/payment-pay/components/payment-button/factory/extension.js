/**
 * @module crm/terminal/payment-pay/components/payment-button/factory
 */
jn.define('crm/terminal/payment-pay/components/payment-button/factory', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { withPressed } = require('utils/color');
	const { PaymentButton } = require('crm/terminal/payment-pay/components/payment-button/button');

	/**
	 * @class PaymentButtonFactory
	 */
	class PaymentButtonFactory
	{
		/**
		 * @param {TerminalPaymentSystem} paymentSystem
		 * @param {Object} props
		 * @returns {PaymentButton}
		 */
		static createByPaymentSystem(paymentSystem, props = {})
		{
			props.testId = 'TerminalPaymentPayPaymentSystemButton';
			props.text = paymentSystem.title;
			props.iconUri = PaymentButtonFactory.getImagePath('default');
			props.styles = {
				iconContainer: {
					marginRight: 10,
				},
				icon: {
					width: 17,
					height: 17,
				},
			};

			if (paymentSystem.handler === Handlers.yandexcheckout)
			{
				if (paymentSystem.type === Types.sbp)
				{
					props.testId = 'TerminalPaymentPaySBPButton';
					props.text = Loc.getMessage('M_CRM_TL_PAYMENT_PAY_VIA_QR_FAST_MONEY_TRANSFER');
					props.iconUri = PaymentButtonFactory.getImagePath('sbp');
					props.styles = {
						container: {
							backgroundColor: withPressed(AppTheme.colors.accentMainPrimary),
						},
						iconContainer: {
							padding: 1,
							marginRight: 4,
							backgroundColor: AppTheme.colors.bgContentPrimary,
							borderRadius: 3,
						},
						icon: {
							width: 26,
							height: 26,
						},
						text: {
							color: AppTheme.colors.bgContentPrimary,
						},
					};
				}

				if (paymentSystem.type === Types.sberbankQr)
				{
					props.testId = 'TerminalPaymentPaySberbankButton';
					props.text = Loc.getMessage('M_CRM_TL_PAYMENT_PAY_VIA_QR_SBERBANK');
					props.iconUri = PaymentButtonFactory.getImagePath('sber-qr');
					props.styles = {
						iconContainer: {
							marginRight: 5,
						},
						icon: {
							width: 27,
							height: 27,
						},
					};
				}
			}

			return new PaymentButton(props);
		}

		static getImagePath(image)
		{
			return `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/terminal/payment-pay/images/${image}.png`;
		}
	}

	const Handlers = {
		yandexcheckout: 'yandexcheckout',
	};

	const Types = {
		sbp: 'sbp',
		sberbankQr: 'sberbank_qr',
	};

	module.exports = { PaymentButtonFactory };
});
