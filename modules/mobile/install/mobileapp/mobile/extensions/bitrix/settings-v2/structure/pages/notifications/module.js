/**
 * @module settings-v2/structure/pages/notifications/module
 */
jn.define('settings-v2/structure/pages/notifications/module', (require, exports, module) => {
	const {
		createSection,
		createToggle,
		createBanner,
	} = require('settings-v2/structure/src/item-create-helper');
	const { PushConfigSettingController } = require('settings-v2/controller/push-config');
	const {
		SettingsPageId,
		PushConfigKeys,
		BannerImageName,
	} = require('settings-v2/const');
	const { NotificationLoadService } = require('settings-v2/services/notification-load');
	const { Loc } = require('loc');
	const { isEmpty } = require('utils/object');

	const requestSettingsData = async ({ moduleId }) => {
		const cachedPushTypes = Application.storage.get(PushConfigKeys.TYPES);
		const cachedPushConfig = Application.storage.get(PushConfigKeys.CONFIG);

		if (cachedPushTypes && cachedPushConfig)
		{
			return cachedPushTypes.find((item) => item.module_id === moduleId);
		}

		const data = await NotificationLoadService.fetchPushSettings();

		return data.pushTypes.find((item) => item.module_id === moduleId);
	};

	const prepareItems = (moduleData, isBannerSection = false) => {
		if (isEmpty(moduleData))
		{
			return [];
		}

		const items = [];

		if (isBannerSection)
		{
			const banner = prepareBanner(moduleData.module_id);

			if (banner)
			{
				items.push(banner);
			}

			return items;
		}

		moduleData.types.forEach((typeItem) => {
			items.push(
				createToggle({
					id: `notifications-${moduleData.module_id}-${typeItem.type}`,
					title: typeItem.name,
					controller: new PushConfigSettingController(
						{
							settingId: `notifications-${moduleData.module_id}-${typeItem.type}`,
							moduleId: moduleData.module_id,
							pushType: typeItem.type,
						},
					),
					disabled: typeItem.disabled,
				}),
			);
		});

		return items;
	};

	const Modules = {
		BIZPROC: 'bizproc',
		MAIL: 'mail',
		SOCIALNETWORK: 'socialnetwork',
		VOXIMPLANT: 'voximplant',
	};

	const ModuleToBannerImage = {
		[Modules.BIZPROC]: BannerImageName.BIZPROC,
		[Modules.MAIL]: BannerImageName.MAIL,
		[Modules.SOCIALNETWORK]: BannerImageName.SOCIALNETWORK,
		[Modules.VOXIMPLANT]: BannerImageName.VOXIMPLANT,
	};

	const ModuleBannerPhrases = {
		[Modules.BIZPROC]: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_BIZPROC_BANNER_TEXT'),
		[Modules.MAIL]: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_MAIL_BANNER_TEXT'),
		[Modules.SOCIALNETWORK]: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_SOCIALNETWORK_BANNER_TEXT'),
		[Modules.VOXIMPLANT]: Loc.getMessage('SETTINGS_V2_STRUCTURE_NOTIFICATIONS_VOXIMPLANT_BANNER_TEXT'),
	};

	const prepareBanner = (moduleId) => {
		if (!moduleId || !Object.values(Modules).includes(moduleId))
		{
			return null;
		}

		return createBanner({
			id: `module-banner-${moduleId}`,
			bannerImageName: ModuleToBannerImage[moduleId],
			text: ModuleBannerPhrases[moduleId],
		});
	};

	/** @type SettingPage */
	const NotificationsModulePage = {
		id: SettingsPageId.NOTIFICATIONS_MODULE,
		requestSettingsData,
		items: [
			createSection(
				{
					id: 'notification-module-banner-section',
					items: [],
					prepareItems: (moduleData) => prepareItems(moduleData, true),
					divider: false,
				},
			),
			createSection(
				{
					id: 'notifications-module-section',
					items: [],
					prepareItems,
				},
			),
		],
	};

	module.exports = {
		NotificationsModulePage,
	};
});
