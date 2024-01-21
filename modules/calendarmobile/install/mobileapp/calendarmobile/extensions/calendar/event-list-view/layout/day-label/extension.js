/**
 * @module calendar/event-list-view/layout/day-label
 */
jn.define('calendar/event-list-view/layout/day-label', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Moment } = require('utils/date');
	const { dayOfWeekMonth, fullDate } = require('utils/date/formats');

	const DayLabel = (props) => {
		const moment = Moment.createFromTimestamp(props.date / 1000);
		const dateColor = moment.isToday ? AppTheme.colors.accentBrandBlue : AppTheme.colors.base1;
		const format = moment.inThisYear ? dayOfWeekMonth() : fullDate();

		return View(
			{
				style: {
					flexDirection: 'row',
					paddingLeft: 20,
					paddingRight: 20,
					paddingTop: 20,
					paddingBottom: 10,
				},
				testId: `day_label_${props.date}`,
			},
			Text(
				{
					style: {
						...textStyles,
						color: dateColor,
					},
					text: moment.format(format).toUpperCase(),
				},
			),
		);
	};

	const textStyles = {
		fontWeight: '600',
		fontSize: 14,
		lineSpacing: 3,
	};

	module.exports = { DayLabel };
});
