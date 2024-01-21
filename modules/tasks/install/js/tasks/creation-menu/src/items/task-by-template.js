import { CreationMenu } from '../creation-menu';
import { ajax, Loc } from 'main.core';
import { Loading } from './loading';

export class TaskByTemplate
{
	static create(link: string = ''): JSON
	{
		return {
			tabId: CreationMenu.MENU_ID,
			text: Loc.getMessage('TASKS_CREATION_MENU_CREATE_TASK_BY_TEMPLATE'),
			cacheable: true,
			items: [Loading.create()],
			events: {
				onSubMenuShow: (event) => {
					const item = new TaskByTemplate();
					item.getTemplates().then(
						(response) => {
							if (this.isTemplateListLoaded)
							{
								return;
							}
							this.isTemplateListLoaded = true;
							item.addSubItems(event.getTarget(), response, link);
						},
						() => {
							this.isTemplateListLoaded = true;
							item.addError(event.getTarget());
						},
					);
				},
			},
		};
	}

	getTemplates(): Promise
	{
		return ajax.runComponentAction('bitrix:tasks.templates.list', 'getList', {
			mode: 'class',
			data: {
				select: ['ID', 'TITLE'],
				order: { ID: 'DESC' },
				filter: { ZOMBIE: 'N' },
			},
		});
	}

	addSubItems(menuItem: MenuItem, response, link: string): void
	{
		if (response.data.length > 0)
		{
			response.data.forEach(item => {
				menuItem.getSubMenu().addMenuItem({
					text: BX.util.htmlspecialchars(item.TITLE),
					href: link + '&TEMPLATE=' + item.ID,
					onclick: function() {
						menuItem.getMenuWindow().close();
					},
				});
			});
		}
		else
		{
			menuItem.getSubMenu().addMenuItem({
				text: Loc.getMessage('TASKS_CREATION_MENU_EMPTY_TEMPLATE_LIST'),
			});
		}

		this.removeLoading(menuItem);
	}

	addError(menuItem: MenuItem): void
	{
		menuItem.getSubMenu().addMenuItem({
			text: Loc.getMessage('TASKS_CREATION_MENU_ERROR_LOAD_TEMPLATE_LIST'),
		});

		this.removeLoading(menuItem);
	}

	removeLoading(menuItem: MenuItem): void
	{
		menuItem.getSubMenu().removeMenuItem(Loading.ID);
	}
}