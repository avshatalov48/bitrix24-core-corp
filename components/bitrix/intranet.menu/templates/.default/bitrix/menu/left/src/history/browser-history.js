import {Tag, Type} from 'main.core';

export class BrowserHistory
{
	wrapper: HTMLElement;
	items = [];

	constructor()
	{
		this.wrapper = document.getElementById("history-items");
	}

	init(): void
	{
		if ('object' != typeof BXDesktopSystem)
		{
			console.log('BXDesktopSystem is empty');
			return;
		}

		this.items = BXDesktopSystem?.BrowserHistory();

		this.showHistory();
	}

	showHistory(): void
	{
		let i = 0;
		this.items.forEach((item) => {
			if (i > 15) {
				return true;
			}
			let icoName = '';
			let title = '';
			if (Type.isStringFilled(item.title))
			{
				icoName = this.getShortName(item.title);
				title = item.title;
			}
			else
			{
				if (item.url.includes('/desktop_app/'))
				{
					icoName = Loc.getMessage('MENU_HISTORY_ITEM_ICON');
					title = Loc.getMessage('MENU_HISTORY_ITEM_NAME');
				}
				else
				{
					return;
				}
			}

			if (item.url.includes('/desktop/menu'))
			{
				return;
			}

			let url = item.url;
			if (item.url.includes('/online/'))
			{
				url = 'bx://v2/' + location.hostname + '/chat/';
			}

			let li = Tag.render`
				<li class="intranet__desktop-menu_item">
					<a class="intranet__desktop-menu_item-link" href="${url}">
						<span class="intranet__desktop-menu_item-icon --custom">${icoName}</span>
						<span class="intranet__desktop-menu_item-title">${title}</span>
					</a>
				</li>
			`;

			this.wrapper.appendChild(li);
			i++;
		});
	}

	getShortName(name): string
	{
		if (!Type.isStringFilled(name))
		{
			return "...";
		}

		name = name.replace(/['`".,:;~|{}*^$#@&+\-=?!()[\]<>\n\r]+/g, "").trim();
		if (name.length <= 0)
		{
			return '...';
		}

		let shortName;
		let words = name.split(/[\s,]+/);
		if (words.length <= 1)
		{
			shortName = name.substring(0, 1);
		}
		else if (words.length === 2)
		{
			shortName = words[0].substring(0, 1) + words[1].substring(0, 1);
		}
		else
		{
			let firstWord = words[0];
			let secondWord = words[1];

			for (let i = 1; i < words.length; i++)
			{
				if (words[i].length > 3)
				{
					secondWord = words[i];
					break;
				}
			}

			shortName = firstWord.substring(0, 1) + secondWord.substring(0, 1);
		}

		return shortName.toUpperCase();
	}

}
