/**
* @bxjs_lang_path component.php
*/

(function()
{
	const { SettingsProvider } = jn.require('settings/provider');
	this.SettingsProvider = SettingsProvider;

	const AppSettingsManager = {
		/**
		 * @type  {Array<SettingsProvider>} provider
		 */
		providers: [],
		listener(event, item)
		{
			if (item)
			{
				/**
				 * @type  {SettingsProvider} provider
				 */
				const provider = this.providerById(item.params.providerId);
				if (provider)
				{
					provider.onButtonTap(item);
				}
			}
		},
		/**
		 * @param id
		 * @return {SettingsProvider}
		 */
		providerById(id)
		{
			return this.providers.find((provider) => provider.id === id);
		},
		/**
		 *
		 * @param {SettingsProvider} provider
		 */
		addProvider(provider)
		{
			// eslint-disable-next-line no-undef
			if (!(provider instanceof SettingsProvider))
			{
				return;
			}

			this.providers.push(provider);
		},
		async init()
		{
			if (!result?.isNewMenuEnabled)
			{
				await requireLazy('im:chat/settings/notify');
			}

			this.providers = [];
			// eslint-disable-next-line no-undef
			settings.setListener((event, item) => this.listener(event, item));

			BX.onCustomEvent('onRegisterProvider', [(provider) => this.addProvider(provider)]);

			const items = [];
			if (this.providers.length > 0)
			{
				this.providers.forEach((provider) => {
					/**
					 * @type {SettingsProvider} provider
					 */
					items.push(provider.getSection());
				});
			}

			// eslint-disable-next-line no-undef
			BX.onViewLoaded(() => settings.setItems(items, [
				// eslint-disable-next-line no-undef
				new FormSection('main', BX.message('SETTINGS_TITLE')).compile(),
			]));

			console.info('AppSettingsManager.init:', items);
		},
	};

	AppSettingsManager.init();
	BX.addCustomEvent('onAppSettingsShouldRedraw', () => AppSettingsManager.init());
})();
