import { Text, Type } from 'main.core';
import type { SchemeItemData } from './scheme-item';
import { SchemeItem } from './scheme-item';

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

	constructor(currentItemId: string | null, items: SchemeItem[])
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
						// eslint-disable-next-line @bitrix24/bitrix24-rules/no-typeof
						`SchemeItem is invalid in Scheme constructor. Expected instance of SchemeItem, got ${typeof item}`,
					);
				}
			});
		}
	}

	static create(params: SchemeData): Scheme
	{
		const schemeItems = [];
		params.items.forEach((item: SchemeItemData) => {
			schemeItems.push(new SchemeItem(item));
		});

		return new Scheme(params.currentItemId, schemeItems);
	}

	getCurrentItem(): ?SchemeItem
	{
		if (!this.#items || this.#items.length === 0)
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
			if (entityTypeIds.length === 1 && [...entityTypeIds][0] === entityTypeId)
			{
				return item;
			}
		}

		return null;
	}

	getItemForEntityTypeIds(entityTypeIds: number[]): ?SchemeItem
	{
		const makeIntSet = (input: Array): Set<number> => {
			// Set - to remove possible duplicates in the array
			return new Set(input.map((id) => Text.toInteger(id)));
		};

		const targetEntityTypeIds = [...makeIntSet(entityTypeIds)];

		for (const item of this.#items)
		{
			const itemSet = makeIntSet(item.getEntityTypeIds());

			if (targetEntityTypeIds.length !== itemSet.size)
			{
				continue;
			}

			const notFoundTargetIds = targetEntityTypeIds.filter((entityTypeId) => !itemSet.has(entityTypeId));

			if (notFoundTargetIds.length <= 0)
			{
				return item;
			}
		}

		return null;
	}

	getAllEntityTypeIds(): number[]
	{
		const entityTypeIds = new Set();
		for (const item of this.#items)
		{
			for (const entityTypeId of item.getEntityTypeIds())
			{
				entityTypeIds.add(entityTypeId);
			}
		}

		return [...entityTypeIds];
	}
}
