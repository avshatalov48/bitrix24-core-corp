/**
 * @module feature
 */
jn.define('feature', (require, exports, module) => {
	const { feature } = require('native/feature');
	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class Feature
	 */
	class Feature
	{
		static async showDefaultUnsupportedWidget(props = {}, parentWidget = PageManager)
		{
			const { AppUpdateNotifier } = await requireLazy('app-update-notifier');

			AppUpdateNotifier.open(props, parentWidget);
		}

		static isToastSupported()
		{
			return Boolean(require('native/notify'));
		}

		static isSafeAreaSupportedOnAndroid()
		{
			return isAndroid && minApiVersion(59, 'isSafeAreaSupportedOnAndroid');
		}

		static isSelectorWidgetOnViewHiddenEventBugFixed()
		{
			if (isAndroid)
			{
				return minApiVersion(59, 'isSelectorWidgetOnViewHiddenEventBugFixed');
			}

			return true;
		}

		static get isSupportedMediaGalleryCollection()
		{
			return minApiVersion(
				60,
				'isSupportedMediaGalleryCollection',
			) && feature?.isFeatureEnabled('viewer_gallery');
		}

		static isMultipleFilesDownloadSupported()
		{
			return minApiVersion(60, 'isMultipleFilesDownloadSupported');
		}

		static isNativeStoreSupported()
		{
			// api 59
			return Boolean(require('native/store'));
		}

		static canUseAnimatedCounter()
		{
			return !isAndroid || minApiVersion(60, 'canUseAnimatedCounter');
		}

		static canUseWidgetEventOpened()
		{
			return feature?.isFeatureEnabled('widget_event_opened');
		}

		static isNativeSettingApiSupported()
		{
			return Boolean(feature?.isFeatureEnabled('module-config-app-settings'));
		}

		static isNativeSettingsCacheApiSupported()
		{
			return Boolean(feature?.isFeatureEnabled('cache_module_api'));
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
