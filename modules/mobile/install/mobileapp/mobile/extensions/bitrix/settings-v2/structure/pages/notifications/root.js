/**
 * @module settings-v2/structure/pages/notifications/root
 */
jn.define('settings-v2/structure/pages/notifications/root', (require, exports, module) => {
	const {
		createLink,
		createSection,
	} = require('settings-v2/structure/src/item-create-helper');
	const { SettingsPageId } = require('settings-v2/const');
	const { NotificationLoadService } = require('settings-v2/services/notification-load');
	const { Loc } = require('loc');

	const requestSettingsData = async () => {
		await NotificationLoadService.fetchAll();
	};

	/** @type SettingPage */
	const NotificationsRootPage = {
		id: SettingsPageId.NOTIFICATIONS_ROOT,
		title: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_ROOT_TITLE'),
		requestSettingsData,
		items: [
			createSection({
				id: 'notifications-root-section',
				items: [
					createLink({
						id: 'notifications-counter',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_ROOT_NOTIFICATION_PUSH'),
						subtitle: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_ROOT_NOTIFICATION_PUSH_DESCRIPTION'),
						nextPage: SettingsPageId.NOTIFICATIONS_PUSH,
					}),
					createLink({
						id: 'notifications-counter',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_ROOT_NOTIFICATION_COUNTER'),
						subtitle: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_ROOT_NOTIFICATION_COUNTER_DESCRIPTION'),
						nextPage: SettingsPageId.NOTIFICATIONS_COUNTER,
					}),
				],
			}),
		],
	};

	module.exports = {
		NotificationsRootPage,
	};
});
