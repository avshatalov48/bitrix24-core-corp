/**
 * @module settings-v2/services/native/src/loc
 */
jn.define('settings-v2/services/native/src/loc', (require, exports, module) => {
	const { appConfig } = require('native/config');

	/**
	 * @class NativeLocService
	 */
	class NativeLocService
	{
		/**
		 * @return {Promise<Object>}
		 */
		static async getOptionLabels()
		{
			const locSetting = await NativeLocService.getLocSetting();

			return locSetting.optionLabels;
		}

		/**
		 * @return {Promise<Array<String>>}
		 */
		static async getOptions()
		{
			const locSetting = await NativeLocService.getLocSetting();

			return locSetting.options;
		}

		static async getLocSetting()
		{
			const settingsList = await appConfig.getSettings();

			return settingsList.find((setting) => setting.id === 'app_language');
		}
	}

	module.exports = {
		NativeLocService,
	};
});
