/**
 * @module layout/ui/copilot-role-selector/src/types
 */
jn.define('layout/ui/copilot-role-selector/src/types', (require, exports, module) => {
	const ListType = {
		INDUSTRIES: 'industries',
		ROLES: 'roles',
	};

	const ListItemType = {
		INDUSTRY: 'industry',
		ROLE: 'role',
		FEEDBACK: 'feedback',
		UNIVERSAL_ROLE: 'universal_role',
	};

	module.exports = { ListType, ListItemType };
});
