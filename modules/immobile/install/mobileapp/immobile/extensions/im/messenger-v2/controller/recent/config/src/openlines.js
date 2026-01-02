/**
 * @module im/messenger-v2/controller/recent/config/openlines
 */
jn.define('im/messenger-v2/controller/recent/config/openlines', (require, exports, module) => {
	const { RecentServiceName } = require('im/messenger-v2/controller/recent/const/service');

	const OpenlinesConfig = {
		services: {
			[RecentServiceName.quickRecent]: {
				extension: 'im/messenger-v2/controller/recent/service/quick-recent/dummy',
				props: {},
			},
			[RecentServiceName.databaseLoad]: {
				extension: 'im/messenger-v2/controller/recent/service/database-load/dummy',
				props: {},
			},
			[RecentServiceName.serverLoad]: {
				extension: 'im/messenger-v2/controller/recent/service/server-load/dummy',
				props: {},
			},
			[RecentServiceName.floatingButton]: {
				extension: 'im/messenger-v2/controller/recent/service/floating-button/dummy',
				props: {},
			},
			[RecentServiceName.emptyState]: {
				extension: 'im/messenger-v2/controller/recent/service/empty-state/dummy',
				props: {},
			},
			[RecentServiceName.pagination]: {
				extension: 'im/messenger-v2/controller/recent/service/pagination/dummy',
				props: {},
			},
			[RecentServiceName.search]: {
				extension: 'im/messenger-v2/controller/recent/service/search/dummy',
				props: {},
			},
			[RecentServiceName.render]: {
				extension: 'im/messenger-v2/controller/recent/service/render/dummy',
				props: {},
			},
			[RecentServiceName.vuex]: {
				extension: 'im/messenger-v2/controller/recent/service/vuex/dummy',
				props: {},
			},
			[RecentServiceName.action]: {
				extension: 'im/messenger-v2/controller/recent/service/action/dummy',
				props: {},
			},
			[RecentServiceName.select]: {
				extension: 'im/messenger-v2/controller/recent/service/select/dummy',
				props: {},
			},
		},
	};

	module.exports = { OpenlinesConfig };
});
