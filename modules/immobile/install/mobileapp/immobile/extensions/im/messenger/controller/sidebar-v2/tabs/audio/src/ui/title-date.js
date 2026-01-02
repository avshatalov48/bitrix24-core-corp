/**
 * @module im/messenger/controller/sidebar-v2/tabs/audio/src/ui/title-date
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/audio/src/ui/title-date', (require, exports, module) => {
	const { Moment } = require('utils/date');
	const { dayMonth, longDate, shortTime } = require('utils/date/formats');
	const { Text4 } = require('ui-system/typography/text');
	const { Color } = require('tokens');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');

	/**
	 * @param {number | string | Date} date
	 * @param {string} testId
	 * @returns {View}
	 */
	function TitleDate(date, testId)
	{
		return Text4({
			text: getFormatted(date),
			color: Color.base2,
			numberOfLines: 1,
			ellipsize: 'middle',
			testId: `${testId}-date`,
		});
	}

	/**
	 * @param {number | string | Date} date
	 * @returns {?string|string}
	 */
	function getFormatted(date)
	{
		if (!date)
		{
			return '';
		}

		const moment = new Moment(new Date(date));

		const formattedDate = moment.inThisYear ? moment.format(dayMonth) : moment.format(longDate);
		const formattedTime = moment.format(shortTime);

		return Loc.getMessage('IMMOBILE_SIDEBAR_V2_TAB_ITEM_DATE_AT_TIME', {
			'#DATE#': formattedDate,
			'#TIME#': formattedTime,
		});
	}

	module.exports = {
		TitleDate,
	};
});
