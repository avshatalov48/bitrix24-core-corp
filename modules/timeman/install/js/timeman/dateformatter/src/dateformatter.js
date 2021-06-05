import {Type} from 'main.core';

class DateFormatter
{
	init(params)
	{
		this.formatLong = params.long;
		this.formatShort = params.short;

		this.isInitialized = true;
	}

	isInit()
	{
		return this.isInitialized;
	}

	toLong(date)
	{
		if (!this.isInit())
		{
			throw new Error("DateFormatter has not been initialized.");
		}

		return this.format(this.formatLong, date);
	}

	toShort(date)
	{
		if (!this.isInit())
		{
			throw new Error("DateFormatter has not been initialized.");
		}

		return this.format(this.formatShort, date);
	}

	format(format, date)
	{
		if (!Type.isDate(date))
		{
			date = new Date(date);
		}

		if (isNaN(date))
		{
			throw new Error("DateFormatter: Invalid date.");
		}

		return BX.date.format(format, date);
	}
}

const dateFormatter = new DateFormatter();

export {dateFormatter as DateFormatter};