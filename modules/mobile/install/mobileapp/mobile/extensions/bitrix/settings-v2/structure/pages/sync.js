/**
 * @module settings-v2/structure/pages/sync
 */
jn.define('settings-v2/structure/pages/sync', (require, exports, module) => {
	const {
		createToggle,
		createSection,
		createBanner,
		createButton,
		createDescription,
	} = require('settings-v2/structure/src/item-create-helper');
	const { SettingsPageId, BannerImageName } = require('settings-v2/const');
	const { Loc } = require('loc');
	const { NativeSettingController } = require('settings-v2/controller/native');

	/**
	 * @param settingId
	 * @param fallbackValue
	 * @returns {NativeSettingController}
	 */
	const createSyncController = (settingId, fallbackValue) => {
		return new NativeSettingController({
			settingId,
			fallbackValue,
		});
	};

	const isAndroid = Application.getPlatform() === 'android';

	const Resources = {
		CALENDAR: 'caldav',
		CONTACTS: 'carddav',
	};

	const iosSync = (resource) => {
		BX.ajax({
			url: `/bitrix/tools/dav_profile.php?action=token&params[resources]=${resource}`,
			dataType: 'json',
			method: 'GET',
		}).then((response) => {
			if (response.token)
			{
				const urlPath = `/bitrix/tools/dav_profile.php?action=payload&params[resources]=${resource}&params[access_token]=`;
				Application.openUrl(currentDomain + urlPath + response.token);
			}
		}).catch(console.error);
	};

	/** @type SettingPage */
	const SyncPage = {
		id: SettingsPageId.SYNC,
		title: Loc.getMessage('SETTINGS_V2_STRUCTURE_SYNC_TITLE'),
		items: [
			createSection({
				id: 'sync-banner-section',
				divider: false,
				items: [
					createBanner({
						id: 'sync-banner',
						bannerImageName: BannerImageName.SYNC,
						text: Loc.getMessage('SETTINGS_V2_STRUCTURE_SYNC_BANNER_TEXT'),
					}),
				],
			}),
			createSection({
				id: 'android-auto-sync-section',
				title: Loc.getMessage('SETTINGS_V2_STRUCTURE_SYNC_AUTO_SYNC'),
				prefilter: () => isAndroid,
				items: [
					createToggle({
						id: 'android-sync-calendar',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_SYNC_CALENDAR'),
						subtitle: Loc.getMessage('SETTINGS_V2_STRUCTURE_SYNC_CALENDAR_SUBTITLE'),
						controller: createSyncController('sync_calendar', false),
					}),
					createToggle({
						id: 'android-sync-contacts',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_SYNC_CONTACTS'),
						subtitle: Loc.getMessage('SETTINGS_V2_STRUCTURE_SYNC_CONTACTS_SUBTITLE'),
						divider: false,
						controller: createSyncController('sync_contacts', false),
					}),
				],
			}),
			createSection({
				id: 'ios-auto-sync-section',
				title: Loc.getMessage('SETTINGS_V2_STRUCTURE_SYNC_AUTO_SYNC'),
				prefilter: () => !isAndroid,
				items: [
					createButton({
						id: 'ios-sync-calendar',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_SYNC_CALENDAR'),
						subtitle: Loc.getMessage('SETTINGS_V2_STRUCTURE_SYNC_CALENDAR_SUBTITLE'),
						onClick: () => {
							iosSync(Resources.CALENDAR);
						},
					}),
					createButton({
						id: 'ios-sync-contacts',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_SYNC_CONTACTS'),
						subtitle: Loc.getMessage('SETTINGS_V2_STRUCTURE_SYNC_CONTACTS_SUBTITLE'),
						onClick: () => {
							iosSync(Resources.CONTACTS);
						},
					}),
				],
			}),
		],
	};

	module.exports = {
		SyncPage,
	};
});
