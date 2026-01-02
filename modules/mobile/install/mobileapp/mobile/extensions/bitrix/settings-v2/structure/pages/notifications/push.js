/**
 * @module settings-v2/structure/pages/notifications/push
 */
jn.define('settings-v2/structure/pages/notifications/push', (require, exports, module) => {
	const {
		createToggle,
		createLink,
		createSection,
		createImage,
	} = require('settings-v2/structure/src/item-create-helper');
	const { SettingsPageId, PushConfigKeys } = require('settings-v2/const');
	const { NotificationLoadService } = require('settings-v2/services/notification-load');
	const { PushStatusSettingController } = require('settings-v2/controller/push-status');
	const { SmartFilterStatusSettingController } = require('settings-v2/controller/smartfilter-status');
	const { Loc } = require('loc');

	const requestSettingsData = async () => {
		const cachedPushTypes = Application.storage.get(PushConfigKeys.TYPES);
		const cachedPushConfig = Application.storage.get(PushConfigKeys.CONFIG);

		if (cachedPushTypes && cachedPushConfig)
		{
			return cachedPushTypes;
		}

		const data = await NotificationLoadService.fetchAll();

		return data.pushTypes;
	};

	const prepareItems = (pushTypes) => {
		const items = [];

		pushTypes.forEach((pushType) => {
			items.push(
				createLink({
					id: `notifications-${pushType.module_id}`,
					title: pushType.name,
					nextPage: SettingsPageId.NOTIFICATIONS_MODULE,
					nextPageParams: {
						moduleId: pushType.module_id,
						title: pushType.name,
					},
				}),
			);
		});

		return items;
	};

	/** @type SettingPage */
	const NotificationsPushPage = {
		id: SettingsPageId.NOTIFICATIONS_ROOT,
		title: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_ROOT_NOTIFICATION_PUSH'),
		requestSettingsData,
		items: [
			createImage({
				id: 'notifications-push-image',
				name: 'notifications-push',
				externalStyle: {
					height: 246,
				},
			}),
			createSection({
				id: 'notifications-section',
				items: [
					createToggle({
						id: 'enable-notifications',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_ROOT_NOTIFICATION_PUSH'),
						subtitle: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_ROOT_NOTIFICATION_PUSH_SUBTITLE'),
						controller: new PushStatusSettingController({ settingId: 'enable-notifications' }),
					}),
				],
			}),
			createSection({
				id: 'intelligent-filtering-section',
				items: [
					createToggle({
						id: 'enable-intelligent-filtering',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_ROOT_INTELLIGENT_FILTERING'),
						subtitle: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_ROOT_INTELLIGENT_DESCRIPTION'),
						controller: new SmartFilterStatusSettingController({ settingId: 'enable-intelligent-filtering' }),
					}),
				],
			}),
			createSection({
				id: 'advanced-notifications-section',
				title: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_ROOT_ADVANCED_NOTIFICATIONS_SECTION_TITLE'),
				items: [],
				prepareItems,
			}),
		],
	};

	module.exports = {
		NotificationsPushPage,
	};
});
