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
		 * @private
		 * @param {Moment} moment
		 * @return {string}
		 */
		formatDefault(moment)
		{
			if (typeof this.props.defaultFormat === 'function')
			{
				return this.props.defaultFormat(moment);
			}
			else if (this.props.defaultFormat)
			{
				return moment.format(this.props.defaultFormat);
			}

			return moment.format(shortTime).toLocaleLowerCase(env.languageId);
		}

		/**
		 * @private
		 * @param {Moment} moment
		 * @return {string|null}
		 */
		formatSeconds(moment)
		{
			const seconds = moment.secondsFromNow;
			if (seconds === 1)
			{
				const code = moment.inFuture
					? 'MOBILE_UI_TIME_AGO_FUTURE_SECONDS_EXACT_ONE'
					: 'MOBILE_UI_TIME_AGO_SECONDS_EXACT_ONE';

				return this.getPhrase({
					code,
				});
			}

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
		 * @private
		 * @param {Moment} moment
		 * @return {string|null}
		 */
		formatMinutes(moment)
		{
			const minutes = moment.minutesFromNow;
			if (minutes === 1)
			{
				const code = moment.inFuture
					? 'MOBILE_UI_TIME_AGO_FUTURE_MINUTES_EXACT_ONE'
					: 'MOBILE_UI_TIME_AGO_MINUTES_EXACT_ONE';

				return this.getPhrase({
					code,
				});
			}

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
		 * @private
		 * @param {Moment} moment
		 * @return {string|null}
		 */
		formatHours(moment)
		{
			const hours = moment.hoursFromNow;
			if (hours === 1)
			{
				const code = moment.inFuture
					? 'MOBILE_UI_TIME_AGO_FUTURE_HOURS_EXACT_ONE'
					: 'MOBILE_UI_TIME_AGO_HOURS_EXACT_ONE';

				return this.getPhrase({
					code,
				});
			}

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
		 * @private
		 * @param {string} code
		 * @param {any|null} value
		 * @param {object} replacements
		 * @return {string|null}
		 */
		getPhrase({code, value = null, replacements = {}})
		{
			const phrase = value === null
				? Loc.getMessage(code, replacements)
				: Loc.getMessagePlural(code, value, replacements);

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