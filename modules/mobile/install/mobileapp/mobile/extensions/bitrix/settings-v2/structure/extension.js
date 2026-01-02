/**
 * @module settings-v2/structure
 */
jn.define('settings-v2/structure', (require, exports, module) => {
	const { SettingsPageId } = require('settings-v2/const');
	const { RootPage } = require('settings-v2/structure/pages/root');
	const { ThemePage } = require('settings-v2/structure/pages/theme');
	const { SyncPage } = require('settings-v2/structure/pages/sync');
	const { DebugPage } = require('settings-v2/structure/pages/debug');
	const { MessengerPage } = require('settings-v2/structure/pages/messenger');
	const { VideoQualityPage } = require('settings-v2/structure/pages/video-quality');
	const { MemoryPage } = require('settings-v2/structure/pages/memory');
	const { NotificationsRootPage } = require('settings-v2/structure/pages/notifications/root');
	const { NotificationsModulePage } = require('settings-v2/structure/pages/notifications/module');
	const { NotificationsCounterPage } = require('settings-v2/structure/pages/notifications/counter');
	const { NotificationsPushPage } = require('settings-v2/structure/pages/notifications/push');
	const { DeveloperPage } = require('settings-v2/structure/pages/developer');

	/**
	 * @type {Object.<SettingsPageId, SettingPage>}
	 */
	const Pages = {
		[SettingsPageId.ROOT]: RootPage,
		[SettingsPageId.MESSENGER]: MessengerPage,
		[SettingsPageId.THEME]: ThemePage,
		[SettingsPageId.SYNC]: SyncPage,
		[SettingsPageId.DEBUG]: DebugPage,
		[SettingsPageId.DEVELOPER]: DeveloperPage,
		[SettingsPageId.VIDEO_QUALITY]: VideoQualityPage,
		[SettingsPageId.MEMORY]: MemoryPage,
		[SettingsPageId.NOTIFICATIONS_ROOT]: NotificationsRootPage,
		[SettingsPageId.NOTIFICATIONS_MODULE]: NotificationsModulePage,
		[SettingsPageId.NOTIFICATIONS_COUNTER]: NotificationsCounterPage,
		[SettingsPageId.NOTIFICATIONS_PUSH]: NotificationsPushPage,
	};

	module.exports = {
		Pages,
	};
});
