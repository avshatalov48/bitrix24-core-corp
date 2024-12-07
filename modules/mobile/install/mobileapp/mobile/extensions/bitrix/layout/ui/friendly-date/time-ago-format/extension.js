/**
 * @module layout/ui/friendly-date/time-ago-format
 */
jn.define('layout/ui/friendly-date/time-ago-format', (require, exports, module) => {
	const { Loc } = require('loc');
	const { shortTime } = require('utils/date/formats');

	/**
	 * @class TimeAgoFormat
	 */
	class TimeAgoFormat
	{
		constructor(props = {})
		{
			this.props = props;
		}

		get skipAfterSeconds()
		{
			return BX.prop.getNumber(this.props, 'skipAfterSeconds', null);
		}

		get justNowDelay()
		{
			return BX.prop.getNumber(this.props, 'justNowDelay', 60);
		}

		get futureAllowed()
		{
			return BX.prop.getBoolean(this.props, 'futureAllowed', false);
		}

		/**
		 * @public
		 * @param {Moment} moment
		 * @return {string}
		 */
		format(moment)
		{
			if (!this.futureAllowed && !moment.hasPassed)
			{
				return this.formatDefault(moment);
			}

			if (this.skipAfterSeconds !== null && moment.isOverSeconds(this.skipAfterSeconds))
			{
				return this.formatDefault(moment);
			}

			if (moment.isJustNow(this.justNowDelay))
			{
				return this.getPhrase({
					code: 'MOBILE_UI_TIME_AGO_JUST_NOW',
				});
			}

			if (moment.withinMinute)
			{
				return this.formatSeconds(moment);
			}

			if (moment.withinHour)
			{
				return this.formatMinutes(moment);
			}

			if (moment.isToday)
			{
				return this.formatHours(moment);
			}

			return this.formatDefault(moment);
		}

		/**
		 * @protected
		 * @param {Moment} moment
		 * @return {string}
		 */
		formatDefault(moment)
		{
			if (typeof this.props.defaultFormat === 'function')
			{
				const context = this.props.context ?? this;

				return this.props.defaultFormat(moment, context);
			}

			if (this.props.defaultFormat)
			{
				return moment.format(this.props.defaultFormat);
			}

			return moment.format(shortTime).toLocaleLowerCase(env.languageId);
		}

		/**
		 * @protected
		 * @param {Moment} moment
		 * @return {string|null}
		 */
		formatSeconds(moment)
		{
			const seconds = moment.secondsFromNow;

			const code = moment.inFuture
				? 'MOBILE_UI_TIME_AGO_FUTURE_SECONDS'
				: 'MOBILE_UI_TIME_AGO_SECONDS';

			return this.getPhrase({
				code,
				value: seconds,
				replacements: { '#NUM#': seconds },
			});
		}

		/**
		 * @public
		 * @param {Moment} moment
		 * @return {string|null}
		 */
		formatMinutes(moment)
		{
			const minutes = moment.minutesFromNow;

			const code = moment.inFuture
				? 'MOBILE_UI_TIME_AGO_FUTURE_MINUTES'
				: 'MOBILE_UI_TIME_AGO_MINUTES';

			return this.getPhrase({
				code,
				value: minutes,
				replacements: { '#NUM#': minutes },
			});
		}

		/**
		 * @public
		 * @param {Moment} moment
		 * @return {string|null}
		 */
		formatHours(moment)
		{
			const hours = moment.hoursFromNow;

			const code = moment.inFuture
				? 'MOBILE_UI_TIME_AGO_FUTURE_HOURS'
				: 'MOBILE_UI_TIME_AGO_HOURS';

			return this.getPhrase({
				code,
				value: hours,
				replacements: { '#NUM#': hours },
			});
		}

		/**
		 * @public
		 * @param {Moment} moment
		 * @return {string|null}
		 */
		formatDays(moment)
		{
			const days = moment.daysFromNow;

			const code = moment.inFuture
				? 'MOBILE_UI_TIME_AGO_FUTURE_DAYS'
				: 'MOBILE_UI_TIME_AGO_DAYS';

			return this.getPhrase({
				code,
				value: days,
				replacements: { '#NUM#': days },
			});
		}

		/**
		 * @public
		 * @param {Moment} moment
		 * @return {string|null}
		 */
		formatWeeks(moment)
		{
			const weeks = moment.weeksFromNow;

			const code = moment.inFuture
				? 'MOBILE_UI_TIME_AGO_FUTURE_WEEKS'
				: 'MOBILE_UI_TIME_AGO_WEEKS';

			return this.getPhrase({
				code,
				value: weeks,
				replacements: { '#NUM#': weeks },
			});
		}

		/**
		 * @public
		 * @param {Moment} moment
		 * @return {string|null}
		 */
		formatMonths(moment)
		{
			const months = moment.monthsFromNow;

			const code = moment.inFuture
				? 'MOBILE_UI_TIME_AGO_FUTURE_MONTHS'
				: 'MOBILE_UI_TIME_AGO_MONTHS';

			return this.getPhrase({
				code,
				value: months,
				replacements: { '#NUM#': months },
			});
		}

		/**
		 * @public
		 * @param {Moment} moment
		 * @return {string|null}
		 */
		formatYears(moment)
		{
			const years = moment.yearsFromNow;

			const code = moment.inFuture
				? 'MOBILE_UI_TIME_AGO_FUTURE_YEARS'
				: 'MOBILE_UI_TIME_AGO_YEARS';

			return this.getPhrase({
				code,
				value: years,
				replacements: { '#NUM#': years },
			});
		}

		/**
		 * @protected
		 * @param {Moment} moment
		 * @return {?string}
		 */
		formatYesterday(moment)
		{
			return this.getPhrase({
				code: 'MOBILE_UI_TIME_AGO_YESTERDAY',
				replacements: {
					'#TIME#': moment.format(shortTime).toLocaleLowerCase(env.languageId),
				},
			});
		}

		/**
		 * @protected
		 * @param {Moment} moment
		 * @return {?string}
		 */
		formatToday(moment)
		{
			return this.getPhrase({
				code: 'MOBILE_UI_TIME_AGO_TODAY',
				replacements: {
					'#TIME#': moment.format(shortTime).toLocaleLowerCase(env.languageId),
				},
			});
		}

		/**
		 * @protected
		 * @param {?Moment} moment
		 * @return {?string}
		 */
		formatMoreYear(moment)
		{
			return Loc.getMessage('MOBILE_UI_TIME_AGO_MORE_YEAR');
		}

		/**
		 * @protected
		 * @param {string} code
		 * @param {any|null} value
		 * @param {object} replacements
		 * @return {string|null}
		 */
		getPhrase({ code, value = null, replacements = {} })
		{
			const phrase = value === null
				? Loc.getLastMessageVer(code, replacements)
				: Loc.getLastMessageVerPlural(code, value, replacements);

			if (typeof this.props.onGetPhrase === 'function')
			{
				return this.props.onGetPhrase({
					code,
					value,
					replacements,
					phrase,
				});
			}

			return phrase;
		}
	}

	module.exports = { TimeAgoFormat };
});
