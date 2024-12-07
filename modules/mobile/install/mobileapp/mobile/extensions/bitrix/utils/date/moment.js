/**
 * @module utils/date/moment
 */
jn.define('utils/date/moment', (require, exports, module) => {
	const { Duration } = require('utils/date/duration');

	const DurationLengths = Duration.getLengthFormat();

	const toSeconds = (ms) => ms / 1000;

	const MomentDuration = {
		SECOND: toSeconds(DurationLengths.SECOND),
		MINUTE: toSeconds(DurationLengths.MINUTE),
		HOUR: toSeconds(DurationLengths.HOUR),
		DAY: toSeconds(DurationLengths.DAY),
		WEEK: toSeconds(DurationLengths.DAY * 7),
		MONTH: toSeconds(DurationLengths.MONTH),
		YEAR: toSeconds(DurationLengths.YEAR),
	};

	const MMMregex = /\bMMM\b/;

	const NATIVE_LANGS_MAP = {
		ru: 'ru', // Russian
		en: 'en', // English
		de: 'de', // German
		ua: 'ua', // Ukrainian
		la: 'es', // Spanish
		br: 'pt', // Portuguese
		fr: 'fr', // French
		sc: 'cn', // Chinese Traditional -> Chinese
		tc: 'cn', // Chinese Traditional -> Chinese
		pl: 'pl', // Polish
		it: 'it', // Italian
		tr: 'tr', // Turkish
		ja: 'jp', // Japanese
		vn: 'vn', // Vietnamese
		id: 'id', // Indonesian
		ms: 'my', // Malay
		th: 'th', // Thai
		ar: 'ar', // Arabic
	};
	/**
	 * Handy wrapper for standard js Date object.
	 */
	class Moment
	{
		/**
		 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Date/Date
		 */
		constructor(...props)
		{
			/** @type {Date} */
			this.date = new Date(...props);

			/** @type {Moment|null} */
			this.now = null;
		}

		/**
		 * Creates new Moment instance from UNIX timestamp in seconds.
		 * @param {number} seconds
		 * @return {Moment}
		 */
		static createFromTimestamp(seconds)
		{
			return new Moment(seconds * 1000);
		}

		/**
		 * Returns Moment object for current time, or some preassigned moment.
		 * Uses for all internal comparisons.
		 * @return {Moment}
		 */
		getNow()
		{
			return this.now === null ? new Moment() : this.now;
		}

		/**
		 * You can specify "now" moment, to prevent getting fresh timestamp every time.
		 * @param {Moment} moment
		 * @return {Moment}
		 */
		setNow(moment)
		{
			this.now = moment;

			return this;
		}

		/**
		 * @return {boolean}
		 */
		get isToday()
		{
			return this.date.toDateString() === this.getNow().date.toDateString();
		}

		/**
		 * @return {boolean}
		 */
		get isYesterday()
		{
			const yesterday = new Date(this.getNow().date);
			yesterday.setDate(yesterday.getDate() - 1);

			return this.date.toDateString() === yesterday.toDateString();
		}

		/**
		 * @return {boolean}
		 */
		get isTomorrow()
		{
			const tomorrow = new Date(this.getNow().date);
			tomorrow.setDate(tomorrow.getDate() + 1);

			return this.date.toDateString() === tomorrow.toDateString();
		}

		/**
		 * @return {boolean}
		 */
		get inThisMinute()
		{
			return this.inThisHour && this.date.getMinutes() === this.getNow().date.getMinutes();
		}

		/**
		 * @return {boolean}
		 */
		get inThisHour()
		{
			return this.isToday && this.date.getHours() === this.getNow().date.getHours();
		}

		/**
		 * @return {boolean}
		 */
		get inThisMonth()
		{
			return this.inThisYear && this.date.getMonth() === this.getNow().date.getMonth();
		}

		/**
		 * @return {boolean}
		 */
		get inThisWeek()
		{
			if (this.inThisYear)
			{
				const momentWeek = Number(this.format('w'));
				const nowWeek = Number(this.getNow().format('w'));

				return nowWeek === momentWeek;
			}

			return false;
		}

		/**
		 * @return {boolean}
		 */
		get inThisYear()
		{
			return this.date.getFullYear() === this.getNow().date.getFullYear();
		}

		/**
		 * Returns new moment instance containing start of current hour.
		 * @return {Moment}
		 */
		get startOfHour()
		{
			const moment = this.clone();
			moment.date.setMinutes(0, 0, 0);

			return moment;
		}

		/**
		 * @return {Moment}
		 */
		get endOfHour()
		{
			const moment = this.clone();
			moment.date.setMinutes(59, 59, 999);

			return moment;
		}

		/**
		 * @return {Moment}
		 */
		get startOfWeek()
		{
			const currentWeekStart = this.clone();
			const weekDay = this.date.getDay() === 0 ? 6 : this.date.getDay() - 1;

			currentWeekStart.date.setDate(this.date.getDate() - weekDay);
			currentWeekStart.date.setHours(0, 0, 0, 0);

			return currentWeekStart;
		}

		/**
		 * @return {Moment}
		 */
		get endOfWeek()
		{
			const currentWeekStart = this.startOfWeek;

			const currentWeekEnd = new Moment(currentWeekStart.date);
			currentWeekEnd.date.setDate(currentWeekStart.date.getDate() + 6);
			currentWeekEnd.date.setHours(23, 59, 59, 999);

			return currentWeekEnd;
		}

		/**
		 * @return {Moment}
		 */
		get startOfMonth()
		{
			const currentMonthStart = this.clone();

			currentMonthStart.date.setDate(1);
			currentMonthStart.date.setHours(0, 0, 0, 0);

			return currentMonthStart;
		}

		/**
		 * @return {Moment}
		 */
		get endOfMonth()
		{
			const currentMonthEnd = new Moment(this.date);

			currentMonthEnd.date.setMonth((this.date.getMonth() + 1) % 12, 1);
			currentMonthEnd.date.setDate(0);
			currentMonthEnd.date.setHours(23, 59, 59, 999);

			return currentMonthEnd;
		}

		/**
		 * @return {Moment}
		 */
		get startOfYear()
		{
			const currentYearStart = this.clone();

			currentYearStart.date.setMonth(0);

			return currentYearStart.startOfMonth;
		}

		/**
		 * @return {Moment}
		 */
		get endOfYear()
		{
			const currentYearEnd = this.clone();

			currentYearEnd.date.setMonth(11);

			return currentYearEnd.endOfMonth;
		}

		/**
		 * @returns {number}
		 */
		get daysInYear()
		{
			const year = this.date.getFullYear();

			return ((year % 4 === 0 && year % 100 > 0) || year % 400 === 0) ? 366 : 365;
		}

		/**
		 * We assume that "just now" is {delta} seconds before and after current moment.
		 * @param {number} delta
		 * @return {boolean}
		 */
		isJustNow(delta = 60)
		{
			return this.isWithinSeconds(delta);
		}

		/**
		 * @return {boolean}
		 */
		get hasPassed()
		{
			return this.timestamp < this.getNow().timestamp;
		}

		/**
		 * @return {boolean}
		 */
		get inFuture()
		{
			return this.timestamp > this.getNow().timestamp;
		}

		/**
		 * Returns true if current moment is inside interval of {delta} seconds before/after now.
		 * @param {number} delta
		 * @return {boolean}
		 */
		isWithinSeconds(delta)
		{
			const interval = Math.abs(this.getNow().timestamp - this.timestamp);

			return interval <= delta;
		}

		/**
		 * @return {boolean}
		 */
		get withinMinute()
		{
			return this.isWithinSeconds(MomentDuration.MINUTE);
		}

		/**
		 * @return {boolean}
		 */
		get withinHour()
		{
			return this.isWithinSeconds(MomentDuration.HOUR);
		}

		/**
		 * @return {boolean}
		 */
		get withinDay()
		{
			return this.isWithinSeconds(MomentDuration.DAY);
		}

		/**
		 * @return {boolean}
		 */
		get withinWeek()
		{
			return this.isWithinSeconds(MomentDuration.WEEK);
		}

		/**
		 * @return {boolean}
		 */
		get withinMonth()
		{
			return this.isWithinSeconds(MomentDuration.MONTH);
		}

		/**
		 * @return {boolean}
		 */
		get withinYear()
		{
			return this.isWithinSeconds(MomentDuration.YEAR);
		}

		/**
		 * Returns true if current moment is outside interval of {delta} seconds before/after now.
		 * @param {number} delta
		 * @return {boolean}
		 */
		isOverSeconds(delta)
		{
			return !this.isWithinSeconds(delta);
		}

		/**
		 * Timestamp in seconds
		 * @return {number}
		 */
		get timestamp()
		{
			return Math.round(this.date.getTime() / 1000);
		}

		/**
		 * @return {number}
		 */
		get secondsFromNow()
		{
			const delta = Math.abs(this.getNow().timestamp - this.timestamp);

			return Math.floor(delta);
		}

		/**
		 * @return {number}
		 */
		get minutesFromNow()
		{
			const delta = Math.abs(this.getNow().timestamp - this.timestamp);

			return Math.floor(delta / 60);
		}

		/**
		 * @return {number}
		 */
		get hoursFromNow()
		{
			return Math.floor(this.minutesFromNow / 60);
		}

		/**
		 * @return {number}
		 */
		get daysFromNow()
		{
			const delta = Math.abs(this.getNow().timestamp - this.timestamp);

			return Math.floor(delta / (60 * 60 * 24));
		}

		/**
		 * @return {number}
		 */
		get weeksFromNow()
		{
			return Math.floor(this.daysFromNow / 7);
		}

		/**
		 * @deprecated due to controversial implementation
		 * @return {number}
		 */
		get monthsFromNow()
		{
			const nowDate = this.getNow().date;
			const date = this.date;

			return Math.abs(date.getMonth() - nowDate.getMonth()
				+ (12 * (date.getFullYear() - nowDate.getFullYear())));
		}

		/**
		 * @deprecated due to controversial implementation
		 * @return {number}
		 */
		get yearsFromNow()
		{
			const nowDate = this.getNow().date;
			const date = this.date;

			return Math.abs(date.getFullYear() - nowDate.getFullYear());
		}

		/**
		 * @param {string|function} format
		 * @param {string|null} locale
		 * @param {*} rest
		 * @return {string}
		 */
		format(format, locale = env?.languageId, ...rest)
		{
			const languageId = NATIVE_LANGS_MAP[locale] || locale;
			const formatStr = typeof format === 'function' ? format(this, locale, ...rest) : format;

			if (MMMregex.test(formatStr))
			{
				// fix for MMM format to remove trailing dot (e.g. Aug. => Aug)
				const formattedMMM = this.getMMMWithoutTrailingDot(languageId);
				const dateWithoutMMM = formatStr.replace(MMMregex, '#');

				// eslint-disable-next-line no-undef
				return DateFormatter.getDateString(this.timestamp, dateWithoutMMM, languageId).replace('#', formattedMMM);
			}

			// eslint-disable-next-line no-undef
			return DateFormatter.getDateString(this.timestamp, formatStr, languageId);
		}

		getMMMWithoutTrailingDot(locale)
		{
			const languageId = NATIVE_LANGS_MAP[locale] || locale;
			// eslint-disable-next-line no-undef
			const formattedMMM = DateFormatter.getDateString(this.timestamp, 'MMM', languageId);

			if (formattedMMM.endsWith('.'))
			{
				return formattedMMM.slice(0, -1);
			}

			return formattedMMM;
		}

		/**
		 * @param {Moment} other
		 */
		equals(other)
		{
			return this.date.getTime() === other.date.getTime();
		}

		/**
		 * @param {Moment} other
		 * @return {boolean}
		 */
		isBefore(other)
		{
			return this.date.getTime() < other.date.getTime();
		}

		/**
		 * @param {Moment} other
		 * @return {boolean}
		 */
		isAfter(other)
		{
			return !this.isBefore(other);
		}

		/**
		 * @return {Moment}
		 */
		clone()
		{
			const moment = new Moment(this.date);
			if (this.now !== null)
			{
				moment.setNow(new Moment(this.now.date));
			}

			return moment;
		}

		/**
		 * @param {number} ms
		 * @return {Moment}
		 */
		add(ms = 0)
		{
			const moment = this.clone();
			moment.date.setTime(this.date.getTime() + ms);

			return moment;
		}

		/**
		 * @param {number} seconds
		 * @return {Moment}
		 */
		addSeconds(seconds = 0)
		{
			return this.add(seconds * 1000);
		}

		/**
		 * @param {number} minutes
		 * @return {Moment}
		 */
		addMinutes(minutes = 0)
		{
			return this.add(minutes * 60 * 1000);
		}

		/**
		 * @param {number} hours
		 * @return {Moment}
		 */
		addHours(hours = 0)
		{
			return this.add(hours * 60 * 60 * 1000);
		}

		/**
		 * @param {number} days
		 * @return {Moment}
		 */
		addDays(days = 0)
		{
			return this.add(days * 24 * 60 * 60 * 1000);
		}
	}

	module.exports = { Moment };
});
