/**
 * @module crm/kanban/toolbar
 */
jn.define('crm/kanban/toolbar', (require, exports, module) => {
	const { TypeName, Type } = require('crm/type');
	const { EntityToolbar } = require('crm/kanban/toolbar/entity-toolbar');

	const AVAILABLE_TYPES = new Set([
		TypeName.Deal,
		TypeName.Lead,
		TypeName.SmartInvoice,
		TypeName.Quote,
	]);

	class ToolbarFactory
	{
		/**
		 * @param {String} type
		 * @return {Boolean}
		 */
		has(type)
		{
			return (AVAILABLE_TYPES.has(type) || Type.isDynamicTypeByName(type));
		}

		/**
		 * @param {string} type
		 * @return {typeof KanbanToolbar|null}
		 */
		get(type)
		{
			if (this.has(type))
			{
				return EntityToolbar;
			}

			return null;
		}
	}

	module.exports = { ToolbarFactory };
});
