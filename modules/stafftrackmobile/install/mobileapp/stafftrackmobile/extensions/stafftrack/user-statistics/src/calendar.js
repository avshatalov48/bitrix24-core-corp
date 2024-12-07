/**
 * @module stafftrack/user-statistics/calendar
 */
jn.define('stafftrack/user-statistics/calendar', (require, exports, module) => {
	const { Color, Component } = require('tokens');
	const { PureComponent } = require('layout/pure-component');
	const { DateHelper } = require('stafftrack/date-helper');
	const { withCurrentDomain } = require('utils/url');

	const calendarSettings = jnExtensionData.get('stafftrack:user-statistics').calendarSettings;

	const imagesPath = '/bitrix/mobileapp/stafftrackmobile/extensions/stafftrack/user-statistics/images/';
	const calendarCross = `${imagesPath}calendar-cross.svg`;
	const calendarCheck = `${imagesPath}calendar-check.svg`;

	class Calendar extends PureComponent
	{
		constructor(props)
		{
			super(props);

			const isAndroid = Application.getPlatform() === 'android';
			this.timezoneOffset = isAndroid ? 0 : new Date().getTimezoneOffset() * 60000;
			this.todayMonthCode = DateHelper.getMonthCode(new Date());

			this.refs = {
				calendarRef: null,
			};

			this.iconDates = [];

			this.setShifts(props.shifts);
		}

		render()
		{
			return View(
				{
					style: {
						borderRightWidth: 1,
						borderBottomWidth: 1,
						borderLeftWidth: 1,
						borderRightColor: Color.bgSeparatorPrimary.toHex(),
						borderBottomColor: Color.bgSeparatorPrimary.toHex(),
						borderLeftColor: Color.bgSeparatorPrimary.toHex(),
						borderBottomLeftRadius: Component.cardCorner.toNumber(),
						borderBottomRightRadius: Component.cardCorner.toNumber(),
					},
				},
				CalendarView(
					{
						testId: 'stafftrack-user-statistics-calendar',
						firstWeekday: calendarSettings.firstWeekday,
						textStyle: {
							today: {
								textColor: Color.accentMainPrimary.toHex(),
							},
							todaySelected: {
								textColor: Color.accentMainPrimary.toHex(),
								backgroundColor: '#0000',
							},
							selected: {
								textColor: Color.base3.toHex(),
								backgroundColor: '#0000',
							},
						},
						icons: [
							{ type: calendarIconTypes.cross, iconUrl: withCurrentDomain(calendarCross) },
							{ type: calendarIconTypes.check, iconUrl: withCurrentDomain(calendarCheck) },
						],
						onMonthSwitched: (timestamp) => this.props.onMonthSwitched(timestamp),
						onDateSelected: (timestamp) => {
							if (!this.dateSetProgrammatically)
							{
								this.props.onDateSelected(timestamp);
							}

							this.dateSetProgrammatically = false;
						},
						ref: (ref) => {
							this.refs.calendarRef = ref;
							this.setIconDates();
						},
					},
				),
			);
		}

		setMonth(monthCode)
		{
			this.dateSetProgrammatically = true;

			const date = DateHelper.getDateFromMonthCode(monthCode);
			if (monthCode === this.todayMonthCode)
			{
				date.setDate(new Date().getDate());
			}

			return this.refs.calendarRef?.setDate(Math.floor((date.getTime() - this.timezoneOffset) / 1000), false);
		}

		setShifts(shifts)
		{
			this.shifts = shifts;
			setTimeout(() => this.setIconDates(), 200);
		}

		setIconDates()
		{
			if (!this.refs.calendarRef)
			{
				return;
			}

			const timestamps = [];
			for (const shift of this.shifts)
			{
				const timestamp = Math.round(DateHelper.getTimestampFromDate(shift.getShiftDate()) / 1000);
				const color = shift.isWorkingStatus()
					? Color.accentMainSuccess.toHex()
					: Color.accentMainWarning.toHex()
				;
				const type = shift.isWorkingStatus()
					? calendarIconTypes.check
					: calendarIconTypes.cross
				;

				if (this.iconDates[timestamp]?.color !== color)
				{
					timestamps.push(timestamp);

					this.iconDates[timestamp] = { timestamp, color, type };
				}
			}

			if (timestamps.length > 0)
			{
				this.refs.calendarRef.setIconDates(Object.values(this.iconDates));
			}
		}
	}

	const calendarIconTypes = {
		cross: 'cross',
		check: 'check',
	};

	module.exports = { Calendar };
});
