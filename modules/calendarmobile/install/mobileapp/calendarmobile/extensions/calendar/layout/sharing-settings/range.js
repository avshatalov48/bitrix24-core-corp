/**
 * @module calendar/layout/sharing-settings/range
 */
jn.define('calendar/layout/sharing-settings/range', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { isAmPmMode } = require('utils/date/formats');

	const SharingSettingsRange = (props) => {
		const range = props.range;

		return View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
					marginVertical: 5,
				},
			},
			WeekDays(range),
			TimeInterval(range),
		);
	};

	const WeekDays = (range) => {
		return View(
			{
				style: {
					width: '50%',
					flexDirection: 'row',
					alignItems: 'center',
				},
			},
			Image({
				svg: {
					content: Svg.Check,
				},
				style: {
					width: 14,
					height: 14,
				},
			}),
			Text(
				{
					style: {
						marginLeft: 6,
						width: '80%',
						color: AppTheme.colors.base3,
					},
					text: range.getWeekdaysFormatted(),
					numberOfLines: 1,
					ellipsize: 'end',
				},
			),
		);
	};

	const TimeInterval = (range) => {
		return View(
			{
				style: {
					width: '50%',
					flexDirection: 'row',
					alignItems: 'center',
				},
			},
			Time(range.getFromFormatted()),
			Dash(),
			Time(range.getToFormatted()),
		);
	};

	const Time = (time) => {
		const amPm = (time.match(/(am|pm)/) ?? [])[0] ?? '';
		const timeWithoutAmPm = time.replace(/( am| pm)/, '');

		return View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
					justifyContent: 'center',
					width: isAmPmMode() ? 53 : 40,
				},
			},
			Text(
				{
					style: {
						color: AppTheme.colors.base3,
					},
					text: timeWithoutAmPm,
					numberOfLines: 1,
				},
			),
			View(
				{},
				Text(
					{
						style: {
							fontSize: 10,
							color: AppTheme.colors.base3,
						},
						text: amPm.toUpperCase(),
					},
				),
			),
		);
	};

	const Dash = () => {
		return View(
			{
				style: {
					height: 1,
					width: 5,
					marginHorizontal: 5,
					backgroundColor: AppTheme.colors.base3,
				},
			},
		);
	};

	const Svg = {
		Check: `<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.43816 7.56148L5.39509 6.51841L4.65772 7.25579L6.39803 8.9961L6.39875 8.99538L6.43888 9.03551L9.67607 5.79832L8.9387 5.06094L6.43816 7.56148ZM6.96561 11.648C4.39348 11.648 2.30835 9.56289 2.30835 6.99076C2.30835 4.41862 4.39348 2.3335 6.96561 2.3335C9.53774 2.3335 11.6229 4.41862 11.6229 6.99076C11.6229 9.56289 9.53774 11.648 6.96561 11.648Z" fill="${AppTheme.colors.accentMainSuccess}"/></svg>`,
	};

	module.exports = { SharingSettingsRange };
});
