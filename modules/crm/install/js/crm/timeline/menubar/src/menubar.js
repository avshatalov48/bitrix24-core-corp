/** @memberof BX.Crm.Timeline.MenuBar */

import { Dom } from 'main.core';
import Context from './context';
import Factory from './factory';
import Item from './item';

declare type MenuBarParams = {
	containerId: String,
	entityTypeId: Number,
	entityId: Number,
	entityCategoryId: ?Number,
	isReadonly: Boolean,
	menuId: String,
	items: MenuBarItemParams[],
	extras?: Extras,
}

declare type MenuBarItemParams = {
	id: String,
	settings: ?Object,
}

declare type Extras = {
	analytics: Object;
}

export class MenuBar
{
	#entityTypeId: Number = null;
	#entityId: Number = null;
	#entityCategoryId: Number = null;
	#isReadonly: Boolean = false;
	#container: HTMLElement = null;
	#items = {};
	#extras: Extras = {};
	#selectedItemId: String = null;
	#menu: BX.Main.interfaceButtons = null;

	constructor(id: String, params: MenuBarParams)
	{
		this.#entityTypeId = params.entityTypeId;
		this.#entityId = params.entityId;
		this.#entityCategoryId = params.entityCategoryId;
		this.#isReadonly = params.isReadonly;
		this.#extras = params.extras ?? {};

		this.#container = document.getElementById(params.containerId);
		const menuId = params.menuId ?? (BX.CrmEntityType.resolveName(this.#entityTypeId) + '_menu').toLowerCase();
		this.#menu = BX.Main.interfaceButtonsManager.getById(menuId);

		const context = new Context({
			entityTypeId: this.#entityTypeId,
			entityId: this.#entityId,
			entityCategoryId: this.#entityCategoryId,
			isReadonly: this.#isReadonly,
			menuBarContainer: this.#container,
			extras: this.#extras,
		});

		(params.items).forEach((itemData) => {
			const id = itemData.id;
			const item = Factory.createItem(id, context, itemData.settings ?? null);
			if (item)
			{
				item.addFinishEditListener(this.#onItemFinishEdit.bind(this));
				this.#items[id] = item;
			}
		});

		this.setActiveItemById(this.getFirstItemIdWithLayout());
	}

	getItemById(id: String): ?Item
	{
		return this.#items[id] ?? null;
	}

	getContainer(): HTMLElement
	{
		return this.#container;
	}

	onMenuItemClick(selectedItemId: String): void
	{
		if (this.#isReadonly)
		{
			return;
		}
		this.setActiveItemById(selectedItemId);
	}

	setActiveItemById(selectedItemId: String): Boolean
	{
		if (!selectedItemId || this.#selectedItemId === selectedItemId)
		{
			return false;
		}
		const menuBarItem = this.#items[selectedItemId];
		if (!menuBarItem)
		{
			return false;
		}
		menuBarItem.activate();

		if (!this.#isReadonly && menuBarItem.supportsLayout())
		{
			Object.keys(this.#items).forEach(itemId => {
				if (itemId !== selectedItemId)
				{
					this.#items[itemId].deactivate();
				}
			});
			this.#selectMenuItem(selectedItemId);
			this.#selectedItemId = selectedItemId;

			return true;
		}

		return false;
	}

	scrollIntoView(): void
	{
		this
			.getContainer()
			.scrollIntoView({
				behavior: 'smooth',
				block: 'end',
				inline: 'nearest',
			})
		;
	}

	#onItemFinishEdit(): void
	{
		this.setActiveItemById(this.getFirstItemIdWithLayout());
	}

	getFirstItemIdWithLayout(): ?Item
	{
		if (this.#isReadonly)
		{
			return null;
		}

		let firstId = null;
		this.#menu.getAllItems().forEach(function (itemElement) {
			if (firstId === null)
			{
				const id = itemElement.dataset.id;
				const item = this.#items[id];
				if (item && item.supportsLayout())
				{
					firstId = id;
				}
			}
		}.bind(this));

		return firstId;
	}

	static create(id: String, params: MenuBarParams): MenuBar
	{
		const self = new MenuBar(id, params);
		MenuBar.instances[id] = self;

		return self;
	}

	static getDefault(): ?MenuBar
	{
		return MenuBar.#defaultInstance;
	}
	static setDefault(instance: MenuBar): void
	{
		MenuBar.#defaultInstance = instance;
	}

	static getById(id): ?MenuBar
	{
		return MenuBar.instances[id] || null;
	}

	static #defaultInstance = null;
	static instances = {};

	#selectMenuItem(id: String): void
	{
		const activeItem = this.#menu.getItemById(this.#selectedItemId);
		const currentDiv = this.#menu.getItemById(id);
		let wasActiveInMoreMenu = false;
		if (currentDiv && activeItem !== currentDiv)
		{
			wasActiveInMoreMenu = this.#menu.isActiveInMoreMenu();
			Dom.addClass(currentDiv, this.#menu.classes.itemActive);

			if (this.#menu.getItemData)
			{
				const currentDivData = this.#menu.getItemData(currentDiv);
				currentDivData['IS_ACTIVE'] = true;
				if (BX.type.isDomNode(activeItem))
				{
					Dom.removeClass(activeItem, this.#menu.classes.itemActive);
					const activeItemData = this.#menu.getItemData(activeItem);
					activeItemData['IS_ACTIVE'] = false;
				}
			}

			const isActiveInMoreMenu = this.#menu.isActiveInMoreMenu();
			if (isActiveInMoreMenu || wasActiveInMoreMenu)
			{
				const submenu = this.#menu.getSubmenu();
				if (submenu)
				{
					submenu.getMenuItems().forEach((menuItem) => {
						const container = menuItem.getContainer();
						if (isActiveInMoreMenu && container.title === currentDiv.title)
						{
							Dom.addClass(container, this.#menu.classes.itemActive);
						}
						else if (wasActiveInMoreMenu && container.title === activeItem.title)
						{
							Dom.removeClass(container, this.#menu.classes.itemActive);
						}

					});
				}

				if (isActiveInMoreMenu)
				{
					Dom.addClass(this.#menu.getMoreButton(), this.#menu.classes.itemActive);
				}
				else if (wasActiveInMoreMenu)
				{
					Dom.removeClass(this.#menu.getMoreButton(), this.#menu.classes.itemActive);
				}
			}
		}

		this.#menu.closeSubmenu();
	}
}
