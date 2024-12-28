/**
 * @module im/messenger/lib/element/dialog/message/banner/factory
 */
jn.define('im/messenger/lib/element/dialog/message/banner/factory', (require, exports, module) => {
	const { CustomMessageFactory } = require('im/messenger/lib/element/dialog/message/custom/factory');
	const { TextMessage } = require('im/messenger/lib/element/dialog/message/text');
	const { InviteUsersCopilotBanner } = require('im/messenger/lib/element/dialog/message/banner/banners/invite-users-copilot');
	const { CreateChatBanner } = require('im/messenger/lib/element/dialog/message/banner/banners/create-chat');
	const { CreateGeneralChatBanner } = require('im/messenger/lib/element/dialog/message/banner/banners/create-general-chat');
	const { CreateChannelBanner } = require('im/messenger/lib/element/dialog/message/banner/banners/create-channel');
	const { CreateGeneralChannelBanner } = require('im/messenger/lib/element/dialog/message/banner/banners/create-general-channel');
	const { CreateChatConferenceBanner } = require('im/messenger/lib/element/dialog/message/banner/banners/create-conference');
	const { PlanLimitsBanner } = require('im/messenger/lib/element/dialog/message/banner/banners/plan-limits');
	const { SignMessage } = require('im/messenger/lib/element/dialog/message/banner/banners/sign/banner');
	const { MessageParams } = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class CreateBannerFactory
	 */
	class CreateBannerFactory extends CustomMessageFactory
	{
		static create(modelMessage, options = {})
		{
			if (!Feature.isCreateBannerMessageSupported)
			{
				return new TextMessage(modelMessage, options);
			}

			try
			{
				const optionsBanner = {
					...options,
					showReaction: false,
					showAvatarsInReaction: false,
					canBeQuoted: false,
					canBeChecked: false,
					showAvatar: false,
					showUsername: false,
				};

				switch (modelMessage.params?.componentId)
				{
					case MessageParams.ComponentId.ChatCreationMessage:
						return new CreateChatBanner(modelMessage, optionsBanner);
					case MessageParams.ComponentId.GeneralChatCreationMessage:
						return new CreateGeneralChatBanner(modelMessage, optionsBanner);
					case MessageParams.ComponentId.ChannelCreationMessage:
					case MessageParams.ComponentId.OpenChannelCreationMessage:
						return new CreateChannelBanner(modelMessage, { ...optionsBanner, showCommentInfo: false });
					case MessageParams.ComponentId.GeneralChannelCreationMessage:
						return new CreateGeneralChannelBanner(modelMessage, { ...optionsBanner, showCommentInfo: false });
					case MessageParams.ComponentId.ConferenceCreationMessage:
						return new CreateChatConferenceBanner(modelMessage, optionsBanner);
					case MessageParams.ComponentId.ChatCopilotAddedUsersMessage:
						return new InviteUsersCopilotBanner(modelMessage, optionsBanner);
					case MessageParams.ComponentId.PlanLimitsMessage:
						return new PlanLimitsBanner(modelMessage, optionsBanner);
					case MessageParams.ComponentId.SignMessage:
						return new SignMessage(modelMessage, optionsBanner);
					default: return new TextMessage(modelMessage, optionsBanner);
				}
			}
			catch (error)
			{
				Logger.error('CreateBannerFactory.create: error', error);

				return new TextMessage(modelMessage, options);
			}
		}

		static checkSuitableForDisplay(modelMessage)
		{
			const creationParams = [
				MessageParams.ComponentId.ChatCreationMessage,
				MessageParams.ComponentId.GeneralChatCreationMessage,
				MessageParams.ComponentId.ChannelCreationMessage,
				MessageParams.ComponentId.OpenChannelCreationMessage,
				MessageParams.ComponentId.GeneralChannelCreationMessage,
				MessageParams.ComponentId.ConferenceCreationMessage,
				MessageParams.ComponentId.ChatCopilotAddedUsersMessage,
				MessageParams.ComponentId.PlanLimitsMessage,
				MessageParams.ComponentId.SignMessage,
			];

			return creationParams.includes(modelMessage.params?.componentId);
		}
	}

	module.exports = {
		CreateBannerFactory,
	};
});
