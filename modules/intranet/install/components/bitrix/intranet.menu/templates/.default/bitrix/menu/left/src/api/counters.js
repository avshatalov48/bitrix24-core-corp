import { BaseEvent, EventEmitter } from 'main.core.events';

export default class Counters
{
	init()
	{
		// All Counters
		EventEmitter.subscribe('onPullEvent-main', (event: BaseEvent) => {
			const [command, params] = event.getCompatData();
			if (command === 'user_counter' && params[Loc.getMessage('SITE_ID')])
			{
				const counters = { ...params[Loc.getMessage('SITE_ID')] };
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

		// All Counters from IM
		EventEmitter.subscribe('onImUpdateCounter', (event: BaseEvent) => {
			const [counters] = event.getCompatData();
			this.updateCounters(counters, false);
		});

		// Messenger counter
		EventEmitter.subscribe('onImUpdateCounterMessage', (event: BaseEvent) => {
			const [counter] = event.getCompatData();
			this.updateCounters({ 'im-message': counter }, false);
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

		// Live Feed Counter
		EventEmitter.subscribe('onCounterDecrement', (event: BaseEvent) => {
			const [decrement] = event.getCompatData();
			this.decrementCounter(document.getElementById('menu-counter-live-feed'), decrement);
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
