/**
 * @module im/messenger/lib/element/recent
 */
jn.define('im/messenger/lib/element/recent', (require, exports, module) => {
	const { RecentItem } = require('im/messenger/lib/element/recent/item/base');
	const { ChatItem } = require('im/messenger/lib/element/recent/item/chat');
	const { CopilotItem } = require('im/messenger/lib/element/recent/item/copilot');
	const { CallItem } = require('im/messenger/lib/element/recent/item/call');
	const { CollabItem } = require('im/messenger/lib/element/recent/item/chat/collab');
	const { AnnouncementItem } = require('im/messenger/lib/element/recent/item/chat/announcement');
	const { ExtranetItem } = require('im/messenger/lib/element/recent/item/chat/extranet');
	const { Support24NotifierItem } = require('im/messenger/lib/element/recent/item/chat/support-24-notifier');
	const { ChannelItem } = require('im/messenger/lib/element/recent/item/chat/channel');
	const { Support24QuestionItem } = require('im/messenger/lib/element/recent/item/chat/support-24-question');
	const { TaskCommentItem } = require('im/messenger/lib/element/recent/item/chat/task-comment');
	const { UserItem } = require('im/messenger/lib/element/recent/item/user');
	const { CurrentUserItem } = require('im/messenger/lib/element/recent/item/user/current');
	const { BotItem } = require('im/messenger/lib/element/recent/item/user/bot');
	const { SupportBotItem } = require('im/messenger/lib/element/recent/item/user/support');
	const { ConnectorUserItem } = require('im/messenger/lib/element/recent/item/user/connector');
	const { ExtranetUserItem } = require('im/messenger/lib/element/recent/item/user/extranet');
	const { CollaberUserItem } = require('im/messenger/lib/element/recent/item/user/collaber');
	const { InvitedUserItem } = require('im/messenger/lib/element/recent/item/user/invited');
	const { NetworkUserItem } = require('im/messenger/lib/element/recent/item/user/network');

	module.exports = {
		RecentItem,
		ChatItem,
		CopilotItem,
		CallItem,
		CollabItem,
		AnnouncementItem,
		ExtranetItem,
		Support24NotifierItem,
		ChannelItem,
		Support24QuestionItem,
		TaskCommentItem,
		UserItem,
		CurrentUserItem,
		BotItem,
		SupportBotItem,
		ConnectorUserItem,
		ExtranetUserItem,
		CollaberUserItem,
		InvitedUserItem,
		NetworkUserItem,
	};
});
