/**
 * @bxjs_lang_path extension.php
 * This settings extension for ios platform only
 */

BX.addCustomEvent("onRegisterProvider", (addProviderHandler) =>
{
	let paramsType = {
		push_low_activity: {
			type: FormItemType.SWITCH,
			name: BX.message("SE_SYS_LOW_PUSH_ACTIVITY"),
		}
	};
	let forms = {};
	let cache = {};

	/**
	 * @class
	 * @implements DelayedRestRequestDelegate
	 */
	class OtherSettingsProvider extends SettingsProvider
	{
		constructor(id, title, subtitle = "")
		{
			super(id, title, subtitle);
			this.params = {};
			this.request = new DelayedRestRequest("mobile.settings.energy.set", this);
		}

		onButtonTap(data)
		{
			cache = Application.storage.getObject(`settings.others.${env.userId}`, {});
			forms["other"] = new Form("other", BX.message("SE_SYS_TITLE"))
				.addSection(
					new FormSection("main", BX.message("SE_SYS_ENERGY_BACKGROUND"),
						BX.message("SE_SYS_LOW_PUSH_ACTIVITY_DESC"))
						.addItems([
							new FormItem("push_low_activity", FormItemType.SWITCH,
								BX.message("SE_SYS_LOW_PUSH_ACTIVITY"))
								.setDefaultValue(cache["push_low_activity"] ? !!cache["push_low_activity"] : false)
						])
				).compile();

			if (data.id == this.id && forms[data.id])
			{
				this.openForm(forms[data.id], this.id, form =>
				{
					BX.rest.callMethod("mobile.settings.energy.get").then((result) =>
					{
						console.log(env.userId);
						let params = result.answer.result;
						if (params)
						{
							let list = [];

							Application.storage.setObject(`settings.others.${env.userId}`, params);
							for (let key in params)
							{
								if (paramsType[key])
								{
									list.push(new FormItem(key, paramsType[key].type, paramsType[key].name)
										.setDefaultValue(!!params[key]));
								}

							}

							form.setItems(list, null, true);
						}
					});
				});

			}
		}

		onValueChanged(item)
		{
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

	addProviderHandler(new OtherSettingsProvider("other", BX.message("SE_SYS_TITLE")));
});
