/**
 * @module im/messenger/lib/element
 */
jn.define('im/messenger/lib/element', (require, exports, module) => {

	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');

	const {
		MessageAlign,
		MessageTextAlign,
	} = require('im/messenger/lib/element/dialog/message/base');
	const { TextMessage } = require('im/messenger/lib/element/dialog/message/text');
	const { DeletedMessage } = require('im/messenger/lib/element/dialog/message/deleted');
	const { ImageMessage } = require('im/messenger/lib/element/dialog/message/image');
	const { AudioMessage } = require('im/messenger/lib/element/dialog/message/audio');
	const { VideoMessage } = require('im/messenger/lib/element/dialog/message/video');
	const { FileMessage } = require('im/messenger/lib/element/dialog/message/file');
	const { SystemTextMessage } = require('im/messenger/lib/element/dialog/message/system-text');
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
		TextMessage,
		DeletedMessage,
		ImageMessage,
		AudioMessage,
		VideoMessage,
		FileMessage,
		SystemTextMessage,
		UnsupportedMessage,
		DateSeparatorMessage,
		UnreadSeparatorMessage,
		MessageAlign,
		MessageTextAlign,

		ActionType,
		MessageMenu,
		ReplyAction,
		CopyAction,
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
