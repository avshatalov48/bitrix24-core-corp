/**
 * @module layout/ui/friendly-date/human-date-formatter
 */
jn.define('layout/ui/friendly-date/human-date-formatter', (require, exports, module) => {
	const { Loc } = require('loc');
	const { longDate, dayMonth } = require('utils/date/formats');
	const { TimeAgoFormat } = require('layout/ui/friendly-date/time-ago-format');

	const ONE_HOUR = 3600;
	/**
	 * @class TimeAgoFormat
	 */
	class HumanDateFormatter extends TimeAgoFormat
	{
		/**
		 * @public
		 * @param {Moment} moment
		 * @return {string}
		 */
		format(moment)
		{
			const time = this.formatDefault(moment);

			if (!this.showTime)
			{
				return moment.inThisYear ? moment.format(dayMonth) : moment.format(longDate);
			}

			if (moment.isJustNow(this.justNowDelay))
			{
				return Loc.getMessage('MOBILE_UI_VIEW_JUST_NOW');
			}

			if (!this.futureAllowed && !moment.hasPassed)
			{
				return time;
			}

			if (this.skipAfterSeconds !== null && moment.isOverSeconds(this.skipAfterSeconds))
			{
				return time;
			}

			if (moment.isWithinSeconds(ONE_HOUR))
			{
				return this.formatMinutes(moment);
			}

			if (moment.isToday)
			{
				return Loc.getMessage('MOBILE_UI_VIEW_TODAY', { '#TIME#': time });
			}

			if (moment.isYesterday)
			{
				return Loc.getMessage('MOBILE_UI_VIEW_YESTERDAY', { '#TIME#': time });
			}

			if (moment.inThisYear)
			{
				return Loc.getMessage('MOBILE_UI_VIEW_DATE_AT_TIME', {
					'#DATE#': moment.format(dayMonth),
					'#TIME#': time,
				});
			}

			return Loc.getMessage('MOBILE_UI_VIEW_DATE_AT_TIME', {
				'#DATE#': moment.format(longDate),
				'#TIME#': time,
			});
		}
	}

	module.exports = { HumanDateFormatter };
});
