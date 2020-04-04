BX.namespace("BX.Tasks");

BX.Tasks.Calendar = (function() {

	function Calendar(settings)
	{
		//Defaults
		this.weekends = { 0: true, 6: true };
		this.holidays = {};

		//00:00-24:00
		this.worktime = [{
			start: { hours: 0, minutes: 0 },
			end: { hours: 24, minutes: 0 }
		}];

		this.setWeekends(settings.weekEnds);
		this.setHolidays(settings.holidays);
		this.setWorkTime(settings.worktime);
	}
	Calendar.adaptSettings = function(cwt) // map php-based json settings to a format suitable by Calendar
	{
		var cOpts = {};
		if(BX.type.isPlainObject(cwt))
		{
			var wh = cwt.HOURS;
			var we = cwt.WEEKEND;
			var dayMap = {
				'MO': 1,
				'TU': 2,
				'WE': 3,
				'TH': 4,
				'FR': 5,
				'SA': 6,
				'SU': 0
			};

			var pad = function(num)
			{
				num = num.toString();

				if(num.length == 0)
				{
					return '00';
				}
				if(num.length == 1)
				{
					return '0'+num;
				}

				return num;
			};

			cOpts.worktime = pad(wh.START.H)+':'+pad(wh.START.M)+'-'+pad(wh.END.H)+':'+pad(wh.END.M);

			cOpts.weekEnds = [];
			BX.Tasks.each(we, function(day){
				cOpts.weekEnds.push(dayMap[day]);
			});
			cOpts.weekStart = dayMap[cwt.WEEK_START];

			cOpts.holidays = [];
			for(var k in cwt.HOLIDAYS)
			{
				cOpts.holidays.push({
					month: 	parseInt(cwt.HOLIDAYS[k].M) - 1,
					day: 	parseInt(cwt.HOLIDAYS[k].D)
				});
			}
		}

		return cOpts;
	};

	Calendar.prototype.getClosestWorkTime = function(date, isForward)
	{
		var startDate = isForward ? date : null;
		var endDate = isForward ? null : date;

		this.processEachDay(startDate, endDate, isForward, function (start, end) {
			date = isForward ? start : end;
			return false;
		});

		return new Date(date.getTime());
	};

	Calendar.prototype.calculateDuration = function(startDate, endDate)
	{
		var duration = 0;
		if (startDate < endDate)
		{
			this.processEachDay(startDate, endDate, true, function (start, end) {
				duration += end - start;
			});
		}
		else
		{
			this.processEachDay(endDate, startDate, true, function (start, end) {
				duration -= end - start;
			});
		}

		return duration;
	};

	Calendar.prototype.calculateStartDate = function(endDate, duration)
	{
		var newDate = null;
		this.processEachDay(null, endDate, false, function(start, end) {
			var interval = end - start;
			if (interval >= duration)
			{
				newDate = new Date(end.getTime() - duration);
				return false;
			}
			else
			{
				duration -= interval;
			}
		});

		return newDate;
	};

	Calendar.prototype.calculateEndDate = function(startDate, duration)
	{
		var newDate = null;
		//console.log("startDate", startDate, "duration", duration);
		this.processEachDay(startDate, null, true, function(start, end) {
			var interval = end - start;
			//console.log(interval, duration);
			if (interval >= duration)
			{
				newDate = new Date(start.getTime() + duration);
				return false;
			}
			else
			{
				duration -= interval;
			}
		});

		//console.log("calculateEndDate", "startDate", startDate, duration, "newDate", newDate);
		return newDate;
	};

	Calendar.prototype.processEachDay = function(startDate, endDate, isForward, callback)
	{
		var currentDate = new Date(isForward ? startDate.getTime() : endDate.getTime());
		var endless = isForward ? !endDate : !startDate;

		//console.log("startDate", startDate, "endDate", endDate);
		while (endless || (isForward ? currentDate < endDate : currentDate > startDate))
		{
			var intervals = this.getWorkHours(currentDate);
			for (var i = isForward ? 0 : intervals.length - 1; (isForward ? i < intervals.length : i >= 0); (isForward ? i++ : i--))
			{
				var interval = intervals[i];
				var intervalStart = interval.startDate;
				var intervalEnd = interval.endDate;

				//console.log("intervalStart", intervalStart, "intervalEnd", intervalEnd);

				if ((endDate !== null && intervalStart > endDate) || (startDate !== null && intervalEnd < startDate))
				{
					continue;
				}

				var availableStart = startDate !== null && intervalStart < startDate ? startDate : intervalStart;
				var availableEnd = endDate !== null && intervalEnd > endDate ? endDate : intervalEnd;

				//console.log("availableStart", availableStart, "availableEnd", availableEnd);
				if (callback.call(this, availableStart, availableEnd) === false)
				{
					return false;
				}
			}

			currentDate = BX.Tasks.Date.add(BX.Tasks.Date.floorDate(currentDate, BX.Tasks.Date.Unit.Day), BX.Tasks.Date.Unit.Day, isForward ? 1 : -1);
		}
	};

	Calendar.prototype.getWorkHours = function(date)
	{
		var hours = [];
		if (this.isWeekend(date) || this.isHoliday(date))
		{
			return hours;
		}

		return this.getWorkIntervals(date);
	};

	Calendar.prototype.getWorkIntervals = function(date)
	{
		var year = date.getUTCFullYear();
		var month = date.getUTCMonth();
		var day = date.getUTCDate();

		var hours = [];
		for (var i = 0; i < this.worktime.length; i++)
		{
			var time = this.worktime[i];
			hours.push({
				startDate: new Date(Date.UTC(year, month, day, time.start.hours, time.start.minutes)),
				endDate: new Date(Date.UTC(year, month, day, time.end.hours, time.end.minutes))
			});
		}

		return hours;
	};

	Calendar.prototype.getWorkDayDuration = function()
	{
		var duration = 0;
		var intervals = this.getWorkIntervals(new Date());
		for (var i = 0; i < intervals.length; i++)
		{
			duration += intervals[i].endDate - intervals[i].startDate;
		}

		return duration;
	};

	Calendar.prototype.isWorkTime = function(date)
	{
		if (this.isWeekend(date) || this.isHoliday(date))
		{
			return false;
		}

		var isWorkTime = null;
		this.processEachDay(date, null, true, function (start, end) {
			isWorkTime = date >= start && date <= end;
			return false;
		});

		return isWorkTime;
	};

	Calendar.prototype.isHoliday = function(date)
	{
		var month = date.getUTCMonth();
		var day = date.getUTCDate();
		return this.holidays[month + "_" + day] ? true : false;
	};

	Calendar.prototype.isWeekend = function(date)
	{
		var day = date.getUTCDay();
		return this.weekends[day] ? true : false;
	};

	Calendar.prototype.setWeekends = function(weekends)
	{
		if (!BX.type.isArray(weekends))
		{
			return;
		}

		this.weekends = {};
		for (var i = 0; i < weekends.length; i++)
		{
			var day = weekends[i];
			if (day >= 0 && day <= 6)
			{
				this.weekends[day] = true;
			}
		}
	};

	Calendar.prototype.setHolidays = function(holidays)
	{
		if (!BX.type.isArray(holidays))
		{
			return;
		}

		this.holidays = {};
		for (var j = 0; j < holidays.length; j++)
		{
			var holiday = holidays[j];
			var validMonth = BX.type.isNumber(holiday.month) && holiday.month >= 0 && holiday.month <= 11;
			var validDay = BX.type.isNumber(holiday.day) && holiday.month >= 0 && holiday.month <= 31;

			if (validMonth && validDay)
			{
				this.holidays[holiday.month + "_" + holiday.day] = true;
			}
		}
	};

	Calendar.prototype.setWorkTime = function(worktime)
	{
		if (BX.type.isNotEmptyString(worktime))
		{
			worktime = [worktime];
		}

		if (!BX.type.isArray(worktime))
		{
			return;
		}

		var times = [];
		for (var i = 0; i < worktime.length; i++)
		{
			var time = worktime[i];
			var regex = /(\d\d):(\d\d)-(\d\d):(\d\d)/;
			var matches = regex.exec(time);
			if (!matches)
			{
				continue;
			}

			var startHours = parseInt(matches[1], 10);
			var startMinutes = parseInt(matches[2], 10);
			var endHours = parseInt(matches[3], 10);
			var endMinutes = parseInt(matches[4], 10);

			times.push({
				start: { hours: startHours, minutes: startMinutes, time: startHours * 60 +  startMinutes},
				end: { hours: endHours, minutes: endMinutes, time: endHours * 60 + endMinutes }
			});
		}

		if (this.isWorkTimeCorrect(times))
		{
			this.worktime = times;
		}
	};

	Calendar.prototype.isWorkTimeCorrect = function(times)
	{
		if (!times.length)
		{
			return false;
		}

		times.sort(function (a, b) {
			return a.start.time - b.start.time;
		});

		for (var i = 0; i < times.length; i++)
		{
			var time = times[i];
			if (time.start.hours < 0 || time.start.hours > 23 || time.end.hours < 0 || time.end.hours > 24)
			{
				return false;
			}

			if (time.start.minutes < 0 || time.start.minutes > 59 || time.end.minutes < 0 || time.end.minutes > 59)
			{
				return false;
			}

			if (time.start.time > time.end.time)
			{
				return false;
			}

			if (i > 0)
			{
				var prevTime = times[i - 1];
				if (prevTime.end.time > time.start.time)
				{
					return false;
				}
			}
		}

		return true;
	};

	return Calendar;

})();