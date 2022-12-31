import { Text, Type } from "main.core";
import { SortParams } from "./sort-params";
import { Type as SortType } from "./type";
import { SettingsController } from "./settings-controller";

/**
 * @memberOf BX.CRM.Kanban.Sort
 */
export class Sorter
{
	#sortType: string;
	#items: BX.CRM.Kanban.Item[];

	static createWithCurrentSortType(items: BX.CRM.Kanban.Item[]): Sorter
	{
		return new Sorter(
			SettingsController.Instance.getCurrentSettings().getCurrentType(),
			items,
		);
	}

	constructor(sortType: string, items: BX.CRM.Kanban.Item[])
	{
		if (!SortType.isDefined(sortType))
		{
			throw new Error('Undefined sort type');
		}
		this.#sortType = sortType;

		this.#items = Type.isArray(items) ? items : [];
	}

	getSortType()
	{
		return this.#sortType;
	}

	/**
	 * Returns items sorted in descending order. Beginning of array - is column top, end - column bottom.
	 *
	 * @returns {BX.CRM.Kanban.Item[]}
	 */
	getSortedItems(): BX.CRM.Kanban.Item[]
	{
		let extractValue: (BX.CRM.Kanban.Item) => number;
		if (this.#sortType === SortType.BY_ID)
		{
			extractValue = this.#extractId;
		}
		else if (this.#sortType === SortType.BY_LAST_ACTIVITY_TIME)
		{
			extractValue = this.#extractTimestamp;
		}
		else
		{
			throw new Error('Unknown sort type');
		}

		const sortedItems = Array.from(this.#items);

		sortedItems.sort((left, right) => {
			return extractValue(right) - extractValue(left);
		});

		return sortedItems;
	}

	#extractId(item: BX.CRM.Kanban.Item): number
	{
		return Text.toInteger(item.getData()?.sort?.id);
	}

	#extractTimestamp(item: BX.CRM.Kanban.Item): number
	{
		return Text.toInteger(item.getData()?.sort?.lastActivityTimestamp);
	}

	calcBeforeItem(item: BX.CRM.Kanban.Item): ?BX.CRM.Kanban.Item
	{
		const sortParams: ?SortParams = item.getData().sort;

		return Type.isPlainObject(sortParams) ? this.calcBeforeItemByParams(sortParams) : null;
	}

	calcBeforeItemByParams(sort: SortParams): ?BX.CRM.Kanban.Item
	{
		const id = Text.toInteger(sort?.id);
		if (id <= 0)
		{
			return null;
		}

		if (this.#sortType === SortType.BY_ID)
		{
			return this.#calcById(id);
		}
		else if (this.#sortType === SortType.BY_LAST_ACTIVITY_TIME)
		{
			const lastActivityTimestamp = Text.toInteger(sort?.lastActivityTimestamp);
			if (lastActivityTimestamp <= 0)
			{
				return null;
			}

			return this.#calcByLastActivityTime(id, lastActivityTimestamp);
		}
		else
		{
			throw new Error('Unknown sort type');
		}
	}

	#calcById(id: number): ?BX.CRM.Kanban.Item
	{
		const notSortedItems = this.#items;
		for (let index = 0; index < notSortedItems.length; index++)
		{
			const item = notSortedItems[index];
			if (this.#extractId(item) === id)
			{
				return this.#findFirstDifferentItem(id, notSortedItems, index);
			}
		}

		return null;
	}

	#calcByLastActivityTime(id: number, lastActivityTimestamp: number): ?BX.CRM.Kanban.Item
	{
		const sortedItems = this.getSortedItems();
		for (let index = 0; index < sortedItems.length; index++)
		{
			const item = sortedItems[index];
			if (this.#extractTimestamp(item) <= lastActivityTimestamp)
			{
				return this.#findFirstDifferentItem(id, sortedItems, index);
			}
		}

		if (sortedItems.length > 0)
		{
			// item should be placed at bottom
			return sortedItems[sortedItems.length - 1];
		}

		// no items, place item on top
		return null;
	}

	#findFirstDifferentItem(itemId: number, items: BX.CRM.Kanban.Item[], startIndex: number): ?BX.CRM.Kanban.Item
	{
		for (let index = startIndex; index < items.length; index++)
		{
			const item = items[index];

			if (itemId !== this.#extractId(item))
			{
				return item;
			}
		}

		return null;
	}
}
