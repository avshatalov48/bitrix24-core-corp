/**
 * @module im/messenger/lib/settings
 */
jn.define('im/messenger/lib/settings', (require, exports, module) => {
	const { MessengerParams } = require('im/messenger/lib/params');

	/**
	 * @class Settings
	 */
	class Settings
	{
		static get()
		{
			return Application.storage.getObject('settings.chat', {
				chatBetaEnable: false,
				localStorageEnable: true,
				autoplayVideo: true,
			});
		}

		static get isChatBetaEnabled()
		{
			return Settings.get().chatBetaEnable;
		}

		static get isChatV2Enabled()
		{
			return MessengerParams.isChatM1Enabled() && Settings.isChatV2Supported;
		}

		static get isChatV2Supported()
		{
			return Application.getApiVersion() >= 52;
		}

		static get isLocalStorageEnabled()
		{
			return (
				MessengerParams.isChatM1Enabled()
				&& MessengerParams.isChatLocalStorageAvailable()
				&& Settings.isLocalStorageSupported
				&& Settings.get().localStorageEnable
			);
		}

		static get isLocalStorageSupported()
		{
			const isSupportedApp = Application.getApiVersion() >= 52;
			const isSupportedAndroid = (
				Application.getPlatform() === 'android'
				&& parseInt(Application.getBuildVersion(), 10) >= 2443
			);
			const isSupportedIos = device.platform === 'iOS'
				&& parseInt(device.version, 10) >= 15
			;

			return isSupportedApp && (isSupportedAndroid || isSupportedIos);
		}

		static get isAutoplayVideoEnabled()
		{
			return Settings.get().autoplayVideo;
		}
	}

	module.exports = {
		Settings,
	};
});
