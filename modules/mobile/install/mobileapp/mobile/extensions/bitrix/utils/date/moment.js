/**
 * @module utils/date/moment
 */
jn.define('utils/date/moment', (require, exports, module) => {

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
		get inThisYear()
		{
			return this.date.getFullYear() === this.getNow().date.getFullYear();
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
		 * @return {boolean}
		 */
		get withinMinute()
		{
			return this.isWithinSeconds(60);
		}

		/**
		 * @return {boolean}
		 */
		get withinHour()
		{
			return this.isWithinSeconds(3600);
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
		 * @param {string|function} format
		 * @param {string|null} locale
		 * @param {*} rest
		 * @return {string}
		 */
		format(format, locale = null, ...rest)
		{
			const formatStr = typeof format === 'function' ? format(this, locale, ...rest) : format;
			return DateFormatter.getDateString(this.timestamp, formatStr, locale);
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

		/**
		 * Returns new moment instance containing start of current hour.
		 * @return {Moment}
		 */
		startOfHour()
		{
			const moment = this.clone();
			moment.date.setMinutes(0, 0, 0);
			return moment;
		}
	}

	module.exports = { Moment };

});