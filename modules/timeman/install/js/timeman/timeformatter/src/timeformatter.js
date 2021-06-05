import {Type} from 'main.core';

class TimeFormatter
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

	toLong(time)
	{
		if (!this.isInit())
		{
			throw new Error("TimeFormatter has not been initialized.");
		}

		return this.format(this.formatLong, time);
	}

	toShort(time)
	{
		if (!this.isInit())
		{
			throw new Error("TimeFormatter has not been initialized.");
		}

		return this.format(this.formatShort, time);
	}

	format(format, time)
	{
		if (!Type.isDate(time))
		{
			time = new Date(time);
		}

		if (isNaN(time))
		{
			throw new Error("TimeFormatter: Invalid time. An object of type date was expected.");
		}

		return BX.date.format(format, time);
	}
}

const timeFormatter = new TimeFormatter();

export {timeFormatter as TimeFormatter};