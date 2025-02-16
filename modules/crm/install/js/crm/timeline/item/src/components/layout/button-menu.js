import { Type } from 'main.core';

import { ButtonState } from '../enums/button-state';
import { Menu } from './menu';

export class ButtonMenu extends Menu
{
	constructor(vueComponent: Object, menuItems: Array, menuOptions: ?Object)
	{
		super(vueComponent, menuItems, menuOptions);

		this.#applyMenuItems();
	}

	/**
	 * @override
	 */
	createMenuItem(item: Object): Object
	{
		const result = {
			text: item.title,
			value: item.title,
		};

		if (Type.isStringFilled(item.state))
		{
			switch (item.state)
			{
				case ButtonState.AI_LOADING:
					result.className = 'menu-popup-item-ai-loading menu-popup-item-disabled';
					break;
				case ButtonState.AI_SUCCESS:
					result.className = 'menu-popup-item-accept menu-popup-item-disabled';
					break;
				case ButtonState.DISABLED:
					result.className = 'menu-popup-no-icon menu-popup-item-disabled';
					break;
				case ButtonState.LOCKED:
					result.className = 'menu-popup-item-locked';
					break;
				default:
					result.className = '';
			}
		}

		if (Type.isObject(item.action))
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

	#applyMenuItems(): void
	{
		const items = this.getMenuItems();
		if (!items)
		{
			return;
		}

		const emptyClassItems = items.filter((item: Object) => item.className === '');
		if (emptyClassItems.length === items.length)
		{
			return;
		}

		items.forEach((item: Object) => {
			if (item.className === '')
			{
				// eslint-disable-next-line no-param-reassign
				item.className = 'menu-popup-empty-icon';
			}
		});
	}

	static showMenu(vueComponent: Object, menuItems: Array, menuOptions: ?Object): void
	{
		const menu = new ButtonMenu(vueComponent, menuItems, menuOptions);

		menu.show();
	}
}
