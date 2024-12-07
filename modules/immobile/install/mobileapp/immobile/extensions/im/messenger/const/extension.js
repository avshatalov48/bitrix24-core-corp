/**
 * @module im/messenger/const
 */
jn.define('im/messenger/const', (require, exports, module) => {
	const { AppStatus } = require('im/messenger/const/app-status');
	const {
		AttachType,
		AttachDescription,
		AttachGridItemDisplay,
		AttachColorToken,
	} = require('im/messenger/const/attach');
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
		MessageParams,
		MessageComponent,
	} = require('im/messenger/const/message');
	const { ReactionType } = require('im/messenger/const/reaction-type');
	const { DialogType } = require('im/messenger/const/dialog-type');
	const { DialogActionType } = require('im/messenger/const/dialog-action-type');
	const {
		SidebarActionType,
		SidebarContextMenuActionType,
	} = require('im/messenger/const/sidebar-action-type');
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
	const { SidebarFileType, SidebarTab } = require('im/messenger/const/sidebar');
	const { Promo, PromoType } = require('im/messenger/const/promo');
	const { CopilotButtonType, CopilotPromptType } = require('im/messenger/const/copilot-button');
	const { ComponentCode } = require('im/messenger/const/component-code');
	const { Analytics } = require('im/messenger/const/analytics');
	const { HeaderButton } = require('im/messenger/const/header-button');
	const { NavigationTab } = require('im/messenger/const/navigation-tab');
	const {
		KeyboardButtonContext,
		KeyboardButtonType,
		KeyboardButtonNewLineSeparator,
		KeyboardButtonColorToken,
		KeyboardButtonAction,
	} = require('im/messenger/const/keyboard');
	const { WaitingEntity } = require('im/messenger/const/waiting-entity');
	const { OpenRequest } = require('im/messenger/const/open-request');
	const { OpenDialogContextType } = require('im/messenger/const/context-type');
	const { UrlGetParameter } = require('im/messenger/const/url-params');
	const { MessengerInitRestMethod } = require('im/messenger/const/messenger-init-rest');

	module.exports = {
		AppStatus,
		Analytics,
		AttachType,
		AttachDescription,
		AttachGridItemDisplay,
		AttachColorToken,
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
		MessageComponent,
		OwnMessageStatus,
		MessageParams,
		ReactionType,
		DialogType,
		DialogActionType,
		SidebarActionType,
		SidebarContextMenuActionType,
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
		SidebarFileType,
		SidebarTab,
		Promo,
		PromoType,
		CopilotButtonType,
		CopilotPromptType,
		ComponentCode,
		HeaderButton,
		NavigationTab,
		KeyboardButtonContext,
		KeyboardButtonType,
		KeyboardButtonNewLineSeparator,
		KeyboardButtonColorToken,
		KeyboardButtonAction,
		WaitingEntity,
		OpenRequest,
		OpenDialogContextType,
		UrlGetParameter,
		MessengerInitRestMethod,
	};
});
