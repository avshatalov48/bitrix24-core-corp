BX.addCustomEvent("onRegisterProvider", (addProviderHandler) =>
{
	if (Application.getPlatform() !== "android")
		return;

	let forms = {};

	class CalltrackerSettingsProvider extends SettingsProvider
	{
		onButtonTap(data)
		{
			if (data.id == this.id)
			{
				let formItems = [
					new FormItem(
						"calltracker_service", FormItemType.SWITCH, BX.message("SE_CALLTRACKER_TITLE")
					)
						.setDefaultValue(this.isCalltrackerEnabled())
				];

				forms["calltracker"] =
					new Form("calltracker", BX.message("SE_CALLTRACKER_TITLE"))
						.addSection(
							new FormSection("calltracker_main", '')
								.addItems(formItems)
						)
						.compile();

				this.openForm(forms[data.id], this.id);
			}
		}

		onValueChanged(item)
		{
			if (item.value)
			{
				CallTracker.enableCallTracker();
			}
			else
			{
				CallTracker.disableCallTracker();
			}
		}

		isCalltrackerEnabled()
		{
			return CallTracker.checkEnableTracker();
		}
	}

	include('CallTracker');
	if (CallTracker && this && this.result && this.result.showCalltrackerSettings)
	{
		addProviderHandler(new CalltrackerSettingsProvider("calltracker", BX.message("SE_CALLTRACKER_TITLE")));
	}
});
