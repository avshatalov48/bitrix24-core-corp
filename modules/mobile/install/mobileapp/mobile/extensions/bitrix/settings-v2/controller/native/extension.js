/**
 * @module settings-v2/controller/native
 */
jn.define('settings-v2/controller/native', (require, exports, module) => {
	const { BaseSettingController } = require('settings-v2/controller/base');
	const { appConfig } = require('native/config');
	const { Alert, ButtonType } = require('alert');
	const { Loc } = require('loc');

	class NativeSettingController extends BaseSettingController
	{
		/**
		 * @public
		 */
		async get()
		{
			try
			{
				const currentSetting = await this.getCurrentSetting();

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
				const currentSetting = await this.getCurrentSetting();
				if (currentSetting.reloadOnChanged)
				{
					Alert.confirm(
						Loc.getMessage('SETTINGS_V2_CONTROLLER_RELOADED_SETTING_CHANGE_TITLE'),
						Loc.getMessage('SETTINGS_V2_CONTROLLER_RELOADED_SETTING_CHANGE_DESCRIPTION'),
						[
							{
								type: ButtonType.DEFAULT,
								onPress: async () => {
									if (this.onChange)
									{
										this.onChange(value);
									}
									dialogs.showLoadingIndicator();
									await currentSetting.set(value);
								},
							},
						],
					);
				}
				else
				{
					await currentSetting.set(value);
					if (this.onChange)
					{
						this.onChange(value);
					}
				}
			}
			catch (e)
			{
				console.error(e);
			}
		}

		async getCurrentSetting()
		{
			const settingsList = await appConfig.getSettings();

			return settingsList.find((setting) => setting.id === this.settingId);
		}
	}

	module.exports = {
		NativeSettingController,
	};
});
