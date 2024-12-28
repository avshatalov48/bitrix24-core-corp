/**
 * @module crm/timeline/controllers/order-check
 */
jn.define('crm/timeline/controllers/order-check', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { Loc } = require('loc');

	const SupportedActions = {
		ORDER_CHECK_OPEN_CHECK: 'OrderCheck:OpenCheck',
		ORDER_CHECK_REPRINT_CHECK: 'OrderCheck:ReprintCheck',
	};

	/**
	 * @class TimelineOrderCheckController
	 */
	class TimelineOrderCheckController extends TimelineBaseController
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
				case SupportedActions.ORDER_CHECK_OPEN_CHECK:
					return this.openOrderCheck(actionParams);
				case SupportedActions.ORDER_CHECK_REPRINT_CHECK:
					return this.reprintOrderCheck(actionParams);
			}
		}

		openOrderCheck(actionParams)
		{
			qrauth.open({
				title: `${actionParams.entityName} ${actionParams.shortTitle}`,
				redirectUrl: actionParams.checkUrl,
				hintText: Loc.getMessage('M_CRM_TIMELINE_ORDER_CHECK_QRAUTH_HINT'),
				analyticsSection: 'crm',
			});
		}

		reprintOrderCheck(actionParams)
		{
			BX.ajax.runAction('crm.ordercheck.reprint', {
				data: {
					checkId: actionParams.checkId,
				},
			}).catch((response) => {
				void ErrorNotifier.showError(response.errors[0].message);
			});
		}
	}

	module.exports = { TimelineOrderCheckController };
});
