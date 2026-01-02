import { Type, Extension } from 'main.core';
import { MenuItem } from 'main.popup';
import { DefaultItem } from './item/default-item';
import { NotificationItem } from './item/notification-item';
import './style.css';

export class Menu
{
	#isDeadlineNotificationAvailable: boolean;

	get menuItems(): MenuItem[]
	{
		const defaultMenuItem = new DefaultItem(this.isDeadlineNotificationAvailable);
		const menuItems = [defaultMenuItem.getItem()];

		if (this.isDeadlineNotificationAvailable)
		{
			const notificationMenuItem = new NotificationItem();
			menuItems.push(notificationMenuItem.getItem());
		}

		return menuItems;
	}

	get isDeadlineNotificationAvailable(): boolean
	{
		if (!Type.isUndefined(this.#isDeadlineNotificationAvailable))
		{
			return this.#isDeadlineNotificationAvailable;
		}

		const settings = Extension.getSettings('tasks.deadline.menu');
		this.#isDeadlineNotificationAvailable = Boolean(settings.get('isDeadlineNotificationAvailable'));

		return this.#isDeadlineNotificationAvailable;
	}
}
