(() => {
	const require = (ext) => jn.require(ext);
	const { describe, test, expect } = require('testing');
	const { DynamicDateFormatter } = require('utils/date');
	const { Moment } = require('utils/date/moment');
	const { datetime, shortTime, longTime } = require('utils/date/formats');

	const now = new Moment('June 16 2025 12:00:00');

	const previousWeekStart = now.startOfWeek.addDays(-1).startOfWeek.timestamp;
	const previousWeekEnd = now.startOfWeek.addDays(-1).endOfWeek.timestamp;

	const nextWeekStart = now.endOfWeek.addDays(1).startOfWeek.timestamp;
	const nextWeekEnd = now.endOfWeek.addDays(1).endOfWeek.timestamp;

	const pastMoments = {
		now: now.clone(),
		'30SecsAgo': now.addSeconds(-30),
		'45SecsAgo': now.addSeconds(-45),
		'1MinAgo': now.addMinutes(-1),
		'5MinsAgo': now.addMinutes(-5),
		'1HourAgo': now.addHours(-1),
		'3HoursAgo': now.addHours(-3),
		'23HoursAgo': now.addHours(-23),
		'1Day1HourAgo': now.addDays(-1).addHours(-1),
		previousWeekMiddle: new Moment(
			(previousWeekStart + (previousWeekEnd - previousWeekStart) / 2) * 1000,
		),
		thisWeekBeforeNow: new Moment(
			(now.startOfWeek.timestamp + (now.timestamp - now.startOfWeek.timestamp) / 2) * 1000,
		),
	};
	const futureMoments = {
		in30Secs: now.addSeconds(30),
		in45Secs: now.addSeconds(45),
		in1Min: now.addMinutes(1),
		in5Mins: now.addMinutes(5),
		in30Mins: now.addMinutes(30),
		in31Mins: now.addMinutes(31),
		in59Mins: now.addMinutes(59),
		in1Hour: now.addHours(1),
		in23Hours: now.addHours(23),
		in1Day1Hour: now.addDays(1).addHours(1),
		thisWeekAfterNow: new Moment(
			(now.endOfWeek.timestamp - (now.endOfWeek.timestamp - now.timestamp) / 2) * 1000,
		),
		nextWeekMiddle: new Moment(
			(nextWeekStart + (nextWeekEnd - nextWeekStart) / 2) * 1000,
		),
	};

	const runTests = (formatter, expectedResults) => {
		Object.keys(expectedResults).forEach((momentName) => {
			const moment = pastMoments[momentName] || futureMoments[momentName];
			const formattedDate = formatter.format(moment);

			expect(formattedDate).toBe(expectedResults[momentName]);
		});
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
				'5MinsAgo': pastMoments['5MinsAgo'].format('E'),
				'1HourAgo': pastMoments['1HourAgo'].format('E'),
				previousWeekMiddle: pastMoments.previousWeekMiddle.format(datetime),
				thisWeekBeforeNow: pastMoments.thisWeekBeforeNow.format('E'),
				in1Min: 'delta less or equal than 1 minute',
				in59Mins: futureMoments.in59Mins.format(longTime),
				in1Hour: futureMoments.in1Hour.format('E'),
				thisWeekAfterNow: futureMoments.thisWeekAfterNow.format('E'),
				nextWeekMiddle: futureMoments.nextWeekMiddle.format(datetime),
			};

			const formatter = new DynamicDateFormatter({
				defaultFormat: datetime(),
				config: {
					[DynamicDateFormatter.deltas.MINUTE]: () => 'delta less or equal than 1 minute',
					[DynamicDateFormatter.periods.HOUR]: longTime(),
					[DynamicDateFormatter.periods.WEEK]: 'E',
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
				'1Day1HourAgo': 'yesterday',
				'23HoursAgo': 'yesterday',
				in1Day1Hour: 'tomorrow',
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
