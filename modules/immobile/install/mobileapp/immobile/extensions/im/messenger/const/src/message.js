/**
 * @module im/messenger/const/message
 */
jn.define('im/messenger/const/message', (require, exports, module) => {
	const MessageType = Object.freeze({
		deleted: 'deleted',
		text: 'text',
		emojiOnly: 'emoji-only',
		audio: 'audio',
		image: 'image',
		video: 'video',
		mediaGallery: 'media-gallery',
		file: 'file',
		fileGallery: 'file-gallery',
		status: 'status',
		systemText: 'system-text',
		unsupported: 'unsupported',
		copilot: 'copilot',
		copilotError: 'copilot-error',
		copilotPrompt: 'copilot-promt',
		banner: 'banner',
		checkIn: 'check-in',
	});

	const MessageIdType = {
		statusMessage: 'status-message',
		templateSeparatorUnread: 'template-separator-unread',
		templateSeparatorDate: 'template-separator',
		planLimitBanner: 'plan-limit-banner',
	};

	const OwnMessageStatus = Object.freeze({
		sending: 'sending',
		sent: 'sent',
		viewed: 'viewed',
		error: 'error',
	});

	const MessageParams = Object.freeze({
		ComponentId: {
			ChatCopilotCreationMessage: 'ChatCopilotCreationMessage',
			ChatCopilotAddedUsersMessage: 'ChatCopilotAddedUsersMessage',
			CopilotMessage: 'CopilotMessage',

			ConferenceCreationMessage: 'ConferenceCreationMessage',

			ChatCreationMessage: 'ChatCreationMessage',
			GeneralChatCreationMessage: 'GeneralChatCreationMessage',
			SignMessage: 'SignMessage',

			ChannelCreationMessage: 'ChannelCreationMessage',
			OpenChannelCreationMessage: 'OpenChannelCreationMessage',
			GeneralChannelCreationMessage: 'GeneralChannelCreationMessage',

			PlanLimitsMessage: 'PlanLimitsMessage',

			CheckInMessage: 'CheckInMessage',

			CallMessage: 'CallMessage',
		},
	});

	const MessageComponent = Object.freeze({
		default: 'DefaultMessage',
		file: 'FileMessage',
		smile: 'SmileMessage',
		unsupported: 'UnsupportedMessage',
		deleted: 'DeletedMessage',
		callInvite: 'CallInviteMessage',
		zoomInvite: 'ZoomInviteMessage',
		chatCreation: 'ChatCreationMessage',
		ownChatCreation: 'OwnChatCreationMessage',
		copilotCreation: 'ChatCopilotCreationMessage',
		copilotMessage: 'CopilotMessage',
		copilotAddedUsers: 'ChatCopilotAddedUsersMessage',
		conferenceCreation: 'ConferenceCreationMessage',
		supervisorUpdateFeature: 'SupervisorUpdateFeatureMessage',
		supervisorEnableFeature: 'SupervisorEnableFeatureMessage',
		sign: 'SignMessage',
		checkIn: 'CheckInMessage',
		supportVote: 'SupportVoteMessage',
		supportSessionNumber: 'SupportSessionNumberMessage',
		supportChatCreation: 'SupportChatCreationMessage',
		system: 'SystemMessage',
		channelPost: 'ChannelPost',
		generalChatCreationMessage: 'GeneralChatCreationMessage',
		generalChannelCreationMessage: 'GeneralChannelCreationMessage',
		channelCreationMessage: 'ChannelCreationMessage',
	});

	module.exports = {
		MessageType,
		MessageIdType,
		MessageComponent,
		OwnMessageStatus,
		MessageParams,
	};
});
