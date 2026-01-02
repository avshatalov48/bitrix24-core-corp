(() => {
	const require = (extension) => jn.require(extension);

	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const formId = AppTheme.id === 'light' ? 'appFeedbackLight' : 'appFeedbackDark';
	const hiddenFields = encodeURIComponent(JSON.stringify({
		from_domain: currentDomain,
		back_version: Application.getAppVersion(),
		os_phone: Application.getPlatform(),
		app_version: Application.getApiVersion(),
		region_model: env.languageId,
		sender_page: 'mobile_settings',
		phone_model: device.model,
		os_version: device.version,
	}));
	const { isModuleInstalled } = require('module');

	BX.addCustomEvent('onRegisterProvider', (addProviderHandler) => {
		// eslint-disable-next-line no-undef
		class FeedbackSettingsProvider extends SettingsProvider
		{
			constructor(id, title, subtitle = '')
			{
				super(id, title, subtitle);
			}

			onButtonTap(data)
			{
				if (data.id === this.id)
				{
					PageManager.openPage({
						url: `${env.siteDir}mobile/settings?formId=${formId}&hiddenFields=${hiddenFields}`,
						titleParams: {
							text: Loc.getMessage('SE_FEEDBACK_TITLE'),
						},
						enableNavigationBarBorder: false,
						cache: true,
					});
				}
			}
		}

		if (
			(Application.getPlatform() === 'android' || Application.getApiVersion() >= 60)
			&& isModuleInstalled('ui')
		)
		{
			addProviderHandler(new FeedbackSettingsProvider('feedback', Loc.getMessage('SE_FEEDBACK_TITLE'), ''));
		}
	});
})();
