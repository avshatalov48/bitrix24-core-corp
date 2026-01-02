(() => {
	const require = (ext) => jn.require(ext);
	const {
		FormatterFactory,
		FormatterTypes,
	} = require('layout/ui/friendly-date/formatter-factory');
	const { Moment } = require('utils/date');
	const { describe, test, expect } = require('testing');

	describe('HumanDateFormatter (FriendlyDate)', () => {
		const now = new Moment('2025-03-09T12:00:00');
		const createMoment = (dateString) => new Moment(dateString).setNow(now);

		const createFormatter = (props = {}) => FormatterFactory.create(FormatterTypes.HUMAN_DATE, {
			futureAllowed: false,
			skipAfterSeconds: null,
			...props,
		});

		test('shows "just now" for recent moments (under a minute)', () => {
			const formatter = createFormatter({ showTime: true });
			const moment = createMoment('2025-03-09T11:59:30');
			expect(formatter.format(moment)).toBe('только что');
		});

		test('shows minutes ago for 1-59 minutes interval', () => {
			const formatter = createFormatter({ showTime: true });
			const cases = [
				{ time: '2025-03-09T11:58:59', expected: '1 минуту назад' },
				{ time: '2025-03-09T11:55:00', expected: '5 минут назад' },
				{ time: '2025-03-09T11:01:00', expected: '59 минут назад' },
			];

			cases.forEach(({ time, expected }) => {
				const moment = createMoment(time);
				expect(formatter.format(moment)).toBe(expected);
			});
		});

		test('shows "today" time for same-day moments', () => {
			const formatter = createFormatter({ showTime: true });
			const moment = createMoment('2025-03-09T09:30:00');
			expect(formatter.format(moment)).toBe('сегодня в 09:30');
		});

		test('shows "yesterday" for previous day moments', () => {
			const formatter = createFormatter({ showTime: true });
			const cases = [
				{ time: '2025-03-08T23:59:00', expected: 'вчера в 23:59' },
				{ time: '2025-03-08T00:01:00', expected: 'вчера в 00:01' },
			];

			cases.forEach(({ time, expected }) => {
				const moment = createMoment(time);
				expect(formatter.format(moment)).toBe(expected);
			});
		});

		test('shows date without year for current year moments', () => {
			const formatter = createFormatter({ showTime: true });
			const cases = [
				{ time: '2025-03-07T12:00:00', expected: '7 марта в 12:00' },
				{ time: '2025-01-01T00:00:00', expected: '1 января в 00:00' },
			];

			cases.forEach(({ time, expected }) => {
				const moment = createMoment(time);
				expect(formatter.format(moment)).toBe(expected);
			});
		});

		test('shows full date with year for previous year moments', () => {
			const formatter = createFormatter({ showTime: true });

			const moment = createMoment('2024-06-11T12:00:00');
			expect(formatter.format(moment)).toBe('11 июня 2024 в 12:00');
		});

		test('corner cases', () => {
			const formatter = createFormatter({ showTime: true });

			const exactly1Hour = createMoment('2025-03-09T11:00:00');
			expect(formatter.format(exactly1Hour)).toBe('60 минут назад');

			const exactly24h = createMoment('2025-03-08T12:00:00');
			expect(formatter.format(exactly24h)).toBe('вчера в 12:00');
		});

		test('shows month and day without time for current year dates', () => {
			const formatter = createFormatter();
			const cases = [
				{ time: '2025-03-09T11:59:30', expected: '9 марта' },
				{ time: '2025-03-08T23:59:00', expected: '8 марта' },
				{ time: '2025-03-07T12:00:00', expected: '7 марта' },
				{ time: '2025-01-01T00:00:00', expected: '1 января' },
			];

			cases.forEach(({ time, expected }) => {
				const moment = createMoment(time);
				expect(formatter.format(moment)).toBe(expected);
			});
		});

		test('shows full date with year for previous year dates', () => {
			const formatter = createFormatter();
			const cases = [
				{ time: '2024-06-11T12:00:00', expected: '11 июня 2024' },
				{ time: '2024-12-31T23:59:59', expected: '31 декабря 2024' },
				{ time: '2023-05-15T09:30:00', expected: '15 мая 2023' },
			];

			cases.forEach(({ time, expected }) => {
				const moment = createMoment(time);
				expect(formatter.format(moment)).toBe(expected);
			});
		});

		test('handles edge cases correctly', () => {
			const formatter = createFormatter();

			const exactly1Hour = createMoment('2025-03-09T11:00:00');
			expect(formatter.format(exactly1Hour)).toBe('9 марта');

			const yearBorder = createMoment('2024-12-31T23:59:59');
			expect(formatter.format(yearBorder)).toBe('31 декабря 2024');
		});
	});
})();
