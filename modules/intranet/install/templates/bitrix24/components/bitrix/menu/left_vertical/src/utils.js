import { Loc, Reflection, Runtime, Uri } from 'main.core';

export default class Utils
{
	static #curPage = null;
	static #curUri = null;

	static getCurPage(): ?String
	{
		if (this.#curPage === null)
		{
			this.#curPage = document.location.pathname + document.location.search;
		}

		return this.#curPage === '' ? null : this.#curPage;
	}

	static getCurUri(): Uri
	{
		if (this.#curUri === null)
		{
			this.#curUri = new Uri(document.location.href);
		}

		return this.#curUri;
	}

	static catchError(response): void
	{
		Runtime.loadExtension('ui.notification')
			.then(() => {
				const notificationCenter = Reflection.getClass('BX.UI.Notification.Center');
				notificationCenter.notify({
					content: [
						Loc.getMessage('MENU_ERROR_OCCURRED'),
						(response.errors ? `: ${response.errors[0].message}` : ''),
					].join(' '),
					position: 'bottom-left',
					category: 'menu-self-item-popup',
					autoHideDelay: 3000,
				});
			})
			.catch(() => {
				console.log('LeftMenu: cannot load ui.notification.');
			})
		;
	}

	static refineUrl(originUrl: string): string
	{
		let url = String(originUrl).trim();
		if (url !== '')
		{
			if (!/^https?:\/\//i.test(url) && !/^\//i.test(url))
			{
				// for external links like "google.com" (without a protocol)
				url = `https://${url}`;
			}
			else
			{
				const link = document.createElement('a');
				link.href = url;

				if (document.location.host === link.host)
				{
					// http://portal.com/path/ => /path/
					url = link.pathname + link.search + link.hash;
				}
			}
		}

		return url;
	}
}
