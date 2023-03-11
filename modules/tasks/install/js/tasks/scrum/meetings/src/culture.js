export type CultureData = {
	dayMonthFormat: string,
	longDateFormat: string,
	shortTimeFormat: string
}

export class Culture
{
	static #instance;

	static getInstance()
	{
		if (!Culture.#instance)
		{
			Culture.#instance = new Culture();
		}

		return Culture.#instance;
	}

	setData(data: CultureData)
	{
		this.data = data;
	}

	getDayMonthFormat(): string
	{
		return this.data.dayMonthFormat;
	}

	getLongDateFormat(): string
	{
		return this.data.longDateFormat;
	}

	getShortTimeFormat(): string
	{
		return this.data.shortTimeFormat;
	}
}