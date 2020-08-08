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
			section: "energy"
		},
		allow_invite_users: {
			type: FormItemType.SWITCH,
			name: BX.message("SE_SYS_ALLOW_INVITE_USERS"),
			section: "invite"
		}
	};

	let forms = {};
	let cache = {};

	class OtherSettingsProvider extends SettingsProvider
	{
		constructor(id, title, subtitle = "")
		{
			super(id, title, subtitle);
			this.params = {};
			this.request = new DelayedRestRequest("mobile.settings.other.set");
		}

		onButtonTap(data)
		{
			cache = Application.storage.getObject(`settings.others.${env.userId}`, {});

			let sections = [
				new FormSection("energy", BX.message("SE_SYS_ENERGY_BACKGROUND"), BX.message("SE_SYS_LOW_PUSH_ACTIVITY_DESC")).addItems([
					new FormItem("push_low_activity", FormItemType.SWITCH, BX.message("SE_SYS_LOW_PUSH_ACTIVITY")).setDefaultValue(cache["push_low_activity"] ? !!cache["push_low_activity"] : false)
				]),
			];

			if (BX.componentParameters.get('IS_ADMIN', false))
			{
				sections.push(new FormSection("invite", BX.message("SE_SYS_INVITE"), BX.message("SE_SYS_ALLOW_INVITE_USERS_DESC")).addItems([
					new FormItem("allow_invite_users", FormItemType.SWITCH, BX.message("SE_SYS_ALLOW_INVITE_USERS")).setDefaultValue(cache["allow_invite_users"] ? !!cache["allow_invite_users"] : false)
				]));
			}

			forms["other"] = new Form("other", BX.message("SE_SYS_TITLE"))
				.addSections(sections).compile();

			if (data.id == this.id && forms[data.id])
			{
				this.openForm(forms[data.id], this.id, form =>
				{
					BX.rest.callMethod("mobile.settings.other.get").then((result) =>
					{

						console.error(result.answer.result);
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
										.setSectionCode(paramsType[key].section)
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
			this.request
				.setOptions(this.params)
				.setDelay(500)
				.call()
				.then(()=>{
					cache = Object.assign(cache, this.params);
					console.log(cache);
					Application.storage.setObject(`settings.others.${env.userId}`, cache);
					this.params = {};
				})
				.catch(() => this.params = {});

			super.onValueChanged();
		}

		onStateChanged(event, formId)
		{
			super.onStateChanged();
		}
	}

	addProviderHandler(new OtherSettingsProvider("other", BX.message("SE_SYS_TITLE")));
});
