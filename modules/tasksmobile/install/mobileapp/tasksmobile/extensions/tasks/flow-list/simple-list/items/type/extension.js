/**
 * @module tasks/flow-list/simple-list/items/type
 */
jn.define('tasks/flow-list/simple-list/items/type', (require, exports, module) => {
	const ListItemType = {
		FLOW: 'flow',
		SIMILAR_FLOW: 'similar_flow',
		PROMO_FLOW: 'promo_flow',
		DISABLED_FLOW: 'disabled_flow',
		FLOWS_INFO: 'flows-info',
	};

	module.exports = { ListItemType };
});
