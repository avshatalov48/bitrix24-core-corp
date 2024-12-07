/**
 * @module calendar/calendar-grid
 */
jn.define('calendar/calendar-grid', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { DateHelper } = require('calendar/date-helper');
	const { Color } = require('tokens');

	/**
	 * @class CalendarGrid
	 */
	class CalendarGrid extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.calendarRef = null;
			this.initialDate = this.props.initialDate;
			this.firstWeekday = this.props.firstWeekday;
			this.setCheckingDateFrom(this.initialDate * 1000);
			this.eventsMap = null;
		}

		render()
		{
			return CalendarView(
				{
					style: {
						backgroundColor: Color.bgNavigation.toHex(),
					},
					textStyle: {
						today: {
							textColor: AppTheme.colors.accentBrandBlue,
						},
						todaySelected: {
							backgroundColor: AppTheme.colors.base1,
							textColor: Color.bgNavigation.toHex(),
						},
						currentWeekNumber: {
							textColor: AppTheme.colors.accentBrandBlue,
						},
					},
					initialDate: this.initialDate,
					showWeekNumbers: this.props.showWeekNumbers,
					firstWeekday: this.firstWeekday,
					onMonthSwitched: (timestamp) => {
						this.handleMonthSwitch(timestamp);
						this.props.onMonthSwitched(timestamp);
					},
					onDateSelected: (timestamp) => {
						this.props.onDateSelected(timestamp);
					},
					ref: (ref) => {
						this.calendarRef = ref;
						this.setCheckedDates(this.eventsMap);
					},
				},
			);
		}

		/**
		 * @public
		 * @param {number} date
		 * @param {boolean} animate
		 * @returns {*}
		 */
		setDate(date, animate)
		{
			return this.calendarRef.setDate(date, animate);
		}

		/**
		 * @public
		 * Updates eventList
		 * @param eventsMap
		 */
		setEvents(eventsMap)
		{
			this.eventsMap = eventsMap;
			this.setCheckedDates(this.eventsMap);
		}

		/**
		 * @public
		 * Checks days with red points in calendar grid
		 * @param eventsMap
		 */
		setCheckedDates(eventsMap)
		{
			if (!this.calendarRef || !eventsMap)
			{
				return;
			}

			const checkedDates = [];
			const invites = this.prepareInvites(eventsMap);
			for (const type in invites)
			{
				for (const parentId in invites[type])
				{
					for (const row of invites[type][parentId])
					{
						const { event, dayCode } = row;

						const filteredByParent = invites[type][event.getParentId()];

						if (filteredByParent[0]?.dayCode === dayCode)
						{
							checkedDates.push({
								timestamp: Math.round(DateHelper.getTimestampFromDayCode(dayCode) / 1000),
								color: AppTheme.colors.accentMainAlert,
							});
						}
					}
				}
			}

			this.calendarRef.setCheckedDates(checkedDates);
		}

		/**
		 * @private
		 * @param eventsMap
		 * @returns {event, dayCode, isFullDay, isLongWithTime}[]}
		 */
		prepareInvites(eventsMap)
		{
			const invites = {};

			Object.keys(eventsMap).forEach((date) => {
				eventsMap[date].forEach((row) => {
					if (row.event)
					{
						const { event, isFullDay, isLongWithTime } = row ?? { isFullDay: null, isLongWithTime: null };

						if (event.isInvited())
						{
							let type = 'single';
							if (isLongWithTime)
							{
								type = 'long';
							}
							if (event.isRecurrence())
							{
								type = 'recurrent';
							}

							invites[type] ??= {};
							invites[type][event.getParentId()] ??= [];

							invites[type][event.getParentId()].push({
								event,
								dayCode: date,
								isFullDay,
								isLongWithTime,
							});
						}
					}
				});
			});

			if (invites['long'])
			{
				for (const parentId in invites['long'])
				{
					invites['long'][parentId] = this.sortEventsByDayCode(invites['long'][parentId]);
					invites['long'][parentId] = this.filterEventsByDayCode(
						invites['long'][parentId],
						this.checkingDayCode,
					);
				}
			}

			if (invites['recurrent'])
			{
				for (const parentId in invites['recurrent'])
				{
					invites['recurrent'][parentId] = this.sortEventsByDayCode(invites['recurrent'][parentId]);
					invites['recurrent'][parentId] = this.filterEventsByDayCode(
						invites['recurrent'][parentId],
						this.checkingDayCode,
					);
				}
			}

			return invites;
		}

		/**
		 * @private
		 * @param {{event, dayCode, isFullDay, isLongWithTime}[]} events
		 * @param {string} dayCode
		 * @returns {{event, dayCode, isFullDay, isLongWithTime}[]}
		 */
		filterEventsByDayCode(events, dayCode)
		{
			return events.filter((event) => DateHelper.compareDayCodes(event.dayCode, dayCode) >= 0);
		}

		/**
		 * @private
		 * @param {{event, dayCode, isFullDay, isLongWithTime}[]} events
		 * @returns {{event, dayCode, isFullDay, isLongWithTime}[]}
		 */
		sortEventsByDayCode(events)
		{
			return [...events].sort((event1, event2) => {
				return DateHelper.compareDayCodes(event1.dayCode, event2.dayCode);
			});
		}

		/**
		 * @private
		 * @param {number} timestamp
		 * @returns void
		 */
		handleMonthSwitch(timestamp)
		{
			const switchedDate = new Date(timestamp * 1000);
			const switchedDateTime = switchedDate.getTime();

			this.setCheckingDateFrom(switchedDateTime);

			this.setCheckedDates(this.eventsMap);
		}

		setCheckingDateFrom(from)
		{
			const date = new Date(from);
			date.setDate(1);
			date.setHours(0, 0, 0, 0);
			this.checkingDayCode = DateHelper.getDayCode(new Date(date.getTime()));
		}
	}

	module.exports = { CalendarGrid };
});
