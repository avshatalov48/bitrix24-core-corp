/**
 * @module im/messenger-v2/controller/recent/config/collab
 */
jn.define('im/messenger-v2/controller/recent/config/collab', (require, exports, module) => {
	const {
		DialogType,
		ActionByUserType,
	} = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { UserPermission } = require('im/messenger/lib/permission-manager');
	const { openChatCreateByActiveRecentTab } = require('im/messenger-v2/lib/open-chat-create');
	const { RecentServiceName } = require('im/messenger-v2/controller/recent/const/service');

	const CollabConfig = {
		services: {
			[RecentServiceName.quickRecent]: {
				extension: 'im/messenger-v2/controller/recent/service/quick-recent/common',
				props: {},
			},
			[RecentServiceName.databaseLoad]: {
				extension: 'im/messenger-v2/controller/recent/service/database-load/common',
				props: {
					savePageAction: 'recentModel/setCollab',
					filter: {
						dialogTypes: [DialogType.collab],
						limit: 50,
					},
				},
			},
			[RecentServiceName.serverLoad]: {
				extension: 'im/messenger-v2/controller/recent/service/server-load/collab',
				props: {},
			},
			[RecentServiceName.floatingButton]: {
				extension: 'im/messenger-v2/controller/recent/service/floating-button/common',
				props: {
					checkShouldShowButton: () => {
						return (
							Feature.isCollabCreationAvailable
							&& UserPermission.canPerformActionByUserType(ActionByUserType.createCollab)
						);
					},
					onTap: async () => {
						void openChatCreateByActiveRecentTab();
					},
				},
			},
			[RecentServiceName.emptyState]: {
				extension: 'im/messenger-v2/controller/recent/service/empty-state/common',
				props: {
					welcomeScreenExtension: 'im/messenger-v2/controller/recent/service/empty-state/lib/welcome-screen/collab',
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
				extension: 'im/messenger-v2/controller/recent/service/vuex/collab',
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

	module.exports = { CollabConfig };
});
