/**
 * @module crm/kanban/toolbar
 */
jn.define('crm/kanban/toolbar', (require, exports, module) => {
	const { ToolbarFactory: BaseToolbarFactory } = require('layout/ui/kanban/toolbar');
	const { TypeName } = require('crm/type');
	const { DealToolbar } = require('crm/kanban/toolbar/deal');

	class ToolbarFactory extends BaseToolbarFactory
	{
		/**
		 * @param {String} type
		 * @return {Boolean}
		 */
		has(type)
		{
			return type === TypeName.Deal;
		}

		create(type, data)
		{
			if (type === TypeName.Deal)
			{
				return new DealToolbar(data);
			}

			throw new Error(`Toolbar entity ${type} not found.`);
		}
	}

	module.exports = { ToolbarFactory };
});
