import { Dom } from 'main.core';
import { MenuItem, Menu } from 'main.popup';
import { OrderType } from './order-type/order-type';

export class KanbanMenu
{
	#mainMenu: Menu;
	#mainItem: MenuItem;

	static SELECTED = 'menu-popup-item-accept';
	static DESELECTED = 'menu-popup-item-none';

	constructor(item: MenuItem)
	{
		this.#mainItem = item;
		this.#mainMenu = this.#mainItem.getMenuWindow();
	}

	select(item: MenuItem)
	{
		Dom.removeClass(item.layout.item, KanbanMenu.DESELECTED);
		Dom.addClass(item.layout.item, KanbanMenu.SELECTED);
	}

	deselect(item: MenuItem)
	{
		Dom.removeClass(item.layout.item, KanbanMenu.SELECTED);
		Dom.addClass(item.layout.item, KanbanMenu.DESELECTED);
	}

	deselectAll()
	{
		this.getItems().forEach(element => {
			this.deselect(element);
		})
	}

	deselectSubItems()
	{
		this.getSubItems().forEach(element => {
			this.deselect(element);
		});
	}

	isCustomSortEnabled()
	{
		const items = this.getItems();
		const ascSort = items.find(item => item.params?.type === 'sub' && item.params?.order === OrderType.ASC);
		const descSort = items.find(item => item.params?.type === 'sub' && item.params?.order === OrderType.DESC);

		return Boolean(ascSort) && Boolean(descSort);
	}

	addItemsFromItemParams(onSelectCallback)
	{
		this.#mainItem.params.forEach(subItem => {
			subItem.params = BX.parseJSON(subItem.params);
			if (subItem.params?.order)
			{
				subItem.onclick = onSelectCallback;
			}
			this.#mainMenu.addMenuItem(subItem);
		});
	}

	removeSubItems()
	{
		this.getSubItems().forEach(subItem => {
			this.#mainMenu.removeMenuItem(subItem.getId());
		});
	}

	getSubItems()
	{
		return this.getItems().filter(element => element.params?.type === 'sub');
	}

	getItems()
	{
		return this.#mainMenu.getMenuItems();
	}

	find(order: string = '')
	{
		return this.getItems().find(element => element.params?.order === order);
	}
}

