/**
 * @module settings-v2/services/native/src/cache
 */
jn.define('settings-v2/services/native/src/cache', (require, exports, module) => {
	const { appConfig } = require('native/config');
	const { NativeSettingsId } = require('settings-v2/const');

	/**
	 * @class NativeCacheService
	 */
	class NativeCacheService
	{
		/**
		 * @return {Promise<Object>}
		 */
		static async getCacheIntervalOptionLabels()
		{
			const setting = await NativeCacheService.getCacheIntervalSetting();

			return setting.optionLabels;
		}

		/**
		 * @return {Promise<Array<String>>}
		 */
		static async getCacheIntervalOptions()
		{
			const setting = await NativeCacheService.getCacheIntervalSetting();

			return setting.options;
		}

		static async getCacheIntervalSetting()
		{
			return NativeCacheService.getSettingById(NativeSettingsId.CACHE_INTERVAL);
		}

		static async getCacheSettings()
		{
			const cacheObject = appConfig.cache;

			return cacheObject.getSettings();
		}

		static async getSettingById(id)
		{
			const cacheSettings = await NativeCacheService.getCacheSettings();

			return cacheSettings.find((setting) => setting.id === id);
		}

		static async getSettingValueById(id)
		{
			const setting = await NativeCacheService.getSettingById(id);

			return setting?.value;
		}

		/**
		 * @return {Promise<String>}
		 */
		static async clearCache()
		{
			const cacheObject = appConfig.cache;

			return cacheObject.clearAll();
		}

		/**
		 * @return {Promise<String>}
		 */
		static async clearImages()
		{
			const cacheObject = appConfig.cache;

			return cacheObject.clearImages();
		}

		/**
		 * @return {Promise<String>}
		 */
		static async clearAppUserData()
		{
			const cacheObject = appConfig.cache;

			return cacheObject.clearAppUserData();
		}
	}

	module.exports = {
		NativeCacheService,
	};
});
