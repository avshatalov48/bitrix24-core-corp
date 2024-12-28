/**
 * @module im/messenger/lib/dev/settings
 */
jn.define('im/messenger/lib/dev/settings', (require, exports, module) => {
	const {
		CacheNamespace,
		CacheName,
	} = require('im/messenger/const/cache');
	const { merge } = require('utils/object');

	/**
	 * @class DeveloperSettings
	 */
	class DeveloperSettings
	{
		static getDefaultSettingsCollection()
		{
			return {
				showMessageId: {
					name: 'Show message IDs',
					value: false,
				},
				showDialogIds: {
					name: 'Show dialog IDs',
					value: false,
				},
			};
		}

		static getStorage()
		{
			return Application.storageById(CacheNamespace + CacheName.developer);
		}

		static getSettings()
		{
			return DeveloperSettings.getStorage().getObject('settings', DeveloperSettings.getDefaultSettingsCollection());
		}

		static setSettings(settings)
		{
			const settingsForSave = merge(DeveloperSettings.getDefaultSettingsCollection(), settings);

			return DeveloperSettings.getStorage().setObject('settings', settingsForSave);
		}

		static getSetting(name)
		{
			return DeveloperSettings.getSettings()[name];
		}

		static getSettingValue(name)
		{
			// eslint-disable-next-line es/no-optional-chaining
			return DeveloperSettings.getSettings()[name]?.value;
		}
	}

	module.exports = {
		DeveloperSettings,
	};
});
