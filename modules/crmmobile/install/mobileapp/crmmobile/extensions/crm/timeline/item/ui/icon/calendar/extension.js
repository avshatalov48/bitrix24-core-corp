/**
 * @module crm/timeline/item/ui/icon/calendar
 */
jn.define('crm/timeline/item/ui/icon/calendar', (require, exports, module) => {
	const { Moment } = require('utils/date');
	const { shortTime } = require('utils/date/formats');
	const AppTheme = require('apptheme');

	const isAndroid = Application.getPlatform() === 'android';

	function TimelineItemCalendar({ timestamp })
	{
		const moment = Moment.createFromTimestamp(timestamp);
		const day = moment.format('DD');
		const month = moment.format('MMMM').toLocaleUpperCase(env.languageId);
		const time = moment.format(`E ${shortTime()}`).toLocaleUpperCase(env.languageId);
		const accentColor = AppTheme.colors.accentMainWarning;

		return View(
			{
				style: {
					width: 52,
					height: 53,
				},
			},
			Image({
				style: {
					position: 'absolute',
					width: 52,
					height: 53,
				},
				resizeMode: 'contain',
				svg: {
					content: `<svg width="50" height="51" viewBox="0 0 50 51" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M50 8C50 5.23858 47.7614 3 45 3H5C2.23858 3 -2.38419e-06 5.23858 -2.38419e-06 8V46C-2.38419e-06 48.7614 2.23857 51 5 51H45C47.7614 51 50 48.7614 50 46L50 8Z" fill="${accentColor}"/><path d="M50 8C50 5.23858 47.7614 3 45 3H5C2.23858 3 -2.38419e-06 5.23858 -2.38419e-06 8V46C-2.38419e-06 48.7614 2.23857 51 5 51H45C47.7614 51 50 48.7614 50 46L50 8Z" fill="${AppTheme.colors.base8}" fill-opacity="0.5"/><rect x="49" y="50" width="48" height="47" rx="4" transform="rotate(180 49 50)" fill="${AppTheme.colors.base8}"/><path d="M50 6.32977C50 2.83394 47.1661 0 43.6702 0H6.32977C2.83393 0 0 2.83394 0 6.32978V9H50V6.32977Z" fill="${accentColor}"/><ellipse opacity="0.5" rx="1.89235" ry="1.90268" transform="matrix(-1 0 0 1 34.5923 4.90268)" fill="${AppTheme.colors.base8}"/><ellipse opacity="0.5" rx="1.89235" ry="1.90268" transform="matrix(-1 0 0 1 14.8076 4.90268)" fill="${AppTheme.colors.base8}"/><rect opacity="0.161457" width="50" height="1" transform="matrix(-1 0 0 1 50 9)"/></svg>`,
				},
			}),
			View(
				{
					style: {
						paddingTop: isAndroid ? 8 : 10,
						paddingBottom: 8,
						alignItems: 'center',
					},
				},
				Text({
					testId: 'TimelineItemIconCalendarDay',
					text: day,
					style: {
						color: AppTheme.colors.base2,
						fontSize: 17,
						fontWeight: '700',
					},
				}),
				Text({
					testId: 'TimelineItemIconCalendarMonth',
					text: month,
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						color: AppTheme.colors.base4,
						fontSize: 7,
						fontWeight: '700',
						marginTop: isAndroid ? -2 : -1,
						marginBottom: isAndroid ? 0 : 1,
					},
				}),
				Text({
					testId: 'TimelineItemIconCalendarTime',
					text: time,
					numberOfLines: 1,
					ellipsize: 'start',
					style: {
						color: accentColor,
						fontSize: time.length > 10 ? 6 : 7,
						fontWeight: '700',
					},
				}),
			),
		);
	}

	module.exports = { TimelineItemCalendar };
});
