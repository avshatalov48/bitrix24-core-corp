import type { ConfigItemData } from "./config-item";
import { ConfigItem } from "./config-item";
import { SchemeItem } from "./scheme-item";
import { Scheme } from "./scheme";
import { Type } from "main.core";

/**
 * @memberOf BX.Crm.Conversion
 */
export class Config
{
	#entityTypeId: number;
	#items: ConfigItem[] = [];
	#scheme: Scheme;

	constructor(
		entityTypeId: number,
		items: ConfigItem[],
		scheme: Scheme
	)
	{
		this.#entityTypeId = Number(entityTypeId);

		if (Type.isArray(items))
		{
			items.forEach((item) => {
				if (item instanceof ConfigItem)
				{
					this.#items.push(item);
				}
				else
				{
					console.error(
						'ConfigItem is invalid in Config constructor. Expected instance of ConfigItem, got ' + (typeof item)
					);
				}
			})
		}

		if (scheme instanceof Scheme)
		{
			this.#scheme = scheme;
		}
		else
		{
			console.error('Scheme is invalid in Config constructor. Expected instance of Scheme, got ' + (typeof scheme));
		}
	}

	static create(entityTypeId: number, items: ConfigItemData[], scheme: Scheme)
	{
		const configItems = [];
		items.forEach((item: ConfigItemData) => {
			configItems.push(new ConfigItem(item))
		});

		return new Config(entityTypeId, configItems, scheme);
	}

	getEntityTypeId(): number
	{
		return this.#entityTypeId;
	}

	getItems(): ConfigItem[]
	{
		return this.#items;
	}

	getScheme(): Scheme
	{
		return this.#scheme;
	}

	updateFromSchemeItem(schemeItem: SchemeItem = null): Config
	{
		if (!schemeItem)
		{
			schemeItem = this.getScheme().getCurrentItem();
		}
		else
		{
			this.getScheme().setCurrentItemId(schemeItem.getId());
		}
		const activeEntityTypeIds = schemeItem.getEntityTypeIds();

		this.#items.forEach((item) => {
			const isActive = activeEntityTypeIds.indexOf(item.getEntityTypeId()) > -1;
			item.setEnableSync(isActive);
			item.setActive(isActive);
		});

		return this;
	}

	getItemByEntityTypeId(entityTypeId: number): ?ConfigItem
	{
		for (const item of this.#items)
		{
			if (item.getEntityTypeId() === entityTypeId)
			{
				return item;
			}
		}

		return null;
	}

	externalize(): Object
	{
		const data = {};

		this.getItems().forEach((item) => {
			data[BX.CrmEntityType.resolveName(item.getEntityTypeId()).toLowerCase()] = item.externalize();
		});

		return data;
	}

	updateItems(items: ConfigItemData[]): self
	{
		this.#items = [];
		items.forEach((item) => {
			this.#items.push(new ConfigItem(item))
		});

		return this;
	}
}
