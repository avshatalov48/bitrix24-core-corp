/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/date-formatter
 */
jn.define('im/messenger/lib/date-formatter', (require, exports, module) => {

	const { Type } = require('type');
	const { Loc } = require('loc');

	const DateFormat = Object.freeze({
		date: 'date',
		datetime: 'datetime',
		dayMonth: 'dayMonth',
		dayOfWeekMonth: 'dayOfWeekMonth',
		dayShortMonth: 'dayShortMonth',
		fullDate: 'fullDate',
		longDate: 'longDate',
		longTime: 'longTime',
		mediumDate: 'mediumDate',
		shortTime: 'shortTime',
	});

	const DAY_IN_MILLISECONDS = 86400000;

	/**
	 * @class DateFormatter
	 */
	class DateFormatter
	{
		constructor()
		{
			this.locale = null;
			this.formatCollection = {};

			this.setLocale(Application.getLang());
			this.setFormatCollection(jnExtensionData.get('messenger/lib/date-formatter')['formatCollection']);
		}

		setLocale(locale)
		{
			if (!Type.isString(locale))
			{
				return this;
			}

			this.locale = locale;

			return this;
		}

		setFormatCollection(formatCollection)
		{
			if (!Type.isObject(formatCollection))
			{
				return this;
			}

			this.formatCollection = formatCollection;

			return this;
		}

		isSupported(dateFormat)
		{
			return !Type.isUndefined(DateFormat[dateFormat]);
		}

		format(date, format)
		{
			if (
				!Type.isDate(date)
				|| (
					Type.isDate(date)
					&& Number.isNaN(date.getTime())
				)
			)
			{
				throw new Error('DateFormatter.format: Invalid date ' + date);
			}

			if (!this.isSupported(format))
			{
				throw new Error('DateFormatter.format: Unsupported format');
			}

			const timestampInSeconds = Math.round(date.getTime() / 1000);

			return dateFormatter.get(timestampInSeconds, dateFormatter.formats[format]);
		}

		getTodayMessage()
		{
			return Loc.getMessage('IMMOBILE_DATE_FORMATTER_TODAY');
		}

		getYesterdayMessage()
		{
			return Loc.getMessage('IMMOBILE_DATE_FORMATTER_YESTERDAY');
		}

		getShortTime(date)
		{
			return this.format(date, DateFormat.shortTime, this.locale);
		}

		getDayMonth(date)
		{
			return this.format(date, DateFormat.dayMonth, this.locale);
		}

		getDate(date)
		{
			return this.format(date, DateFormat.date, this.locale);
		}

		getDatetime(date)
		{
			return this.format(date, DateFormat.datetime, this.locale);
		}

		getDayOfWeekMonth(date)
		{
			return this.format(date, DateFormat.dayOfWeekMonth, this.locale);
		}

		getDayShortMonth(date)
		{
			return this.format(date, DateFormat.dayShortMonth, this.locale);
		}

		getFullDate(date)
		{
			return this.format(date, DateFormat.fullDate, this.locale);
		}

		getLongTime(date)
		{
			return this.format(date, DateFormat.longTime, this.locale);
		}

		getLongDate(date)
		{
			return this.format(date, DateFormat.longDate, this.locale);
		}

		getMediumDate(date)
		{
			return this.format(date, DateFormat.mediumDate, this.locale);
		}

		getQuoteFormat(date)
		{
			const today = new Date();
			if (this.isToday(date))
			{
				return `${this.getTodayMessage()}, ${this.getShortTime(date)}`;
			}

			if (this.isYesterday(date))
			{
				return `${this.getYesterdayMessage()}, ${this.getShortTime(date)}`;
			}

			const isThisYear = date.getFullYear() === today.getFullYear();
			if (isThisYear)
			{
				return `${this.getDayMonth(date)}, ${this.getShortTime(date)}`;
			}

			if (!isThisYear)
			{
				return `${this.getLongDate(date)}, ${this.getShortTime(date)}`;
			}

			return '';
		}

		getDateGroupFormat(date)
		{
			const today = new Date();
			if (this.isToday(date))
			{
				return this.getTodayMessage();
			}

			if (this.isYesterday(date))
			{
				return this.getYesterdayMessage();
			}

			const isThisYear = date.getFullYear() === today.getFullYear();
			if (isThisYear)
			{
				return this.getDayOfWeekMonth(date);
			}

			if (!isThisYear)
			{
				return this.getFullDate(date);
			}

			return '';
		}

		isToday(date)
		{
			const todayCode = this._getDateCode(new Date());
			const dateCode = this._getDateCode(date);

			return dateCode === todayCode;
		}

		isYesterday(date)
		{
			const yesterday = new Date(Date.now() - DAY_IN_MILLISECONDS);
			const yesterdayCode = this._getDateCode(yesterday);
			const dateCode = this._getDateCode(date);

			return dateCode === yesterdayCode;
		}

		_getDateCode(date)
		{
			return date.getFullYear() + '-' + date.getMonth() + '-' + date.getDate();
		}
	}

	module.exports = {
		DateFormatter: new DateFormatter(),
		DateFormat,
	};
});
