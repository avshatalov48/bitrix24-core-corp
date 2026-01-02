(() => {
	const require = (ext) => jn.require(ext);
	const {
		AppRatingManagerClass,
		TestEventStorageProvider,
		RateEvent,
		sharedStorageKey,
		storedCountersDataKey,
		storedCounterLimitsDataKey,
		millisecondsInDay,
		RateBoxDisplayIntervals: Intervals,
	} = require('app-rating-manager');
	const { TestUserEvent, TestUserEventLimit } = require('app-rating-manager/manual-testing-tool');
	const { MinRateForStore } = require('layout/ui/app-rating');
	const { describe, test, expect, beforeEach, afterAll } = require('testing');

	const provider = new TestEventStorageProvider();
	const AppRatingManager = new AppRatingManagerClass({
		eventStorageProvider: provider,
		isAppRatingEnabledFunction: () => true,
	});

	const increaseEventCounterToLimit = (event) => {
		const limit = TestUserEventLimit[event];
		const increaseCountersPromises = [];

		for (let i = 0; i < limit; i++)
		{
			increaseCountersPromises.push(AppRatingManager.increaseCounter(event));
		}

		return Promise.allSettled(increaseCountersPromises);
	};

	const clearAppRatingData = async () => {
		provider.clearAll();
		const storage = Application.sharedStorage(sharedStorageKey);
		storage.set(storedCountersDataKey, '{}');
		storage.set(storedCounterLimitsDataKey, '{}');
		AppRatingManager.registerLimits(TestUserEventLimit);
	};

	const saveEventWithTime = async (event, options) => {
		await provider.save(event, options);
	};

	const getTimeDaysAgo = (days) => Date.now() - days * millisecondsInDay;

	describe('AppRatingManager canOpenAppRating', () => {
		beforeEach(clearAppRatingData);
		afterAll(clearAppRatingData);

		test('should return false if no limits reached', async () => {
			const result = AppRatingManager.canOpenAppRating();
			expect(result).toBe(false);
		});

		Object.values(TestUserEvent).forEach((event) => {
			test(`should return true if ${event} limit reached and box was not shown yet`, async () => {
				await increaseEventCounterToLimit(event);
				const result = AppRatingManager.canOpenAppRating();
				expect(result).toBe(true);
			});
		});

		test('should return false if rate box shown once and time for second display not come', async () => {
			await increaseEventCounterToLimit(TestUserEvent.TASK_COMPLETED);
			await provider.save(RateEvent.RATE_BOX_OPENED);
			const result = AppRatingManager.canOpenAppRating();
			expect(result).toBe(false);
		});

		test('should return true if rate box shown once, not rated and time for second display come', async () => {
			const limitReachedTime = getTimeDaysAgo(Intervals.SECOND_DISPLAY_NOT_RATED + 1);
			await saveEventWithTime(RateEvent.ANY_EVENT_COUNTER_REACHED_LIMIT, { time: limitReachedTime });
			const rateBoxOpenedTime = getTimeDaysAgo(Intervals.SECOND_DISPLAY_NOT_RATED + 1);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: rateBoxOpenedTime });
			const result = AppRatingManager.canOpenAppRating();
			expect(result).toBe(true);
		});

		test('should return false if rate box shown twice, not rated, and time for third display not come', async () => {
			const secondAndThirdIntervalsSum = Intervals.SECOND_DISPLAY_NOT_RATED + Intervals.THIRD_DISPLAY_NOT_RATED;
			const limitReachedTime = getTimeDaysAgo(secondAndThirdIntervalsSum);
			await saveEventWithTime(RateEvent.ANY_EVENT_COUNTER_REACHED_LIMIT, { time: limitReachedTime });
			const firstRateBoxOpenTime = getTimeDaysAgo(secondAndThirdIntervalsSum);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: firstRateBoxOpenTime });
			const secondRateBoxOpenTime = getTimeDaysAgo(Intervals.THIRD_DISPLAY_NOT_RATED - 1);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: secondRateBoxOpenTime });

			const result = AppRatingManager.canOpenAppRating();
			expect(result).toBe(false);
		});

		test('should return false if rate box shown twice, not rated, and time for third display come', async () => {
			const secondAndThirdIntervalsSum = Intervals.SECOND_DISPLAY_NOT_RATED + Intervals.THIRD_DISPLAY_NOT_RATED;
			const limitReachedTime = getTimeDaysAgo(secondAndThirdIntervalsSum + 1);
			await saveEventWithTime(RateEvent.ANY_EVENT_COUNTER_REACHED_LIMIT, { time: limitReachedTime });
			const firstRateBoxOpenTime = getTimeDaysAgo(secondAndThirdIntervalsSum + 1);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: firstRateBoxOpenTime });
			const secondRateBoxOpenTime = getTimeDaysAgo(Intervals.THIRD_DISPLAY_NOT_RATED + 1);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: secondRateBoxOpenTime });

			const result = AppRatingManager.canOpenAppRating();
			expect(result).toBe(true);
		});

		test('should return false if rate box shown third, not rated, and time for subsequent display not come', async () => {
			const allNotRatedIntervalsSum = Intervals.SECOND_DISPLAY_NOT_RATED
				+ Intervals.THIRD_DISPLAY_NOT_RATED + Intervals.SUBSEQUENT_DISPLAYS_NOT_RATED;
			const secondAndThirdIntervalsSum = Intervals.SECOND_DISPLAY_NOT_RATED + Intervals.THIRD_DISPLAY_NOT_RATED;
			const limitReachedTime = getTimeDaysAgo(allNotRatedIntervalsSum + 1);
			await saveEventWithTime(RateEvent.ANY_EVENT_COUNTER_REACHED_LIMIT, { time: limitReachedTime });
			const firstRateBoxOpenTime = getTimeDaysAgo(allNotRatedIntervalsSum + 1);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: firstRateBoxOpenTime });
			const secondRateBoxOpenTime = getTimeDaysAgo(secondAndThirdIntervalsSum + 1);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: secondRateBoxOpenTime });
			const thirdRateBoxOpenTime = getTimeDaysAgo(Intervals.SUBSEQUENT_DISPLAYS_NOT_RATED - 1);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: thirdRateBoxOpenTime });

			const result = AppRatingManager.canOpenAppRating();
			expect(result).toBe(false);
		});

		test('should return false if rate box shown third, not rated, and time for subsequent display come', async () => {
			const allNotRatedIntervalsSum = Intervals.SECOND_DISPLAY_NOT_RATED
				+ Intervals.THIRD_DISPLAY_NOT_RATED + Intervals.SUBSEQUENT_DISPLAYS_NOT_RATED;
			const secondAndThirdIntervalsSum = Intervals.SECOND_DISPLAY_NOT_RATED + Intervals.THIRD_DISPLAY_NOT_RATED;
			const limitReachedTime = getTimeDaysAgo(allNotRatedIntervalsSum + 1);
			await saveEventWithTime(RateEvent.ANY_EVENT_COUNTER_REACHED_LIMIT, { time: limitReachedTime });
			const firstRateBoxOpenTime = getTimeDaysAgo(allNotRatedIntervalsSum + 1);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: firstRateBoxOpenTime });
			const secondRateBoxOpenTime = getTimeDaysAgo(secondAndThirdIntervalsSum + 1);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: secondRateBoxOpenTime });
			const thirdRateBoxOpenTime = getTimeDaysAgo(Intervals.SUBSEQUENT_DISPLAYS_NOT_RATED + 1);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: thirdRateBoxOpenTime });

			const result = AppRatingManager.canOpenAppRating();
			expect(result).toBe(true);
		});

		test('should return false if app rated with not top value, and time for next display not come', async () => {
			const limitReachedTime = getTimeDaysAgo(Intervals.DISPLAY_FOR_RATED - 1);
			await saveEventWithTime(RateEvent.ANY_EVENT_COUNTER_REACHED_LIMIT, { time: limitReachedTime });
			const firstRateBoxOpenTime = getTimeDaysAgo(Intervals.DISPLAY_FOR_RATED - 1);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: firstRateBoxOpenTime });
			const appRateTime = getTimeDaysAgo(Intervals.DISPLAY_FOR_RATED - 1);
			await saveEventWithTime(RateEvent.APP_RATED, { time: appRateTime, context: { value: MinRateForStore - 1 } });

			const result = AppRatingManager.canOpenAppRating();
			expect(result).toBe(false);
		});

		test('should return true if app rated with not top value, and time for next display come', async () => {
			const limitReachedTime = getTimeDaysAgo(Intervals.DISPLAY_FOR_RATED + 1);
			await saveEventWithTime(RateEvent.ANY_EVENT_COUNTER_REACHED_LIMIT, { time: limitReachedTime });
			const firstRateBoxOpenTime = getTimeDaysAgo(Intervals.DISPLAY_FOR_RATED + 1);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: firstRateBoxOpenTime });
			const appRateTime = getTimeDaysAgo(Intervals.DISPLAY_FOR_RATED + 1);
			await saveEventWithTime(RateEvent.APP_RATED, { time: appRateTime, context: { value: MinRateForStore - 1 } });

			const result = AppRatingManager.canOpenAppRating();
			expect(result).toBe(true);
		});

		test('should return true if app rated top value, but user not went to store', async () => {
			const limitReachedTime = getTimeDaysAgo(Intervals.DISPLAY_FOR_RATED * 2);
			await saveEventWithTime(RateEvent.ANY_EVENT_COUNTER_REACHED_LIMIT, { time: limitReachedTime });
			const firstRateBoxOpenTime = getTimeDaysAgo(Intervals.DISPLAY_FOR_RATED * 2);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: firstRateBoxOpenTime });
			const appRateTime = getTimeDaysAgo(Intervals.DISPLAY_FOR_RATED * 2);
			await saveEventWithTime(RateEvent.APP_RATED, { time: appRateTime, context: { value: MinRateForStore } });

			const result = AppRatingManager.canOpenAppRating();
			expect(result).toBe(true);
		});

		test('should return false if app rated top value and user went to store', async () => {
			const limitReachedTime = getTimeDaysAgo(Intervals.DISPLAY_FOR_RATED * 2);
			await saveEventWithTime(RateEvent.ANY_EVENT_COUNTER_REACHED_LIMIT, { time: limitReachedTime });
			const firstRateBoxOpenTime = getTimeDaysAgo(Intervals.DISPLAY_FOR_RATED * 2);
			await saveEventWithTime(RateEvent.RATE_BOX_OPENED, { time: firstRateBoxOpenTime });
			const appRateTime = getTimeDaysAgo(Intervals.DISPLAY_FOR_RATED * 2);
			await saveEventWithTime(RateEvent.APP_RATED, { time: appRateTime, context: { value: MinRateForStore } });
			const userWentToStoreTime = getTimeDaysAgo(Intervals.DISPLAY_FOR_RATED * 2);
			await saveEventWithTime(RateEvent.USER_WENT_TO_STORE, { time: userWentToStoreTime });

			const result = AppRatingManager.canOpenAppRating();
			expect(result).toBe(false);
		});

		test('should return false if app not rated and limit reached not in context event', async () => {
			await increaseEventCounterToLimit(TestUserEvent.TASK_VIEWED);
			const result = AppRatingManager.canOpenAppRating(TestUserEvent.TASK_COMPLETED);
			expect(result).toBe(false);
		});
	});
})();
