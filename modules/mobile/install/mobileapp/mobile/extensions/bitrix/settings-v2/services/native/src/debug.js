/**
 * @module settings-v2/services/native/src/debug
 */
jn.define('settings-v2/services/native/src/debug', (require, exports, module) => {
	const { appConfig } = require('native/config');

	/**
	 * @class NativeDebugService
	 */
	class NativeDebugService
	{
		static sendCallLogs()
		{
			appConfig.sendCallLogs();
		}

		static sendSystemLogs()
		{
			appConfig.sendSystemLogs();
		}

		static async getDebugSettings()
		{
			const allSettings = await appConfig.getSettings();

			return allSettings.filter((setting) => setting.id.startsWith('debug'));
		}
	}

	module.exports = {
		NativeDebugService,
	};
});
