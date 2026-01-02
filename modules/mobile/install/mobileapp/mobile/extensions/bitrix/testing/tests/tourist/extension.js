(() => {
	const require = (ext) => jn.require(ext);
	const { describe, test, expect, beforeEach, afterAll } = require('testing');
	const { Tourist } = require('tourist');
	const EVENT_NAME = 'awesome-test-event';
	const clearTouristData = async () => {
		await Tourist.ready();
		await Tourist.forget(EVENT_NAME);
	};

	describe('Tourist', () => {
		beforeEach(clearTouristData);
		afterAll(clearTouristData);

		test('should return true for firstTime before remember', async () => {
			const isFirst = Tourist.firstTime(EVENT_NAME);
			expect(isFirst).toBe(true);
		});

		test('should return false for firstTime after remember', async () => {
			await Tourist.remember(EVENT_NAME);
			const isFirst = Tourist.firstTime(EVENT_NAME);
			expect(isFirst).toBe(false);
		});

		test('never() should behave same as firstTime()', async () => {
			await Tourist.remember(EVENT_NAME);
			expect(Tourist.never(EVENT_NAME)).toBe(false);

			await Tourist.forget(EVENT_NAME);
			expect(Tourist.never(EVENT_NAME)).toBe(true);
		});

		test('should increment event count on multiple remember calls', async () => {
			await Tourist.remember(EVENT_NAME);
			await Tourist.remember(EVENT_NAME);
			expect(Tourist.numberOfTimes(EVENT_NAME)).toBe(2);
		});

		test('should clamp event count to MAX_TIMES_TO_REMEMBER', async () => {
			const params = { context: null, time: null, count: 2000 };
			await Tourist.remember(EVENT_NAME, params);
			expect(Tourist.numberOfTimes(EVENT_NAME)).toBe(1000);
		});

		test('rememberFirstTime() should return false if event was already remembered', async () => {
			await Tourist.remember(EVENT_NAME);
			await Tourist.remember(EVENT_NAME);
			expect(Tourist.rememberFirstTime(EVENT_NAME)).toBe(false);
		});

		test('should reset data after forget', async () => {
			await Tourist.remember(EVENT_NAME);
			await Tourist.forget(EVENT_NAME);
			expect(Tourist.firstTime(EVENT_NAME)).toBe(true);
		});

		test('getContext() should return saved context', async () => {
			const context = { foo: 'bar', id: 123 };
			await Tourist.remember(EVENT_NAME, { context });
			const storedContext = Tourist.getContext(EVENT_NAME);
			expect(storedContext).toEqual(context);
		});

		test('getContext() should return null if context was not set', async () => {
			await Tourist.remember(EVENT_NAME);
			expect(Tourist.getContext(EVENT_NAME)).toBeNull();
		});

		test('lastTime() should return undefined if event was never remembered', () => {
			expect(Tourist.lastTime('never-used')).toBeUndefined();
		});

		test('lastTime() should return a valid Date object', async () => {
			const now = new Date();
			await Tourist.remember(EVENT_NAME, { time: now });
			const ts = Tourist.lastTime(EVENT_NAME);
			expect(ts).toBeInstanceOf(Date);
			expect(Math.abs(ts.getTime() - now.getTime())).toBeLessThan(1000);
		});
	});
})();
