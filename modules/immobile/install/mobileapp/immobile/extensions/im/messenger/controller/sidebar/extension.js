/**
 * @module im/messenger/controller/sidebar
 */
jn.define('im/messenger/controller/sidebar', (require, exports, module) => {
	const { ChannelSidebarController } = require('im/messenger/controller/sidebar/channel/sidebar-controller');
	const { ChatSidebarController } = require('im/messenger/controller/sidebar/chat/sidebar-controller');
	const { CommentSidebarController } = require('im/messenger/controller/sidebar/comment/sidebar-controller');
	const { CollabSidebarController } = require('im/messenger/controller/sidebar/collab/sidebar-controller');

	module.exports = {
		ChatSidebarController,
		ChannelSidebarController,
		CommentSidebarController,
		CollabSidebarController,
	};
});
