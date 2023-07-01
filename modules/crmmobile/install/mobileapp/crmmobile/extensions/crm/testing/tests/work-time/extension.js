(() => {
	const require = (ext) => jn.require(ext);

	const { describe, test, expect } = require('testing');
	const { WorkTimeMoment } = require('crm/work-time');
	const { Moment } = require('utils/date');

	const testCalendar = {
		WEEK_START: 'MO',
		TIME_FROM: '9:0',
		TIME_TO: '19:0',
		HOLIDAYS: ['1.01', '2.01', '7.01', '23.02', '8.03', '1.05', '9.05', '12.06', '4.11'],
		DAY_OFF: ['SA', 'SU'],
	};

	describe('crm: work time', () => {
		test('basic next working day calculation', () => {
			const currentTime = '2022-11-19T12:10:00+03:00';
			const moment = (new Moment(currentTime)).setNow(new Moment(currentTime));
			const workTimeMoment = new WorkTimeMoment(moment, testCalendar);

			const actual = workTimeMoment.getNextWorkingDay(3).moment;
			const expected = new Moment('2022-11-23T12:10:00+03:00');

			expect(expected.equals(actual)).toBeTrue();
		});

		test('holidays', () => {
			const januaryFirst = new Moment('2022-01-01T12:00:00+03:00');
			const decemberFirst = new Moment('2022-12-01T12:00:00+03:00');

			const workTimeJan = new WorkTimeMoment(januaryFirst, testCalendar);
			const workTimeDec = new WorkTimeMoment(decemberFirst, testCalendar);

			expect(workTimeJan.isHoliday()).toBeTrue();
			expect(workTimeDec.isHoliday()).toBeFalse();
		});

		test('day offs', () => {
			const sunday = new Moment('2022-12-04T12:00:00+03:00');
			const monday = new Moment('2022-12-05T12:00:00+03:00');

			const workTimeSunday = new WorkTimeMoment(sunday, testCalendar);
			const workTimeMonday = new WorkTimeMoment(monday, testCalendar);

			expect(workTimeSunday.isDayOff()).toBeTrue();
			expect(workTimeMonday.isDayOff()).toBeFalse();
		});

		test('working time', () => {
			const before = new WorkTimeMoment(new Moment('2022-12-05T07:00:00+03:00'), testCalendar);
			const inside = new WorkTimeMoment(new Moment('2022-12-05T12:00:00+03:00'), testCalendar);
			const after = new WorkTimeMoment(new Moment('2022-12-05T20:00:00+03:00'), testCalendar);

			expect(before.isWorkingTime()).toBeFalse();
			expect(inside.isWorkingTime()).toBeTrue();
			expect(after.isWorkingTime()).toBeFalse();
		});
	});
})();
