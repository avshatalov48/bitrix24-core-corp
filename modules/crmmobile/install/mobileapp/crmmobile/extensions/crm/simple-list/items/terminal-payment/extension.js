/**
 * @module crm/simple-list/items/terminal-payment
 */
jn.define('crm/simple-list/items/terminal-payment', (require, exports, module) => {
	const { FieldManagerService } = require('crm/terminal/services/field-manager');
	const { Extended } = require('layout/ui/simple-list/items/extended');

	/**
	 * @class TerminalPayment
	 */
	class TerminalPayment extends Extended
	{
		prepareItem(item)
		{
			const data = item.data;
			if (data.fields)
			{
				data.fields = FieldManagerService.prepareFieldsData(data.fields);
			}

			return item;
		}

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
