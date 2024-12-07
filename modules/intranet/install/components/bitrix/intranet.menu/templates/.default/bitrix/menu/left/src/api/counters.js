export default class Counters
{
	init()
	{
		BX.addCustomEvent("onPullEvent-main", (command,params) => {
			const key = 'SITE_ID';
			const siteId = BX.message(key);
			if (command === "user_counter" && params[siteId])
			{
				let counters = BX.clone(params[siteId]);
				this.updateCounters(counters, false);
			}
		});

		BX.addCustomEvent("onPullEvent-tasks", (command, params) => {
			if (
				command === "user_counter"
				&& Number(params.userId) === Number(BX.Loc.getMessage('USER_ID'))
			)
			{
				let counters = {};
				if (!BX.Type.isUndefined(params.projects_major))
				{
					counters.projects_major = params.projects_major;
				}
				if (!BX.Type.isUndefined(params.scrum_total_comments))
				{
					counters.scrum_total_comments = params.scrum_total_comments;
				}

				this.updateCounters(counters, false);
			}
		});

		BX.addCustomEvent(window, "onImUpdateCounter", (counters) => {

			if (!counters)
				return;

			this.updateCounters(BX.clone(counters), false);
		});

		BX.addCustomEvent("onImUpdateCounterMessage",(counter) => {
			this.updateCounters({'im-message': counter}, false);
		});

		if (BX.browser.SupportLocalStorage())
		{
			BX.addCustomEvent(window, 'onLocalStorageSet', (params) =>
			{
				if (params.key.substring(0, 4) === 'lmc-')
				{
					let counters = {};
					counters[params.key.substring(4)] = params.value;
					this.updateCounters(counters, false);
				}
			});
		}

		BX.addCustomEvent("onCounterDecrement", (iDecrement) => {
			this.decrementCounter(BX("menu-counter-live-feed"), iDecrement)
		});
	}

	updateCounters(counters, send)
	{
		BX.ready(function ()
		{
			if (BX.getClass("BX.Intranet.DescktopLeftMenu"))
			{
				BX.Intranet.DescktopLeftMenu.updateCounters(counters, send);
			}
		});
	}

	decrementCounter(node, iDecrement)
	{
		BX.ready(function ()
		{
			if (BX.getClass("BX.Intranet.DescktopLeftMenu"))
			{
				BX.Intranet.DescktopLeftMenu.decrementCounter(node, iDecrement);
			}
		});
	}
}
