import { Type } from 'main.core';

export class Filter
{
	#filter: BX.Main.Filter;

	constructor(options: Object)
	{
		this.#filter = BX.Main.filterManager.getById(options.filterId);
	}

	toggleField(name: string, value: string, resetAllFields: boolean): boolean
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

		if (this.isFieldValueAlreadyApplied(name, filteredValues, resetAllFields))
		{
			return false;
		}

		if (resetAllFields)
		{
			this.#filter.getApi().setFields({});
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

	isFieldValueAlreadyApplied(name: string, setValues: Array, withoutOtherFilters: boolean): boolean
	{
		const filterRows = this.getFilterRows();
		const currentValuesObject = filterRows[name];
		if (!Type.isObject(currentValuesObject))
		{
			return false;
		}

		if (withoutOtherFilters && this.isSomeOtherFilterPresent(name))
		{
			return false;
		}

		const currentValuesArray = Object.values(currentValuesObject);
		for (const setValue of setValues)
		{
			if (!currentValuesArray.includes(setValue))
			{
				return false;
			}
		}

		if (withoutOtherFilters)
		{
			for (const currentValue of currentValuesArray)
			{
				if (!setValues.includes(currentValue))
				{
					return false;
				}
			}
		}

		return true;
	}

	isSomeOtherFilterPresent(name: string): boolean
	{
		for (const [key, value] of Object.entries(this.getFilterRows()))
		{
			const isPresent = Type.isStringFilled(value) || Type.isArrayFilled(value);
			if (key !== name && isPresent)
			{
				return true;
			}
		}

		return false;
	}
}
