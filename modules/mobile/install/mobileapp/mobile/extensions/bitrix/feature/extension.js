/**
 * @module feature
 */
jn.define('feature', (require, exports, module) => {
	const { AppUpdateNotifier } = require('app-update-notifier');
	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class Feature
	 */
	class Feature
	{
		static isShareDialogSupportsFiles()
		{
			return minApiVersion(47, 'isShareDialogSupportsFiles');
		}

		static isGridViewSupported()
		{
			return minApiVersion(47, 'isGridViewSupported');
		}

		static isImageSupportsSuccessCallback()
		{
			return minApiVersion(47, 'isImageSupportsSuccessCallback');
		}

		static isKeyboardEventsSupported()
		{
			return minApiVersion(48, 'isKeyboardEventsSupported');
		}

		static isOAuthSupported()
		{
			return minApiVersion(48, 'isOAuthSupported');
		}

		static isReceivePaymentSupported()
		{
			return minApiVersion(49, 'isReceivePaymentSupported');
		}

		/**
		 * Some devices will automatically show notification, when you copy something to clipboard,
		 * so you don't have to show it manually.
		 * @return {boolean}
		 */
		static hasCopyToClipboardAutoNotification()
		{
			const deviceVersion = parseInt(device.version, 10);
			return isAndroid && deviceVersion > 11;
		}

		static isPreventBottomSheetDismissSupported()
		{
			return Application.getApiVersion() >= 50;
		}

		static showDefaultUnsupportedWidget(props = {}, parentWidget = PageManager)
		{
			AppUpdateNotifier.open(props, parentWidget);
		}
	}

	/**
	 * @private
	 * @param {number} minVersion
	 * @param {string} featureName
	 * @return {boolean}
	 */
	const minApiVersion = (minVersion, featureName) => {
		const currentVersion = Application.getApiVersion();
		if ((currentVersion - minVersion) > 2)
		{
			console.warn(`Feature ${featureName} requires API ${minVersion} and probably can be omitted (current is ${currentVersion}).`);
		}
		return currentVersion >= minVersion;
	};

	module.exports = { Feature };
});
