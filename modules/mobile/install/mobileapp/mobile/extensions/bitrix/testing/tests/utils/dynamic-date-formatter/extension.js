(() => {
	const require = (ext) => jn.require(ext);

	const { describe, test, expect } = require('testing');
	const { DynamicDateFormatter } = require('utils/date');
	const { Moment } = require('utils/date/moment');
	const { datetime, shortTime, longTime } = require('utils/date/formats');
	const { clone } = require('utils/object');

	const now = new Moment('Oct 16 2023 12:00:00');

	const runTests = (formatter, expectedResults) => {
		Object.keys(expectedResults).forEach((momentName) => {
			const moment = pastMoments[momentName] || futureMoments[momentName];
			const formattedDate = formatter.format(moment);

			expect(formattedDate).toBe(expectedResults[momentName]);
		});
	};

	const pastMoments = {
		now: clone(now),
		'30SecsAgo': new Moment('Oct 16 2023 11:59:30'),
		'45SecsAgo': new Moment('Oct 16 2023 11:59:15'),
		'1MinAgo': new Moment('Oct 16 2023 11:59:00'),
		'5MinsAgo': new Moment('Oct 16 2023 11:55:00'),
		'1HourAgo': new Moment('Oct 16 2023 11:00:00'),
		'3HoursAgo': new Moment('Oct 16 2023 09:00:00'),
		'23HoursAgo': new Moment('Oct 15 2023 13:00:00'),
		'1DayAndHourAgo': new Moment('Oct 15 2023 11:00:00'),
		'2DaysAgo': new Moment('Oct 14 2023 12:00:00'),
		'1WeekAgo': new Moment('Oct 9 2023 12:00:00'),
	};

	const futureMoments = {
		in30Secs: new Moment('Oct 16 2023 12:00:30'),
		in45Secs: new Moment('Oct 16 2023 12:00:45'),
		in1Min: new Moment('Oct 16 2023 12:01:00'),
		in5Mins: new Moment('Oct 16 2023 12:05:00'),
		in30Mins: new Moment('Oct 16 2023 12:30:00'),
		in31Mins: new Moment('Oct 16 2023 12:31:00'),
		in59Mins: new Moment('Oct 16 2023 12:59:00'),
		in1Hour: new Moment('Oct 16 2023 13:00:00'),
		in3Hours: new Moment('Oct 16 2023 15:00:00'),
		in23Hours: new Moment('Oct 17 2023 11:00:00'),
		in1DayAndHour: new Moment('Oct 17 2023 13:00:00'),
		in2Days: new Moment('Oct 18 2023 12:00:00'),
		in1Week: new Moment('Oct 23 2023 12:00:00'),
	};

	Object.keys(pastMoments).forEach((key) => {
		pastMoments[key].setNow(now.clone());
	});

	Object.keys(futureMoments).forEach((key) => {
		futureMoments[key].setNow(now.clone());
	});

	describe('DynamicDateFormatter', () => {
		test('Format a date in the past', () => {
			const expectedResults = {
				'30SecsAgo': 'just now',
				// eslint-disable-next-line no-undef
				'45SecsAgo': '11:59:15',
				'5MinsAgo': '5 minutes ago',
				'1HourAgo': '60 minutes ago',
				'3HoursAgo': '09:00',
			};

			const getMinutesDelta = (m1, m2) => Math.round(Math.abs(m1.timestamp - m2.timestamp) / 60);

			const formatter = new DynamicDateFormatter({
				defaultFormat: datetime(),
				config: {
					[DynamicDateFormatter.scope.PAST]: {
						30: () => 'just now',
						[DynamicDateFormatter.deltas.MINUTE]: longTime(),
						[DynamicDateFormatter.deltas.HOUR]: (moment) => `${getMinutesDelta(moment, now)} minutes ago`,
						[DynamicDateFormatter.deltas.DAY]: shortTime(),
					},
				},
			});

			runTests(formatter, expectedResults);
		});

		test('combine past and future', () => {
			const expectedResults = {
				'30SecsAgo': 'just now',
				'45SecsAgo': 'just now',
				'1MinAgo': '11:59:00',
				in30Secs: 'very soon',
				in45Secs: '12:00:45',
			};

			const formatter = new DynamicDateFormatter({
				defaultFormat: datetime(),
				config: {
					[DynamicDateFormatter.scope.PAST]: {
						45: () => 'just now',
					},
					[DynamicDateFormatter.scope.FUTURE]: {
						30: () => 'very soon',
					},
					[DynamicDateFormatter.deltas.MINUTE]: longTime(),
				},
			});

			runTests(formatter, expectedResults);
		});

		test('deltas and periods', () => {
			const expectedResults = {
				now: 'delta less or equal than 1 minute',
				'45SecsAgo': 'delta less or equal than 1 minute',
				'5MinsAgo': '16.10.2023 11:55:00',
				'1HourAgo': '16.10.2023 11:00:00',
				in1Min: 'delta less or equal than 1 minute',
				in59Mins: '12:59:00',
				in1Hour: '16.10.2023 13:00:00',
			};

			const formatter = new DynamicDateFormatter({
				defaultFormat: datetime(),
				config: {
					[DynamicDateFormatter.deltas.MINUTE]: () => 'delta less or equal than 1 minute',
					[DynamicDateFormatter.periods.HOUR]: longTime(),
				},
			});

			runTests(formatter, expectedResults);
		});

		test('specific settings have priority', () => {
			const expectedResults = {
				'45SecsAgo': 'specific settings prioritised',
			};

			const formatter = new DynamicDateFormatter({
				defaultFormat: datetime(),
				config: {
					45: () => 'general settings prioritised',
					[DynamicDateFormatter.scope.PAST]: {
						45: () => 'specific settings prioritised',
					},
				},
			});

			runTests(formatter, expectedResults);
		});

		test('yesterday and tomorrow periods', () => {
			const expectedResults = {
				'1DayAndHourAgo': 'yesterday',
				'23HoursAgo': 'yesterday',
				in1DayAndHour: 'tomorrow',
				in23Hours: 'tomorrow',
				'1HourAgo': 'today',
			};

			const formatter = new DynamicDateFormatter({
				defaultFormat: datetime(),
				config: {
					[DynamicDateFormatter.periods.DAY]: () => 'today',
					[DynamicDateFormatter.scope.PAST]: {
						[DynamicDateFormatter.periods.YESTERDAY]: () => 'yesterday',
					},
					[DynamicDateFormatter.scope.FUTURE]: {
						[DynamicDateFormatter.periods.TOMORROW]: () => 'tomorrow',
					},
				},
			});

			runTests(formatter, expectedResults);
		});

		test('test of potential conflict of periods and deltas', () => {
			const expectedResults = {
				in5Mins: 'через 5 минут',
				in30Mins: 'через 30 минут',
				in31Mins: 'сегодня в 12:31',
				in1Hour: 'сегодня в 13:00',
			};

			const formatter = new DynamicDateFormatter({
				defaultFormat: datetime(),
				config: {
					[DynamicDateFormatter.scope.FUTURE]: {
						1800: (moment) => {
							const remainSeconds = moment.timestamp - moment.getNow().timestamp;
							const remainMinutes = Math.round(remainSeconds / 60);

							return `через ${remainMinutes} минут`;
						},
						[DynamicDateFormatter.periods.DAY]: (moment) => {
							return `сегодня в ${moment.format(shortTime())}`;
						},
					},
				},
			});

			runTests(formatter, expectedResults);
		});
	});
})();
