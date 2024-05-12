/**
 * @module calendar/model/sharing/range
 */
jn.define('calendar/model/sharing/range', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { shortTime } = require('utils/date/formats');
	const { Moment } = require('utils/date');
	const { capitalize } = require('utils/string');

	/**
	 * @class Range
	 */
	class Range
	{
		constructor(range)
		{
			this.id = BX.prop.getNumber(range, 'id', 0);
			this.from = BX.prop.getNumber(range, 'from', 0);
			this.to = BX.prop.getNumber(range, 'to', 0);
			this.weekdays = BX.prop.getArray(range, 'weekdays', [1, 2, 3, 4, 5]);
			this.weekStart = BX.prop.getNumber(range, 'weekStart', 1);
			this.slotSize = BX.prop.getNumber(range, 'slotSize', 30);
			this.isNew = BX.prop.getBoolean(range, 'isNew', false);
		}

		/**
		 * @returns {number}
		 */
		getId()
		{
			return this.id;
		}

		/**
		 * @returns {number}
		 */
		getFrom()
		{
			return this.from;
		}

		/**
		 * @param value {number}
		 */
		setFrom(value)
		{
			this.from = parseInt(value, 10);

			if (this.from + this.slotSize > this.to)
			{
				this.to = this.from + this.slotSize;
			}
		}

		/**
		 * @returns {number}
		 */
		getTo()
		{
			return this.to;
		}

		/**
		 * @param value {number}
		 */
		setTo(value)
		{
			this.to = value;
		}

		/**
		 * @param {number} slotSize
		 */
		setSlotSize(slotSize)
		{
			this.slotSize = slotSize;

			const maxFrom = 24 * 60 - this.slotSize;

			if (this.from > maxFrom)
			{
				this.from = maxFrom;
				this.to = this.from + this.slotSize;
			}
			else if (this.from + this.slotSize > this.to)
			{
				this.to = this.from + this.slotSize;
			}
		}

		/**
		 * @returns {number[]}
		 */
		getWeekDays()
		{
			return this.weekdays;
		}

		/**
		 * @param days {number[]}
		 */
		setWeekDays(days)
		{
			this.weekdays = days;
		}

		/**
		 * @returns {string}
		 */
		getFromFormatted()
		{
			return this.formatMinutes(this.getFrom());
		}

		/**
		 * @returns {string}
		 */
		getToFormatted()
		{
			return this.formatMinutes(this.getTo());
		}

		/**
		 * @returns {string}
		 */
		getWeekdaysFormatted()
		{
			if (this.weekdays.sort().join(',') === [1, 2, 3, 4, 5].sort().join(','))
			{
				return Loc.getMessage('M_CALENDAR_SETTINGS_WORKDAYS');
			}

			const weekdaysLoc = this.getWeekdaysLoc(this.weekdays.length === 1);

			return this.sortWeekdays(this.weekdays).map((weekday) => weekdaysLoc[weekday]).join(', ');
		}

		sortWeekdays(weekdays)
		{
			return weekdays
				.map((w) => (w < this.weekStart ? w + 10 : w))
				.sort((a, b) => a - b)
				.map((w) => w % 10);
		}

		/**
		 * @returns {{value: {number}, name: {string}}[]}
		 */
		getAvailableTimeFrom()
		{
			const timeStamps = [];

			const maxFrom = 24 * 60 - this.slotSize;
			for (let hour = 0; hour <= 24; hour++)
			{
				if (hour * 60 <= maxFrom)
				{
					timeStamps.push({
						value: hour * 60,
						name: this.formatTime(hour, 0),
					});
				}

				if (hour !== 24 && hour * 60 + 30 <= maxFrom)
				{
					timeStamps.push({
						value: hour * 60 + 30,
						name: this.formatTime(hour, 30),
					});
				}
			}

			return timeStamps;
		}

		/**
		 * @returns {{value: {number}, name: {string}}[]}
		 */
		getAvailableTimeTo()
		{
			const timeStamps = [];

			for (let hour = 0; hour <= 24; hour++)
			{
				if (hour * 60 >= this.from + this.slotSize)
				{
					timeStamps.push({
						value: hour * 60,
						name: this.formatTime(hour, 0),
					});
				}

				if (hour !== 24 && hour * 60 + 30 >= this.from + this.slotSize)
				{
					timeStamps.push({
						value: hour * 60 + 30,
						name: this.formatTime(hour, 30),
					});
				}
			}

			return timeStamps;
		}

		/**
		 * @returns {string[]}
		 */
		getWeekdaysLoc(isLong = false)
		{
			const weekdays = [];
			const locale = this.getLocale(env.languageId);
			for (let weekOffset = 0; weekOffset < 7; weekOffset++)
			{
				// eslint-disable-next-line no-undef
				weekdays[weekOffset] = capitalize(DateFormatter.getDateString(
					new Date(`June ${4 + weekOffset} 2023`).getTime() / 1000,
					isLong ? 'EEEE' : 'E',
					locale,
				));
			}

			return weekdays;
		}

		getLocale(bitrixLang)
		{
			const bitrixLangToLocale = {
				br: 'pt', // Portuguese (Brazil)
				la: 'es', // Spanish
				sc: 'zh', // Chinese (Simplified)
				tc: 'zh', // Chinese (Traditional)
				vn: 'vi', // Vietnamese
				ua: 'uk', // Ukrainian
			};

			return bitrixLangToLocale[bitrixLang] ?? bitrixLang;
		}

		formatMinutes(minutes)
		{
			return this.formatTime(minutes / 60, minutes % 60);
		}

		/**
		 * @param hours
		 * @param minutes
		 * @returns {string}
		 */
		formatTime(hours, minutes)
		{
			// eslint-disable-next-line init-declarations
			let day;

			if (Type.isDate(hours))
			{
				day = hours;
			}
			else
			{
				day = new Date();
				day.setHours(hours, minutes, 0);
			}

			const timestamp = (new Date(day.getTime() / 1000)).getTime();

			const moment = Moment.createFromTimestamp(timestamp);

			return moment.format(shortTime);
		}
	}

	module.exports = { Range };
});
