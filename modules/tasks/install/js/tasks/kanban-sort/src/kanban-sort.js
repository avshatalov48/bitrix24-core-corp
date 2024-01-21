import { MenuItem } from 'main.popup';
import { KanbanMenu } from './kanban-menu';
import { KanbanRequestSender } from './kanban-request/kanban-request-sender';
import { OrderType } from './order-type/order-type';

export class KanbanSort
{
	#kanbanMenu: KanbanMenu;
	#requestSender: KanbanRequestSender;
	constructor()
	{
		this.#bindMethods();
	}

	static getInstance()
	{
		return new this();
	}

	enableCustomSort(event: PointerEvent, item: MenuItem)
	{
		this.#requestSender = new KanbanRequestSender();
		this.#kanbanMenu = new KanbanMenu(item);
		if (this.#kanbanMenu.isCustomSortEnabled())
		{
			return;
		}

		this.#kanbanMenu.addItemsFromItemParams(this.selectCustomOrder.bind(this));

		this.#kanbanMenu.deselectAll();
		this.#kanbanMenu.select(item);
		const selectedItem = this.#kanbanMenu.find(OrderType.DESC);
		this.#kanbanMenu.select(selectedItem);
		this.#setOrder(selectedItem);

	}

	disableCustomSort(event: PointerEvent, item: MenuItem)
	{
		this.#requestSender = new KanbanRequestSender();
		this.#kanbanMenu = new KanbanMenu(item);
		if (!this.#kanbanMenu.isCustomSortEnabled())
		{
			return;
		}

		this.#kanbanMenu.removeSubItems();
		this.#kanbanMenu.deselectAll();
		this.#kanbanMenu.select(item);
		this.#setOrder(item);
	}

	selectCustomOrder(event: PointerEvent, item: MenuItem)
	{
		this.#requestSender = new KanbanRequestSender();
		this.#kanbanMenu = new KanbanMenu(item);
		if (!this.#kanbanMenu.isCustomSortEnabled())
		{
			return;
		}

		this.#kanbanMenu.deselectSubItems();
		this.#kanbanMenu.select(item);
		this.#setOrder(item);
	}

	#bindMethods()
	{
		this.enableCustomSort = this.enableCustomSort.bind(this);
		this.disableCustomSort = this.disableCustomSort.bind(this);
		this.selectCustomOrder = this.selectCustomOrder.bind(this);
	}

	#setOrder(item: MenuItem)
	{
		const order = item?.params?.order || '';
		this.#requestSender.setOrder(order)
	}
}