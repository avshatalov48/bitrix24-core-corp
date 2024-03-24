import { CreationMenu } from '../creation-menu';
import { Loc } from 'main.core';
import { sendData } from 'ui.analytics';
export class Task
{
	static create(link: string = ''): JSON
	{
		return {
			tabId: CreationMenu.MENU_ID,
			text: Loc.getMessage('TASKS_CREATION_MENU_CREATE_TASK'),
			href: link,
			onclick: (event, menuItem) => {
				menuItem.getMenuWindow().close();
			},
		};
	}
}