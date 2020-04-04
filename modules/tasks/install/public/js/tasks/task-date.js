BX.namespace("BX.Tasks");

BX.Tasks.Date = {

	Unit: {
		Milli: "milli",
		Second: "second",
		Minute: "minute",
		Hour: "hour",
		Day: "day",
		Week: "week",
		Month: "month",
		Quarter: "quarter",
		Year: "year"
	},

	getNext: function(date, unit, increment, firstWeekDay)
	{
		var newDate = new Date(date.getTime());
		firstWeekDay = typeof(firstWeekDay) !== "undefined" ? firstWeekDay : 1;
		increment = increment || 1;

		if (unit === this.Unit.Day)
		{
			newDate.setUTCMinutes(0, 0, 0);
			newDate = this.add(newDate, this.Unit.Day, increment);
		}
		else if (unit === this.Unit.Week)
		{
			var dayOfWeek = newDate.getUTCDay();
			newDate = this.add(newDate, this.Unit.Day, (7 * (increment - 1)) + (dayOfWeek < firstWeekDay ? (firstWeekDay - dayOfWeek) : (7 - dayOfWeek + firstWeekDay)));
		}
		else if (unit === this.Unit.Month)
		{
			newDate = this.add(newDate, this.Unit.Month, increment);
			newDate.setUTCDate(1);
		}
		else if (unit === this.Unit.Quarter)
		{
			newDate = this.add(newDate, this.Unit.Month, ((increment - 1) * 3) + (3 - (newDate.getUTCMonth() % 3)));
		}
		else if (unit === this.Unit.Year)
		{
			newDate = new Date(Date.UTC(newDate.getUTCFullYear() + increment, 0, 1));
		}
		else
		{
			newDate = this.add(date, unit, increment);
		}

		return newDate;
	},

	add: function(date, unit, increment)
	{
		var newDate = new Date(date.getTime());
		if (!unit || increment === 0)
		{
			return newDate;
		}

		switch (unit.toLowerCase()) {
			case this.Unit.Milli:
				newDate = new Date(date.getTime() + increment);
				break;
			case this.Unit.Second:
				newDate = new Date(date.getTime() + (increment * 1000));
				break;
			case this.Unit.Minute:
				newDate = new Date(date.getTime() + (increment * 60000));
				break;
			case this.Unit.Hour:
				newDate = new Date(date.getTime() + (increment * 3600000));
				break;
			case this.Unit.Day:
				newDate.setUTCDate(date.getUTCDate() + increment);
				break;
			case this.Unit.Week:
				newDate.setUTCDate(date.getUTCDate() + increment * 7);
				break;
			case this.Unit.Month:
				var day = date.getUTCDate();

				if (day > 28)
				{
					var firstDayOfMonth = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), 1));
					day = Math.min(day, this.getDaysInMonth(this.add(firstDayOfMonth, this.Unit.Month, increment)));
				}

				newDate.setUTCDate(day);
				newDate.setUTCMonth(newDate.getUTCMonth() + increment);
				break;
			case this.Unit.Quarter:
				newDate = this.add(date, this.Unit.Month, increment * 3);
				break;
			case this.Unit.Year:
				newDate.setUTCFullYear(date.getUTCFullYear() + increment);
				break
		}

		return newDate;
	},

	getUnitRatio: function(baseUnit, unit)
	{
		if (baseUnit === unit)
		{
			return 1;
		}
		else if (baseUnit === this.Unit.Day)
		{
			switch (unit)
			{
				case this.Unit.Quarter:
					return 90;
				case this.Unit.Month:
					return 30;
				case this.Unit.Week:
					return 7;
				case this.Unit.Hour:
					return 1 / 24;
				case this.Unit.Minute:
					return 1 / 1440;
			}
		}
		else if (baseUnit === this.Unit.Week)
		{
			switch (unit)
			{
				case this.Unit.Day:
					return 1 / 7;
				case this.Unit.Hour:
					return 1 / 168;
			}
		}
		else if (baseUnit === this.Unit.Hour)
		{
			switch (unit)
			{
				case this.Unit.Week:
					return 168;
				case this.Unit.Day:
					return 24;
				case this.Unit.Minute:
					return 1 / 60;
			}
		}
		else if (baseUnit === this.Unit.Month)
		{
			switch (unit)
			{
				case this.Unit.Day:
					return 1 / 30;
				case this.Unit.Year:
					return 12;
				case this.Unit.Quarter:
					return 3;
			}
		}
		else if (baseUnit === this.Unit.Year)
		{
			switch (unit)
			{
				case this.Unit.Quarter:
					return 1 / 4;
				case this.Unit.Month:
					return 1 / 12;
			}
		}
		else if (baseUnit === this.Unit.Quarter)
		{
			switch (unit)
			{
				case this.Unit.Year:
					return 4;
				case this.Unit.Month:
					return 1 / 3;
				case this.Unit.Day:
					return 1 / 90;
			}
		}
		else if (baseUnit === this.Unit.Minute)
		{
			switch (unit)
			{
				case this.Unit.Hour:
					return 60;
				case this.Unit.Second:
					return 1 / 60;
				case this.Unit.Milli:
					return 1 / 60000
			}
		}
		else if (baseUnit === this.Unit.Second)
		{
			switch (unit)
			{
				case this.Unit.Milli:
					return 1 / 1000;
			}
		}
		else if (baseUnit === this.Unit.Milli)
		{
			switch (unit)
			{
				case this.Unit.Week:
					return 604800000;
				case this.Unit.Day:
					return 86400000;
				case this.Unit.Hour:
					return 3600000;
				case this.Unit.Minute:
					return 60000;
				case this.Unit.Second:
					return 1000;
			}
		}

		return -1;
	},

	getDurationInUnit: function(start, end, unit)
	{
		var result = null;
		if (unit === this.Unit.Day)
		{
			result = Math.round(this.getDurationInDays(start, end));
		}
		else if (unit === this.Unit.Week)
		{
			result = Math.round(this.getDurationInDays(start, end) / 7);
		}
		else if (unit === this.Unit.Month)
		{
			result = Math.round(this.getDurationInMonths(start, end));
		}
		else if (unit === this.Unit.Hour)
		{
			result = Math.round(this.getDurationInHours(start, end));
		}
		else if (unit === this.Unit.Minute)
		{
			result = Math.round(this.getDurationInMinutes(start, end));
		}
		else if (unit === this.Unit.Second)
		{
			result = Math.round(this.getDurationInSeconds(start, end));
		}
		else if (unit === this.Unit.Year)
		{
			result = Math.round(this.getDurationInYears(start, end));
		}
		else if (unit === this.Unit.Quarter)
		{
			result = Math.round(this.getDurationInMonths(start, end) / 3);
		}
		else if (unit === this.Unit.Milli)
		{
			result = Math.round(this.getDurationInMilliseconds(start, end));
		}

		return result;
	},

	getDurationInMilliseconds: function(start, end)
	{
		return end - start;
	},

	getDurationInSeconds: function(start, end)
	{
		return (end - start) / 1000;
	},

	getDurationInMinutes: function(start, end)
	{
		return (end - start) / 60000;
	},

	getDurationInHours: function(start, end)
	{
		return (end - start) / 3600000;
	},

	getDurationInDays: function(start, end)
	{
		return (end - start) / 86400000;
	},

	getDurationInMonths: function(start, end)
	{
		return ((end.getUTCFullYear() - start.getUTCFullYear()) * 12) + (end.getUTCMonth() - start.getUTCMonth());
	},

	getDurationInYears: function(start, end)
	{
		return this.getDurationInMonths(start, end) / 12;
	},

	min: function(date1, date2)
	{
		return date1 < date2 ? date1 : date2;
	},

	max: function(date1, date2)
	{
		return date1 > date2 ? date1 : date2
	},

	isDate: function(date)
	{
		return date && Object.prototype.toString.call(date) === "[object Date]";
	},

	getDaysInMonth: function(date)
	{
		var month = date.getUTCMonth();
		var year = date.getUTCFullYear();
		var daysInMonth = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
		if (month !== 1 || (year % 4 === 0 && year % 100 !== 0 || year % 400 === 0))
		{
			return daysInMonth[month];
		}
		else
		{
			return 28;
		}
	},

	floorDate: function(date, unit, firstWeekDay) {

		var newDate = new Date(date.getTime());
		if (unit === this.Unit.Day)
		{
			newDate.setUTCHours(0, 0, 0, 0);
		}
		else if (unit === this.Unit.Week)
		{
			var day = newDate.getUTCDay();
			newDate.setUTCHours(0, 0, 0, 0);
			if (day !== firstWeekDay)
			{
				newDate = this.add(newDate, this.Unit.Day, - (day > firstWeekDay ? (day - firstWeekDay) : (7 - day - firstWeekDay)))
			}
		}
		else if (unit === this.Unit.Month)
		{
			newDate.setUTCHours(0, 0, 0, 0);
			newDate.setUTCDate(1);
		}
		else if (unit === this.Unit.Hour)
		{
			newDate.setUTCMinutes(0, 0, 0);
		}
		else if (unit === this.Unit.Minute)
		{
			newDate.setUTCSeconds(0);
			newDate.setUTCMilliseconds(0);
		}
		else if (unit === this.Unit.Second)
		{
			newDate.setUTCMilliseconds(0);
		}
		else if (unit === this.Unit.Year)
		{
			newDate = new Date(Date.UTC(date.getUTCFullYear(), 0, 1));
		}
		else if (unit === this.Unit.Quarter)
		{
			newDate.setUTCHours(0, 0, 0, 0);
			newDate.setUTCDate(1);
			newDate = this.add(newDate, this.Unit.Month, -(newDate.getUTCMonth() % 3));
		}

		return newDate;
	},

	ceilDate: function(date, unit, increment, firstWeekDay)
	{
		var newDate = new Date(date.getTime());
		if (unit === this.Unit.Week)
		{
			newDate.setUTCHours(0, 0, 0, 0);
			return this.add(this.floorDate(newDate, unit, firstWeekDay), unit, 1);
		}

		if (unit === this.Unit.Hour)
		{
			newDate.setUTCMinutes(0, 0, 0);
		}
		else if (unit === this.Unit.Minute)
		{
			newDate.setUTCSeconds(0, 0);
		}
		else if (unit === this.Unit.Second)
		{
			newDate.setUTCMilliseconds(0);
		}
		else
		{
			newDate.setUTCHours(0, 0, 0, 0);
		}

		return this.getNext(newDate, unit, increment);
	},

	convertToUTC: function(date)
	{
		if (!date)
		{
			return null;
		}
	
		return new Date(Date.UTC(
			date.getFullYear(), 
			date.getMonth(), 
			date.getDate(), 
			date.getHours(), 
			date.getMinutes(), 
			date.getSeconds(), 
			0
		));
	},

	convertFromUTC: function(date)
	{
		if (!date)
		{
			return null;
		}
		
		return new Date(
			date.getUTCFullYear(), 
			date.getUTCMonth(), 
			date.getUTCDate(), 
			date.getUTCHours(), 
			date.getUTCMinutes(), 
			date.getUTCSeconds(), 
			0
		);
	}
};