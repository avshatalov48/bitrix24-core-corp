/**
 * @module crm/timeline/controllers/payment
 */
jn.define('crm/timeline/controllers/payment', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { Loc } = require('loc');

	const SupportedActions = {
		SALESCENTER_APP_START: 'SalescenterApp:Start',
		PAYMENT_OPEN_REALIZATION: 'Payment:OpenRealization',
	};

	/**
	 * @class TimelinePaymentController
	 */
	class TimelinePaymentController extends TimelineBaseController
	{
		constructor(item, entity)
		{
			super(item, entity);
		}

		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		/**
		 * @public
		 * @param {string} action
		 * @param {object} actionParams
		 */
		onItemAction({ action, actionParams = {} })
		{
			switch (action)
			{
				case SupportedActions.SALESCENTER_APP_START:
					return this.openPayment(actionParams);
				case SupportedActions.PAYMENT_OPEN_REALIZATION:
					return this.openRealization();
			}
		}

		openPayment(actionParams)
		{
			this.timelineScopeEventBus.emit('EntityPaymentDocument::Click', [{
				ID: actionParams.paymentId,
				TYPE: 'PAYMENT',
				FORMATTED_DATE: actionParams.formattedDate,
				ACCOUNT_NUMBER: actionParams.accountNumber,
			}]);
		}

		openRealization()
		{
			qrauth.open({
				title: Loc.getMessage('M_CRM_TIMELINE_DESKTOP'),
				redirectUrl: '/shop/documents/sales_order/',
			});
		}
	}

	module.exports = { TimelinePaymentController };
});
