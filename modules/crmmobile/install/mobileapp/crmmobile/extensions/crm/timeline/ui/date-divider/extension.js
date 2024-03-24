/**
 * @module crm/timeline/ui/date-divider
 */
jn.define('crm/timeline/ui/date-divider', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { Moment } = require('utils/date');
	const { dayMonth, longDate } = require('utils/date/formats');
	const { Divider } = require('layout/ui/timeline/components/divider');

	function DateDivider({ moment = null, showLine = true, onLayout })
	{
		if (moment === null)
		{
			moment = new Moment();
		}

		const color = moment.isToday ? AppTheme.colors.accentBrandBlue : AppTheme.colors.base6;
		const textColor = moment.isToday ? AppTheme.colors.base1 : AppTheme.colors.base3;
		const text = (() => {
			switch (true)
			{
				case moment.isToday:
					return Loc.getMessage('CRM_TIMELINE_HISTORY_TODAY');
				case moment.isYesterday:
					return Loc.getMessage('CRM_TIMELINE_HISTORY_YESTERDAY');
				case moment.inThisYear:
					return moment.format(dayMonth());
				default:
					return moment.format(longDate());
			}
		})();

		return Divider({
			color,
			text,
			textColor,
			showLine,
			onLayout,
		});
	}

	module.exports = { DateDivider };
});
