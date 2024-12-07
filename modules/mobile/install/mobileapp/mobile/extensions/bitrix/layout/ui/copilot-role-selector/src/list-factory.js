/**
 * @module layout/ui/copilot-role-selector/src/list-factory
 */
jn.define('layout/ui/copilot-role-selector/src/list-factory', (require, exports, module) => {
	const { CopilotRoleSelectorIndustriesList } = require('layout/ui/copilot-role-selector/src/industries-list');
	const { CopilotRoleSelectorRolesList } = require('layout/ui/copilot-role-selector/src/roles-list');
	const { ListType } = require('layout/ui/copilot-role-selector/src/types');

	const Types = new Map([
		[ListType.INDUSTRIES, CopilotRoleSelectorIndustriesList],
		[ListType.ROLES, CopilotRoleSelectorRolesList],
	]);

	class CopilotRoleSelectorListFactory
	{
		static create(type, props = {})
		{
			if (!Types.has(type))
			{
				return null;
			}

			const listInitializer = Types.get(type);

			return listInitializer(props);
		}
	}

	module.exports = { CopilotRoleSelectorListFactory };
});
