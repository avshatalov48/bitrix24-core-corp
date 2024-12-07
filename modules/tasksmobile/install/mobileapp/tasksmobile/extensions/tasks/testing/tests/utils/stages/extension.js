(() => {
	const require = (ext) => jn.require(ext);

	const { describe, test, expect } = require('testing');

	const { getStageByDeadline } = require('tasks/utils/stages');
	const { DeadlinePeriod } = require('tasks/enum');

	const now = new Date();
	const startOfTodayInSeconds = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime() / 1000;
	const endOfYesterdayInSeconds = startOfTodayInSeconds - 1;
	const startOfTomorrowInSeconds = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1).getTime() / 1000;
	const endOfTodayInSeconds = startOfTomorrowInSeconds - 1;
	const dayOfWeek = now.getDay(); // 0 (Sunday) - 6 (Saturday)
	const daysUntilNextSunday = (dayOfWeek === 0) ? 0 : (7 - dayOfWeek);
	const nextSundayInSeconds = endOfTodayInSeconds + (daysUntilNextSunday * 60 * 60 * 24) - 1;
	const nextMondayInSeconds = endOfTodayInSeconds + ((daysUntilNextSunday + 1) * 60 * 60 * 24);
	const twoWeeksFromNextMondayInSeconds = nextMondayInSeconds + (60 * 60 * 24 * 7 * 2);
	const overTwoWeeksInSeconds = twoWeeksFromNextMondayInSeconds + 1;

	const stages = [
		{
			id: 1,
			rightBorder: endOfYesterdayInSeconds, // One day ago
			statusId: DeadlinePeriod.PERIOD_OVERDUE,
		},
		{
			id: 2,
			rightBorder: endOfTodayInSeconds, // Today
			statusId: DeadlinePeriod.PERIOD_TODAY,
		},
		{
			id: 3,
			rightBorder: nextSundayInSeconds, // This week
			statusId: DeadlinePeriod.PERIOD_THIS_WEEK,
		},
		{
			id: 4,
			rightBorder: twoWeeksFromNextMondayInSeconds, // Next week
			statusId: DeadlinePeriod.PERIOD_NEXT_WEEK,
		},
		{
			id: 5,
			rightBorder: null,
			statusId: DeadlinePeriod.PERIOD_NO_DEADLINE,
		},
		{
			id: 6,
			rightBorder: overTwoWeeksInSeconds, // Over two weeks
			statusId: DeadlinePeriod.PERIOD_OVER_TWO_WEEKS,
		},
	];

	describe('tasks:util', () => {
		test('should return stage with id 1 when deadline is one day ago', () => {
			const stage = getStageByDeadline(endOfYesterdayInSeconds, stages);
			expect(stage.id).toBe(1);
		});

		test('should return stage with id 2 when deadline is today', () => {
			const stage = getStageByDeadline(endOfTodayInSeconds * 1000, stages);
			expect(stage.id).toBe(2);
		});

		test('should return stage with id 3 when deadline is this week', () => {
			const stage = getStageByDeadline(nextSundayInSeconds * 1000, stages);
			expect(stage.id).toBe(3);
		});

		test('should return stage with id 4 when deadline is next week', () => {
			const stage = getStageByDeadline(twoWeeksFromNextMondayInSeconds * 1000, stages);
			expect(stage.id).toBe(4);
		});

		test('should return stage with id 23 when there is no deadline', () => {
			const stage = getStageByDeadline(null, stages);
			expect(stage.id).toBe(5);
		});

		test('should return stage with id 5 when deadline is over two weeks', () => {
			const stage = getStageByDeadline(overTwoWeeksInSeconds * 1000, stages);
			expect(stage.id).toBe(6);
		});
	});
})();
