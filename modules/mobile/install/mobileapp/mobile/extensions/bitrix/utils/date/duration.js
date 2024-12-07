/**
 * @module utils/date/duration
 */
jn.define('utils/date/duration', (require, exports, module) => {
	const { Loc } = require('loc');

	const SECOND_LENGTH = 1000;
	const MINUTE_LENGTH = 60000;
	const HOUR_LENGTH = 3_600_000;
	const DAY_LENGTH = 86_400_000;
	const MONTH_LENGTH = 2_678_400_000;
	const YEAR_LENGTH = 31_536_000_000;

	/**
	 * Handy wrapper for working with duration.
	 */
	class Duration
	{
		constructor(milliseconds)
		{
			this.milliseconds = Math.abs(milliseconds);
		}

		/**
		 * Creates new Duration instance from seconds.
		 * @param {number} seconds
		 * @return {Duration}
		 */
		static createFromSeconds(seconds)
		{
			return new Duration(seconds * SECOND_LENGTH);
		}

		/**
		 * Creates new Duration instance from minutes.
		 * @param {number} minutes
		 * @return {Duration}
		 */
		static createFromMinutes(minutes)
		{
			return new Duration(minutes * MINUTE_LENGTH);
		}

		/**
		 * Duration in seconds
		 * @return {number}
		 */
		get seconds()
		{
			return Math.floor(this.milliseconds / SECOND_LENGTH);
		}

		/**
		 * Duration in minutes
		 * @return {number}
		 */
		get minutes()
		{
			return Math.floor(this.milliseconds / MINUTE_LENGTH);
		}

		/**
		 * Duration in hours
		 * @return {number}
		 */
		get hours()
		{
			return Math.floor(this.milliseconds / HOUR_LENGTH);
		}

		/**
		 * Duration in days
		 * @return {number}
		 */
		get days()
		{
			return Math.floor(this.milliseconds / DAY_LENGTH);
		}

		/**
		 * Duration in months (considering that a month is 31 days)
		 * @return {number}
		 */
		get months()
		{
			return Math.floor(this.milliseconds / MONTH_LENGTH);
		}

		/**
		 * Duration in years (considering that a year is 365 days)
		 * @return {number}
		 */
		get years()
		{
			return Math.floor(this.milliseconds / YEAR_LENGTH);
		}

		/**
		 * Formats duration
		 *
		 * Available units: `s` - seconds, `i` - minutes, `H` - hours, `d` - days, `m` - months, `Y` - years.
		 *
		 * If not pass format string then:
		 * - Duration will be formatted automatically with 'Y m d H i s'
		 * @example '1 day 2 hours 20 minutes'
		 * - Units will be taken with mod:
		 * @example result will be '1 hour 30 minutes' instead of '1 hour 90 minutes 3600 seconds'
		 * - Zero units will not be shown
		 * @example result will be '1 hour' instead of '1 hour 0 minutes 0 seconds'
		 *
		 * @param {string} formatStr
		 * @returns {string}
		 */
		format(formatStr = '')
		{
			if (formatStr === '')
			{
				return this.formatAllUnits('Y m d H i s', true).replace(/\s+/g, ' ').trim();
			}

			return this.formatAllUnits(formatStr, false);
		}

		formatAllUnits(formatStr, mod)
		{
			// eslint-disable-next-line unicorn/better-regex
			return formatStr.replaceAll(/([isHdmY])/g, (unitStr) => this.formatUnit(unitStr, mod));
		}

		formatUnit(unitStr, mod)
		{
			const value = mod ? this.getUnitPropertyModByFormat(unitStr) : this.getUnitPropertyByFormat(unitStr);
			if (mod && value === 0)
			{
				return '';
			}

			const locUnit = this.getLocUnitByFormat(unitStr);
			const messageCode = `MOBILE_DATE_${locUnit}_DIFF`;

			return Loc.getMessagePlural(messageCode, value, { '#VALUE#': value });
		}

		getUnitPropertyByFormat(unitStr)
		{
			const props = { s: this.seconds, i: this.minutes, H: this.hours, d: this.days, m: this.months, Y: this.years };

			return props[unitStr];
		}

		getUnitPropertyModByFormat(unitStr)
		{
			const propsMod = {
				s: this.seconds % 60,
				i: this.minutes % 60,
				H: this.hours % 24,
				d: this.days % 31,
				m: this.months % 12,
				Y: this.years,
			};

			return propsMod[unitStr];
		}

		getLocUnitByFormat(unitStr)
		{
			return { s: 'SECOND', i: 'MINUTE', H: 'HOUR', d: 'DAY', m: 'MONTH', Y: 'YEAR' }[unitStr];
		}

		/**
		 * Length formats
		 * @returns {{MONTH: number, YEAR: number, HOUR: number, SECOND: number, MINUTE: number, DAY: number}}
		 */
		static getLengthFormat()
		{
			return {
				SECOND: SECOND_LENGTH,
				MINUTE: MINUTE_LENGTH,
				HOUR: HOUR_LENGTH,
				DAY: DAY_LENGTH,
				MONTH: MONTH_LENGTH,
				YEAR: YEAR_LENGTH,
			};
		}
	}

	module.exports = { Duration };
});
