/**
 * @module im/messenger/lib/element
 */
jn.define('im/messenger/lib/element', (require, exports, module) => {
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { UserStatus } = require('im/messenger/lib/element/user-status');

	const { RecentItem } = require('im/messenger/lib/element/recent/item/base');
	const { ChatItem } = require('im/messenger/lib/element/recent/item/chat');
	const { CallItem } = require('im/messenger/lib/element/recent/item/call');
	const { AnnouncementItem } = require('im/messenger/lib/element/recent/item/chat/announcement');
	const { ExtranetItem } = require('im/messenger/lib/element/recent/item/chat/extranet');
	const { Support24NotifierItem } = require('im/messenger/lib/element/recent/item/chat/support-24-notifier');
	const { Support24QuestionItem } = require('im/messenger/lib/element/recent/item/chat/support-24-question');
	const { UserItem } = require('im/messenger/lib/element/recent/item/user');
	const { CurrentUserItem } = require('im/messenger/lib/element/recent/item/user/current');
	const { BotItem } = require('im/messenger/lib/element/recent/item/user/bot');
	const { SupportBotItem } = require('im/messenger/lib/element/recent/item/user/support');
	const { ConnectorUserItem } = require('im/messenger/lib/element/recent/item/user/connector');
	const { ExtranetUserItem } = require('im/messenger/lib/element/recent/item/user/extranet');
	const { InvitedUserItem } = require('im/messenger/lib/element/recent/item/user/invited');
	const { NetworkUserItem } = require('im/messenger/lib/element/recent/item/user/network');

	const {
		Message,
		MessageAlign,
		MessageTextAlign,
	} = require('im/messenger/lib/element/dialog/message/base');
	const { TextMessage } = require('im/messenger/lib/element/dialog/message/text');
	const { EmojiOnlyMessage } = require('im/messenger/lib/element/dialog/message/emoji-only');
	const { DeletedMessage } = require('im/messenger/lib/element/dialog/message/deleted');
	const { ImageMessage } = require('im/messenger/lib/element/dialog/message/image');
	const { AudioMessage } = require('im/messenger/lib/element/dialog/message/audio');
	const { VideoMessage } = require('im/messenger/lib/element/dialog/message/video');
	const { FileMessage } = require('im/messenger/lib/element/dialog/message/file');
	const { SystemTextMessage } = require('im/messenger/lib/element/dialog/message/system-text');
	const { StatusField } = require('im/messenger/lib/element/dialog/message/status');
	const { UnsupportedMessage } = require('im/messenger/lib/element/dialog/message/unsupported');
	const { DateSeparatorMessage } = require('im/messenger/lib/element/dialog/message/date-separator');
	const { UnreadSeparatorMessage } = require('im/messenger/lib/element/dialog/message/unread-separator');

	const {
		LikeReaction,
		KissReaction,
		LaughReaction,
		WonderReaction,
		CryReaction,
		AngryReaction,
		FacepalmReaction,
	} = require('im/messenger/lib/element/dialog/message-menu/reaction');
	const {
		ActionType,
		ReplyAction,
		CopyAction,
		PinAction,
		ForwardAction,
		DownloadToDeviceAction,
		DownloadToDiskAction,
		QuoteAction,
		ProfileAction,
		EditAction,
		DeleteAction,
		SeparatorAction,
	} = require('im/messenger/lib/element/dialog/message-menu/action');
	const { MessageMenu } = require('im/messenger/lib/element/dialog/message-menu/menu');

	module.exports = {
		ChatAvatar,
		ChatTitle,
		UserStatus,

		RecentItem,
		CallItem,
		ChatItem,
		UserItem,
		CurrentUserItem,
		AnnouncementItem,
		ExtranetItem,
		Support24NotifierItem,
		Support24QuestionItem,
		BotItem,
		SupportBotItem,
		ConnectorUserItem,
		ExtranetUserItem,
		InvitedUserItem,
		NetworkUserItem,

		Message,
		TextMessage,
		EmojiOnlyMessage,
		DeletedMessage,
		ImageMessage,
		AudioMessage,
		VideoMessage,
		FileMessage,
		SystemTextMessage,
		StatusField,
		UnsupportedMessage,
		DateSeparatorMessage,
		UnreadSeparatorMessage,
		MessageAlign,
		MessageTextAlign,

		ActionType,
		MessageMenu,
		ReplyAction,
		CopyAction,
		PinAction,
		ForwardAction,
		DownloadToDeviceAction,
		DownloadToDiskAction,
		QuoteAction,
		ProfileAction,
		EditAction,
		DeleteAction,
		SeparatorAction,
		LikeReaction,
		KissReaction,
		LaughReaction,
		WonderReaction,
		CryReaction,
		AngryReaction,
		FacepalmReaction,
	};
});
