/**
 * @module im/messenger/lib/element/dialog/message/banner/const/configuration
 */
jn.define('im/messenger/lib/element/dialog/message/banner/const/configuration', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ButtonType, ButtonDesignType, ImageNameType, ButtonId } = require('im/messenger/lib/element/dialog/message/banner/const/type');
	const { MessageParams } = require('im/messenger/const');
	const { Theme } = require('im/lib/theme');
	const { openPlanLimitsWidget } = require('im/messenger/lib/plan-limit');
	const { ButtonSize } = require('ui-system/form/buttons');
	const { transparent } = require('utils/color');
	const { AnalyticsEvent } = require('analytics');
	const { Analytics } = require('im/messenger/const');
	const { SignMetaData } = require('im/messenger/lib/element/dialog/message/banner/banners/sign/configuration');

	/**
	 * @type {BannerMetaData}
	 */
	const metaData = {
		[MessageParams.ComponentId.ChatCreationMessage]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_GROUP_MSGVER_1'),
				imageName: ImageNameType.chat,
				backgroundColor: Theme.colors.chatOtherMessage1,
				picBackgroundColor: transparent(Theme.colors.accentMainPrimaryalt, 0.2),
				buttons: [],
			},
		},
		[MessageParams.ComponentId.GeneralChatCreationMessage]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHAT_GENERAL_CREATE_BANNER_TITLE'),
				imageName: ImageNameType.generalChat,
				backgroundColor: Theme.colors.chatOtherMessage1,
				picBackgroundColor: transparent(Theme.colors.accentMainPrimaryalt, 0.2),
				buttons: [],
			},
		},
		[MessageParams.ComponentId.ChannelCreationMessage]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHANNEL_CREATE_BANNER_TITLE'),
				imageName: ImageNameType.channel,
				backgroundColor: Theme.colors.chatOtherMessage1,
				picBackgroundColor: transparent(Theme.colors.accentMainSuccess, 0.2),
				buttons: [],
			},
		},
		[MessageParams.ComponentId.OpenChannelCreationMessage]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHANNEL_CREATE_BANNER_TITLE'),
				imageName: ImageNameType.channel,
				backgroundColor: Theme.colors.chatOtherMessage1,
				picBackgroundColor: transparent(Theme.colors.accentMainSuccess, 0.2),
				buttons: [],
			},
		},
		[MessageParams.ComponentId.GeneralChannelCreationMessage]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHANNEL_GENERAL_CREATE_BANNER_TITLE'),
				imageName: ImageNameType.channel,
				backgroundColor: Theme.colors.chatOtherMessage1,
				picBackgroundColor: transparent(Theme.colors.accentMainSuccess, 0.2),
				buttons: [],
			},
		},
		[MessageParams.ComponentId.ConferenceCreationMessage]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHAT_CONFERENCE_CREATE_BANNER_TITLE'),
				imageName: ImageNameType.videoconf,
				backgroundColor: Theme.colors.chatOtherMessage1,
				picBackgroundColor: transparent(Theme.colors.accentMainPrimaryalt, 0.2),
				buttons: [],
			},
		},
		[MessageParams.ComponentId.ChatCopilotAddedUsersMessage]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_BANNER_TITLE_ADD_USERS'),
				description: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_BANNER_DESC_ADD_USERS'),
				imageName: ImageNameType.copilotNewUser,
				backgroundColor: Theme.colors.chatOtherCopilot1,
				picBackgroundColor: transparent(Theme.colors.accentMainCopilot, 0.2),
				buttons: [],
			},
		},
		[MessageParams.ComponentId.PlanLimitsMessage]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_PLAN_LIMITS_BANNER_TITTLE'),
				imageName: ImageNameType.planLimits,
				backgroundColor: Theme.color.chatOverallBannerBg.toHex(),
				picBackgroundColor: Theme.color.techOpacity.toHex(),
				buttons: [
					{
						id: ButtonId.planLimitsUnlock,
						text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_PLAN_LIMITS_BANNER_BUTTON_UNLOCK'),
						design: ButtonDesignType.filled,
						height: ButtonSize.M.getName(),
						type: ButtonType.full,
						callback: () => {
							const analytics = getAnalytics()
								.setSection(Analytics.Section.chatWindow);

							return openPlanLimitsWidget(analytics);
						},
					},
				],
			},
		},
		[MessageParams.ComponentId.SignMessage]: SignMetaData,
	};

	/**
	 * @return {AnalyticsEvent}
	 */
	function getAnalytics()
	{
		return new AnalyticsEvent();
	}

	module.exports = {
		metaData,
	};
});
