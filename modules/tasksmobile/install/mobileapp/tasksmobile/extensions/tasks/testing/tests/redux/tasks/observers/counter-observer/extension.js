(() => {
	const require = (ext) => jn.require(ext);

	const { describe, test, expect } = require('testing');
	const { Type } = require('type');
	const { TaskModel } = require('tasks/statemanager/redux/slices/tasks/model/task');
	const { getCounterChangeValue } = require('tasks/statemanager/redux/slices/tasks/observers/counter-observer');

	const prepareTaskModels = (tasks) => {
		const taskModels = {};

		Object.values(tasks).forEach((task) => {
			const taskId = task.id;

			taskModels[taskId] = {
				...TaskModel.prepareReduxTaskFromServerTask(task),
				creator: 0,
				responsible: 0,
				accomplices: [],
				auditors: [],
			};

			if (!Type.isNil(task.isRemoved))
			{
				taskModels[taskId].isRemoved = task.isRemoved;
			}

			if (task.isConsideredForCounterChange)
			{
				taskModels[taskId].isConsideredForCounterChange = true;
			}

			if (task.isExpired)
			{
				taskModels[taskId].isExpired = true;
			}

			if (task.isMember)
			{
				taskModels[taskId].responsible = Number(env.userId);
			}
		});

		return taskModels;
	};

	const niceTask = {
		newCommentsCount: 1,
		isExpired: true,
		isMember: true,
		isMuted: false,
		isConsideredForCounterChange: true,
		isRemoved: false,
	};

	describe('tasks:redux/tasks/counter-observer', () => {
		test('shouldn\'t do anything when prevTasks and nextTasks are empty', () => {
			const prevTasks = prepareTaskModels({});
			const nextTasks = prepareTaskModels({});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(0);
		});

		test('should correctly process added tasks', () => {
			const prevTasks = prepareTaskModels({});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1 },
				2: { ...niceTask, id: 2 },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(4);
		});

		test('should correctly process added tasks without me in participants', () => {
			const prevTasks = prepareTaskModels({});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isMember: false },
				2: { ...niceTask, id: 2, isMember: false },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(0);
		});

		test('should correctly process added tasks with mute', () => {
			const prevTasks = prepareTaskModels({});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isMuted: true },
				2: { ...niceTask, id: 2, isMuted: true },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(0);
		});

		test('should correctly process added tasks with isConsideredForCounterChange=false', () => {
			const prevTasks = prepareTaskModels({});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isConsideredForCounterChange: false },
				2: { ...niceTask, id: 2, isConsideredForCounterChange: false },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(0);
		});

		test('should correctly process tasks we are no longer participate in', () => {
			const prevTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isMember: true },
				2: { ...niceTask, id: 2, isMember: true, isMuted: true },
				3: { ...niceTask, id: 3, isMember: true, isConsideredForCounterChange: false },
			});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isMember: false },
				2: { ...niceTask, id: 2, isMember: false, isMuted: true },
				3: { ...niceTask, id: 3, isMember: false, isConsideredForCounterChange: false },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(-2);
		});

		test('should correctly process tasks we started to participate in', () => {
			const prevTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isMember: false },
				2: { ...niceTask, id: 2, isMember: false, isMuted: true },
				3: { ...niceTask, id: 3, isMember: false, isConsideredForCounterChange: false },
			});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isMember: true },
				2: { ...niceTask, id: 2, isMember: true, isMuted: true },
				3: { ...niceTask, id: 3, isMember: true, isConsideredForCounterChange: false },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(2);
		});

		test('should correctly process tasks we muted', () => {
			const prevTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isMuted: false },
				2: { ...niceTask, id: 2, isMuted: false, isMember: false },
				3: { ...niceTask, id: 3, isMuted: false, isConsideredForCounterChange: false },
			});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isMuted: true },
				2: { ...niceTask, id: 2, isMuted: true, isMember: false },
				3: { ...niceTask, id: 3, isMuted: true, isConsideredForCounterChange: false },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(-2);
		});

		test('should correctly process tasks we unmuted', () => {
			const prevTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isMuted: true },
				2: { ...niceTask, id: 2, isMuted: true, isMember: false },
				3: { ...niceTask, id: 3, isMuted: true, isConsideredForCounterChange: false },
			});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isMuted: false },
				2: { ...niceTask, id: 2, isMuted: false, isMember: false },
				3: { ...niceTask, id: 3, isMuted: false, isConsideredForCounterChange: false },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(2);
		});

		test('should correctly process tasks we marked as removed', () => {
			const prevTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isRemoved: false },
				2: { ...niceTask, id: 2, isRemoved: false, isMuted: true },
				3: { ...niceTask, id: 3, isRemoved: false, isMember: false },
				4: { ...niceTask, id: 4, isRemoved: false, isConsideredForCounterChange: false },
			});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isRemoved: true },
				2: { ...niceTask, id: 2, isRemoved: true, isMuted: true },
				3: { ...niceTask, id: 3, isRemoved: true, isMember: false },
				4: { ...niceTask, id: 4, isRemoved: true, isConsideredForCounterChange: false },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(-2);
		});

		test('should correctly process tasks we unmarked as removed', () => {
			const prevTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isRemoved: true },
				2: { ...niceTask, id: 2, isRemoved: true, isMuted: true },
				3: { ...niceTask, id: 3, isRemoved: true, isMember: false },
				4: { ...niceTask, id: 4, isRemoved: true, isConsideredForCounterChange: false },
			});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isRemoved: false },
				2: { ...niceTask, id: 2, isRemoved: false, isMuted: true },
				3: { ...niceTask, id: 3, isRemoved: false, isMember: false },
				4: { ...niceTask, id: 4, isRemoved: false, isConsideredForCounterChange: false },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(2);
		});

		test('should correctly process tasks we made expired', () => {
			const prevTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isExpired: false },
				2: { ...niceTask, id: 2, isExpired: false, isMuted: true },
				3: { ...niceTask, id: 3, isExpired: false, isMember: false },
				4: { ...niceTask, id: 4, isExpired: false, isConsideredForCounterChange: false },
			});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isExpired: true },
				2: { ...niceTask, id: 2, isExpired: true, isMuted: true },
				3: { ...niceTask, id: 3, isExpired: true, isMember: false },
				4: { ...niceTask, id: 4, isExpired: true, isConsideredForCounterChange: false },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(1);
		});

		test('should correctly process tasks we made unexpired', () => {
			const prevTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isExpired: true },
				2: { ...niceTask, id: 2, isExpired: true, isMuted: true },
				3: { ...niceTask, id: 3, isExpired: true, isMember: false },
				4: { ...niceTask, id: 4, isExpired: true, isConsideredForCounterChange: false },
			});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, isExpired: false },
				2: { ...niceTask, id: 2, isExpired: false, isMuted: true },
				3: { ...niceTask, id: 3, isExpired: false, isMember: false },
				4: { ...niceTask, id: 4, isExpired: false, isConsideredForCounterChange: false },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(-1);
		});

		test('should correctly process tasks with new comments', () => {
			const prevTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, newCommentsCount: 1 },
				2: { ...niceTask, id: 2, newCommentsCount: 1, isMuted: true },
				3: { ...niceTask, id: 3, newCommentsCount: 1, isMember: false },
				4: { ...niceTask, id: 4, newCommentsCount: 1, isConsideredForCounterChange: false },
			});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, newCommentsCount: 3 },
				2: { ...niceTask, id: 2, newCommentsCount: 3, isMuted: true },
				3: { ...niceTask, id: 3, newCommentsCount: 3, isMember: false },
				4: { ...niceTask, id: 4, newCommentsCount: 3, isConsideredForCounterChange: false },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(2);
		});

		test('should correctly process tasks with read comments', () => {
			const prevTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, newCommentsCount: 3 },
				2: { ...niceTask, id: 2, newCommentsCount: 3, isMuted: true },
				3: { ...niceTask, id: 3, newCommentsCount: 3, isMember: false },
				4: { ...niceTask, id: 4, newCommentsCount: 3, isConsideredForCounterChange: false },
			});
			const nextTasks = prepareTaskModels({
				1: { ...niceTask, id: 1, newCommentsCount: 0 },
				2: { ...niceTask, id: 2, newCommentsCount: 0, isMuted: true },
				3: { ...niceTask, id: 3, newCommentsCount: 0, isMember: false },
				4: { ...niceTask, id: 4, newCommentsCount: 0, isConsideredForCounterChange: false },
			});

			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			expect(counterChangeValue).toEqual(-3);
		});
	});
})();
