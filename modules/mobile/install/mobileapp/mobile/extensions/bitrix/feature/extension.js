/**
 * @module feature
 */
jn.define('feature', (require, exports, module) => {
	const { AppUpdateNotifier } = require('app-update-notifier');
	const { isESClass } = require('utils/type');
	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class Feature
	 */
	class Feature
	{
		static isReceivePaymentSupported()
		{
			return minApiVersion(49, 'isReceivePaymentSupported');
		}

		static isBackgroundGradientSupported()
		{
			return minApiVersion(50, 'isBackgroundGradientSupported');
		}

		static isGradientWithTextSupported()
		{
			return Application.getPlatform() === 'android' || minApiVersion(52, 'isGradientWithTextSupported');
		}

		static isGeoPositionSupported()
		{
			return minApiVersion(51, 'isGeoPositionSupported');
		}

		/**
		 * Some devices will automatically show notification, when you copy something to clipboard,
		 * so you don't have to show it manually.
		 * @return {boolean}
		 */
		static hasCopyToClipboardAutoNotification()
		{
			const deviceVersion = parseInt(device.version, 10);

			return isAndroid && deviceVersion > 12;
		}

		static isPreventBottomSheetDismissSupported()
		{
			return minApiVersion(52, 'isPreventBottomSheetDismissSupported');
		}

		static showDefaultUnsupportedWidget(props = {}, parentWidget = PageManager)
		{
			AppUpdateNotifier.open(props, parentWidget);
		}

		static canChangeAudioDevice()
		{
			return minApiVersion(52, 'canChangeAudioDevice');
		}

		static isToastSupported()
		{
			return minApiVersion(52, 'isToastSupported') && Boolean(require('native/notify'));
		}

		static isToastPositionSupported()
		{
			return minApiVersion(53, 'isToastPositionSupported');
		}

		static isListViewUpdateRowByKeySupported()
		{
			return minApiVersion(53, 'isListViewUpdateRowByKeySupported');
		}

		static isAirStyleSupported()
		{
			if (minApiVersion(54, 'isAirStyleSupported'))
			{
				const { AppTheme } = require('native/apptheme');

				return AppTheme.getId().startsWith('new');
			}

			return false;
		}

		static isMemoryStorageSupported()
		{
			if (minApiVersion(54, 'isMemoryStorageSupported'))
			{
				const { MemoryStorage } = require('native/memorystore') || {};

				return typeof MemoryStorage === 'function' || isESClass(MemoryStorage);
			}

			return false;
		}

		static isOpenImageNonContextSupported()
		{
			if (isAndroid)
			{
				return true;
			}

			return minApiVersion(55, 'isOpenImageNonContextSupported');
		}

		static isFallbackUrlSupported()
		{
			return minApiVersion(55, 'isFallbackUrlSupported');
		}

		static isDidAdoptHeightByKeyboardEventSupported()
		{
			return minApiVersion(55, 'isDidAdoptHeightByKeyboardEventSupported');
		}

		static isSmartphoneContactsAPISupported()
		{
			return minApiVersion(55, 'isSmartphoneContactsAPISupported');
		}

		static isNativeAvatarSupported()
		{
			return typeof Avatar === 'function' && minApiVersion(56, 'isNativeAvatarSupported');
		}

		static isListViewMoveRowToSectionEndSupported()
		{
			return minApiVersion(56, 'isListViewMoveRowToSectionEndSupported');
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
