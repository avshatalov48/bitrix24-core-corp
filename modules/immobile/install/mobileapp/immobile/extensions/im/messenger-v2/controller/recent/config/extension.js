/**
 * @module im/messenger-v2/controller/recent/config
 */
jn.define('im/messenger-v2/controller/recent/config', (require, exports, module) => {
	const { NavigationTabId } = require('im/messenger/const');
	const { ChannelConfig } = require('im/messenger-v2/controller/recent/config/channel');
	const { ChatsConfig } = require('im/messenger-v2/controller/recent/config/chats');
	const { CopilotConfig } = require('im/messenger-v2/controller/recent/config/copilot');
	const { CollabConfig } = require('im/messenger-v2/controller/recent/config/collab');
	const { OpenlinesConfig } = require('im/messenger-v2/controller/recent/config/openlines');
	const { TaskConfig } = require('im/messenger-v2/controller/recent/config/task');

	const RecentConfig = {
		[NavigationTabId.chats]: ChatsConfig,
		[NavigationTabId.channel]: ChannelConfig,
		[NavigationTabId.copilot]: CopilotConfig,
		[NavigationTabId.collab]: CollabConfig,
		[NavigationTabId.openlines]: OpenlinesConfig,
		[NavigationTabId.task]: TaskConfig,
	};

	module.exports = { RecentConfig };
});
