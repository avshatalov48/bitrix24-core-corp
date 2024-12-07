(() => {
	const require = (ext) => jn.require(ext);

	const { describe, test, expect } = require('testing');
	const { Type } = require('type');
	const { TaskModel } = require('tasks/statemanager/redux/slices/tasks/model/task');
	const { getDiffForTasksObserver } = require('tasks/statemanager/redux/slices/tasks/observers/stateful-list-observer');

	const prepareTaskModels = (tasks) => {
		const taskModels = {};

		Object.values(tasks).forEach((task) => {
			const taskId = task.id;

			taskModels[taskId] = TaskModel.prepareReduxTaskFromServerTask(task);

			if (Type.isString(taskId))
			{
				taskModels[taskId].id = taskId;
			}

			if (!Type.isUndefined(task.guid))
			{
				taskModels[taskId].guid = task.guid;
			}

			if (!Type.isNil(task.isRemoved))
			{
				taskModels[taskId].isRemoved = task.isRemoved;
			}
		});

		return taskModels;
	};

	describe('tasks:redux/tasks/stateful-list-observer', () => {
		test('should return an empty object when prevTasks and nextTasks are empty', () => {
			const tasks = prepareTaskModels({});

			const diff = getDiffForTasksObserver(tasks, tasks);

			expect(diff).toEqual({
				moved: [],
				removed: [],
				added: [],
				created: [],
			});
		});

		test('should return an empty object when prevTasks and nextTasks are the same', () => {
			const tasks = prepareTaskModels({ 1: { id: 1 } });

			const diff = getDiffForTasksObserver(tasks, tasks);

			expect(diff).toEqual({
				moved: [],
				removed: [],
				added: [],
				created: [],
			});
		});

		test('should correctly identify restored tasks', () => {
			const prevTasks = prepareTaskModels({ 1: { id: 1, isRemoved: true } });
			const nextTasks = prepareTaskModels({ 1: { id: 1 } });

			const diff = getDiffForTasksObserver(prevTasks, nextTasks);

			expect(diff).toEqual({
				moved: [],
				removed: [],
				added: [nextTasks[1]],
				created: [],
			});
		});

		test('should correctly identify restored and added tasks', () => {
			const prevTasks = prepareTaskModels({ 1: { id: 1, isRemoved: true } });
			const nextTasks = prepareTaskModels({ 1: { id: 1 }, 2: { id: 2 } });

			const diff = getDiffForTasksObserver(prevTasks, nextTasks);

			expect(diff).toEqual({
				moved: [],
				removed: [],
				added: [nextTasks[1], nextTasks[2]],
				created: [],
			});
		});

		test('should correctly identify added tasks when empty', () => {
			const prevTasks = prepareTaskModels({});
			const nextTasks = prepareTaskModels({ 1: { id: 1 }, 2: { id: 2 } });

			const diff = getDiffForTasksObserver(prevTasks, nextTasks);

			expect(diff).toEqual({
				moved: [],
				removed: [],
				added: [nextTasks[1], nextTasks[2]],
				created: [],
			});
		});

		test('should correctly identify added tasks when non-empty', () => {
			const prevTasks = prepareTaskModels({ 1: { id: 1 } });
			const nextTasks = prepareTaskModels({ 1: { id: 1 }, 2: { id: 2 } });

			const diff = getDiffForTasksObserver(prevTasks, nextTasks);

			expect(diff).toEqual({
				moved: [],
				removed: [],
				added: [nextTasks[2]],
				created: [],
			});
		});

		test('should correctly identify created tasks', () => {
			const prevTasks = prepareTaskModels({
				1: { id: 1, guid: 'aaa' },
				bbb: { id: 'bbb', guid: 'bbb' },
				ccc: { id: 'ccc', guid: 'ccc' },
			});
			const nextTasks = prepareTaskModels({
				1: { id: 1, guid: 'aaa' },
				2: { id: 2, guid: 'bbb' },
				ccc: { id: 'ccc', guid: 'ccc' },
			});

			const diff = getDiffForTasksObserver(prevTasks, nextTasks);

			expect(diff).toEqual({
				moved: [],
				removed: [],
				added: [],
				created: [nextTasks[2]],
			});
		});

		test('should correctly identify moved tasks', () => {
			const prevTasks = prepareTaskModels({ 1: { id: 1, name: 'Task 1' } });
			const nextTasks = prepareTaskModels({ 1: { id: 1, name: 'Updated Task 1' } });

			const diff = getDiffForTasksObserver(prevTasks, nextTasks);

			expect(diff).toEqual({
				moved: [nextTasks[1]],
				removed: [],
				added: [],
				created: [],
			});
		});

		test('should correctly identify removed tasks', () => {
			const prevTasks = prepareTaskModels({ 1: { id: 1 }, 2: { id: 2 } });
			const nextTasks = prepareTaskModels({ 1: { id: 1 } });

			const diff = getDiffForTasksObserver(prevTasks, nextTasks);

			expect(diff).toEqual({
				moved: [],
				removed: [prevTasks[2]],
				added: [],
				created: [],
			});
			expect(diff.moved).toEqual([]);
			expect(diff.removed).toEqual([prevTasks[2]]);
			expect(diff.added).toEqual([]);
		});

		test('should correctly identify removed tasks with isRemoved flag', () => {
			const prevTasks = prepareTaskModels({ 1: { id: 1 }, 2: { id: 2 } });
			const nextTasks = prepareTaskModels({ 1: { id: 1 }, 2: { id: 2, isRemoved: true } });

			const diff = getDiffForTasksObserver(prevTasks, nextTasks);

			expect(diff).toEqual({
				moved: [],
				removed: [nextTasks[2]],
				added: [],
				created: [],
			});
		});

		test('should correctly identify removed modified tasks with isRemoved property', () => {
			const prevTasks = prepareTaskModels({ 1: { id: 1, name: 'Task 1' } });
			const nextTasks = prepareTaskModels({ 1: { id: 1, name: 'Updated Task 1', isRemoved: true } });

			const diff = getDiffForTasksObserver(prevTasks, nextTasks);

			expect(diff).toEqual({
				moved: [],
				removed: [nextTasks[1]],
				added: [],
				created: [],
			});
		});

		test('should correctly identify modified tasks with isRemoved property', () => {
			const prevTasks = prepareTaskModels({ 1: { id: 1, name: 'Task 1', isRemoved: true } });
			const nextTasks = prepareTaskModels({ 1: { id: 1, name: 'Updated Task 1', isRemoved: true } });

			const diff = getDiffForTasksObserver(prevTasks, nextTasks);

			expect(diff).toEqual({
				moved: [nextTasks[1]],
				removed: [],
				added: [],
				created: [],
			});
		});

		test('should correctly identify removed tasks both with isRemovedFlag=true', () => {
			const prevTasks = prepareTaskModels({ 1: { id: 1 }, 2: { id: 2, isRemoved: true } });
			const nextTasks = prepareTaskModels({ 1: { id: 1 }, 2: { id: 2, isRemoved: true } });

			const diff = getDiffForTasksObserver(prevTasks, nextTasks);

			expect(diff).toEqual({
				moved: [],
				removed: [],
				added: [],
				created: [],
			});
		});

		test('should correctly identify removed tasks both with isRemovedFlag=false', () => {
			const prevTasks = prepareTaskModels({ 1: { id: 1 }, 2: { id: 2, isRemoved: false } });
			const nextTasks = prepareTaskModels({ 1: { id: 1 }, 2: { id: 2, isRemoved: false } });

			const diff = getDiffForTasksObserver(prevTasks, nextTasks);

			expect(diff).toEqual({
				moved: [],
				removed: [],
				added: [],
				created: [],
			});
		});
	});
})();
