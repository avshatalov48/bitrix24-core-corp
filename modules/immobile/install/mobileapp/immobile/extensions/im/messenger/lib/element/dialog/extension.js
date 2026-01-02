/**
 * @module im/messenger/lib/element/dialog
 */
jn.define('im/messenger/lib/element/dialog', (require, exports, module) => {
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
	const { ErrorMessage } = require('im/messenger/lib/element/dialog/message/error');
	const { CheckInMessageFactory } = require('im/messenger/lib/element/dialog/message/check-in/factory');
	const { CheckInMessageHandler } = require('im/messenger/lib/element/dialog/message/check-in/handler');
	const { GalleryMessageFactory } = require('im/messenger/lib/element/dialog/message/gallery/factory');
	const { GalleryMessageHandler } = require('im/messenger/lib/element/dialog/message/gallery/handler');
	const { CreateBannerFactory } = require('im/messenger/lib/element/dialog/message/banner/factory');
	const { BannerMessageHandler } = require('im/messenger/lib/element/dialog/message/banner/handler');
	const { CallMessageFactory } = require('im/messenger/lib/element/dialog/message/call/factory');
	const { CallMessageHandler } = require('im/messenger/lib/element/dialog/message/call/handler');
	const { VoteMessageFactory } = require('im/messenger/lib/element/dialog/message/vote/factory');
	const { VoteMessageHandler } = require('im/messenger/lib/element/dialog/message/vote/handler');
	const { AiAssistantMessage } = require('im/messenger/lib/element/dialog/message/ai-assistant');

	module.exports = {
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
		ErrorMessage,
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
		VoteMessageFactory,
		VoteMessageHandler,
		AiAssistantMessage,
	};
});
