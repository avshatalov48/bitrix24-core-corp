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

	toString(date)
	{
		if (!Type.isDate(date))
		{
			date = new Date(date);
		}

		if (isNaN(date))
		{
			throw new Error("DateFormatter: Invalid date.");
		}

		const addZero = num => (num >= 0 && num <= 9) ? '0' + num : num;

		const year = date.getFullYear();
		const month = addZero(date.getMonth() + 1);
		const day = addZero(date.getDate());

		return year + '-' + month + '-' + day;
	}
}

const dateFormatter = new DateFormatter();

export {dateFormatter as DateFormatter};