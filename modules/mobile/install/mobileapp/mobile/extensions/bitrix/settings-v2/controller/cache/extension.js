/**
 * @module settings-v2/controller/cache
 */
jn.define('settings-v2/controller/cache', (require, exports, module) => {
	const { BaseSettingController } = require('settings-v2/controller/base');
	const { appConfig } = require('native/config');
	const { NativeCacheService } = require('settings-v2/services/native');

	/**
	 * @class CacheSettingController
	 */
	class CacheSettingController extends BaseSettingController
	{
		/**
		 * @public
		 */
		async get()
		{
			try
			{
				const currentSetting = await NativeCacheService.getSettingById(this.settingId);

				return currentSetting.value ?? this.fallbackValue;
			}
			catch (e)
			{
				console.error(e);

				return this.fallbackValue;
			}
		}

		/**
		 * @public
		 * @param {*} value
		 */
		async set(value)
		{
			try
			{
				const currentSetting = await NativeCacheService.getSettingById(this.settingId);
				await currentSetting.set(value);

				if (this.onChange)
				{
					this.onChange(value);
				}
			}
			catch (e)
			{
				console.error(e);
			}
		}
	}

	module.exports = {
		CacheSettingController,
	};
});
