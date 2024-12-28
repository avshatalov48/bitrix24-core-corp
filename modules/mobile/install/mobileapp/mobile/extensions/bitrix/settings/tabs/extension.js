/**
 * @bxjs_lang_path extension.php
 * This settings extension for ios platform only
 */

BX.addCustomEvent("onRegisterProvider", (addProviderHandler) =>
{
	if (env.isCollaber)
	{
		return;
	}

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

				PageManager.openComponent("JSStackComponent",{
					scriptPath: availableComponents["tab.presets"].publicUrl,
					rootWidget:{
						name: "layout",
						settings:{
							objectName: "layout",
							titleParams: { text: data.title, useLargeTitleMode: true}
						}
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

	addProviderHandler(new TabSettingsProvider("tab_settings", BX.message("MENU_PRESET_TAB")));
});
