/**
 * @module settings-v2/structure/pages/messenger
 */
jn.define('settings-v2/structure/pages/messenger', (require, exports, module) => {
	const { createToggle, createSection, createDescription } = require('settings-v2/structure/src/item-create-helper');
	const { ApplicationStorageSettingController } = require('settings-v2/controller/application-storage');
	const { SettingsPageId, EventType } = require('settings-v2/const');
	const { SettingEmitter } = require('settings-v2/emitter');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { Loc } = require('loc');

	const SETTING_SCOPE_ID = 'settings.chat';

	/**
	 * @param settingId
	 * @param fallbackValue
	 * @returns {ApplicationStorageSettingController}
	 */
	const createMessengerController = (settingId, fallbackValue) => {
		return new ApplicationStorageSettingController({
			settingScopeId: SETTING_SCOPE_ID,
			settingId,
			fallbackValue,
		});
	};

	const requestSettingsData = async () => {
		const response = await new RunActionExecutor(
			'immobile.api.Settings.get',
			{},
		)
			.setCacheId('messenger.settingsData')
			.setCacheTtl(2_592_000)
			.setCacheHandler(() => {})
			.setSkipRequestIfCacheExists()
			.call(true);

		const { IS_CHAT_LOCAL_STORAGE_AVAILABLE, IS_BETA_AVAILABLE } = response.data;

		return {
			isLocalStorageAvailable: IS_CHAT_LOCAL_STORAGE_AVAILABLE === true,
			isBetaAvailable: IS_BETA_AVAILABLE === true,
		};
	};

	/** @type SettingPage */
	const MessengerPage = {
		id: SettingsPageId.MESSENGER,
		title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MESSENGER_TITLE'),
		requestSettingsData,
		items: [
			createSection({
				id: 'message-history',
				title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MESSENGER_CHAT_HISTORY'),
				items: [
					createToggle({
						id: 'message-show_history',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MESSENGER_SHOW_HISTORY'),
						controller: createMessengerController('historyShow', true),
					}),

					createToggle({
						id: 'message_cache',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MESSENGER_CACHE_MESSAGES'),
						subtitle: Loc.getMessage('SETTINGS_V2_STRUCTURE_MESSENGER_CACHE_MESSAGES_DESCRIPTION'),
						prefilter: (settingsData) => settingsData?.isLocalStorageAvailable,
						controller: createMessengerController('localStorageEnable', true)
							.setOnChange(() => {
								Application.relogin();
							}),
						divider: false,
					}),
					createDescription({
						id: 'loc-description',
						text: Loc.getMessage('SETTINGS_V2_STRUCTURE_RELOAD_DESCRIPTION'),
					}),
				],
			}),

			createSection({
				id: 'messenger-auto_play',
				title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MESSENGER_AUTOPLAY'),
				items: [
					createToggle({
						id: 'message_video',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MESSENGER_AUTOPLAY_VIDEO'),
						controller: createMessengerController('autoplayVideo', true),
					}),
				],
			}),
			createSection({
				id: 'messenger-beta',
				title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MESSENGER_BETA_VERSION'),
				prefilter: (settingsData) => settingsData?.isBetaAvailable,
				items: [
					createToggle({
						id: 'messenger-test_mode',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MESSENGER_BETA_TEST_MODE'),
						controller: createMessengerController('chatBetaEnable', false)
							.setOnChange((value) => {
								SettingEmitter.emit(EventType.changeChatSettings, { id: 'chatBetaEnable', value });
							}),
					}),
					createToggle({
						id: 'messenger-dev_mode',
						title: Loc.getMessage('SETTINGS_V2_STRUCTURE_MESSENGER_BETA_DEV_MODE'),
						controller: createMessengerController('chatDevModeEnable', false)
							.setOnChange(() => {
								Application.relogin();
							}),
					}),
				],
			}),
		],
	};

	module.exports = {
		MessengerPage,
	};
});
