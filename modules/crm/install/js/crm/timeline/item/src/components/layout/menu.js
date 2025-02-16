import { MenuManager } from 'main.popup';

import { Action } from '../../action';

export class Menu
{
	#menuOptions = {};
	#vueComponent = {};

	constructor(vueComponent: Object, menuItems: Array, menuOptions: ?Object)
	{
		this.#vueComponent = vueComponent;
		this.#menuOptions = menuOptions || {};
		this.#menuOptions = {
			angle: false,
			cacheable: false,
			...this.#menuOptions,
		};

		this.#menuOptions.items = [];
		for (const item of menuItems)
		{
			this.#menuOptions.items.push(this.createMenuItem(item));
		}
	}

	getMenuItems(): Array
	{
		return this.#menuOptions.items;
	}

	show(): void
	{
		MenuManager.show(this.#menuOptions);
	}

	createMenuItem(item): Object
	{
		if (Object.prototype.hasOwnProperty.call(item, 'delimiter') && item.delimiter)
		{
			return {
				text: item.title || '',
				delimiter: true,
			};
		}

		const result = {
			text: item.title,
			value: item.title,
		};

		if (item.icon)
		{
			result.className = `menu-popup-item-${item.icon}`;
		}

		if (item.menu)
		{
			result.items = [];
			for (const subItem of Object.values(item.menu.items || {}))
			{
				result.items.push(this.createMenuItem(subItem));
			}
		}
		else if (item.action)
		{
			if (item.action.type === 'redirect')
			{
				result.href = item.action.value;
			}
			else if (item.action.type === 'jsCode')
			{
				result.onclick = item.action.value;
			}
			else
			{
				result.onclick = () => {
					void this.onMenuItemClick(item);
				};
			}
		}

		return result;
	}

	onMenuItemClick(item): void
	{
		const menu = MenuManager.getCurrentMenu();
		if (menu)
		{
			menu.close();
		}

		void (new Action(item.action)).execute(this.#vueComponent);
	}

	static showMenu(vueComponent: Object, menuItems: Array, menuOptions: ?Object): void
	{
		const menu = new Menu(vueComponent, menuItems, menuOptions);

		menu.show();
	}
}
