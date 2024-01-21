/**
 * @module crm/timeline/controllers/payment
 */
jn.define('crm/timeline/controllers/payment', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');

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
					return this.openRealization(actionParams);
			}
		}

		openPayment(actionParams)
		{
			this.timelineScopeEventBus.emit('EntityPaymentDocument::Click', [{
				ID: actionParams.paymentId,
				TYPE: actionParams.isTerminalPayment === 'Y' ? 'TERMINAL_PAYMENT' : 'PAYMENT',
				PAID: actionParams.isPaid === 'Y' ? 'Y' : 'N',
			}]);
		}

		openRealization(actionParams)
		{
			this.timelineScopeEventBus.emit('EntityRealizationDocument::Click', [{
				ownerId: this.entity.id,
				ownerTypeId: this.entity.typeId,
				paymentId: actionParams.paymentId,
			}]);
		}
	}

	module.exports = { TimelinePaymentController };
});
