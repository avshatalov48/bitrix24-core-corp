import {Loc, Uri} from 'main.core';
import AdminPanel from "./utils/admin-panel";

export default class Utils {
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

	static catchError(response) {
		BX.UI.Notification.Center.notify({
			content: [Loc.getMessage("MENU_ERROR_OCCURRED"),
				(response.errors ? ': ' + response.errors[0].message : '')].join(' '),
			position: 'bottom-left',
			category: 'menu-self-item-popup',
			autoHideDelay: 3000
		});
	}
	static refineUrl(url)
	{
		url = String(url).trim();
		if (url !== '')
		{
			if (!url.match(/^https?:\/\//i) && !url.match(/^\//i))
			{
				//for external links like "google.com" (without a protocol)
				url = "http://" + url;
			}
			else
			{
				var link = document.createElement("a");
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

	static get adminPanel(): AdminPanel
	{
		return AdminPanel.getInstance();
	}
}