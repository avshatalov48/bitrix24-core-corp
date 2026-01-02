/**
 * @module settings/developer/cache-manager
 */
jn.define('settings/developer/cache-manager', (require, exports, module) => {
	const { DEV_SETTINGS, STORAGE_PREFIX } = require('settings/developer/meta');

	/**
	 * @class DeveloperSettingsCacheManager
	 */
	class DeveloperSettingsCacheManager
	{
		/**
		 * @param {string} key
		 * @param {string} value
		 */
		static saveSetting(key, value)
		{
			const storageKey = DeveloperSettingsCacheManager.getStorageKeyBySettingId(key);

			Application.storage.setObject(storageKey, { value });
		}

		/**
		 *
		 * @param {string} settingId
		 * @return {*|null}
		 */
		static getSettingValueById(settingId)
		{
			const setting = DEV_SETTINGS[settingId];
			if (setting)
			{
				const storageKey = DeveloperSettingsCacheManager.getStorageKeyBySettingId(setting.id);

				const defaultValue = { value: setting.defaultValue };

				const cache = Application.storage.getObject(storageKey, defaultValue);
				if (cache)
				{
					return cache.value;
				}
			}

			return null;
		}

		static getStorageKeyBySettingId(id)
		{
			return `${STORAGE_PREFIX}.${env.userId}.${id}`;
		}
	}

	module.exports = {
		DeveloperSettingsCacheManager,
	};
});
