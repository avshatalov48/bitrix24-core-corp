/**
 * @bxjs_lang_path extension.php
 * This settings extension for ios platform only
 */

BX.addCustomEvent("onRegisterProvider", (addProviderHandler) =>
{
	if (Application.getPlatform() != "ios")
	{
		return;
	}

	let forms = {};
	let formItems = [];

	if(!env.extranet)
		formItems.push(new FormItem("sync_calendar", FormItemType.BUTTON, BX.message("SE_SYNC_CAL_TITLE")).setCustomParam("resource", "caldav"));

	formItems.push(new FormItem("sync_contacts", FormItemType.BUTTON, BX.message("SE_SYNC_CONTACTS_TITLE")).setCustomParam("resource", "carddav"));
	forms["sync"] = new Form("sync", BX.message("SE_SYNC_TITLE")).addSection(
		new FormSection("main", BX.message("SE_SYNC_SUBTITLE_TITLE"), BX.message("SE_SYNC_PROFILE_DESCRIPTION")).addItems(formItems)
	).compile();

	class SyncSettingsProvider extends SettingsProvider
	{
		onButtonTap(data)
		{
			if (data.id == this.id && forms[data.id])
			{
				this.openForm(forms[data.id], this.id);
			}
			else if (data.id == "sync_contacts" || data.id == "sync_calendar")
			{
				BX.ajax({
					url: "/bitrix/tools/dav_profile.php?action=token&params[resources]=" + data.params.resource,
					dataType: "json",
					method: "GET"
				}).then(response =>
				{
					if (response.token)
					{
						let urlPath = "/bitrix/tools/dav_profile.php?action=payload&params[resources]="
							+ data.params.resource
							+ "&params[access_token]=";
						Application.openUrl(currentDomain + urlPath + response.token);
					}
				}).catch(e => console.error(e));
			}
		}

		onValueChanged(item)
		{
			super.onValueChanged();
		}

		onStateChanged(event, formId)
		{
			super.onStateChanged();
		}
	}

	addProviderHandler(new SyncSettingsProvider("sync", BX.message("SE_SYNC_TITLE"), BX.message("SE_SYNC_SUBTITLE_TITLE")));
});
