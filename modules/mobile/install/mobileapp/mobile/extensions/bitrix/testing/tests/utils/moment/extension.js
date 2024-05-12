(() => {
	const require = (ext) => jn.require(ext);

	const { describe, test, expect } = require('testing');
	const { Moment } = require('utils/date');

	describe('date moment.js utils test', () => {
		test('timestamp should return timestamp in seconds', () => {
			const moment = new Moment('2023-06-03T10:00:00');

			expect(moment.timestamp).toBe(moment.date.getTime() / 1000);
		});

		test('createFromTimestamp should create a new Moment instance from UNIX timestamp in seconds', () => {
			const timestamp = 1_625_326_200;
			const expectedDate = new Date(timestamp * 1000);
			const result = Moment.createFromTimestamp(timestamp);

			expect(result.date.getTime()).toEqual(expectedDate.getTime());
		});

		test('getNow should return a Moment object for the current time', () => {
			const moment = new Moment();
			const result = moment.getNow();

			expect(result instanceof Moment).toBe(true);
		});

		test('getNow should return a preassigned moment, if it was set', () => {
			const moment = new Moment();
			const preassignedMoment = new Moment();
			moment.setNow(preassignedMoment);
			const result = moment.getNow();

			expect(result).toEqual(preassignedMoment);
		});

		test('isToday should return true if the moment is today', () => {
			const today = new Moment('2023-06-03T10:00:00');
			today.setNow(new Moment('2023-06-03T16:00:00'));

			expect(today.isToday).toBe(true);

			const tomorrow = new Moment('2023-06-03T10:00:00');
			tomorrow.setNow(new Moment('2023-06-02T16:00:00'));

			expect(tomorrow.isToday).toBe(false);
		});

		test('isYesterday should return true if the moment is yesterday', () => {
			const yesterday = new Moment('2023-06-03T10:00:00');
			yesterday.setNow(new Moment('2023-06-04T16:00:00'));

			expect(yesterday.isYesterday).toBe(true);

			const today = new Moment('2023-06-03T10:00:00');
			today.setNow(new Moment('2023-06-03T16:00:00'));

			expect(today.isYesterday).toBe(false);
		});

		test('isTomorrow should return true if the moment is tomorrow', () => {
			const tomorrow = new Moment('2023-06-03T10:00:00');
			tomorrow.setNow(new Moment('2023-06-02T16:00:00'));

			expect(tomorrow.isTomorrow).toBe(true);

			const today = new Moment('2023-06-03T10:00:00');
			today.setNow(new Moment('2023-06-03T16:00:00'));

			expect(today.isTomorrow).toBe(false);
		});

		test('inThisWeek should return true if the moment is in this week', () => {
			const thisWeek = new Moment('2023-10-25T10:00:00');

			thisWeek.setNow(new Moment('2023-10-25T10:00:00'));
			expect(thisWeek.inThisWeek).toBe(true);

			thisWeek.setNow(new Moment('2023-10-23T10:00:00'));
			expect(thisWeek.inThisWeek).toBe(true);

			thisWeek.setNow(new Moment('2023-10-29T10:00:00'));
			expect(thisWeek.inThisWeek).toBe(true);
		});

		test('inThisWeek should return false if the moment is outside of this week', () => {
			const thisWeek = new Moment('2023-10-25T10:00:00');

			thisWeek.setNow(new Moment('2023-10-22T10:00:00'));
			expect(thisWeek.inThisWeek).toBe(false);

			thisWeek.setNow(new Moment('2022-10-30T10:00:00'));
			expect(thisWeek.inThisWeek).toBe(false);

			thisWeek.setNow(new Moment('2022-10-26T10:00:00'));
			expect(thisWeek.inThisWeek).toBe(false);
		});

		test('inThisMinute should return true if the moment is in this minute', () => {
			const thisMinute = new Moment('2023-10-25T10:00:01');

			thisMinute.setNow(new Moment('2023-10-25T10:00:00'));
			expect(thisMinute.inThisMinute).toBe(true);

			thisMinute.setNow(new Moment('2023-10-25T10:00:01'));
			expect(thisMinute.inThisMinute).toBe(true);

			thisMinute.setNow(new Moment('2023-10-25T10:00:59'));
			expect(thisMinute.inThisMinute).toBe(true);
		});

		test('inThisMinute should return false if the moment is outside of this minute', () => {
			const thisMinute = new Moment('2023-10-25T10:00:00');

			thisMinute.setNow(new Moment('2023-10-25T09:59:59'));
			expect(thisMinute.inThisMinute).toBe(false);

			thisMinute.setNow(new Moment('2022-10-25T11:01:00'));
			expect(thisMinute.inThisMinute).toBe(false);

			thisMinute.setNow(new Moment('2022-10-25T11:00:00'));
			expect(thisMinute.inThisMinute).toBe(false);
		});

		test('inThisHour should return true if the moment is in this hour', () => {
			const thisHour = new Moment('2023-10-25T10:01:00');

			thisHour.setNow(new Moment('2023-10-25T10:00:00'));
			expect(thisHour.inThisHour).toBe(true);

			thisHour.setNow(new Moment('2023-10-25T10:40:00'));
			expect(thisHour.inThisHour).toBe(true);

			thisHour.setNow(new Moment('2023-10-25T10:59:59'));
			expect(thisHour.inThisHour).toBe(true);
		});

		test('inThisHour should return false if the moment is outside of this hour', () => {
			const thisHour = new Moment('2023-10-25T10:00:00');

			thisHour.setNow(new Moment('2023-10-25T09:59:59'));
			expect(thisHour.inThisHour).toBe(false);

			thisHour.setNow(new Moment('2022-10-25T11:00:00'));
			expect(thisHour.inThisHour).toBe(false);

			thisHour.setNow(new Moment('2022-10-26T10:00:00'));
			expect(thisHour.inThisHour).toBe(false);
		});

		test('inThisMonth should return true if the moment is in this month', () => {
			const thisMonth = new Moment('2023-12-15T10:00:00');

			thisMonth.setNow(new Moment('2023-12-31T10:00:00'));
			expect(thisMonth.inThisMonth).toBe(true);

			thisMonth.setNow(new Moment('2023-12-15T10:00:00'));
			expect(thisMonth.inThisMonth).toBe(true);

			thisMonth.setNow(new Moment('2023-12-01T10:00:00'));
			expect(thisMonth.inThisMonth).toBe(true);
		});

		test('inThisMonth should return false if the moment is outside of this month', () => {
			const thisWeek = new Moment('2023-11-30T10:00:00');

			thisWeek.setNow(new Moment('2023-12-01T10:00:00'));
			expect(thisWeek.inThisMonth).toBe(false);

			thisWeek.setNow(new Moment('2023-10-31T10:00:00'));
			expect(thisWeek.inThisMonth).toBe(false);

			thisWeek.setNow(new Moment('2024-11-15T10:00:00'));
			expect(thisWeek.inThisMonth).toBe(false);
		});

		test('inThisYear should return true if the moment is in this year', () => {
			const thisYear = new Moment('2023-06-03T10:00:00');
			thisYear.setNow(new Moment('2023-01-03T10:00:00'));

			expect(thisYear.inThisYear).toBe(true);

			const nextYear = new Moment('2023-06-03T10:00:00');
			nextYear.setNow(new Moment('2022-06-03T10:00:00'));

			expect(nextYear.inThisYear).toBe(false);
		});

		test('hasPassed should return true if the moment has passed', () => {
			const pastMoment = new Moment('2023-01-01');
			pastMoment.setNow(new Moment('2023-01-02'));

			expect(pastMoment.hasPassed).toBe(true);

			const futureMoment = new Moment('2023-01-02');
			futureMoment.setNow(new Moment('2023-01-01'));

			expect(futureMoment.hasPassed).toBe(false);
		});

		test('inFuture should return true if the moment has not passed yet', () => {
			const futureMoment = new Moment('2023-01-02');
			futureMoment.setNow(new Moment('2023-01-01'));

			expect(futureMoment.inFuture).toBe(true);

			const pastMoment = new Moment('2023-01-01');
			pastMoment.setNow(new Moment('2023-01-02'));

			expect(pastMoment.inFuture).toBe(false);
		});

		test('isWithinSeconds should return true if moment is within delta seconds of now', () => {
			const momentBefore = new Moment('2023-06-03T10:00:00');
			momentBefore.setNow(new Moment('2023-06-03T10:00:05'));

			expect(momentBefore.isWithinSeconds(10)).toBe(true);
			expect(momentBefore.isWithinSeconds(5)).toBe(true);
			expect(momentBefore.isWithinSeconds(3)).toBe(false);

			const momentAfter = new Moment('2023-06-03T10:00:05');
			momentAfter.setNow(new Moment('2023-06-03T10:00:00'));

			expect(momentAfter.isWithinSeconds(10)).toBe(true);
			expect(momentBefore.isWithinSeconds(5)).toBe(true);
			expect(momentAfter.isWithinSeconds(3)).toBe(false);
		});

		test('isOverSeconds should return true if moment is over delta seconds of now', () => {
			const momentAfter = new Moment('2023-06-03T10:00:15');
			momentAfter.setNow(new Moment('2023-06-03T10:00:00'));

			expect(momentAfter.isOverSeconds(10)).toBe(true);
			expect(momentAfter.isOverSeconds(15)).toBe(false);
			expect(momentAfter.isOverSeconds(20)).toBe(false);

			const momentBefore = new Moment('2023-06-03T10:00:00');
			momentBefore.setNow(new Moment('2023-06-03T10:00:05'));

			expect(momentBefore.isOverSeconds(3)).toBe(true);
			expect(momentBefore.isOverSeconds(10)).toBe(false);
			expect(momentBefore.isOverSeconds(5)).toBe(false);
		});

		test('withinMinute should return true if moment is within delta minute of now', () => {
			const momentBefore = new Moment('2023-06-03T10:00:00');
			momentBefore.setNow(new Moment('2023-06-03T10:00:30'));

			expect(momentBefore.withinMinute).toBe(true);

			momentBefore.setNow(new Moment('2023-06-03T10:01:30'));

			expect(momentBefore.withinMinute).toBe(false);

			const momentAfter = new Moment('2023-06-03T10:01:00');
			momentAfter.setNow(new Moment('2023-06-03T10:00:00'));

			expect(momentAfter.withinMinute).toBe(true);

			momentAfter.setNow(new Moment('2023-06-03T09:58:00'));

			expect(momentAfter.withinMinute).toBe(false);
		});

		test('withinHour should return true if moment is within delta hour of now', () => {
			const momentBefore = new Moment('2023-06-03T10:00:00');
			momentBefore.setNow(new Moment('2023-06-03T10:45:30'));

			expect(momentBefore.withinHour).toBe(true);

			momentBefore.setNow(new Moment('2023-06-03T11:00:01'));
			expect(momentBefore.withinHour).toBe(false);

			const momentAfter = new Moment('2023-06-03T11:00:00');
			momentAfter.setNow(new Moment('2023-06-03T10:00:00'));

			expect(momentAfter.withinHour).toBe(true);

			momentAfter.setNow(new Moment('2023-06-03T09:59:59'));

			expect(momentAfter.withinHour).toBe(false);
		});

		test('withinDay should return true if moment is within delta day of now', () => {
			const momentBefore = new Moment('2023-06-03T10:00:00');
			momentBefore.setNow(new Moment('2023-06-04T06:00:00'));

			expect(momentBefore.withinDay).toBe(true);

			momentBefore.setNow(new Moment('2023-06-04T10:00:01'));

			expect(momentBefore.withinDay).toBe(false);

			const momentAfter = new Moment('2023-06-03T10:00:00');
			momentAfter.setNow(new Moment('2023-06-02T10:00:00'));

			expect(momentAfter.withinDay).toBe(true);

			momentAfter.setNow(new Moment('2023-06-02T09:59:59'));

			expect(momentAfter.withinDay).toBe(false);
		});

		test('isJustNow should return true if moment is within delta seconds of now', () => {
			const momentBefore = new Moment('2023-06-03T10:00:00');
			momentBefore.setNow(Moment.createFromTimestamp(momentBefore.timestamp + 30));
			expect(momentBefore.isJustNow()).toBe(true);

			const momentFarBefore = new Moment('2023-06-03T10:00:00');
			momentFarBefore.setNow(Moment.createFromTimestamp(momentFarBefore.timestamp + 90));
			expect(momentFarBefore.isJustNow()).toBe(false);

			const momentAfter = new Moment('2023-06-03T10:00:00');
			momentAfter.setNow(Moment.createFromTimestamp(momentAfter.timestamp - 20));
			expect(momentAfter.isJustNow(20)).toBe(true);

			const momentFarAfter = new Moment('2023-06-03T10:00:00');
			momentFarAfter.setNow(Moment.createFromTimestamp(momentFarAfter.timestamp - 80));

			expect(momentFarAfter.isJustNow(70)).toBe(false);
		});

		test('secondsFromNow should return the number of seconds between the moment and now', () => {
			const now = new Moment();
			const moment = now.clone().addSeconds(30);

			expect(moment.secondsFromNow).toBe(30);
		});

		test('minutesFromNow should return the number of minutes between the moment and now', () => {
			const now = new Moment();
			const moment = now.clone().addMinutes(30);

			expect(moment.minutesFromNow).toBe(30);
		});

		test('hoursFromNow should return the number of hours between the moment and now', () => {
			const now = new Moment();
			const moment = now.clone().addHours(12);

			expect(moment.hoursFromNow).toBe(12);
		});

		test('daysFromNow should return the number of days between the moment and now', () => {
			const now = new Moment('2023-06-03T11:00:00');
			now.setNow(new Moment('2023-06-03T11:00:00'));
			const moment = now.clone().addDays(7);

			expect(moment.daysFromNow).toBe(7);
		});

		test('weeksFromNow should return the number of weeks between the moment and now', () => {
			const now = new Moment('2023-06-03T11:00:00');
			now.setNow(new Moment('2023-06-03T11:00:00'));

			const weekAgo = now.clone().addDays(-7);
			const twoDaysAgo = now.clone().addDays(-2);
			const twoDays = now.clone().addDays(2);
			const week = now.clone().addDays(7);
			const tenDays = now.clone().addDays(10);
			const twoWeeks = now.clone().addDays(14);

			expect(weekAgo.weeksFromNow).toBe(1);
			expect(twoDaysAgo.weeksFromNow).toBe(0);
			expect(twoDays.weeksFromNow).toBe(0);
			expect(week.weeksFromNow).toBe(1);
			expect(tenDays.weeksFromNow).toBe(1);
			expect(twoWeeks.weeksFromNow).toBe(2);
		});

		test('monthsFromNow should return the number of months between the moment and now', () => {
			const nextYear = new Moment('2024-06-03T11:00:00');
			nextYear.setNow(new Moment('2023-06-03T10:00:00'));

			expect(nextYear.monthsFromNow).toBe(12);
		});

		test('yearsFromNow should return the number of months between the moment and now', () => {
			const now = new Moment('2023-06-03T10:00:00');

			const lastYear = new Moment('2022-12-03T10:00:00');
			lastYear.setNow(now);

			const thisYear = new Moment('2023-06-03T10:00:00');
			thisYear.setNow(now);

			const nextYear = new Moment('2024-01-03T10:00:00');
			nextYear.setNow(now);

			expect(lastYear.yearsFromNow).toBe(1);
			expect(thisYear.yearsFromNow).toBe(0);
			expect(nextYear.yearsFromNow).toBe(1);
		});

		test('format should return a formatted string representation of the moment', () => {
			const moment = new Moment('2023-06-03T11:00:00');
			const format = 'YYYY-MM-DD HH:mm:ss';

			expect(moment.format(format)).toBe('2023-06-03 11:00:00');
		});

		test('format of exact MMM should return a formatted string representation of the moment', () => {
			const moment = new Moment('2023-02-03T11:00:00');
			const format = 'MMM';

			expect(moment.format(format, 'ru')).toBe('февр');
			expect(moment.format(format, 'en')).toBe('Feb');
		});

		test('format of MMM inclusion should return a formatted string representation of the moment', () => {
			const moment = new Moment('2023-02-03T11:00:00');
			const format = 'DD MMM YYYY';

			expect(moment.format(format, 'ru')).toBe('03 февр 2023');
			expect(moment.format(format, 'en')).toBe('03 Feb 2023');
		});

		test('equals should return true if the moment is the same as the other moment', () => {
			const moment1 = new Moment('2023-06-03T11:00:00');
			const moment2 = new Moment('2023-06-03T11:00:00');
			const moment3 = new Moment('2023-06-03T11:00:05');

			expect(moment1.equals(moment2)).toBe(true);
			expect(moment1.equals(moment3)).toBe(false);
		});

		test('isBefore should return true if the moment is before the other moment', () => {
			const moment1 = new Moment('2023-06-03T10:00:00');
			const moment2 = new Moment('2023-06-03T11:00:00');

			expect(moment1.isBefore(moment2)).toBe(true);
			expect(moment2.isBefore(moment1)).toBe(false);
		});

		test('isBefore should return false if the moment is the same as the other moment', () => {
			const moment1 = new Moment('2023-06-03T11:00:00');
			const moment2 = new Moment('2023-06-03T11:00:00');

			expect(moment1.isBefore(moment2)).toBe(false);
		});

		test('isAfter should return true if the moment is after the other moment', () => {
			const moment1 = new Moment('2023-06-03T11:00:00');
			const moment2 = new Moment('2023-06-03T10:00:00');

			expect(moment1.isAfter(moment2)).toBe(true);
			expect(moment2.isAfter(moment1)).toBe(false);
		});

		test('isAfter should return true if the moment is the same as the other moment', () => {
			const moment1 = new Moment('2023-06-03T11:00:00');
			const moment2 = new Moment('2023-06-03T11:00:00');

			expect(moment1.isAfter(moment2)).toBe(true);
		});

		test('clone should return a new moment instance with the same date and time', () => {
			const moment1 = new Moment('2023-06-03T11:00:00');
			const moment2 = moment1.clone();

			expect(moment1.timestamp).toBe(moment2.timestamp);
			expect(moment1.date.getTime()).toBe(moment2.date.getTime());
		});

		test('clone should return a new moment instance with the same "now" value', () => {
			const moment1 = new Moment('2023-06-03T11:00:00');
			moment1.setNow(new Moment('2023-06-03T12:00:00'));
			const moment2 = moment1.clone();

			expect(moment1.now.timestamp).toBe(moment2.now.timestamp);
			expect(moment1.now.date.getTime()).toBe(moment2.now.date.getTime());
		});

		test('add should return moment this add specified number of milliseconds to the moment', () => {
			const moment = new Moment('2023-06-03T10:00:00');
			const addedMoment = moment.add(3000);

			expect(addedMoment.timestamp).toBe(moment.timestamp + 3);
		});

		test('addSeconds should return moment add specified number of seconds to the moment', () => {
			const moment = new Moment('2023-06-03T10:00:00');
			const addedMoment = moment.addSeconds(3);

			expect(addedMoment.timestamp).toBe(moment.timestamp + 3);
		});

		test('addMinutes should return moment add specified number of minutes to the moment', () => {
			const moment = new Moment('2023-06-03T10:00:00');
			const addedMoment = moment.addMinutes(13);

			expect(addedMoment.timestamp).toBe(moment.timestamp + 13 * 60);
		});

		test('addHours should return moment add specified number of hours to the moment', () => {
			const moment = new Moment('2023-06-03T10:00:00');
			const addedMoment = moment.addHours(2);

			expect(addedMoment.timestamp).toBe(moment.timestamp + 2 * 3600);
		});

		test('addDays should return moment add specified number of days to the moment', () => {
			const moment = new Moment('2023-06-03T10:00:00');
			const addedMoment = moment.addDays(3);

			expect(addedMoment.timestamp).toBe(moment.timestamp + 3 * 3600 * 24);
		});

		test('startOfHour should return a new moment instance with start of the hour', () => {
			const moment = new Moment('2023-06-03T10:30:45');
			const startOfHourMoment = moment.startOfHour;

			expect(startOfHourMoment.date.getHours()).toBe(10);
			expect(startOfHourMoment.date.getMinutes()).toBe(0);
			expect(startOfHourMoment.date.getSeconds()).toBe(0);
			expect(startOfHourMoment.date.getMilliseconds()).toBe(0);
		});

		test('endOfHour should return a new moment instance with end of the hour', () => {
			const moment = new Moment('2023-06-03T10:30:45');
			const startOfHourMoment = moment.endOfHour;

			expect(startOfHourMoment.date.getHours()).toBe(10);
			expect(startOfHourMoment.date.getMinutes()).toBe(59);
			expect(startOfHourMoment.date.getSeconds()).toBe(59);
			expect(startOfHourMoment.date.getMilliseconds()).toBe(999);
		});

		test('startOfWeek should return a new moment instance with start of the week', () => {
			const moment = new Moment('2023-11-15T10:30:45');
			const startOfHourMoment = moment.startOfWeek;

			expect(startOfHourMoment.date.getDate()).toBe(13);
			expect(startOfHourMoment.date.getHours()).toBe(0);
			expect(startOfHourMoment.date.getMinutes()).toBe(0);
			expect(startOfHourMoment.date.getSeconds()).toBe(0);
			expect(startOfHourMoment.date.getMilliseconds()).toBe(0);
		});

		test('endOfWeek should return a new moment instance with end of the week', () => {
			const moment = new Moment('2023-11-15T10:30:45');
			const startOfHourMoment = moment.endOfWeek;

			expect(startOfHourMoment.date.getDate()).toBe(19);
			expect(startOfHourMoment.date.getHours()).toBe(23);
			expect(startOfHourMoment.date.getMinutes()).toBe(59);
			expect(startOfHourMoment.date.getSeconds()).toBe(59);
			expect(startOfHourMoment.date.getMilliseconds()).toBe(999);
		});

		test('startOfMonth should return a new moment instance with start of the month', () => {
			const moment = new Moment('2023-11-15T10:30:45');
			const startOfMonthMoment = moment.startOfMonth;

			expect(startOfMonthMoment.date.getDate()).toBe(1);
			expect(startOfMonthMoment.date.getHours()).toBe(0);
			expect(startOfMonthMoment.date.getMinutes()).toBe(0);
			expect(startOfMonthMoment.date.getSeconds()).toBe(0);
			expect(startOfMonthMoment.date.getMilliseconds()).toBe(0);
		});

		test('endOfMonth should return a new moment instance with end of the month', () => {
			const moment = new Moment('2023-11-15T10:30:45');
			const endOfMonthMoment = moment.endOfMonth;

			expect(endOfMonthMoment.date.getDate()).toBe(30);
			expect(endOfMonthMoment.date.getHours()).toBe(23);
			expect(endOfMonthMoment.date.getMinutes()).toBe(59);
			expect(endOfMonthMoment.date.getSeconds()).toBe(59);
			expect(endOfMonthMoment.date.getMilliseconds()).toBe(999);
		});

		test('startOfYear should return a new moment instance with start of the year', () => {
			const moment = new Moment('2023-11-15T10:30:45');
			const startOfYearMoment = moment.startOfYear;

			expect(startOfYearMoment.date.getDate()).toBe(1);
			expect(startOfYearMoment.date.getMonth()).toBe(0);
			expect(startOfYearMoment.date.getHours()).toBe(0);
			expect(startOfYearMoment.date.getMinutes()).toBe(0);
			expect(startOfYearMoment.date.getSeconds()).toBe(0);
			expect(startOfYearMoment.date.getMilliseconds()).toBe(0);
		});

		test('endOfYear should return a new moment instance with end of the year', () => {
			const moment = new Moment('2023-11-15T10:30:45');
			const endOfYearMoment = moment.endOfYear;

			expect(endOfYearMoment.date.getDate()).toBe(31);
			expect(endOfYearMoment.date.getMonth()).toBe(11);
			expect(endOfYearMoment.date.getHours()).toBe(23);
			expect(endOfYearMoment.date.getMinutes()).toBe(59);
			expect(endOfYearMoment.date.getSeconds()).toBe(59);
			expect(endOfYearMoment.date.getMilliseconds()).toBe(999);
		});

		test('daysInYear should return 366 for leap year and 365 for other years', () => {
			const year1900 = new Moment('1900-01-01T00:00:00');
			const year2000 = new Moment('2000-01-01T00:00:00');
			const year2001 = new Moment('2001-01-01T00:00:00');
			const year2004 = new Moment('2004-01-01T00:00:00');

			expect(year2000.daysInYear).toBe(366);
			expect(year2004.daysInYear).toBe(366);

			expect(year1900.daysInYear).toBe(365);
			expect(year2001.daysInYear).toBe(365);
		});
	});
})();
