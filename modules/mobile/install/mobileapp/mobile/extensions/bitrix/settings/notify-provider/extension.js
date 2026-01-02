jn.define('settings/notify-provider', (require, exports, module) => {
	const { SettingsProvider } = require('settings/provider');

	/**
	 * @class SettingsNotifyProvider
	 */
	class SettingsNotifyProvider extends SettingsProvider
	{
		onButtonTap(data)
		{
			super.onValueChanged(data);
			SettingsNotifyManager.setSettingsProvider(this);
			SettingsNotifyManager.onSettingsProviderButtonTap(data);
		}

		onValueChanged(item)
		{
			super.onValueChanged(item);
			SettingsNotifyManager.setSettingsProvider(this);
			SettingsNotifyManager.onSettingsProviderValueChanged(item);
		}

		onStateChanged(event, formId)
		{
			super.onStateChanged(event, formId);
			SettingsNotifyManager.setSettingsProvider(this);
			SettingsNotifyManager.onSettingsProviderStateChanged(event, formId);
		}
	}

	module.exports = {
		SettingsNotifyProvider,
		SettingsNotifyManager: this.SettingsNotifyManager,
	};
});
