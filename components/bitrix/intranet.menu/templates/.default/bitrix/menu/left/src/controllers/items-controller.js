import getItem from '../items';
import Item from '../items/item';
import { DesktopApi } from 'im.v2.lib.desktop-api';

export default class ItemsController
{
	parentContainer: Element;
	container: Element;
	items: Map = new Map();
	#updateCountersLastValue = null

	constructor(container: Element)
	{
		this.parentContainer = container;
		this.container = container.querySelector(".menu-items");

		container
			.querySelectorAll('li.menu-item-block')
			.forEach(this.registerItem.bind(this))
		;
	}

	registerItem(node: Element): Item
	{
		const itemClass = getItem(node);
		const item = new itemClass(this.container, node);
		this.items.set(item.getId(), item);

		return item;
	}

	updateCounters(counters: Object, send: boolean): void
	{
		let countersDynamic = null;
		send = send !== false;
		[...Object.entries(counters)]
			.forEach(([counterId, counterValue]) => {
				[...this.#getItemsByCounterId(counterId)]
					.forEach((item) => {
						const {oldValue, newValue} = item.updateCounter(counterValue);

						if (
							(counterId.indexOf('crm_') < 0 || counterId.indexOf('crm_all') >= 0)
							&&
							(counterId.indexOf('tasks_') < 0 || counterId.indexOf('tasks_total') >= 0)
						)
						{
							countersDynamic = countersDynamic || 0;
							countersDynamic+= (newValue - oldValue);
						}
					})
				;

				if (send)
				{
					BX.localStorage.set('lmc-' + counterId, counterValue, 5);
				}

				if (typeof BXIM !== 'undefined')
				{
					if (this.#updateCountersLastValue === null)
					{
						this.#updateCountersLastValue = 0;
						[...this.items.entries()]
							.forEach(([id, item]) => {
								const res = item.getCounterValue();

								if (res > 0)
								{
									let counterId = 'doesNotMatter';
									if (id.indexOf('menu_crm') >= 0 || id.indexOf('menu_tasks') >= 0)
									{
										const counterNode = item.container.querySelector('[data-role="counter"]');
										if (counterNode)
										{
											counterId = counterNode.id;
										}
									}
									if (
										counterId === 'doesNotMatter'
										|| counterId.indexOf('crm_all') >= 0
										|| counterId.indexOf('tasks_total') >= 0
									)
									{
										this.#updateCountersLastValue += res;
									}
								}
							})
						;
					}
					else
					{
						this.#updateCountersLastValue += (countersDynamic !== null ? countersDynamic : 0);
					}

					const visibleValue = (this.#updateCountersLastValue > 99 ? '99+' : (
						this.#updateCountersLastValue < 0 ? '0' : this.#updateCountersLastValue
					));

					if (DesktopApi.isDesktop())
					{
						DesktopApi.setBrowserIconBadge(visibleValue);
					}
				}
			})
		;

	}

	#getItemsByCounterId(counterId: string): []
	{
		const result = [];
		[...this.items.values()]
			.forEach((item: Item) => {
				const node = item.container.querySelector('[data-role="counter"]');
				if (node && node.id.indexOf(counterId) >= 0)
				{
					result.push(item);
				}
			});
		return result;
	}

	decrementCounter(counters: Object): void
	{
		[...Object.entries(counters)]
			.forEach(([counterId, counterValue]) => {
				const item = this.#getItemsByCounterId(counterId).shift();
				if (item)
				{
					const value = item.getCounterValue();
					counters[counterId] = value > counterValue ? (value - counterValue) : 0;
				}
				else
				{
					delete counters[counterId];
				}
			})
		;
		this.updateCounters(counters, false);
	}
}