/**
 * @module im/messenger/lib/element
 */
jn.define('im/messenger/lib/element', (require, exports, module) => {
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { UserStatus } = require('im/messenger/lib/element/user-status');

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
	const { UserItem } = require('im/messenger/lib/element/recent/item/user');
	const { CurrentUserItem } = require('im/messenger/lib/element/recent/item/user/current');
	const { BotItem } = require('im/messenger/lib/element/recent/item/user/bot');
	const { SupportBotItem } = require('im/messenger/lib/element/recent/item/user/support');
	const { ConnectorUserItem } = require('im/messenger/lib/element/recent/item/user/connector');
	const { ExtranetUserItem } = require('im/messenger/lib/element/recent/item/user/extranet');
	const { CollaberUserItem } = require('im/messenger/lib/element/recent/item/user/collaber');
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
	const { MediaGalleryMessage } = require('im/messenger/lib/element/dialog/message/media-gallery');
	const { AudioMessage } = require('im/messenger/lib/element/dialog/message/audio');
	const { VideoMessage } = require('im/messenger/lib/element/dialog/message/video');
	const { FileMessage } = require('im/messenger/lib/element/dialog/message/file');
	const { FileGalleryMessage } = require('im/messenger/lib/element/dialog/message/file-gallery');
	const { SystemTextMessage } = require('im/messenger/lib/element/dialog/message/system-text');
	const { StatusField } = require('im/messenger/lib/element/dialog/message/status');
	const { UnsupportedMessage } = require('im/messenger/lib/element/dialog/message/unsupported');
	const { DateSeparatorMessage } = require('im/messenger/lib/element/dialog/message/date-separator');
	const { UnreadSeparatorMessage } = require('im/messenger/lib/element/dialog/message/unread-separator');
	const { CopilotMessage } = require('im/messenger/lib/element/dialog/message/copilot');
	const { CopilotPromptMessage } = require('im/messenger/lib/element/dialog/message/copilot-prompt');
	const { CopilotErrorMessage } = require('im/messenger/lib/element/dialog/message/copilot-error');
	const { CheckInMessageFactory } = require('im/messenger/lib/element/dialog/message/check-in/factory');
	const { CheckInMessageHandler } = require('im/messenger/lib/element/dialog/message/check-in/handler');
	const { GalleryMessageFactory } = require('im/messenger/lib/element/dialog/message/gallery/factory');
	const { GalleryMessageHandler } = require('im/messenger/lib/element/dialog/message/gallery/handler');
	const { CreateBannerFactory } = require('im/messenger/lib/element/dialog/message/banner/factory');
	const { BannerMessageHandler } = require('im/messenger/lib/element/dialog/message/banner/handler');
	const { CallMessageFactory } = require('im/messenger/lib/element/dialog/message/call/factory');
	const { CallMessageHandler } = require('im/messenger/lib/element/dialog/message/call/handler');

	module.exports = {
		ChatAvatar,
		ChatTitle,
		UserStatus,

		RecentItem,
		CallItem,
		ChatItem,
		CollabItem,
		CopilotItem,
		UserItem,
		CurrentUserItem,
		AnnouncementItem,
		ExtranetItem,
		Support24NotifierItem,
		Support24QuestionItem,
		ChannelItem,
		BotItem,
		SupportBotItem,
		ConnectorUserItem,
		ExtranetUserItem,
		CollaberUserItem,
		InvitedUserItem,
		NetworkUserItem,

		Message,
		TextMessage,
		EmojiOnlyMessage,
		DeletedMessage,
		ImageMessage,
		MediaGalleryMessage,
		AudioMessage,
		VideoMessage,
		FileMessage,
		FileGalleryMessage,
		SystemTextMessage,
		StatusField,
		UnsupportedMessage,
		DateSeparatorMessage,
		UnreadSeparatorMessage,
		CopilotMessage,
		CopilotPromptMessage,
		CopilotErrorMessage,
		CheckInMessageFactory,
		CheckInMessageHandler,
		GalleryMessageFactory,
		GalleryMessageHandler,
		CreateBannerFactory,
		BannerMessageHandler,
		MessageAlign,
		MessageTextAlign,
		CallMessageFactory,
		CallMessageHandler,
	};
});
