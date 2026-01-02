/**
 * @module im/messenger-v2/controller/recent/const/service
 */
jn.define('im/messenger-v2/controller/recent/const/service', (require, exports, module) => {
	const RecentServiceName = {
		quickRecent: 'quick-recent',
		databaseLoad: 'database-load',
		serverLoad: 'server-load',
		floatingButton: 'floating-button',
		emptyState: 'empty-state',
		pagination: 'pagination',
		search: 'search',
		render: 'render',
		vuex: 'vuex',
		action: 'action',
		select: 'select',
		external: 'external',
	};

	module.exports = { RecentServiceName };
});
