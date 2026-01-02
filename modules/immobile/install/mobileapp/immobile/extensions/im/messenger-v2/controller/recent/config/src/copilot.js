/**
 * @module im/messenger-v2/controller/recent/config/copilot
 */
jn.define('im/messenger-v2/controller/recent/config/copilot', (require, exports, module) => {
	const { DialogType } = require('im/messenger/const');
	const { openChatCreateByActiveRecentTab } = require('im/messenger-v2/lib/open-chat-create');
	const { RecentServiceName } = require('im/messenger-v2/controller/recent/const/service');

	const CopilotConfig = {
		services: {
			[RecentServiceName.quickRecent]: {
				extension: 'im/messenger-v2/controller/recent/service/quick-recent/common',
				props: {},
			},
			[RecentServiceName.databaseLoad]: {
				extension: 'im/messenger-v2/controller/recent/service/database-load/common',
				props: {
					savePageAction: 'recentModel/setCopilot',
					filter: {
						dialogTypes: [DialogType.copilot],
						limit: 50,
					},
				},
			},
			[RecentServiceName.serverLoad]: {
				extension: 'im/messenger-v2/controller/recent/service/server-load/copilot',
				props: {},
			},
			[RecentServiceName.floatingButton]: {
				extension: 'im/messenger-v2/controller/recent/service/floating-button/common',
				props: {
					onTap: async () => {
						void openChatCreateByActiveRecentTab();
					},
				},
			},
			[RecentServiceName.emptyState]: {
				extension: 'im/messenger-v2/controller/recent/service/empty-state/common',
				props: {
					welcomeScreenExtension: 'im/messenger-v2/controller/recent/service/empty-state/lib/welcome-screen/copilot',
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
				extension: 'im/messenger-v2/controller/recent/service/vuex/copilot',
				props: {},
			},
			[RecentServiceName.action]: {
				extension: 'im/messenger-v2/controller/recent/service/action/common',
				props: {},
			},
			[RecentServiceName.select]: {
				extension: 'im/messenger-v2/controller/recent/service/select/common',
				props: {},
			},
		},
	};

	module.exports = { CopilotConfig };
});
