/**
 * @module im/messenger-v2/controller/recent/config/task
 */
jn.define('im/messenger-v2/controller/recent/config/task', (require, exports, module) => {
	const {
		DialogType,
	} = require('im/messenger/const');
	const { RecentServiceName } = require('im/messenger-v2/controller/recent/const/service');

	const TaskConfig = {
		services: {
			[RecentServiceName.quickRecent]: {
				extension: 'im/messenger-v2/controller/recent/service/quick-recent/common',
				props: {},
			},
			[RecentServiceName.databaseLoad]: {
				extension: 'im/messenger-v2/controller/recent/service/database-load/common',
				props: {
					savePageAction: 'recentModel/setTask',
					filter: {
						dialogTypes: [
							DialogType.tasksTask,
						],
						limit: 50,
					},
				},
			},
			[RecentServiceName.serverLoad]: {
				extension: 'im/messenger-v2/controller/recent/service/server-load/task',
				props: {},
			},
			[RecentServiceName.emptyState]: {
				extension: 'im/messenger-v2/controller/recent/service/empty-state/common',
				props: {
					welcomeScreenExtension: 'im/messenger-v2/controller/recent/service/empty-state/lib/welcome-screen/task',
				},
			},
			[RecentServiceName.pagination]: {
				extension: 'im/messenger-v2/controller/recent/service/pagination/common',
				props: {},
			},
			[RecentServiceName.render]: {
				extension: 'im/messenger-v2/controller/recent/service/render/common',
				props: {
					sections: ['pinned', 'general'],
					defaultSection: 'general',
				},
			},
			[RecentServiceName.vuex]: {
				extension: 'im/messenger-v2/controller/recent/service/vuex/task',
				props: {},
			},
			[RecentServiceName.action]: {
				extension: 'im/messenger-v2/controller/recent/service/action/common',
				props: {},
			},
			[RecentServiceName.select]: {
				extension: 'im/messenger-v2/controller/recent/service/select/task',
				props: {},
			},
		},
	};

	module.exports = { TaskConfig };
});
