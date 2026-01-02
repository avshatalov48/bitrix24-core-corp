/**
 * @module im/messenger-v2/controller/recent/config/chats
 */
jn.define('im/messenger-v2/controller/recent/config/chats', (require, exports, module) => {
	const {
		DialogType,
		ActionByUserType,
	} = require('im/messenger/const');
	const { UserPermission } = require('im/messenger/lib/permission-manager');
	const { openChatCreateByActiveRecentTab } = require('im/messenger-v2/lib/open-chat-create');
	const { RecentServiceName } = require('im/messenger-v2/controller/recent/const/service');

	const ChatsConfig = {
		services: {
			[RecentServiceName.quickRecent]: {
				extension: 'im/messenger-v2/controller/recent/service/quick-recent/common',
				props: {},
			},
			[RecentServiceName.databaseLoad]: {
				extension: 'im/messenger-v2/controller/recent/service/database-load/common',
				props: {
					savePageAction: 'recentModel/setChat',
					filter: {
						exceptDialogTypes: [
							DialogType.lines,
							DialogType.comment,
							DialogType.tasksTask,
						],
						limit: 50,
					},
				},
			},
			[RecentServiceName.serverLoad]: {
				extension: 'im/messenger-v2/controller/recent/service/server-load/chat',
				props: {},
			},
			[RecentServiceName.floatingButton]: {
				extension: 'im/messenger-v2/controller/recent/service/floating-button/common',
				props: {
					checkShouldShowButton: () => {
						return (
							UserPermission.canPerformActionByUserType(ActionByUserType.createChat)
							|| UserPermission.canPerformActionByUserType(ActionByUserType.createChannel)
							|| UserPermission.canPerformActionByUserType(ActionByUserType.createCollab)
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
					welcomeScreenExtension: 'im/messenger-v2/controller/recent/service/empty-state/lib/welcome-screen/chat',
				},
			},
			[RecentServiceName.pagination]: {
				extension: 'im/messenger-v2/controller/recent/service/pagination/common',
				props: {},
			},
			[RecentServiceName.search]: {
				extension: 'im/messenger-v2/controller/recent/service/search/chat',
				props: {},
			},
			[RecentServiceName.render]: {
				extension: 'im/messenger-v2/controller/recent/service/render/common',
				props: {
					sections: ['call', 'pinned', 'general'],
					defaultSection: 'general',
				},
			},
			[RecentServiceName.vuex]: {
				extension: 'im/messenger-v2/controller/recent/service/vuex/chat',
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
			[RecentServiceName.external]: {
				extension: 'im/messenger-v2/controller/recent/service/external/chat',
				props: {},
			},
		},
	};

	module.exports = { ChatsConfig };
});
