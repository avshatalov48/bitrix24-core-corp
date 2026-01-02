/**
 * @module im/messenger/lib/date-formatter
 */
jn.define('im/messenger/lib/date-formatter', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('im/messenger/loc');
	const { Moment } = require('utils/date');

	const DateFormat = Object.freeze({
		FORMAT_DATE: 'FORMAT_DATE',
		FORMAT_DATETIME: 'FORMAT_DATETIME',
		SHORT_DATE_FORMAT: 'SHORT_DATE_FORMAT',
		MEDIUM_DATE_FORMAT: 'MEDIUM_DATE_FORMAT',
		LONG_DATE_FORMAT: 'LONG_DATE_FORMAT',
		DAY_MONTH_FORMAT: 'DAY_MONTH_FORMAT',
		DAY_SHORT_MONTH_FORMAT: 'DAY_SHORT_MONTH_FORMAT',
		SHORT_DAY_OF_WEEK_MONTH_FORMAT: 'SHORT_DAY_OF_WEEK_MONTH_FORMAT',
		SHORT_DAY_OF_WEEK_SHORT_MONTH_FORMAT: 'SHORT_DAY_OF_WEEK_SHORT_MONTH_FORMAT',
		DAY_OF_WEEK_MONTH_FORMAT: 'DAY_OF_WEEK_MONTH_FORMAT',
		FULL_DATE_FORMAT: 'FULL_DATE_FORMAT',
		SHORT_TIME_FORMAT: 'SHORT_TIME_FORMAT',
		LONG_TIME_FORMAT: 'LONG_TIME_FORMAT',
	});

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
			this.setFormatCollection(jnExtensionData.get('messenger/lib/date-formatter').formatCollection);
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
				throw new TypeError(`DateFormatter.format: Invalid date ${date}`);
			}

			if (!this.isSupported(format))
			{
				throw new Error('DateFormatter.format: Unsupported format');
			}

			const convertedFormat = dateFormatter.convert(dateFormatter.formats[format]);

			return (new Moment(date)).format(convertedFormat, this.locale);
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
			return this.format(date, DateFormat.SHORT_TIME_FORMAT, this.locale);
		}

		getDayMonth(date)
		{
			return this.format(date, DateFormat.DAY_MONTH_FORMAT, this.locale);
		}

		getDate(date)
		{
			return this.format(date, DateFormat.FORMAT_DATE, this.locale);
		}

		getDatetime(date)
		{
			return this.format(date, DateFormat.FORMAT_DATETIME, this.locale);
		}

		getDayOfWeekMonth(date)
		{
			return this.format(date, DateFormat.DAY_OF_WEEK_MONTH_FORMAT, this.locale);
		}

		getDayShortMonth(date)
		{
			return this.format(date, DateFormat.DAY_SHORT_MONTH_FORMAT, this.locale);
		}

		getFullDate(date)
		{
			return this.format(date, DateFormat.FULL_DATE_FORMAT, this.locale);
		}

		getLongTime(date)
		{
			return this.format(date, DateFormat.LONG_TIME_FORMAT, this.locale);
		}

		getLongDate(date)
		{
			return this.format(date, DateFormat.LONG_DATE_FORMAT, this.locale);
		}

		getMediumDate(date)
		{
			return this.format(date, DateFormat.MEDIUM_DATE_FORMAT, this.locale);
		}

		getDayOfWeek(date)
		{
			return (new Moment(date)).format('E', this.locale);
		}

		getMediaFormat(date)
		{
			return `${this.getDayMonth(date)}, ${this.getShortTime(date)}`;
		}

		getQuoteFormat(date)
		{
			const moment = new Moment(date);

			if (moment.isToday)
			{
				return `${this.getTodayMessage()}, ${this.getShortTime(date)}`;
			}

			if (moment.isYesterday)
			{
				return `${this.getYesterdayMessage()}, ${this.getShortTime(date)}`;
			}

			if (moment.inThisYear)
			{
				return `${this.getDayMonth(date)}, ${this.getShortTime(date)}`;
			}

			if (!moment.inThisYear)
			{
				return `${this.getLongDate(date)}, ${this.getShortTime(date)}`;
			}

			return '';
		}

		getDateGroupFormat(date)
		{
			const moment = new Moment(date);

			if (moment.isToday)
			{
				return this.getTodayMessage();
			}

			if (moment.isYesterday)
			{
				return this.getYesterdayMessage();
			}

			if (moment.inThisYear)
			{
				return this.getDayOfWeekMonth(date);
			}

			if (!moment.inThisYear)
			{
				return this.getFullDate(date);
			}

			return '';
		}

		getRecentFormat(date)
		{
			if (!date)
			{
				return '';
			}
			const moment = new Moment(date);

			if (moment.isToday)
			{
				return this.getShortTime(date);
			}

			if (moment.inThisYear)
			{
				if (this.isCurrentWeek(moment))
				{
					return this.getDayOfWeek(date);
				}

				return this.getDayShortMonth(date).replace('.', '');
			}

			if (!moment.inThisYear)
			{
				return this.getMediumDate(date);
			}

			return '';
		}

		isToday(date)
		{
			const moment = new Moment(date);

			return moment.isToday;
		}

		isYesterday(date)
		{
			const moment = new Moment(date);

			return moment.isYesterday;
		}

		/**
		 *
		 * @param {Moment} moment
		 */
		isCurrentWeek(moment)
		{
			const now = moment.getNow();

			const nowWeek = Number(dateFormatter.get(Math.round(now.date.getTime() / 1000), 'w', this.locale));
			const momentWeek = Number(dateFormatter.get(Math.round(moment.date.getTime() / 1000), 'w', this.locale));

			return nowWeek === momentWeek;
		}

		/**
		 * @private
		 */
		getDateCode(date)
		{
			return `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;
		}
	}

	module.exports = {
		DateFormatter: new DateFormatter(),
		DateFormat,
	};
});
