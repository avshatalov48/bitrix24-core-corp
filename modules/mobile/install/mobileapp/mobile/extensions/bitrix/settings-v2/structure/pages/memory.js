/**
 * @module settings-v2/structure/pages/memory
 */
jn.define('settings-v2/structure/pages/memory', (require, exports, module) => {
	const { SettingsPageId } = require('settings-v2/const');
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { Color } = require('tokens');
	const {
		createCacheInfo,
		createSection,
		createCacheIntervalSelector,
		createDescription,
		createCacheBanner,
	} = require('settings-v2/structure/src/item-create-helper');
	const { CacheSettingController } = require('settings-v2/controller/cache');
	const { NativeCacheService } = require('settings-v2/services/native');
	const { EventType, NativeSettingsId } = require('settings-v2/const');
	const { SettingEmitter } = require('settings-v2/emitter');
	const { showToast } = require('toast');
	const { Alert } = require('alert');

	const isAndroid = (Application.getPlatform() === 'android');

	/** @type SettingPage */
	const MemoryPage = {
		id: SettingsPageId.MEMORY,
		title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_TITLE'),
		items: [
			createCacheBanner({
				id: 'cache-banner',
			}),
			createSection({
				id: 'info-section',
				items: [
					createDescription({
						id: 'cache-info-description',
						text: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_CACHE_INFO_DESCRIPTION'),
					}),
					createCacheInfo({
						id: 'file-cache-size',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_CACHE_INFO_FILES'),
						subtitle: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_CACHE_INFO_FILES_SUBTITLE'),
						icon: Icon.FILE,
						iconColor: Color.accentMainPrimary,
						controller: new CacheSettingController({
							settingId: NativeSettingsId.CACHE_FILES,
							fallbackValue: 0,
						}),
						onClick: async () => {
							await NativeCacheService.clearCache();
							SettingEmitter.emit(EventType.changeCacheSize);
							showToast({
								message: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_CACHE_CLEAR_FILES_TOAST_MESSAGE'),
							});
						},
					}),
					createCacheInfo({
						id: 'images-cache-size',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_CACHE_INFO_IMAGES'),
						subtitle: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_CACHE_INFO_SUBTITLE'),
						icon: Icon.IMAGE,
						iconColor: Color.accentMainSuccess,
						controller: new CacheSettingController({
							settingId: NativeSettingsId.CACHE_IMAGES,
							fallbackValue: 0,
						}),
						onClick: async () => {
							await NativeCacheService.clearImages();
							SettingEmitter.emit(EventType.changeCacheSize);
							showToast({
								message: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_CACHE_CLEAR_IMAGES_TOAST_MESSAGE'),
							});
						},
					}),
				],
			}),
			createSection({
				id: 'data-section',
				items: [
					createCacheIntervalSelector({
						id: 'clear-cache-timeout',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_CACHE_INTERVAL'),
						controller: new CacheSettingController({
							settingId: 'cache_interval',
							fallbackValue: 'never',
						}),
					}),
					createCacheInfo({
						id: 'clear-system-cache-button',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_SYSTEM_CACHE'),
						subtitle: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_SYSTEM_CACHE_SUBTITLE'),
						color: Color.accentMainAlert,
						modeText: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_SYSTEM_CACHE_CLEAR'),
						onClick: () => {
							Alert.confirm(
								Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_DATA_CLEAR_ALL_ALERT_TITLE'),
								Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_DATA_CLEAR_ALL_ALERT_DESCRIPTION'),
								[
									{
										text: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_DATA_CLEAR_ALL_ALERT_SUBMIT_BUTTON'),
										type: 'destructive',
										onPress: () => {
											NativeCacheService.clearAppUserData();
										},
									},
									{
										text: Loc.getMessage('SETTINGS_V2_STRUCTURE_MEMORY_DATA_CLEAR_ALL_ALERT_SUBMIT_CANCEL'),
										type: 'cancel',
									},
								],
							);
						},
						prefilter: () => isAndroid,
					}),
				],
			}),
		],
	};

	module.exports = {
		MemoryPage,
	};
});
