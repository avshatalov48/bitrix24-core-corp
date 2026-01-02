/**
 * @module utils/date/formats
 */
jn.define('utils/date/formats', (require, exports, module) => {
	const { Loc } = require('loc');

	const useCultureSettings = (formatName, fallback = '') => {
		return function() {
			const phpFormat = dateFormatter.formats[formatName];

			return phpFormat ? dateFormatter.convert(phpFormat) : fallback;
		};
	};

	/** @example 16:48 */
	const shortTime = useCultureSettings('SHORT_TIME_FORMAT', 'HH:mm');

	/** @returns boolean */
	const isAmPmMode = () => shortTime().slice(-1) === 'a';

	/** @example 1 April */
	const dayMonth = useCultureSettings('DAY_MONTH_FORMAT', 'd MMMM');

	/** @example 19.02.2022 */
	const date = useCultureSettings('FORMAT_DATE', 'DD.MM.YYYY');

	/** @example 19.02.2022 16:48:55 */
	const datetime = useCultureSettings('FORMAT_DATETIME', 'DD.MM.YYYY HH:MI:SS');

	/** @example Sunday, 19 February */
	const dayOfWeekMonth = useCultureSettings('DAY_OF_WEEK_MONTH_FORMAT', 'EEEE, d MMMM');

	/** @example 19 Feb */
	const dayShortMonth = useCultureSettings('DAY_SHORT_MONTH_FORMAT', 'd MMM');

	/** @example Sunday, 19 February 2040 */
	const fullDate = useCultureSettings('FULL_DATE_FORMAT', 'EEEE, d MMMM Y');

	/** @example 05:48:55 */
	const longTime = useCultureSettings('LONG_TIME_FORMAT', 'HH:mm:ss');

	/** @example 19 February 2040 */
	const longDate = useCultureSettings('LONG_DATE_FORMAT', 'd MMMM Y');

	/** @example 19 Feb 2040 */
	const mediumDate = useCultureSettings('MEDIUM_DATE_FORMAT', 'd MMM Y');

	function monthYear(moment)
	{
		const monthsNominative = [
			Loc.getMessage('MOBILE_MONTH_0_NOMINATIVE'),
			Loc.getMessage('MOBILE_MONTH_1_NOMINATIVE'),
			Loc.getMessage('MOBILE_MONTH_2_NOMINATIVE'),
			Loc.getMessage('MOBILE_MONTH_3_NOMINATIVE'),
			Loc.getMessage('MOBILE_MONTH_4_NOMINATIVE'),
			Loc.getMessage('MOBILE_MONTH_5_NOMINATIVE'),
			Loc.getMessage('MOBILE_MONTH_6_NOMINATIVE'),
			Loc.getMessage('MOBILE_MONTH_7_NOMINATIVE'),
			Loc.getMessage('MOBILE_MONTH_8_NOMINATIVE'),
			Loc.getMessage('MOBILE_MONTH_9_NOMINATIVE'),
			Loc.getMessage('MOBILE_MONTH_10_NOMINATIVE'),
			Loc.getMessage('MOBILE_MONTH_11_NOMINATIVE'),
		];
		const month = monthsNominative[moment.date.getMonth()];
		const year = moment.date.getFullYear();

		return `'${month} ${year}'`;
	}

	module.exports = {
		shortTime,
		isAmPmMode,
		dayMonth,
		date,
		datetime,
		dayOfWeekMonth,
		dayShortMonth,
		fullDate,
		longTime,
		longDate,
		mediumDate,
		monthYear,
	};
});
