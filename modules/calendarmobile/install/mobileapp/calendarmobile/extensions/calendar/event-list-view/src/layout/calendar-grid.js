/**
 * @module calendar/event-list-view/layout/calendar-grid
 */
jn.define('calendar/event-list-view/layout/calendar-grid', (require, exports, module) => {
	const { Type } = require('type');
	const { Color } = require('tokens');

	const { DateHelper } = require('calendar/date-helper');
	const { EventModel } = require('calendar/model/event');
	const { EventManager } = require('calendar/data-managers/event-manager');
	const { SettingsManager } = require('calendar/data-managers/settings-manager');
	const { SectionManager } = require('calendar/data-managers/section-manager');

	const store = require('statemanager/redux/store');
	const { selectByMonth } = require('calendar/statemanager/redux/slices/events');

	const { State, observeState } = require('calendar/event-list-view/state');

	const isAndroid = Application.getPlatform() === 'android';
	const monthSwitchAnimationDuration = 300;

	/**
	 * @class CalendarGrid
	 */
	class CalendarGrid extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.timezoneOffset = isAndroid ? 0 : DateHelper.timezoneOffset;

			this.calendarRef = null;
		}

		componentDidMount()
		{
			super.componentDidMount();

			this.setMonthTitle(State.selectedDate);
		}

		componentWillReceiveProps(props)
		{
			super.componentWillReceiveProps(props);

			this.setCheckedDates(State.selectedDate);
			this.setMonthTitle(State.selectedDate);
		}

		render()
		{
			return View(
				{},
				!this.props.isHidden && CalendarView(
					{
						style: {
							backgroundColor: Color.bgNavigation.toHex(),
							borderBottomWidth: 1,
							borderBottomColor: Color.bgSeparatorSecondary.toHex(),
						},
						textStyle: {
							today: {
								textColor: Color.accentMainPrimary.toHex(),
							},
							todaySelected: {
								backgroundColor: Color.accentMainPrimary.toHex(),
								textColor: Color.baseWhiteFixed.toHex(),
							},
							currentWeekNumber: {
								textColor: Color.accentMainPrimary.toHex(),
							},
						},
						initialDate: Math.round((State.selectedDate.getTime() - this.timezoneOffset) / 1000),
						showWeekNumbers: this.props.showWeekNumbers,
						firstWeekday: SettingsManager.getFirstWeekday(),
						onMonthSwitched: this.onMonthSwitched,
						onDateSelected: this.onDateSelected,
						ref: this.#bindCalendarRef,
					},
				),
			);
		}

		#bindCalendarRef = (ref) => {
			this.calendarRef = ref;
			this.setCheckedDates(State.selectedDate);
		};

		onDateSelected = (timestamp) => {
			const date = new Date(timestamp * 1000 + this.timezoneOffset);

			State.setSelectedDate(date);
		};

		onMonthSwitched = async (timestamp) => {
			if (isAndroid)
			{
				await new Promise((resolve) => {
					setTimeout(resolve, monthSwitchAnimationDuration);
				});
			}

			const date = new Date(timestamp * 1000 + this.timezoneOffset);

			this.setMonthTitle(date);
			this.setDateAfterMonthSwitch(date);

			const startDate = new Date(date.getTime());
			const endDate = new Date(startDate.getFullYear(), startDate.getMonth() + 1);

			if (!EventManager.hasRange(startDate, endDate))
			{
				State.setIsLoading(true);
				await EventManager.loadList(startDate, endDate);
				State.setIsLoading(false);
			}

			this.setCheckedDates(date);
		};

		setMonthTitle(date)
		{
			const text = DateHelper.getShortMonthName(date);
			const detailText = date.getFullYear().toString();

			this.props.layout.setTitle({ text, detailText, useLargeTitleMode: true });
		}

		setDateAfterMonthSwitch(date)
		{
			if (State.selectedDate.getMonth() === date.getMonth())
			{
				return;
			}

			const today = new Date();
			if (date.getMonth() === today.getMonth() && date.getFullYear() === today.getFullYear())
			{
				date.setDate(today.getDate());
			}

			this.calendarRef?.setDate(Math.round((date.getTime() - this.timezoneOffset) / 1000), false);

			State.setSelectedDate(date);
		}

		/**
		 * @public
		 *
		 * Checks grid with gray dots (for events) or red dots (for invites)
		 */
		setCheckedDates(date)
		{
			if (!this.calendarRef)
			{
				return;
			}

			const events = selectByMonth(store.getState(), {
				date,
				sectionIds: SectionManager.getActiveSectionsIds(),
				showDeclined: false,
			});

			const checkedDates = [];
			const inviteDates = [];
			const invites = this.prepareInvites(events);
			const eventTimestamps = events.flatMap((event) => {
				const timestamps = [];

				let timestamp = event.dateFromTs;
				do
				{
					timestamps.push(Math.round((timestamp - this.timezoneOffset) / 1000));
					timestamp += DateHelper.dayLength;
				}
				while (timestamp < event.dateToTs);

				return timestamps;
			});

			Object.keys(invites).forEach((type) => {
				Object.keys(invites[type]).forEach((parentId) => {
					Object.values(invites[type][parentId]).forEach((row) => {
						const { event, dayCode } = row;

						const filteredByParent = invites[type][event.getParentId()];

						if (filteredByParent[0]?.dayCode === dayCode)
						{
							const inviteTimestamp = Math.round(DateHelper.getTimestampFromDayCode(dayCode) / 1000);

							inviteDates.push(inviteTimestamp);
							checkedDates.push({
								timestamp: inviteTimestamp,
								color: Color.accentMainAlert.toHex(),
							});
						}
					});
				});
			});

			eventTimestamps.forEach((timestamp) => {
				if (!inviteDates.includes(timestamp))
				{
					checkedDates.push({
						timestamp,
						color: Color.base5.toHex(),
					});
				}
			});

			this.calendarRef.setCheckedDates(checkedDates);
		}

		/**
		 * @private
		 * @returns {{event, dayCode, isFullDay, isLongWithTime}[]}
		 */
		prepareInvites(events)
		{
			const invites = {};

			events.forEach((reduxEvent) => {
				const event = EventModel.fromReduxModel(reduxEvent);
				const isLongWithTime = false;

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
						dayCode: DateHelper.getDayCode(event.dateFrom),
						isFullDay: event.isFullDay(),
						isLongWithTime,
					});
				}
			});

			const monthFirstDate = new Date(State.selectedDate.getFullYear(), State.selectedDate.getMonth());
			const monthFirstDayCode = DateHelper.getDayCode(monthFirstDate);

			if (!Type.isNil(invites.long))
			{
				Object.keys(invites.long).forEach((parentId) => {
					invites.long[parentId] = this.sortEventsByDayCode(invites.long[parentId]);
					invites.long[parentId] = this.filterEventsByDayCode(
						invites.long[parentId],
						monthFirstDayCode,
					);
				});
			}

			if (!Type.isNil(invites.recurrent))
			{
				Object.keys(invites.recurrent).forEach((parentId) => {
					invites.recurrent[parentId] = this.sortEventsByDayCode(invites.recurrent[parentId]);
					invites.recurrent[parentId] = this.filterEventsByDayCode(
						invites.recurrent[parentId],
						monthFirstDayCode,
					);
				});
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
			return [...events].sort((event1, event2) => DateHelper.compareDayCodes(event1.dayCode, event2.dayCode));
		}
	}

	const mapStateToProps = (state) => ({
		showWeekNumbers: state.showWeekNumbers,
		isHidden: state.isSearchMode && !state.invitesSelected,
		events: selectByMonth(store.getState(), {
			date: state.selectedDate,
			sectionIds: SectionManager.getActiveSectionsIds(),
			showDeclined: state.showDeclined,
		}),
	});

	module.exports = { CalendarGrid: observeState(CalendarGrid, mapStateToProps) };
});
