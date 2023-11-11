/**
 * @module utils/date/formats
 */
jn.define('utils/date/formats', (require, exports, module) => {
	const useCultureSettings = (formatName, fallback = '') => {
		return function() {
			const phpFormat = dateFormatter.formats[formatName];

			return phpFormat ? dateFormatter.convert(phpFormat) : fallback;
		};
	};

	/** @example 16:48 */
	const shortTime = useCultureSettings('shortTime', 'HH:mm');

	/** @returns boolean */
	const isAmPmMode = () => shortTime().slice(-1) === 'a';

	/** @example 1 April */
	const dayMonth = useCultureSettings('dayMonth', 'd MMMM');

	/** @example 19.02.2022 */
	const date = useCultureSettings('date', 'DD.MM.YYYY');

	/** @example 19.02.2022 16:48:55 */
	const datetime = useCultureSettings('datetime', 'DD.MM.YYYY HH:MI:SS');

	/** @example Sunday, 19 February */
	const dayOfWeekMonth = useCultureSettings('dayOfWeekMonth', 'EEEE, d MMMM');

	/** @example 19 Feb */
	const dayShortMonth = useCultureSettings('dayShortMonth', 'd MMM');

	/** @example Sunday, 19 February 2040 */
	const fullDate = useCultureSettings('fullDate', 'EEEE, d MMMM Y');

	/** @example 05:48:55 */
	const longTime = useCultureSettings('longTime', 'HH:mm:ss');

	/** @example 19 February 2040 */
	const longDate = useCultureSettings('longDate', 'd MMMM Y');

	/** @example 19 Feb 2040 */
	const mediumDate = useCultureSettings('mediumDate', 'd MMM Y');

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
	};
});
