import type { SchemeItemData } from "./scheme-item";
import { SchemeItem } from "./scheme-item";
import { Type } from "main.core";

export type SchemeData = {
	currentItemId: string,
	items: SchemeItemData[],
}

/**
 * @memberOf BX.Crm.Conversion
 */
export class Scheme
{
	#currentItemId: string;
	#items: SchemeItem[] = [];

	constructor(currentItemId: string|null, items: SchemeItem[])
	{
		this.#currentItemId = Type.isNull(currentItemId) ? currentItemId : String(currentItemId);

		if (Type.isArray(items))
		{
			items.forEach((item) => {
				if (item instanceof SchemeItem)
				{
					this.#items.push(item);
				}
				else
				{
					console.error(
						'SchemeItem is invalid in Scheme constructor. Expected instance of SchemeItem, got ' + (typeof item)
					);
				}
			});
		}
	}

	static create(params: SchemeData)
	{
		const schemeItems = [];
		params.items.forEach((item: SchemeItemData) => {
			schemeItems.push(new SchemeItem(item));
		});

		return new Scheme(params.currentItemId, schemeItems);
	}

	getCurrentItem(): ?SchemeItem
	{
		if (!this.#items || !this.#items.length)
		{
			return null;
		}
		const item = this.getItemById(this.#currentItemId);

		return item || this.#items[0];
	}

	setCurrentItemId(currentItemId: string)
	{
		this.#currentItemId = currentItemId;
	}

	getItems(): SchemeItem[]
	{
		return this.#items;
	}

	getItemById(itemId: string): ?SchemeItem
	{
		for (const item of this.#items)
		{
			if (item.getId() === itemId)
			{
				return item;
			}
		}

		return null;
	}

	getItemForSingleEntityTypeId(entityTypeId: number): ?SchemeItem
	{
		for (const item of this.#items)
		{
			const entityTypeIds = item.getEntityTypeIds();
			if (entityTypeIds.length === 1 && Array.from(entityTypeIds)[0] === entityTypeId)
			{
				return item;
			}
		}

		return null;
	}
}
