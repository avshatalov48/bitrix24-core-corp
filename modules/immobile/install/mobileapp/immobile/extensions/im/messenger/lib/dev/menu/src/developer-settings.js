/* eslint-disable no-console */
/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/lib/dev/menu/developer-settings
 */
jn.define('im/messenger/lib/dev/menu/developer-settings', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { DeveloperSettings } = require('im/messenger/lib/dev/settings');

	class DeveloperSettingsMenu
	{
		constructor()
		{
			this.titleParams = {
				text: 'Developer settings',
				detailText: '',
				imageColor: AppTheme.colors.accentBrandBlue,
				useLetterImage: true,
			};

			this.form = null;
		}

		open()
		{
			PageManager.openWidget(
				'form',
				{
					titleParams: this.titleParams,
				},
			)
				.then(this.onWidgetReady.bind(this))
				.catch((error) => {
					console.error(error);
				})
			;
		}

		onWidgetReady(form)
		{
			this.form = form;
			this.render();
			this.form.setListener((event, data) => {
				if (event !== 'onItemChanged')
				{
					return;
				}

				const {
					id,
					value,
				} = data;

				const settings = DeveloperSettings.getSettings();
				settings[id].value = value;
				DeveloperSettings.setSettings(settings);
			});
		}

		render()
		{
			const checkboxList = [];
			const settings = DeveloperSettings.getSettings();
			Object.entries(settings).forEach(([key, setting]) => {
				checkboxList.push({
					id: key,
					type: 'switch',
					title: setting.name,
					value: setting.value,
				});
			});

			this.form.setItems(checkboxList);
		}
	}

	module.exports = {
		DeveloperSettingsMenu,
	};
});
