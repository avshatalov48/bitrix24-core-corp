/**
 * @module crm/kanban/toolbar
 */
jn.define('crm/kanban/toolbar', (require, exports, module) => {
	const { ToolbarFactory: BaseToolbarFactory } = require('layout/ui/kanban/toolbar');
	const { TypeName } = require('crm/type');
	const { DealToolbar } = require('crm/kanban/toolbar/entities/deal');
	const { LeadToolbar } = require('crm/kanban/toolbar/entities/lead');
	const { SmartInvoiceToolbar } = require('crm/kanban/toolbar/entities/smart-invoice');
	const { QuoteToolbar } = require('crm/kanban/toolbar/entities/quote');

	const AVAILABLE_TYPES = new Set([
		TypeName.Deal,
		TypeName.Lead,
		TypeName.SmartInvoice,
		TypeName.Quote,
	]);

	class ToolbarFactory extends BaseToolbarFactory
	{
		/**
		 * @param {String} type
		 * @return {Boolean}
		 */
		has(type)
		{
			return AVAILABLE_TYPES.has(type);
		}

		create(type, data)
		{
			if (type === TypeName.Deal)
			{
				return new DealToolbar(data);
			}

			if (type === TypeName.Lead)
			{
				return new LeadToolbar(data);
			}

			if (type === TypeName.SmartInvoice)
			{
				return new SmartInvoiceToolbar(data);
			}

			if (type === TypeName.Quote)
			{
				return new QuoteToolbar(data);
			}

			throw new Error(`Toolbar entity ${type} not found.`);
		}
	}

	module.exports = { ToolbarFactory };
});
