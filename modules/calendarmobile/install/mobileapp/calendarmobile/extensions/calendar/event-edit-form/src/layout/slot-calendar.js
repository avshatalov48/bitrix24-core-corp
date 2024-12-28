/**
 * @module calendar/event-edit-form/layout/slot-calendar
 */
jn.define('calendar/event-edit-form/layout/slot-calendar', (require, exports, module) => {
	const { Color, Indent } = require('tokens');

	const { SettingsManager } = require('calendar/data-managers/settings-manager');
	const { DateHelper } = require('calendar/date-helper');
	const { MonthSelector } = require('calendar/event-edit-form/layout/month-selector');
	const { State, observeState } = require('calendar/event-edit-form/state');

	const isAndroid = Application.getPlatform() === 'android';
	const calendarSlotsEnabled = Application.getApiVersion() >= 56;
	const monthSwitchAnimationDuration = 300;

	/**
	 * @class SlotCalendar
	 */
	class SlotCalendar extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.refs = {
				monthSelector: null,
				calendar: null,
			};

			this.todayDate = new Date();
			this.todayMonthCode = DateHelper.getMonthCode(this.todayDate);

			this.timezoneOffset = DateHelper.timezoneOffset;
			this.calendarTimezoneOffset = isAndroid ? 0 : this.timezoneOffset;
			this.initialDate = this.getInitialDate();
		}

		get selectedDayCode()
		{
			return this.props.selectedDayCode;
		}

		get selectedDateTs()
		{
			return this.dayCodeToTs(this.selectedDayCode);
		}

		get selectedDateMonthCode()
		{
			return DateHelper.getMonthCode(DateHelper.getDateFromDayCode(this.selectedDayCode));
		}

		componentDidUpdate(prevProps, prevState)
		{
			if (!this.props.selectedDayCode)
			{
				return;
			}

			if (
				this.hasNewSlots(prevProps.slots, this.props.slots)
				|| this.monthChanged(prevProps.selectedDayCode, this.props.selectedDayCode)
			)
			{
				this.setSlotDates();
			}

			if (this.props.todayButtonClick)
			{
				this.handleTodayButtonClick();
			}
		}

		monthChanged(prevDate, currentDate)
		{
			const prev = DateHelper.getDateFromDayCode(prevDate);
			const current = DateHelper.getDateFromDayCode(currentDate);

			return prev.getMonth() !== current.getMonth();
		}

		hasNewSlots(prevSlots, currentSlots)
		{
			return Object.keys(prevSlots).length === 0 && Object.keys(currentSlots).length > 0;
		}

		handleTodayButtonClick()
		{
			const date = DateHelper.getDateFromMonthCode(this.todayMonthCode);
			date.setDate(this.todayDate.getDate());

			this.refs.calendar?.setDate(Math.floor((date.getTime() - this.getSetDateOffset(date)) / 1000), true);
		}

		render()
		{
			return View(
				{},
				View(
					{
						style: {
							alignItems: 'center',
							marginBottom: Indent.XL.toNumber(),
						},
					},
					new MonthSelector({
						testId: 'calendar-event-edit-form-month-selector',
						monthCode: this.selectedDateMonthCode,
						onPick: this.onSelectorPickHandler,
						ref: this.#bindMonthSelector,
					}),
				),
				CalendarView({
					testId: 'calendar-event-edit-form-slot-calendar',
					firstWeekday: this.props.firstWeekday,
					initialDate: this.initialDate,
					textStyle: {
						today: {
							textColor: Color.accentMainPrimary.toHex(),
						},
						todaySelected: {
							textColor: Color.baseWhiteFixed.toHex(),
							backgroundColor: Color.accentMainPrimary.toHex(),
						},
						selected: {
							textColor: Color.base8.toHex(),
							backgroundColor: Color.base2.toHex(),
						},
					},
					onMonthSwitched: this.onMonthSwitchedHandler,
					onDateSelected: this.onDateSelectedHandler,
					ref: this.#bindCalendar,
				}),
			);
		}

		#bindMonthSelector = (ref) => {
			this.refs.monthSelector = ref;
		};

		#bindCalendar = (ref) => {
			this.refs.calendar = ref;
			this.setSlotDates();
		};

		setSlotDates()
		{
			if (!this.props.slots || !this.refs?.calendar)
			{
				return;
			}

			const date = DateHelper.getDateFromDayCode(this.selectedDayCode);

			const slotDates = Object.keys(this.props.slots)
				.filter((day) => this.hasWorkTimeSlots(date, day))
				.map((day) => {
					const timestamp = (date.setDate(day) - this.calendarTimezoneOffset) / 1000;

					return {
						timestamp,
						color: calendarSlotsEnabled ? Color.accentSoftBlue2.toHex() : Color.accentMainPrimary.toHex(),
					};
				})
			;

			if (calendarSlotsEnabled)
			{
				this.refs.calendar.setSlotDates(slotDates);
			}
			else
			{
				this.refs.calendar.setCheckedDates(slotDates);
			}
		}

		/**
		 * @param date {Date}
		 * @param day {number}
		 * @returns {boolean}
		 */
		hasWorkTimeSlots(date, day)
		{
			const slots = this.props.slots[day];
			if (slots.length === 0)
			{
				return false;
			}

			date.setDate(day);

			const weekDay = date.getDay();
			if (SettingsManager.getWeekHolidays().includes(weekDay))
			{
				return false;
			}

			const dayMonthCode = DateHelper.getDayMonthCode(date);
			if (SettingsManager.getYearHolidays().includes(dayMonthCode))
			{
				return false;
			}

			const timeFrom = Number(SettingsManager.getWorkTimeStart() * 3600);
			const timeTo = Number(SettingsManager.getWorkTimeEnd() * 3600);

			return slots.some((slot) => {
				const slotFrom = ((slot.from % DateHelper.dayLength) - this.timezoneOffset) / 1000;
				const slotTo = ((slot.to % DateHelper.dayLength) - this.timezoneOffset) / 1000;

				return slotFrom >= timeFrom && slotTo <= timeTo && slotFrom < slotTo;
			});
		}

		onSelectorPickHandler = (monthCode) => {
			this.setMonth(monthCode);
		};

		onMonthSwitchedHandler = async (timestamp) => {
			if (isAndroid)
			{
				// eslint-disable-next-line promise/param-names
				await new Promise((r) => {
					setTimeout(r, monthSwitchAnimationDuration);
				});
			}

			const date = new Date(timestamp * 1000 + this.calendarTimezoneOffset);
			const monthCode = DateHelper.getMonthCode(date);
			this.setMonth(monthCode);
		};

		onDateSelectedHandler = (timestamp) => {
			const date = new Date(timestamp * 1000 + this.calendarTimezoneOffset);
			const selectedDayCode = DateHelper.getDayCode(date);
			State.setSelectedDayCode(selectedDayCode);
		};

		setMonth(monthCode)
		{
			this.refs.monthSelector?.setMonthCode(monthCode);

			if (this.selectedDateMonthCode === monthCode)
			{
				return;
			}

			const date = DateHelper.getDateFromMonthCode(monthCode);
			if (monthCode === this.todayMonthCode)
			{
				date.setDate(this.todayDate.getDate());
			}

			this.refs.calendar?.setDate(Math.floor((date.getTime() - this.getSetDateOffset(date)) / 1000), true);
		}

		dayCodeToTs(dayCode)
		{
			let timestamp = Date.now();
			if (dayCode)
			{
				timestamp = DateHelper.getTimestampFromDayCode(dayCode);
			}

			return timestamp;
		}

		getInitialDate()
		{
			const offset = isAndroid ? this.timezoneOffset : 0;

			return Math.floor((this.selectedDateTs + offset) / 1000);
		}

		getSetDateOffset(date)
		{
			return isAndroid ? 0 : DateHelper.getDateTimezoneOffset(date);
		}
	}

	const mapStateToProps = (state) => ({
		firstWeekday: state.firstWeekday,
		selectedSlot: state.selectedSlot,
		selectedDayCode: state.selectedDayCode,
		todayButtonClick: state.todayButtonClick,
		slots: state.slots,
	});

	module.exports = { SlotCalendar: observeState(SlotCalendar, mapStateToProps) };
});
