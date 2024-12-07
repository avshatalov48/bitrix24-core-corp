/**
 * @module calendar/event-list-view/day-header
 */
jn.define('calendar/event-list-view/day-header', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Moment } = require('utils/date');
	const { dayOfWeekMonth, fullDate } = require('utils/date/formats');
	const { PureComponent } = require('layout/pure-component');
	const { Color } = require('tokens');

	/**
	 * @class DayHeader
	 */
	class DayHeader extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				date: props.date,
			};
		}

		render()
		{
			const moment = Moment.createFromTimestamp(this.state.date.getTime() / 1000);
			const dateColor = moment.isToday ? AppTheme.colors.accentBrandBlue : AppTheme.colors.base1;
			const format = moment.inThisYear ? dayOfWeekMonth() : fullDate();

			return View(
				{
					style: {
						borderTopColor: AppTheme.colors.base6,
						borderTopWidth: 0.5,
						borderBottomWidth: 0.5,
						borderBottomColor: AppTheme.colors.base6,
						paddingLeft: 20,
						paddingRight: 20,
						paddingTop: 10,
						paddingBottom: 10,
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				Text(
					{
						style: {
							...styles.text,
							color: dateColor,
						},
						text: moment.format(format).toUpperCase(),
						testId: `day_header_date_${this.state.date.getTime()}`,
					},
				),
			);
		}

		updateDate(date)
		{
			this.setState({ date });
		}
	}

	const styles = {
		text: {
			fontWeight: '600',
			fontSize: 14,
			lineSpacing: 3,
		},
	};

	module.exports = { DayHeader };
});
