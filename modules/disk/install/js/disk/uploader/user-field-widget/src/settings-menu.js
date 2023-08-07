import { Loc } from 'main.core';
import { Menu, MenuItem } from 'main.popup';

import type UserFieldControl from './user-field-control';

export default class SettingsMenu
{
	#userFieldControl: UserFieldControl = null;
	#menu: Menu = null;

	constructor(userFieldControl: UserFieldControl)
	{
		this.#userFieldControl = userFieldControl;
	}

	getMenu(button): Menu
	{
		if (this.#menu !== null)
		{
			return this.#menu;
		}

		this.#menu = new Menu({
			bindElement: button.getContainer(),
			className: 'disk-user-field-settings-popup',
			angle: true,
			autoHide: true,
			offsetLeft: 16,
			cacheable: false,
			items: this.#getItems(),
			events: {
				onShow: (): void => {
					button.select();
				},
				onDestroy: (): void => {
					button.deselect();
					this.#menu = null;
				}
			}
		});

		return this.#menu;
	}

	#getItems(): Array
	{
		if (!this.#userFieldControl.canChangePhotoTemplate())
		{
			return [];
		}

		return [{
			className: this.#userFieldControl.getPhotoTemplate() === 'grid' ? 'disk-user-field-item-checked' : '',
			text: Loc.getMessage('DISK_UF_WIDGET_ALLOW_PHOTO_COLLAGE'),
			onclick: (event, menuItem: MenuItem): void => {
				this.#userFieldControl.setPhotoTemplateMode('manual');
				if (this.#userFieldControl.getPhotoTemplate() === 'grid')
				{
					this.#userFieldControl.setPhotoTemplate('gallery');
				}
				else
				{
					this.#userFieldControl.setPhotoTemplate('grid');
				}

				menuItem.getMenuWindow().close();
			}
		}];
	}

	show(button): void
	{
		this.getMenu(button).show();
	}

	toggle(button): void
	{
		if (this.#menu !== null && this.#menu.getPopupWindow().isShown())
		{
			this.#menu.close();
		}
		else
		{
			this.show(button);
		}
	}

	hide(): void
	{
		if (this.#menu !== null)
		{
			this.#menu.close();
		}
	}

	hasItems(): boolean
	{
		return this.#getItems().length > 0;
	}
}