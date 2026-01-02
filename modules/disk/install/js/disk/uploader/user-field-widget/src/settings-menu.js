import { Loc } from 'main.core';
import { Menu } from 'ui.system.menu';

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
		this.#menu ??= new Menu({
			bindElement: button.getContainer(),
			angle: true,
			autoHide: true,
			offsetLeft: 16,
			items: this.#getItems(),
			events: {
				onShow: (): void => button.select(),
				onClose: (): void => button.deselect(),
			},
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
			isSelected: this.#userFieldControl.getPhotoTemplate() === 'grid',
			title: Loc.getMessage('DISK_UF_WIDGET_ALLOW_PHOTO_COLLAGE'),
			onClick: (): void => {
				this.#userFieldControl.setPhotoTemplateMode('manual');
				if (this.#userFieldControl.getPhotoTemplate() === 'grid')
				{
					this.#userFieldControl.setPhotoTemplate('gallery');
				}
				else
				{
					this.#userFieldControl.setPhotoTemplate('grid');
				}
				this.#menu.updateItems(this.#getItems());
			},
		}];
	}

	show(button): void
	{
		this.getMenu(button).show();
	}

	toggle(button): void
	{
		if (this.#menu?.getPopup()?.isShown())
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
		this.#menu?.close();
	}

	hasItems(): boolean
	{
		return this.#getItems().length > 0;
	}
}
