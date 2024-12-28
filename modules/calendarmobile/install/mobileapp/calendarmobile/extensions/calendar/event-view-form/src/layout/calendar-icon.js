/**
 * @module calendar/event-view-form/layout/calendar-icon
 */
jn.define('calendar/event-view-form/layout/calendar-icon', (require, exports, module) => {
	const { Color } = require('tokens');
	const { H3 } = require('ui-system/typography/heading');
	const { Text7 } = require('ui-system/typography/text');

	const { DateHelper } = require('calendar/date-helper');

	const CalendarIcon = ({ color, dateFromTs }) => {
		const date = new Date(dateFromTs);

		return View(
			{
				testId: 'calendar-event-view-form-calendar-icon',
				style: {
					borderRadius: 8,
					borderColor: color,
					borderWidth: 2,
					paddingHorizontal: 5,
					paddingTop: 18,
					paddingBottom: 4,
					alignItems: 'center',
					justifyContent: 'center',
					position: 'relative',
					minWidth: 50,
					maxHeight: 50,
				},
			},
			View({
				style: {
					position: 'absolute',
					top: 0,
					left: 0,
					right: 0,
					height: 14,
					backgroundColor: color,
				},
			}),
			View({
				style: {
					position: 'absolute',
					top: 5,
					left: 14,
					height: 5,
					width: 5,
					backgroundColor: Color.base8.toHex(),
					borderRadius: 50,
				},
			}),
			View({
				style: {
					position: 'absolute',
					top: 5,
					right: 14,
					height: 5,
					width: 5,
					backgroundColor: Color.base8.toHex(),
					borderRadius: 50,
				},
			}),
			H3({
				testId: 'calendar-event-view-form-calendar-icon-date',
				text: String(date.getDate()),
				style: {
					marginVertical: -4,
				},
			}),
			Text7({
				testId: 'calendar-event-view-form-calendar-icon-month',
				text: DateHelper.getShortMonthName(date),
				color: Color.base2,
				accent: true,
			}),
		);
	};

	module.exports = { CalendarIcon };
});
