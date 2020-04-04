/**
 * @bxjs_lang_path extension.php
 * This settings extension for ios platform only
 */

BX.addCustomEvent("onRegisterProvider", (addProviderHandler) =>
{
	class TabSettingsProvider extends SettingsProvider
	{
		constructor(id, title, subtitle = "")
		{
			super(id, title, subtitle);
			this.params = {};
			this.request = new DelayedRestRequest("mobile.tabs.setpreset");
		}

		onButtonTap(data)
		{
			if (data.id === this.id)
			{
				ComponentHelper.openList({
					name: "tab.settings",
					object: "list",
					version: availableComponents["tab.settings"].version,
					widgetParams:{
						title:data.title,
						groupStyle: true,
					}
				});
			}
		}

		onValueChanged(item)
		{
			(new RequestExecutor("mobile.tabs.setpreset"))
				.setOptions({name: item.value})
				.setHandler(_ => Application.relogin())
				.call();
			super.onValueChanged();
		}

		onStateChanged(event, formId)
		{
			super.onStateChanged();
		}
	}

	addProviderHandler(new TabSettingsProvider("tab_settings", BX.message("SF_TABS_SETTINGS")));
});
