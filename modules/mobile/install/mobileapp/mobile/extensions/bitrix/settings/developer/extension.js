BX.addCustomEvent('onRegisterProvider', (addProviderHandler) => {
	if (Application.isBeta())
	{
		const { DEV_SETTINGS } = jn.require('settings/developer/meta');
		const { DeveloperSettingsCacheManager } = jn.require('settings/developer/cache-manager');

		/**
		 * @class DevelopersSettingsProvider
		 * @extends SettingsProvider
		 */
		// eslint-disable-next-line no-undef
		class DevelopersSettingsProvider extends SettingsProvider
		{
			/**
			 * @param {Object} data
			 */
			onButtonTap(data)
			{
				if (data.id === this.id)
				{
					const formItems = this.createFormItems();
					const form = this.createForm(formItems);
					this.openForm(form, this.id);
				}
			}

			createFormItems()
			{
				return Object.entries(DEV_SETTINGS).map(([key, value]) => {
					// eslint-disable-next-line no-undef
					return new FormItem(
						value.id,
						FormItemType.SWITCH,
						value.name,
					).setDefaultValue(this.isSettingEnabled(value.id));
				});
			}

			createForm(formItems)
			{
				// eslint-disable-next-line no-undef
				return new Form(this.id, 'Developer Settings')
					.addSection(
						// eslint-disable-next-line no-undef
						new FormSection('developerSection', '')
							.addItems(formItems),
					)
					.compile();
			}

			onValueChanged(item)
			{
				if (item && DEV_SETTINGS[item.id])
				{
					DeveloperSettingsCacheManager.saveSetting(item.id, item.value);
				}
			}

			isSettingEnabled(settingsId)
			{
				return DeveloperSettingsCacheManager.getSettingValueById(settingsId);
			}
		}

		addProviderHandler(new DevelopersSettingsProvider('Developer Settings', 'Developer Settings'));
	}
});
