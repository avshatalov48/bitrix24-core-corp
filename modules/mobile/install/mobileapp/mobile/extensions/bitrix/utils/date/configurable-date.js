/**
 * @module utils/date/configurable-date
 */
jn.define('utils/date/configurable-date', (require, exports, module) => {
	const { Moment } = require('utils/date/moment');

	// ConfigurableDateBySeconds let user set different date formats
	// depend on difference between timestamp time and now.
	// Props keys are timestamp, deltas, defaultFormat.
	// Deltas are set in format { secondsDelta: format_1, ... }. SecondsDelta must be a number.
	// Time formats are the same to DateFormatter ones.
	// Timestamp is time in seconds since epoch.
	/**
	 *  @example
	 *  ConfigurableDateBySeconds({
	 *    'timestamp': 1691745743,
	 *    'deltas' : {
	 *      60: 'mm:ss',
	 *      3600: 'HH:mm:ss',
	 *      86400: 'HH:mm',
	 *    },
	 *    defaultFormat: ''EEEE, d MMMM Y',
	 *  })
	 *
	 *	1691745743 secs since 01.01.1970 = Aug 11 2023 11:22:23 GMT+0200
	 *
	 *  Examples of datetime will look like:
	 *  - within 60 seconds (minute): 22:56 (minutes and seconds)
	 *  - within 3600 seconds (hour): 12:01:13 (hours, minutes and seconds)
	 *  - within 86400 seconds (day): 23:59 (hours and minutes)
	 *  - outside 86400 seconds (day): Friday, 11 August 2023
	 */
	function ConfigurableDateBySeconds(props = {})
	{
		const { timestamp = 0, deltas = {}, defaultFormat = 'DD.MM.YYYY HH:MI:SS' } = props;
		if (deltas.length === 0)
		{
			return DateFormatter.getDateString(timestamp, defaultFormat, env.languageId.toString());
		}

		const moment = Moment.createFromTimestamp(timestamp);

		let currFormat = defaultFormat;
		for (const [secondsDelta, format] of Object.entries(deltas).reverse())
		{
			if (!Number(secondsDelta))
			{
				continue;
			}

			if (moment.isWithinSeconds(Number(secondsDelta)))
			{
				currFormat = format;
				continue;
			}

			return DateFormatter.getDateString(timestamp, currFormat, env.languageId.toString());
		}

		return DateFormatter.getDateString(timestamp, defaultFormat, env.languageId.toString());
	}

	// ConfigurableDateByTimeDeltaTokens let user set different date formats
	// depend on difference between timestamp time and now.
	// Props keys are timestamp, deltas, defaultFormat.
	// Deltas are set in format { deltaToken_1: format_1, ... }
	// Available delta tokens: 'minute', 'hour', 'day', 'week', 'month', 'year'
	// Time formats are the same to DateFormatter ones.
	// Timestamp is time in seconds since epoch.
	/**
	 *  @example
	 *  ConfigurableDateByTimeDeltaTokens({
	 *    'timestamp': 1691745743,
	 *    'deltas' : {
	 *      'minute': 'mm:ss',
	 *      'hour': 'HH:mm:ss',
	 *      'day: 'HH:mm',
	 *      'week': 'E',
	 *      'month': 'EEEE, d MMM',
	 *      'year' 'd MMMM Y',
	 *    },
	 *    defaultFormat: ''EEEE, d MMMM Y',
	 *  })
	 *
	 *	1691745743 secs since 01.01.1970 = Aug 11 2023 11:22:23 GMT+0200
	 *
	 *  Examples of datetime will look like:
	 *  - minute is equal to current minute: 22:56 (minutes and seconds)
	 *  - minute is not equal to current minute, hour is equal: 11:33:37 (hours, minutes and seconds)
	 *  ...
	 *  - date (11.08.2013) is not equal to current date, within time of week: Fr (day of week)
	 *  ...
	 *  - month is not equal to current month, year is equal: 11 August 2023
	 *  - year is not equal to current year: Friday, 11 August 2023
	 */
	function ConfigurableDateByTimeDeltaTokens(props = {})
	{
		const { timestamp = 0, deltas = {}, defaultFormat = 'DD.MM.YYYY HH:MI:SS' } = props;
		if (deltas.length === 0)
		{
			return DateFormatter.getDateString(timestamp, defaultFormat, env.languageId.toString());
		}

		const moment = Moment.createFromTimestamp(timestamp);

		const timeTokensMap = {
			minute: moment.inThisMinute,
			hour: moment.inThisHour,
			day: moment.isToday,
			week: moment.withinWeek,
			month: moment.inThisMonth && moment.inThisYear,
			year: moment.inThisYear,
		};

		for (const [token] of Object.entries(deltas))
		{
			if (!(token in timeTokensMap))
			{
				return moment.format(defaultFormat);
			}
		}

		const getFormatByToken = (token) => {
			for (const [delta, format] of Object.entries(deltas))
			{
				if (delta === token)
				{
					return format;
				}
			}

			return null;
		};

		let format = defaultFormat;
		for (const [token, inDelta] of Object.entries(timeTokensMap).reverse())
		{
			if (inDelta)
			{
				format = getFormatByToken(token) ?? format;
				continue;
			}

			return moment.format(format);
		}

		return moment.format(format);
	}

	module.exports = {
		ConfigurableDateBySeconds,
		ConfigurableDateByTimeDeltaTokens,
	};
});
