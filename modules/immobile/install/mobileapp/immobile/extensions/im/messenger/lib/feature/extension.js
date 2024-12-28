/**
 * @module im/messenger/lib/feature
 */
jn.define('im/messenger/lib/feature', (require, exports, module) => {
	const { Feature: MobileFeature } = require('feature');

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
		static getChatSettings()
		{
			return Application.storage.getObject('settings.chat', {
				chatBetaEnable: false,
				localStorageEnable: true,
				autoplayVideo: true,
			});
		}

		static get isChatBetaEnabled()
		{
			return Feature.getChatSettings().chatBetaEnable;
		}

		static get isChatV2Enabled()
		{
			return MessengerParams.isChatM1Enabled() && Feature.isChatV2Supported;
		}

		static get isChatV2Supported()
		{
			return Application.getApiVersion() >= 52;
		}

		static get isLocalStorageEnabled()
		{
			return (
				isLocalStorageEnabledDuringApplicationStartup
				&& dynamicProperties.localStorageEnable
			);
		}

		static get isLocalStorageSupported()
		{
			const isSupportedApp = Application.getApiVersion() >= 52;
			const isSupportedAndroid = (
				Application.getPlatform() === 'android'
				&& parseInt(Application.getBuildVersion(), 10) >= 2443
			);
			const isSupportedIos = device.platform === 'iOS'
				&& parseInt(device.version, 10) >= 15
			;

			return isSupportedApp && (isSupportedAndroid || isSupportedIos);
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

		static get isGoToMessageContextSupported()
		{
			return Application.getApiVersion() >= 54;
		}

		static get isMessagePinSupported()
		{
			return Feature.isGoToMessageContextSupported;
		}

		static get isMessageForwardSupported()
		{
			return Feature.isGoToMessageContextSupported;
		}

		static get isSupportBMPImageType()
		{
			return Application.getApiVersion() >= 53;
		}

		static get isCheckInMessageSupported()
		{
			return Application.getApiVersion() >= 54;
		}

		static get isCallMessageSupported()
		{
			return Application.getApiVersion() >= 56;
		}

		static get isCreateBannerMessageSupported()
		{
			return Application.getApiVersion() >= 54;
		}

		static get isMessageAttachSupported()
		{
			return Application.getApiVersion() >= 55;
		}

		static get isMessageKeyboardSupported()
		{
			return Application.getApiVersion() >= 55;
		}

		static get isAvatarBorderStylesSupported()
		{
			return Application.getApiVersion() >= 55;
		}

		static get isChatDialogWidgetSupportsSendPutCallBbCodes()
		{
			return Application.getApiVersion() >= 55;
		}

		static get isNavigationContextSupportsGetStack()
		{
			return Application.getApiVersion() >= 56;
		}

		static get isChatDialogWidgetSupportsBots()
		{
			return (
				this.isMessageAttachSupported
				&& this.isMessageKeyboardSupported
				&& this.isChatDialogWidgetSupportsSendPutCallBbCodes
			);
		}

		static get isMessageMenuAirIconSupported()
		{
			return Application.getApiVersion() >= 56;
		}

		static get isGalleryMessageSupported()
		{
			return Application.getApiVersion() >= 56;
		}

		static get isChatDialogWidgetFileDownloadTapEventSupported()
		{
			return Application.getApiVersion() >= 56;
		}

		static get isChatComposerSupported()
		{
			return Application.getApiVersion() >= 56;
		}

		static get isSidebarFilesEnabled()
		{
			return MessengerParams.getImFeatures().sidebarFiles;
		}

		static get isSidebarLinksEnabled()
		{
			return MessengerParams.getImFeatures().sidebarLinks;
		}

		static get isCollabSupported()
		{
			return Application.getApiVersion() >= 56;
		}

		static get isCollabAvailable()
		{
			return MessengerParams.getImFeatures().collabAvailable;
		}

		static get isCollabCreationAvailable()
		{
			return MessengerParams.getImFeatures().collabCreationAvailable;
		}

		static get isMultiSelectAvailable()
		{
			return Application.getApiVersion() >= 56;
		}

		static get isDevelopmentEnvironment()
		{
			return (
				Application.getApiVersion() >= 44
				&& Application.isBeta()
				&& MessengerParams.get('IS_DEVELOPMENT_ENVIRONMENT', false)
			);
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

		static get isImagePickerCustomFieldsSupported()
		{
			return Application.getApiVersion() >= 56;
		}

		static get isIconBoxWithLidAvailable()
		{
			return Application.getApiVersion() >= 56;
		}
	}

	const isLocalStorageEnabledDuringApplicationStartup = (
		MessengerParams.isChatM1Enabled()
		&& MessengerParams.isChatLocalStorageAvailable()
		&& Feature.isLocalStorageSupported
		&& Feature.getChatSettings().localStorageEnable
	);

	module.exports = { Feature };
});
