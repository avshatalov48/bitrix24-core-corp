/**
 * @module im/messenger/lib/feature
 */
jn.define('im/messenger/lib/feature', (require, exports, module) => {
	const { Feature: MobileFeature } = require('feature');
	const { feature: NativeFeature } = require('native/feature') ?? {};

	const { MessengerParams } = require('im/messenger/lib/params');

	const dynamicProperties = {
		localStorageEnable: true,
		localStorageReadOnlyModeEnable: false,
	};

	/**
	 * @class Feature
	 */
	class Feature
	{
		/**
		 * @protected
		 */
		static isLocalStorageEnabledDuringApplicationStartup = null;

		static getIsLocalStorageEnabledDuringApplicationStartup()
		{
			if (this.isLocalStorageEnabledDuringApplicationStartup === null)
			{
				this.isLocalStorageEnabledDuringApplicationStartup = (
					MessengerParams.isChatLocalStorageAvailable()
					&& Feature.isLocalStorageSupported
					&& Feature.getChatSettings().localStorageEnable
				);
			}

			return this.isLocalStorageEnabledDuringApplicationStartup;
		}

		/**
		 * @param {Partial<ImFeatures>} features
		 */
		static updateExistingImFeatures(features)
		{
			return MessengerParams.updateExistingImFeatures(features);
		}

		static getChatSettings()
		{
			return Application.storage.getObject('settings.chat', {
				chatBetaEnable: false,
				chatDevModeEnable: false,
				localStorageEnable: true,
				autoplayVideo: true,
				autoSaveFiles: {
					enabled: true,
					maxSize: 10_485_760,
				},
			});
		}

		static get isChatBetaEnabled()
		{
			return Feature.getChatSettings().chatBetaEnable;
		}

		static get isLocalStorageEnabled()
		{
			return (
				Feature.getIsLocalStorageEnabledDuringApplicationStartup()
				&& dynamicProperties.localStorageEnable
			);
		}

		static get isLocalStorageSupported()
		{
			const isSupportedAndroid = (
				Application.getPlatform() === 'android'
				&& parseInt(Application.getBuildVersion(), 10) >= 2443
			);
			const isSupportedIos = device.platform === 'iOS'
				&& parseInt(device.version, 10) >= 15
			;

			return isSupportedAndroid || isSupportedIos;
		}

		static get isLocalStorageReadOnlyModeEnable()
		{
			return dynamicProperties.localStorageReadOnlyModeEnable;
		}

		static get isCopilotEnabled()
		{
			return MessengerParams.getImFeatures().copilotActive;
		}

		static get isAutoplayVideoEnabled()
		{
			return Feature.getChatSettings().autoplayVideo;
		}

		static get isAutoSaveFilesEnabled()
		{
			return Application.getApiVersion() >= 59 && Feature.getChatSettings().autoSaveFiles;
		}

		static disableLocalStorage()
		{
			dynamicProperties.localStorageEnable = false;
		}

		static enableLocalStorage()
		{
			dynamicProperties.localStorageEnable = true;
		}

		static disableLocalStorageReadOnlyMode()
		{
			dynamicProperties.localStorageReadOnlyModeEnable = false;
		}

		static enableLocalStorageReadOnlyMode()
		{
			dynamicProperties.localStorageReadOnlyModeEnable = true;
		}

		static get isChatDialogListSupportsSubtitleBbCodes()
		{
			return Application.getApiVersion() >= 59;
		}

		static get isChatRecentItemTypeInSearchOverlayAvailable()
		{
			return Application.getApiVersion() >= 60;
		}

		static get isSidebarFilesEnabled()
		{
			return MessengerParams.getImFeatures().sidebarFiles;
		}

		static get isSidebarLinksEnabled()
		{
			return MessengerParams.getImFeatures().sidebarLinks;
		}

		static get isSidebarV2Enabled()
		{
			return Application.getApiVersion() >= 60;
		}

		static get isCollabAvailable()
		{
			return MessengerParams.getImFeatures().collabAvailable;
		}

		static get isCollabCreationAvailable()
		{
			return MessengerParams.getImFeatures().collabCreationAvailable;
		}

		static get isInstantPushEnabled()
		{
			return Application.getApiVersion() >= 59;
		}

		static get isDevelopmentEnvironment()
		{
			return (Application.isBeta() && MessengerParams.get('IS_DEVELOPMENT_ENVIRONMENT', false));
		}

		static get isDevModeEnabled()
		{
			return Feature.getChatSettings().chatDevModeEnable;
		}

		static showUnsupportedWidget(options = {}, parentWidget = PageManager)
		{
			const defaultOptions = {
				isOldBuild: false,
			};

			MobileFeature.showDefaultUnsupportedWidget({
				...defaultOptions,
				...options,
			}, parentWidget);
		}

		static get isLottieInChatTitleAvailable()
		{
			return Application.getApiVersion() >= 59;
		}

		static get isNotesBannerAvailable()
		{
			return Application.getApiVersion() >= 59;
		}

		static get isNotesIconAvailable()
		{
			return Application.getApiVersion() >= 59;
		}

		static get isDialogBackgroundAvailable()
		{
			return Application.getApiVersion() >= 59;
		}

		static get isIntranetInvitationAvailable()
		{
			return MessengerParams.getImFeatures().intranetInviteAvailable && env.installedModules?.intranetmobile;
		}

		static get isSupportedMediaCollection()
		{
			return Application.getApiVersion() >= 59;
		}

		static get isMessagesAutoDeleteNativeAvailable()
		{
			return Feature.isActionButtonAvailable
				&& Feature.isTitleIconsInDialogHeaderAvailable
				&& Feature.isImageInRecentAvatarStyleAvailable;
		}

		static get isActionButtonAvailable()
		{
			return Application.getApiVersion() >= 60;
		}

		static get isIOSChangeTabInBackgroundAvailable()
		{
			return Application.getApiVersion() >= 60;
		}

		static get isTitleIconsInDialogHeaderAvailable()
		{
			return Application.getApiVersion() >= 60;
		}

		static get isImageInRecentAvatarStyleAvailable()
		{
			return Application.getApiVersion() >= 60;
		}

		static get isPinInRecentStyleAvailable()
		{
			return Application.getApiVersion() >= 60;
		}

		static get isBackToReplyAvailable()
		{
			return Application.getApiVersion() >= 60;
		}

		static get isMessagesAutoDeleteEnabled()
		{
			return MessengerParams.getImFeatures().messagesAutoDeleteEnabled;
		}

		static get isSpotlightIdInTabViewAvailable()
		{
			return Application.getApiVersion() >= 60;
		}

		static get isVoteMessageAvailable()
		{
			return (
				NativeFeature?.isFeatureEnabled('chat-vote')
				&& env.installedModules?.vote
				&& MessengerParams.getImFeatures().voteCreationAvailable
			);
		}

		static get isPinPanelNewAPIAvailable()
		{
			return Application.getApiVersion() >= 60 && NativeFeature.isFeatureEnabled('chat-multi-pin');
		}

		static get isChatAvatarAccentTypePurpleAvailable()
		{
			return Application.getApiVersion() >= 60;
		}

		static get isFloatingButtonsBarAvailable()
		{
			return NativeFeature?.isFeatureEnabled('chat-like-mention-comments') ?? false;
		}

		static get isAiAssistantMessageSupported()
		{
			return NativeFeature?.isFeatureEnabled('chat-ai-assistant') ?? false;
		}

		static get isErrorMessageAvailable()
		{
			return NativeFeature?.isFeatureEnabled('chat-error-message');
		}

		static get isRecentLikeAvailable()
		{
			return NativeFeature?.isFeatureEnabled('chat-recent-like') ?? false;
		}

		static get isAsyncRecentOperationsAvailable()
		{
			return NativeFeature?.isFeatureEnabled('listwidget-api-v1');
		}

		static get isDialogHeaderButtonIconSupported()
		{
			return Application.getApiVersion() >= 60;
		}

		static isAudioPanelSupported()
		{
			return NativeFeature?.isFeatureEnabled('audioplayer_api_v1')
				&& MessengerParams.canUseAudioPanel();
		}

		static get isNotifyPanelAvailable()
		{
			return NativeFeature?.isFeatureEnabled('chat_notify_panel');
		}

		static get isMessengerV2Enabled()
		{
			return MessengerParams.getMessengerV2Enabled();
		}

		static get isTabsWidgetApiV2Supported()
		{
			return NativeFeature?.isFeatureEnabled('tabswidget_api_v2');
		}

		static get isAiFileTranscriptionAvailable()
		{
			return MessengerParams.getImFeatures().aiFileTranscriptionAvailable;
		}

		static get isAudioRecordM4ASupported()
		{
			return NativeFeature?.isFeatureEnabled('audio_record_m4a_support');
		}

		static get isAudioRecordM4AEnabled()
		{
			return this.isAudioRecordM4ASupported && this.isAiFileTranscriptionAvailable;
		}
	}

	module.exports = { Feature, MobileFeature };
});
