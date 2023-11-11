/**
 * @module crm/simple-list/items
 */
jn.define('crm/simple-list/items', (require, exports, module) => {
	const { CrmEntity } = require('crm/simple-list/items/crm-entity');
	const { TerminalPayment } = require('crm/simple-list/items/terminal-payment');
	const { ListItemsFactory: BaseListItemsFactory } = require('layout/ui/simple-list/items');

	const ListItemType = {
		CRM_ENTITY: 'CrmEntity',
		TERMINAL_PAYMENT: 'TerminalPayment',
	};

	/**
	 * @class ListItemsFactory
	 */
	class ListItemsFactory extends BaseListItemsFactory
	{
		static create(type, data)
		{
			if (type === ListItemType.CRM_ENTITY)
			{
				return new CrmEntity(data);
			}

			if (type === ListItemType.TERMINAL_PAYMENT)
			{
				return new TerminalPayment(data);
			}

			return BaseListItemsFactory.create(type, data);
		}
	}

	module.exports = { ListItemsFactory, ListItemType };
});
