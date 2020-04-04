/**
 * @bxjs_lang_path extension.php
 * This settings extension for ios platform only
 */

BX.addCustomEvent("onRegisterProvider", (addProviderHandler) =>
{
	let paramsType = {
		preset: {
			type: FormItemType.SELECTOR,
			name: BX.message("SE_SYS_LOW_PUSH_ACTIVITY"),
		}
	};
	let forms = {};
	let cache = {};

	/**
	 * @class
	 * @implements DelayedRestRequestDelegate
	 */
	class TabSettingsProvider extends SettingsProvider
	{
		constructor(id, title, subtitle = "")
		{
			super(id, title, subtitle);
			this.params = {};
			this.request = new DelayedRestRequest("mobile.tabs.setpreset", this);
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
			console.log(item);

			(new RequestExecutor("mobile.tabs.setpreset", {name: item.value}))
				.setHandler(result => Application.relogin())
				.call(false);
			this.params[item.id] = item.value;
			this.request.send();
			super.onValueChanged();
		}

		onStateChanged(event, formId)
		{
			super.onStateChanged();
		}

		onDelayedRequestResult(result)
		{
			if (result["success"] == true)
			{
				for (let key in this.params)
				{
					cache[key] = this.params[key];
				}

				Application.storage.setObject(`settings.others.${env.userId}`, cache);
			}

			this.params = {};
		}

		getParams()
		{
			return this.params;
		}
	}

	addProviderHandler(new TabSettingsProvider("tab_settings", BX.message("SF_TABS_SETTINGS")));
});
