BX.addCustomEvent("onIntentHandle", intent => {
	/** @var {MobileIntent} intent */
	intent.addHandler( () => {
		const intentResult = ["calendar_sync_slider", "calendar_sync_banner"]
			.find((name, index, intents) => {
				({intent} = Application.getLastNotification(name))
				return intents.includes(intent)
			});

		if(intentResult)
		{
			if (Application.getPlatform() === "ios")
			{
				BX.ajax({
					url: "/bitrix/tools/dav_profile.php?action=token&params[resources]=caldav",
					dataType: "json",
					method: "GET"
				}).then(response =>
				{
					if (response.token)
					{
						const urlPath = "/pub/calendar_ios_guide.php?access_token=";
						Application.openUrl(currentDomain + urlPath + response.token);
					}
				}).catch(e => console.error(e));
			}
			else
			{
				if(typeof Application["davSyncEnable"] !== "undefined")
				{
					Application.davSyncEnable("calendar");
					const urlPath = "/pub/calendar_android_guide.php";
					Application.openUrl(currentDomain + urlPath);
				}
			}

			BX.ajax.runAction('calendar.api.calendarajax.analytical', {
				analyticsLabel: {
					mobile_sync_device_platform: Application.getPlatform(),
					link_place: intentResult === 'calendar_sync_banner' ? 'banner' : 'slider',
					mobile_sync_calendar: 'mobile_sync_calendar',
				}
			});

			analytics.send( "calendar_sync_" + Application.getPlatform(), {type: intentResult})
		}
	})
});
