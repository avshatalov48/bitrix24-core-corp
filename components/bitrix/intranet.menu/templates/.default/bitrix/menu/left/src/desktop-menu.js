import {Cache } from 'main.core';
import { Account } from './account/account';
import Counters from './api/counters';
import ItemsController from './controllers/items-controller';
import { BrowserHistory } from './history/browser-history';

export default class DesktopMenu
{
	cache = new Cache.MemoryCache();
	browserHistory = null;
	account = null;
	theme = null;
	#specialLiveFeedDecrement = 0;

	constructor(allCounters: Object)
	{
		this.menuContainer = document.getElementById("menu-items-block");
		if (!this.menuContainer)
		{
			return;
		}

		this.getItemsController();
		this.getHistoryItems();
		this.showAccount(allCounters);
		this.runAPICounters();
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
