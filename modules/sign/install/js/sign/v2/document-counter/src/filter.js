import { Type } from 'main.core';

export class Filter
{
	#filter: BX.Main.Filter;

	constructor(options: Object)
	{
		this.#filter = BX.Main.filterManager.getById(options.filterId);
	}

	toggleField(name: string, value: string): boolean
	{
		const field = this.#filter.getFieldByName(name);

		if (!Type.isPlainObject(field) || field === null)
		{
			return false;
		}

		const items = value.split('_');

		// eslint-disable-next-line no-shadow

		const filteredValues = field.ITEMS
			.filter((item) => items.includes(item.VALUE))
			.map((item) => item.VALUE)
		;
		// const fieldValue = field.ITEMS.find((item) => item.VALUE === value);
		if (filteredValues.length === 0)
		{
			return false;
		}

		this.#filter.getApi().extendFilter({
			[name]: { ...filteredValues },
		});

		return true;
	}

	getFilterRows(): Array
	{
		return this.#filter.getFilterFieldsValues();
	}

	deactivate()
	{
		this.#filter.getApi().setFields({});
		this.#filter.getApi().apply();
	}
}
