/**
 * @module im/messenger-v2/controller/recent/config/channel
 */
jn.define('im/messenger-v2/controller/recent/config/channel', (require, exports, module) => {
	const {
		ActionByUserType,
	} = require('im/messenger/const');
	const { UserPermission } = require('im/messenger/lib/permission-manager');
	const { openChatCreateByActiveRecentTab } = require('im/messenger-v2/lib/open-chat-create');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { RecentServiceName } = require('im/messenger-v2/controller/recent/const/service');

	const ChannelConfig = {
		services: {
			[RecentServiceName.quickRecent]: {
				extension: 'im/messenger-v2/controller/recent/service/quick-recent/common',
				props: {},
			},
			[RecentServiceName.serverLoad]: {
				extension: 'im/messenger-v2/controller/recent/service/server-load/channel',
				props: {},
			},
			[RecentServiceName.floatingButton]: {
				extension: 'im/messenger-v2/controller/recent/service/floating-button/common',
				props: {
					checkShouldShowButton: () => {
						return UserPermission.canPerformActionByUserType(ActionByUserType.createChannel);
					},
					onTap: async () => {
						void openChatCreateByActiveRecentTab();
					},
				},
			},
			[RecentServiceName.emptyState]: {
				extension: 'im/messenger-v2/controller/recent/service/empty-state/common',
				props: {
					welcomeScreenExtension: 'im/messenger-v2/controller/recent/service/empty-state/lib/welcome-screen/channel',
				},
			},
			[RecentServiceName.pagination]: {
				extension: 'im/messenger-v2/controller/recent/service/pagination/common',
				props: {},
			},
			[RecentServiceName.render]: {
				extension: 'im/messenger-v2/controller/recent/service/render/common',
				props: {
					sections: ['general'],
					defaultSection: 'general',
					itemOptions: {
						showCounter: false,
						showActions: false,
						showDraft: false,
						showPin: false,
						getOrder: (item) => {
							return Math.round(item.getMessageDate().getTime() / 1000);
						},
						getDisplayedDate: (item) => {
							return DateFormatter.getRecentFormat(item.getMessageDate());
						},
					},
				},
			},
			[RecentServiceName.vuex]: {
				extension: 'im/messenger-v2/controller/recent/service/vuex/channel',
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

	module.exports = { ChannelConfig };
});
