/**
 * @module im/messenger/const
 */
jn.define('im/messenger/const', (require, exports, module) => {
	const { AppStatus } = require('im/messenger/const/app-status');
	const {
		CacheNamespace,
		CacheName,
	} = require('im/messenger/const/cache');
	const {
		RawBotType,
		BotType,
		BotCode,
		BotCommand,
	} = require('im/messenger/const/bot');
	const { ConnectionStatus } = require('im/messenger/const/connection-status');
	const { EventType } = require('im/messenger/const/event-type');
	const { FeatureFlag } = require('im/messenger/const/feature-flag');
	const {
		RestMethod,
	} = require('im/messenger/const/rest');
	const {
		ChatTypes,
		MessageStatus,
		SubTitleIconType,
	} = require('im/messenger/const/recent');
	const {
		UserExternalType,
		UserRole,
	} = require('im/messenger/const/user');
	const {
		MessageType,
		MessageIdType,
		OwnMessageStatus,
	} = require('im/messenger/const/message');
	const { ReactionType } = require('im/messenger/const/reaction-type');
	const { DialogType } = require('im/messenger/const/dialog-type');
	const { DialogActionType } = require('im/messenger/const/dialog-action-type');
	const { FileStatus } = require('im/messenger/const/file-status');
	const {
		FileType,
		FileEmojiType,
		FileImageType,
	} = require('im/messenger/const/file-type');
	const { Color } = require('im/messenger/const/color');
	const {
		Path,
	} = require('im/messenger/const/path');

	const { DraftType } = require('im/messenger/const/draft');
	const { SearchEntityIdTypes } = require('im/messenger/const/search');
	const { ErrorType, ErrorCode } = require('im/messenger/const/error');
	const { BBCode } = require('im/messenger/const/bb-code');
	const { Setting } = require('im/messenger/const/setting');
	const { Promo, PromoType } = require('im/messenger/const/promo');
	const { CopilotButtonType } = require('im/messenger/const/copilot-button');
	const { ComponentCode } = require('im/messenger/const/component-code');

	module.exports = {
		AppStatus,
		CacheNamespace,
		CacheName,
		RawBotType,
		BotType,
		BotCode,
		BotCommand,
		ConnectionStatus,
		EventType,
		FeatureFlag,
		ChatTypes,
		MessageStatus,
		SubTitleIconType,
		RestMethod,
		MessageType,
		MessageIdType,
		OwnMessageStatus,
		ReactionType,
		DialogType,
		DialogActionType,
		FileStatus,
		FileType,
		FileEmojiType,
		FileImageType,
		UserExternalType,
		UserRole,
		Color,
		Path,
		DraftType,
		SearchEntityIdTypes,
		ErrorType,
		ErrorCode,
		BBCode,
		Setting,
		Promo,
		PromoType,
		CopilotButtonType,
		ComponentCode,
	};
});
