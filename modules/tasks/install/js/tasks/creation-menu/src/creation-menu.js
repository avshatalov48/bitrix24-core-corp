import { Uri } from 'main.core';
import { Menu, MenuItem, MenuManager } from 'main.popup';
import { Delimiter } from './items/delimiter';
import { Task } from './items/task';
import { TaskByTemplate } from './items/task-by-template';
import { TemplateList } from './items/template-list';

type Options = {
	bindElement: HTMLElement;
	createTaskLink: string,
	templatesListLink: string,
}

export class CreationMenu
{
	bindElement: HTMLElement;
	createTaskLink: string;
	templatesListLink: string;
	menu: Menu;

	static MENU_ID = 'tasks-creation-menu';

	static toggle(options: Options)
	{
		const creationMenu = MenuManager.getMenuById(CreationMenu.MENU_ID);
		if (creationMenu)
		{
			creationMenu.toggle();
		}
		else
		{
			(new this(options)).createMenu().toggle();
		}
	}

	constructor(options: Options)
	{
		this.bindElement = options.bindElement;
		this.createTaskLink = options.createTaskLink;
		this.templatesListLink = options.templatesListLink;
	}

	createMenu(): Menu
	{
		this.menu = MenuManager.create({
			id: CreationMenu.MENU_ID,
			bindElement: this.bindElement,
			closeByEsc: true,
			items: this.getCreationItems(),
		});

		return this.menu;
	}

	getCreationItems(): MenuItem[]
	{
		const createLink = Uri.addParam(
			this.createTaskLink,
			{
				ta_sec: 'space',
				ta_el: 'create_button',
			},
		);

		return [
			Task.create(createLink),
			TaskByTemplate.create(createLink),
			Delimiter.create(),
			TemplateList.create(this.templatesListLink),
		];
	}
}
