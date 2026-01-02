/**
 * @module settings-v2/const
 */
jn.define('settings-v2/const', (require, exports, module) => {
	const { withCurrentDomain } = require('utils/url');

	/**
	 * @type SettingsPageId
	 */
	const SettingsPageId = {
		ROOT: 'root',
		MESSENGER: 'messenger',
		THEME: 'theme',
		VIDEO_QUALITY: 'videoQuality',
		SYNC: 'sync',
		MEMORY: 'memory',
		LOC: 'loc',
		LICENSE: 'license',
		FEEDBACK: 'feedback',
		DEBUG: 'debug',
		DEVELOPER: 'developer',
		NOTIFICATIONS_ROOT: 'notificationsRoot',
		NOTIFICATIONS_MODULE: 'notificationsModule',
		NOTIFICATIONS_PUSH: 'notificationsPush',
		NOTIFICATIONS_COUNTER: 'notificationsCounter',
	};

	/**
	 * @type SettingItemType
	 */
	const SettingItemType = {
		SECTION: 'section',
		LINK: 'link',
		CACHE_INFO: 'info',
		TOGGLE: 'toggle',
		BUTTON: 'button',
		THEME: 'theme',
		DESCRIPTION: 'description',
		VIDEO_QUALITY: 'video-quality',
		VIDEO_BANNER: 'video-banner',
		LOC_SELECTOR: 'loc-selector',
		CACHE_INTERVAL: 'cache-interval',
		CACHE_BANNER: 'cache-banner',
		BANNER: 'banner',
		IMAGE: 'image',
		STYLE: 'style',
	};

	const ASSET_PATH = withCurrentDomain('/bitrix/mobileapp/mobile/extensions/bitrix/settings-v2/ui/assets/');

	const ThemeType = {
		LIGHT: 'light',
		DARK: 'dark',
		SYSTEM: 'system',
	};

	const StyleType = {
		ZEFIR: 'zefir',
		DEFAULT: 'default',
	};

	const VideoQualityType = {
		HIGH: 'HQ',
		MEDIUM: 'MQ',
		LOW: 'LQ',
	};

	const EventType = {
		changeVideoQuality: 'settings-v2:changeVideoQuality',
		changeChatSettings: 'ImMobile.Messenger.Settings.Chat:change',
		changeCacheSize: 'settings-v2:changeCacheSize',
	};

	const PushConfigKeys = {
		TYPES: 'mobile.push.types',
		CONFIG: 'mobile.push.config',
	};

	const NotificationCounterKey = {
		TYPES: 'mobile.counter.types',
		CONFIG: 'mobile.counter.config',
	};

	const NativeSettingsId = {
		CACHE_INTERVAL: 'cache_interval',
		CACHE_FILES: 'cache_files',
		CACHE_IMAGES: 'cache_images',
		CACHE_DATABASE: 'cache_database',
		APP_STYLE: 'app_style',
		APP_THEME: 'app_theme',
	};

	const BannerImageName = {
		THEME: 'theme',
		SYNC: 'sync',
		BIZPROC: 'bizproc',
		MAIL: 'mail',
		SOCIALNETWORK: 'socialnetwork',
		VOXIMPLANT: 'voximplant',
	};

	module.exports = {
		SettingsPageId,
		SettingItemType,
		ThemeType,
		StyleType,
		VideoQualityType,
		ASSET_PATH,
		EventType,
		PushConfigKeys,
		NotificationCounterKey,
		NativeSettingsId,
		BannerImageName,
	};
});
