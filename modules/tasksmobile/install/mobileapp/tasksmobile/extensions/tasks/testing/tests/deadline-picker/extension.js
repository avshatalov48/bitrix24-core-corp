(() => {
	const require = (ext) => jn.require(ext);

	const { describe, test, expect } = require('testing');
	const { DeadlinePicker, PickerItemType } = require('tasks/deadline-picker');

	/**
	 * @param {string} workTimeEnd - end of work time e.g. 18:00
	 * @param {number} serverOffsetDifference - difference in hours between client and server offsets
	 * @param {Array<number>} weekends - array of weekends (e.g. [0, 6] - Sunday, Saturday)
	 * @param {Array<string>} holidays - array of holidays (e.g. ['1_18'] - 18 of February)
	 * @returns {object}
	 */
	const getCalendarSettings = (
		workTimeEnd,
		serverOffsetDifference = 0,
		weekends = [],
		holidays = [],
	) => {
		const clientOffset = -(new Date()).getTimezoneOffset() * 60;
		const [hours, minutes] = workTimeEnd.split(':');
		const resultWeekends = Object.fromEntries(weekends.map((day) => [day, true]));
		const resultHolidays = Object.fromEntries(holidays.map((day) => [day, true]));

		return {
			clientOffset,
			serverOffset: clientOffset + serverOffsetDifference * 3600,
			workTime: [{ end: { hours: Number(hours), minutes: Number(minutes) } }],
			weekends: resultWeekends,
			holidays: resultHolidays,
			isWeekendInLocal: (date) => Boolean(resultWeekends[date.getDay()]),
			isHolidayInLocal: (date) => Boolean(resultHolidays[`${date.getMonth()}_${date.getDate()}`]),
		};
	};

	describe('tasks:deadline-picker', () => {
		test('should correctly identify future TODAY', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings('19:00');
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.TODAY)).toEqual((new Date('2023-05-01T19:00:00')).getTime());
		});

		test('should correctly identify future TODAY with different offsets', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings('19:00', -3);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.TODAY)).toEqual((new Date('2023-05-01T19:00:00')).getTime());
		});

		test('should correctly identify future TODAY with different offsets that change day', () => {
			const now = new Date('2023-05-02T00:00:00');
			const calendarSettings = getCalendarSettings('19:00', -6);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.TODAY)).toEqual((new Date('2023-05-02T19:00:00')).getTime());
		});

		test('should correctly identify past TODAY', () => {
			const now = new Date('2023-05-01T20:00:00');
			const calendarSettings = getCalendarSettings('19:00');
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.TODAY)).toBeNull();
		});

		test('should correctly identify past TODAY at the exact moment', () => {
			const now = new Date('2023-05-01T19:00:00');
			const calendarSettings = getCalendarSettings('19:00');
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.TODAY)).toBeNull();
		});

		test('should correctly identify past TODAY with different offsets', () => {
			const now = new Date('2023-05-01T23:00:00');
			const calendarSettings = getCalendarSettings('19:00', -3);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.TODAY)).toBeNull();
		});

		test('should correctly identify past TODAY with different offsets at the exact moment', () => {
			const now = new Date('2023-05-01T22:00:00');
			const calendarSettings = getCalendarSettings('19:00', -3);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.TODAY)).toBeNull();
		});

		test('should correctly identify past TODAY with different offsets that change day', () => {
			const now = new Date('2023-05-02T02:00:00');
			const calendarSettings = getCalendarSettings('19:00', -6);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.TODAY)).toEqual((new Date('2023-05-02T19:00:00')).getTime());
		});

		test('should correctly identify past TODAY with different offsets that change day at the exact moment', () => {
			const now = new Date('2023-05-02T00:00:00');
			const calendarSettings = getCalendarSettings('19:00', -6);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.TODAY)).toEqual((new Date('2023-05-02T19:00:00')).getTime());
		});

		test('should correctly identify TOMORROW', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings('19:00');
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.TOMORROW)).toEqual((new Date('2023-05-02T19:00:00')).getTime());
		});

		test('should correctly identify TOMORROW with different offsets', () => {
			const now = new Date('2023-05-01T19:00:00');
			const calendarSettings = getCalendarSettings('19:00', -3);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.TOMORROW)).toEqual((new Date('2023-05-02T19:00:00')).getTime());
		});

		test('should correctly identify TOMORROW with different offsets that change day', () => {
			const now = new Date('2023-05-02T03:00:00');
			const calendarSettings = getCalendarSettings('19:00', -6);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.TOMORROW)).toEqual((new Date('2023-05-03T19:00:00')).getTime());
		});

		test('should correctly identify THIS WEEK with standard weekend', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings('19:00', 0, [0, 6]);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.THIS_WEEK)).toEqual((new Date('2023-05-05T19:00:00')).getTime());
		});

		test('should correctly identify THIS WEEK when it\'s holiday', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings(
				'19:00',
				0,
				[0, 6],
				['4_5'],
			);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.THIS_WEEK)).toEqual((new Date('2023-05-04T19:00:00')).getTime());
		});

		test('should correctly identify THIS WEEK with no weekend', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings('19:00', 0);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.THIS_WEEK)).toEqual((new Date('2023-05-07T19:00:00')).getTime());
		});

		test('should correctly identify THIS WEEK when it\'s today', () => {
			const now = new Date('2023-05-05T15:00:00');
			const calendarSettings = getCalendarSettings('19:00', 0, [0, 6]);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.THIS_WEEK)).toBeNull();
		});

		test('should correctly identify THIS WEEK when it\'s tomorrow', () => {
			const now = new Date('2023-05-04T15:00:00');
			const calendarSettings = getCalendarSettings('19:00', 0, [0, 6]);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.THIS_WEEK)).toBeNull();
		});

		test('should correctly identify THIS WEEK when it\'s in the past', () => {
			const now = new Date('2023-05-07T15:00:00');
			const calendarSettings = getCalendarSettings('19:00', 0, [0, 6]);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.THIS_WEEK)).toBeNull();
		});

		test('should correctly identify THIS WEEK with different offsets', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings('19:00', -3, [0, 6]);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.THIS_WEEK)).toEqual((new Date('2023-05-05T19:00:00')).getTime());
		});

		test('should correctly identify THIS WEEK with different offsets that change day', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings('19:00', -6, [0, 6]);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.THIS_WEEK)).toEqual((new Date('2023-05-05T19:00:00')).getTime());
		});

		test('should correctly identify NEXT WEEK with standard weekend', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings('19:00', 0, [0, 6]);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.NEXT_WEEK_START)).toEqual((new Date('2023-05-08T19:00:00')).getTime());
			expect(values.get(PickerItemType.NEXT_WEEK_END)).toEqual((new Date('2023-05-12T19:00:00')).getTime());
		});

		test('should correctly identify NEXT WEEK when it\'s holidays on the ends', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings(
				'19:00',
				0,
				[0, 6],
				['4_8', '4_9', '4_12'],
			);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.NEXT_WEEK_START)).toEqual((new Date('2023-05-10T19:00:00')).getTime());
			expect(values.get(PickerItemType.NEXT_WEEK_END)).toEqual((new Date('2023-05-11T19:00:00')).getTime());
		});

		test('should correctly identify NEXT WEEK with no weekend', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings('19:00', 0);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.NEXT_WEEK_START)).toEqual((new Date('2023-05-08T19:00:00')).getTime());
			expect(values.get(PickerItemType.NEXT_WEEK_END)).toEqual((new Date('2023-05-14T19:00:00')).getTime());
		});

		test('should correctly identify NEXT WEEK when it\'s tomorrow', () => {
			const now = new Date('2023-05-07T15:00:00');
			const calendarSettings = getCalendarSettings('19:00', 0, [0, 6]);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.NEXT_WEEK_START)).toBeNull();
			expect(values.get(PickerItemType.NEXT_WEEK_END)).toEqual((new Date('2023-05-12T19:00:00')).getTime());
		});

		test('should correctly identify NEXT WEEK when it\'s a week of holidays', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings(
				'19:00',
				0,
				[0, 6],
				['4_8', '4_9', '4_10', '4_11', '4_12'],
			);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.NEXT_WEEK_START)).toBeNull();
			expect(values.get(PickerItemType.NEXT_WEEK_END)).toBeNull();
		});

		test('should correctly identify NEXT WEEK when it\'s only 1 working day there', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings(
				'19:00',
				0,
				[0, 6],
				['4_8', '4_9', '4_10', '4_12'],
			);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.NEXT_WEEK_START)).toEqual((new Date('2023-05-11T19:00:00')).getTime());
			expect(values.get(PickerItemType.NEXT_WEEK_END)).toBeNull();
		});

		test('should correctly identify NEXT WEEK with different offsets', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings('19:00', -3, [0, 6]);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.NEXT_WEEK_START)).toEqual((new Date('2023-05-08T19:00:00')).getTime());
			expect(values.get(PickerItemType.NEXT_WEEK_END)).toEqual((new Date('2023-05-12T19:00:00')).getTime());
		});

		test('should correctly identify NEXT WEEK with different offsets that change day', () => {
			const now = new Date('2023-05-01T15:00:00');
			const calendarSettings = getCalendarSettings('19:00', -6, [0, 6]);
			const values = (new DeadlinePicker()).getPredefinedItemValues(calendarSettings, now);

			expect(values.get(PickerItemType.NEXT_WEEK_START)).toEqual((new Date('2023-05-08T19:00:00')).getTime());
			expect(values.get(PickerItemType.NEXT_WEEK_END)).toEqual((new Date('2023-05-12T19:00:00')).getTime());
		});
	});
})();
