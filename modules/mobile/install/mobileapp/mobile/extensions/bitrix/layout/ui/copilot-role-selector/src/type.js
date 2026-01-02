/**
 * @module layout/ui/copilot-role-selector/src/type
 */
jn.define('layout/ui/copilot-role-selector/src/type', (require, exports, module) => {
	const ScopeType = {
		RECENTS: 'recents',
		RECOMMENDED: 'recommended',
		FAVORITES: 'favorites',
		CUSTOMS: 'customs',
		EXPERTS: 'experts',
	};

	module.exports = {
		ScopeType,
	};
});
