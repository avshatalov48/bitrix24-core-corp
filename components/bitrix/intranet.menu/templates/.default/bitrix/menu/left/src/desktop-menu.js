import {Cache, Type, Loc} from 'main.core';
import { Account } from './account/account';
import Theme from './api/theme';
import Counters from './api/counters';
import ItemsController from './controllers/items-controller'
import { BrowserHistory } from './history/browser-history';

export default class DesktopMenu
{
	cache = new Cache.MemoryCache();
	browserHistory = null;
	account = null;
	theme = null;
	#specialLiveFeedDecrement = 0;

	constructor(allCounters)
	{
		this.menuContainer = document.getElementById("menu-items-block");
		if (!this.menuContainer)
		{
			return false;
		}

		this.initTheme();
		this.getItemsController();
		this.getHistoryItems();
		this.showAccount(allCounters);
		this.runAPICounters();
	}

	initTheme(): void
	{
		this.theme = new Theme();
		this.theme.init();
	}

	getItemsController(): ItemsController
	{
		return this.cache.remember('itemsMenuController', () => {
			return new ItemsController(this.menuContainer);
		});
	}

	getHistoryItems(): void
	{
		this.browserHistory = new BrowserHistory();
		this.browserHistory.init();
	}

	showAccount(allCounters): void
	{
		this.account = new Account(allCounters);
		BX.Intranet.Account = this.account;
	}

	runAPICounters(): void
	{
		BX.Intranet.Counters = new Counters();
		BX.Intranet.Counters.init();
	}

	decrementCounter(node, iDecrement): void
	{
		if (!node || node.id !== 'menu-counter-live-feed')
		{
			return;
		}
		this.#specialLiveFeedDecrement += parseInt(iDecrement);
		this.getItemsController().decrementCounter({
			'live-feed' : parseInt(iDecrement)
		});
	}

	updateCounters(counters, send): void
	{
		if (!counters)
		{
			return;
		}
		if (counters['**'] !== undefined)
		{
			counters['live-feed'] = counters['**'];
			delete counters['**'];
		}
		let workgroupsCounterUpdated = false;
		if (!Type.isUndefined(counters['**SG0']))
		{
			this.workgroupsCounterData['livefeed'] = counters['**SG0'];
			delete counters['**SG0'];
			workgroupsCounterUpdated = true;
		}

		if (!Type.isUndefined(counters[Loc.getMessage('COUNTER_PROJECTS_MAJOR')]))
		{
			this.workgroupsCounterData[Loc.getMessage('COUNTER_PROJECTS_MAJOR')] = counters[Loc.getMessage('COUNTER_PROJECTS_MAJOR')];
			delete counters[Loc.getMessage('COUNTER_PROJECTS_MAJOR')];
			workgroupsCounterUpdated = true;
		}

		if (!Type.isUndefined(counters[Loc.getMessage('COUNTER_SCRUM_TOTAL_COMMENTS')]))
		{
			this.workgroupsCounterData[Loc.getMessage('COUNTER_SCRUM_TOTAL_COMMENTS')] = counters[Loc.getMessage('COUNTER_SCRUM_TOTAL_COMMENTS')];
			delete counters[Loc.getMessage('COUNTER_SCRUM_TOTAL_COMMENTS')];
			workgroupsCounterUpdated = true;
		}

		if (workgroupsCounterUpdated)
		{
			counters['workgroups'] = Object.entries(this.workgroupsCounterData).reduce((prevValue, [, curValue]) => {
				return prevValue + Number(curValue);
			}, 0);
		}

		if (counters['live-feed'])
		{
			if (counters['live-feed'] <= 0)
			{
				this.#specialLiveFeedDecrement = 0;
			}
			else
			{
				counters['live-feed'] -= this.#specialLiveFeedDecrement;
			}
		}

		this.getItemsController().updateCounters(counters, send);
		BX.Intranet.Account.setCounters(counters);
	}
}