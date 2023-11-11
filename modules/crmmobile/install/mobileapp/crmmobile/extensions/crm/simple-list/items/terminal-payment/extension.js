/**
 * @module crm/simple-list/items/terminal-payment
 */
jn.define('crm/simple-list/items/terminal-payment', (require, exports, module) => {
	const { Extended } = require('layout/ui/simple-list/items/extended');

	/**
	 * @class TerminalPayment
	 */
	class TerminalPayment extends Extended
	{
		prepareActions(actions)
		{
			const { isPaid, permissions } = this.props.item.data;

			if (!permissions.delete || isPaid)
			{
				const deleteAction = actions.find((action) => action.id === 'delete');
				deleteAction.isDisabled = true;
			}
		}
	}

	module.exports = { TerminalPayment };
});
