/**
 * @module settings-v2/structure/pages/theme
 */
jn.define('settings-v2/structure/pages/theme', (require, exports, module) => {
	const { createThemeSwitch, createSection, createBanner, createStyleSwitch, createDescription } = require('settings-v2/structure/src/item-create-helper');
	const { NativeSettingController } = require('settings-v2/controller/native');
	const { SettingsPageId, BannerImageName, NativeSettingsId } = require('settings-v2/const');
	const { appConfig } = require('native/config');
	const { Loc } = require('loc');

	/**
	 * @returns {NativeSettingController}
	 */
	const createThemeController = () => {
		return new NativeSettingController({
			settingId: NativeSettingsId.APP_THEME,
			fallbackValue: 'system',
		});
	};

	const createStyleController = () => {
		return new NativeSettingController({
			settingId: NativeSettingsId.APP_STYLE,
			fallbackValue: 'default',
		});
	};

	const requestSettingsData = async () => {
		const allSettings = await appConfig.getSettings();

		const isStyleCustomizationEnabled = allSettings.some((setting) => setting.id === NativeSettingsId.APP_STYLE);

		return {
			isStyleCustomizationEnabled,
		};
	};

	/** @type SettingPage */
	const ThemePage = {
		id: SettingsPageId.THEME,
		title: Loc.getMessage('SETTINGS_V2_STRUCTURE_THEME_TITLE'),
		requestSettingsData,
		items: [
			createSection({
				id: 'theme-banner-section',
				divider: false,
				items: [
					createBanner({
						id: 'theme-banner',
						bannerImageName: BannerImageName.THEME,
						text: Loc.getMessage('SETTINGS_V2_STRUCTURE_THEME_BANNER_TEXT'),
					}),
				],
			}),
			createSection({
				id: 'app-theme-section',
				title: Loc.getMessage('SETTINGS_V2_STRUCTURE_THEME_SECTION'),
				divider: false,
				items: [
					createThemeSwitch({
						id: 'theme',
						controller: createThemeController(),
						divider: false,
					}),
					createDescription({
						id: 'loc-description',
						text: Loc.getMessage('SETTINGS_V2_STRUCTURE_RELOAD_DESCRIPTION'),
					}),
				],
			}),
			createSection({
				id: 'app-style-section',
				title: Loc.getMessage('SETTINGS_V2_STRUCTURE_THEME_STYLE_SECTION'),
				items: [
					createStyleSwitch({
						id: 'style',
						controller: createStyleController(),
					}),
				],
				prefilter: (settingsData) => settingsData.isStyleCustomizationEnabled === true,
			}),
		],
	};

	module.exports = {
		ThemePage,
	};
});
