/**
 * @module crm/category-list/actions
 */
jn.define('crm/category-list/actions', (require, exports, module) => {
	const CategorySelectActions = {
		SelectTunnelDestination: 'selectTunnelDestination',
		SelectCurrentCategory: 'selectCurrentCategory',
		CreateTunnel: 'createTunnel',
	};

	module.exports = { CategorySelectActions };
});
