/**
 * @module utils/date/dynamic-date-formatter
 */
jn.define('utils/date/dynamic-date-formatter', (require, exports, module) => {
	const { datetime } = require('utils/date/formats');
	const { clone } = require('utils/object');

	/**
	 * @class DynamicDateFormatter
	 */
	class DynamicDateFormatter
	{
		/**
		 * @public
		 * @returns {{PAST: string, FUTURE: string}}
		 */
		static get scope()
		{
			return {
				PAST: 'past',
				FUTURE: 'future',
			};
		}

		/**
		 * @public
		 * @returns {{
		 * MONTH: string,
		 * YEAR: string,
		 * HOUR: string,
		 * MINUTE: string,
		 * WEEK: string,
		 * DAY: string,
		 * YESTERDAY: string,
		 * TOMORROW: string
		 * }}
		 */
		static get deltas()
		{
			return {
				MINUTE: 'DELTA_MINUTE',
				HOUR: 'DELTA_HOUR',
				DAY: 'DELTA_DAY',
				YESTERDAY: 'DELTA_YESTERDAY',
				TOMORROW: 'DELTA_TOMORROW',
				WEEK: 'DELTA_WEEK',
				MONTH: 'DELTA_MONTH',
				YEAR: 'DELTA_YEAR',
			};
		}

		/**
		 * @public
		 * @returns {{
		 * MONTH: string,
		 * YEAR: string,
		 * HOUR: string,
		 * MINUTE: string,
		 * WEEK: string,
		 * DAY: string,
		 * YESTERDAY: string,
		 * TOMORROW: string
		 * }}
		 */
		static get periods()
		{
			return {
				MINUTE: 'PERIOD_MINUTE',
				HOUR: 'PERIOD_HOUR',
				DAY: 'PERIOD_DAY',
				YESTERDAY: 'PERIOD_YESTERDAY',
				TOMORROW: 'PERIOD_TOMORROW',
				WEEK: 'PERIOD_WEEK',
				MONTH: 'PERIOD_MONTH',
				YEAR: 'PERIOD_YEAR',
			};
		}

		/**
		 * @public
		 * @param {?function|?string} options.defaultFormat
		 * @param {?object} options.config.both
		 * @param {?object} options.config.future
		 * @param {?object} options.config.past
		 */
		constructor(options = {})
		{
			this.defaultFormat = options.defaultFormat ?? datetime();

			this.config = options.config;
		}

		/**
		 * @private
		 * @param {Moment} moment
		 * @param {String} delta
		 * @returns {Number|null}
		 */
		resolveDelta(moment, delta)
		{
			switch (delta)
			{
				case DynamicDateFormatter.deltas.MINUTE:
					return 60;
				case DynamicDateFormatter.deltas.HOUR:
					return 3600;
				case DynamicDateFormatter.deltas.DAY:
					return 86400;
				case DynamicDateFormatter.deltas.YESTERDAY:
				case DynamicDateFormatter.deltas.TOMORROW:
					return 86400 * 2;
				case DynamicDateFormatter.deltas.WEEK:
					return 604_800;
				case DynamicDateFormatter.deltas.MONTH:
					return moment.endOfMonth.date.getDate();
				case DynamicDateFormatter.deltas.YEAR:
					return moment.daysInYear;
				default:
					return null;
			}
		}

		/**
		 * @private
		 * @param {Moment} moment
		 * @param {String} period
		 * @returns {Number|null}
		 */
		resolvePeriod(moment, period)
		{
			const toSeconds = (number) => Math.round(number / 1000);
			const getDelta = (timestamp) => Math.abs(moment.getNow().timestamp - timestamp);

			const dateNow = moment.clone().getNow().date;

			switch (period)
			{
				case DynamicDateFormatter.periods.MINUTE:
					return getDelta(
						toSeconds(moment.hasPassed
							? dateNow.setSeconds(0, 0)
							: dateNow.setSeconds(59, 0)),
					);
				case DynamicDateFormatter.periods.HOUR:
					return getDelta(
						toSeconds(moment.hasPassed
							? dateNow.setMinutes(0, 0, 0)
							: dateNow.setMinutes(59, 59, 0)),
					);
				case DynamicDateFormatter.periods.DAY:
					return getDelta(
						toSeconds(moment.hasPassed
							? dateNow.setHours(0, 0, 0, 0)
							: dateNow.setHours(23, 59, 59, 0)),
					);
				case DynamicDateFormatter.periods.YESTERDAY:
					// eslint-disable-next-line no-case-declarations
					const dateYesterday = moment.clone().getNow().addDays(-1).date;

					return getDelta(
						toSeconds(dateYesterday.setHours(0, 0, 0, 0)),
					);
				case DynamicDateFormatter.periods.TOMORROW:
					// eslint-disable-next-line no-case-declarations
					const dateTomorrow = moment.clone().getNow().addDays(1).date;

					return getDelta(
						toSeconds(dateTomorrow.setHours(23, 59, 59, 0)),
					);
				case DynamicDateFormatter.periods.WEEK:
					return getDelta(
						moment.hasPassed
							? moment.clone().getNow().startOfWeek.timestamp
							: moment.clone().getNow().endOfWeek.timestamp,
					);
				case DynamicDateFormatter.periods.MONTH:
					return getDelta(
						moment.hasPassed
							? moment.clone().getNow().startOfMonth.timestamp
							: moment.clone().getNow().endOfMonth.timestamp,
					);
				case DynamicDateFormatter.periods.YEAR:
					return getDelta(
						moment.hasPassed
							? moment.clone().getNow().startOfYear.timestamp
							: moment.clone().getNow().endOfYear.timestamp,
					);
				default:
					return null;
			}
		}

		/**
		 * @public
		 * @param {Moment} moment
		 */
		format(moment)
		{
			const config = this.calculateBreakpoints(moment);

			const orderedBreakpoints = Object.keys(config).sort((a, b) => Number(b) - Number(a));
			let dt = this.getFormattedDatetime(this.defaultFormat, moment);

			for (const bp of orderedBreakpoints)
			{
				if (!moment.isWithinSeconds(Number(bp)))
				{
					break;
				}

				dt = this.getFormattedDatetime(config[bp], moment);
			}

			return dt;
		}

		/**
		 * @private
		 * @param {string|function} format
		 * @param {Moment} moment
		 * @return {String|null}
		 */
		getFormattedDatetime(format, moment)
		{
			if (typeof format === 'function')
			{
				return format(moment);
			}

			if (typeof format === 'string')
			{
				return moment.format(format);
			}

			return null;
		}

		/**
		 * @private
		 * @param {Moment} moment
		 * @returns {Object}
		 */
		calculateBreakpoints(moment)
		{
			const specificBreakpoints = moment.hasPassed ? this.config.past : this.config.future;
			let breakpointsStr = clone(this.config);
			delete breakpointsStr.future;
			delete breakpointsStr.past;
			breakpointsStr = {
				...breakpointsStr,
				...specificBreakpoints,
			};
			const breakpointsNum = {};

			if (typeof breakpointsStr !== 'object')
			{
				return breakpointsNum;
			}

			Object.keys(breakpointsStr).forEach((bpStr) => {
				let bpInSecs = null;

				if (Number.isInteger(Number(bpStr)))
				{
					bpInSecs = bpStr;
				}
				else if (bpStr.includes('PERIOD'))
				{
					bpInSecs = this.resolvePeriod(moment, bpStr);
				}
				else if (bpStr.includes('DELTA'))
				{
					bpInSecs = this.resolveDelta(moment, bpStr);
				}

				if (bpInSecs)
				{
					breakpointsNum[bpInSecs] = breakpointsStr[bpStr];
				}
			});

			return breakpointsNum;
		}
	}

	module.exports = { DynamicDateFormatter };
});
