import { Loc } from 'main.core';
import { CreationMenu } from '../creation-menu';

export class TemplateList
{
	static create(link: string = ''): JSON
	{
		return {
			tabId: CreationMenu.MENU_ID,
			text: Loc.getMessage('TASKS_CREATION_MENU_TEMPLATE_LIST'),
			href: link,
			target: '_top',
		}
	}
}