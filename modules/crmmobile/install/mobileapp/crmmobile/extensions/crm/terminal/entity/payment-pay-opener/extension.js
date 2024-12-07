/**
 * @module crm/terminal/entity/payment-pay-opener
 */
jn.define('crm/terminal/entity/payment-pay-opener', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PaymentPay } = require('crm/terminal/payment-pay');
	const { AnalyticsLabel } = require('analytics-label');

	/**
	 * @class PaymentPayOpener
	 */
	class PaymentPayOpener
	{
		static open(props, parentWidget = PageManager)
		{
			const {
				id,
				uid,
			} = props;

			AnalyticsLabel.send({ event: 'terminal-entity-open-payment-pay' });

			parentWidget.openWidget('layout', {
				modal: true,
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					onlyMediumPosition: true,
					mediumPositionHeight: PaymentPay.getMinHeight(),
					navigationBarColor: AppTheme.colors.bgSecondary,
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
				},
				onReady: (layout) => {
					BX.ajax.runAction('crmmobile.Terminal.Entity.openPaymentPay', { json: { id } })
						.then((response) => {
							const {
								payment,
								isPhoneConfirmed,
								connectedSiteId,
								psCreationActionProviders,
								pullConfig,
							} = response.data;

							layout.showComponent(new PaymentPay({
								layout,
								uid,
								payment,
								psCreationActionProviders,
								pullConfig,
								isStatusVisible: BX.prop.getBoolean(props, 'isStatusVisible', false),
								isPhoneConfirmed,
								connectedSiteId,
							}));
						})
						.catch(console.error);
				},
			});
		}
	}

	module.exports = { PaymentPayOpener };
});
