(() => {
	const require = (ext) => jn.require(ext);

	const { describe, test, expect, beforeEach } = require('testing');
	const { TasksDashboardFilter: TaskFilter } = require('tasks/dashboard/filter');
	const { TaskModel } = require('tasks/statemanager/redux/slices/tasks/model/task');
	const { TaskStatus } = require('tasks/enum');

	describe('tasks:filter/task', () => {
		let taskFilter = null;

		beforeEach(() => {
			taskFilter = new TaskFilter();
		});

		test('should correctly identify if task suits search text', () => {
			const task = {
				...TaskModel.getDefaultReduxTask(),
				name: 'make some search in task',
			};

			expect(taskFilter.isTaskSuitSearch(task, 'search')).toEqual(true);

			task.name = 'make something with task';

			expect(taskFilter.isTaskSuitSearch(task, 'search')).toEqual(false);
		});

		test('should correctly identify if task suits group', () => {
			const task = {
				...TaskModel.getDefaultReduxTask(),
			};
			const groupId = 33;

			expect(taskFilter.isTaskSuitGroup(task, groupId)).toEqual(false);

			task.groupId = groupId;

			expect(taskFilter.isTaskSuitGroup(task, groupId)).toEqual(true);
		});

		test('should correctly identify if task suits stage', () => {
			const task = TaskModel.getDefaultReduxTask();
			const stageId = 22;
			const taskStage = {
				stageId: 0,
			};

			expect(taskFilter.isTaskSuitStage(task, stageId, '', env.userId, taskStage)).toEqual(false);

			taskStage.stageId = stageId;

			expect(taskFilter.isTaskSuitStage(task, stageId, '', env.userId, taskStage)).toEqual(true);
		});

		test('should correctly identify if task not involving user does not suit any role or counter', () => {
			const taskWithoutMe = {
				...TaskModel.getDefaultReduxTask(),
				creator: Number(env.userId) + 1,
				responsible: Number(env.userId) + 1,
			};

			let isTaskSuit = taskFilter.isTaskSuitRoleCounter(
				taskWithoutMe,
				TaskFilter.roleType.all,
				TaskFilter.counterType.none,
			);
			expect(isTaskSuit).toEqual(false);

			isTaskSuit = taskFilter.isTaskSuitRoleCounter(
				taskWithoutMe,
				TaskFilter.roleType.all,
				TaskFilter.counterType.expired,
			);
			expect(isTaskSuit).toEqual(false);

			isTaskSuit = taskFilter.isTaskSuitRoleCounter(
				taskWithoutMe,
				TaskFilter.roleType.all,
				TaskFilter.counterType.newComments,
			);
			expect(isTaskSuit).toEqual(false);
		});

		test('should correctly identify if task involving user suits list with no role or counter', () => {
			const taskWithMe = TaskModel.getDefaultReduxTask();
			const isTaskSuit = taskFilter.isTaskSuitRoleCounter(
				taskWithMe,
				TaskFilter.roleType.all,
				TaskFilter.counterType.none,
			);
			expect(isTaskSuit).toEqual(true);
		});

		test('should correctly identify if expired task with user as creator suits roleType.originator and counterType.expired', () => {
			const expiredTaskWithMeCreator = {
				...TaskModel.getDefaultReduxTask(),
				responsible: Number(env.userId) + 1,
				deadline: Math.ceil(Date.now() / 1000) - 60,
			};
			const isTaskSuit = taskFilter.isTaskSuitRoleCounter(
				expiredTaskWithMeCreator,
				TaskFilter.roleType.originator,
				TaskFilter.counterType.expired,
			);
			expect(isTaskSuit).toEqual(true);
		});

		test('should correctly identify if expired task with user as responsible suits roleType.responsible and counterType.expired', () => {
			const expiredTaskWithMeResponsible = {
				...TaskModel.getDefaultReduxTask(),
				creator: Number(env.userId) + 1,
				deadline: Math.ceil(Date.now() / 1000) - 60,
			};
			const isTaskSuit = taskFilter.isTaskSuitRoleCounter(
				expiredTaskWithMeResponsible,
				TaskFilter.roleType.responsible,
				TaskFilter.counterType.expired,
			);
			expect(isTaskSuit).toEqual(true);
		});

		test('should correctly identify if expired task with user as accomplice suits roleType.accomplice and counterType.expired', () => {
			const expiredTaskWithMeAccomplice = {
				...TaskModel.getDefaultReduxTask(),
				creator: Number(env.userId) + 1,
				responsible: Number(env.userId) + 1,
				accomplices: [Number(env.userId)],
				deadline: Math.ceil(Date.now() / 1000) - 60,
			};
			const isTaskSuit = taskFilter.isTaskSuitRoleCounter(
				expiredTaskWithMeAccomplice,
				TaskFilter.roleType.accomplice,
				TaskFilter.counterType.expired,
			);
			expect(isTaskSuit).toEqual(true);
		});

		test('should correctly identify if expired task with user as auditor suits roleType.auditor and counterType.expired', () => {
			const expiredTaskWithMeAuditor = {
				...TaskModel.getDefaultReduxTask(),
				creator: Number(env.userId) + 1,
				responsible: Number(env.userId) + 1,
				auditors: [Number(env.userId)],
				deadline: Math.ceil(Date.now() / 1000) - 60,
			};
			const isTaskSuit = taskFilter.isTaskSuitRoleCounter(
				expiredTaskWithMeAuditor,
				TaskFilter.roleType.auditor,
				TaskFilter.counterType.expired,
			);
			expect(isTaskSuit).toEqual(true);
		});

		test('should correctly identify if task with new comments and user as creator suits roleType.originator and counterType.newComments', () => {
			const taskWithNewCommentsAndMeCreator = {
				...TaskModel.getDefaultReduxTask(),
				responsible: Number(env.userId) + 1,
				newCommentsCount: 1,
			};
			const isTaskSuit = taskFilter.isTaskSuitRoleCounter(
				taskWithNewCommentsAndMeCreator,
				TaskFilter.roleType.originator,
				TaskFilter.counterType.newComments,
			);
			expect(isTaskSuit).toEqual(true);
		});

		test('should correctly identify if task with new comments and user as responsible suits roleType.responsible and counterType.newComments', () => {
			const taskWithNewCommentsAndMeResponsible = {
				...TaskModel.getDefaultReduxTask(),
				creator: Number(env.userId) + 1,
				newCommentsCount: 1,
			};
			const isTaskSuit = taskFilter.isTaskSuitRoleCounter(
				taskWithNewCommentsAndMeResponsible,
				TaskFilter.roleType.responsible,
				TaskFilter.counterType.newComments,
			);
			expect(isTaskSuit).toEqual(true);
		});

		test('should correctly identify if task with new comments and user as accomplice suits roleType.accomplice and counterType.newComments', () => {
			const taskWithNewCommentsAndMeAccomplice = {
				...TaskModel.getDefaultReduxTask(),
				creator: Number(env.userId) + 1,
				responsible: Number(env.userId) + 1,
				accomplices: [Number(env.userId)],
				newCommentsCount: 1,
			};
			const isTaskSuit = taskFilter.isTaskSuitRoleCounter(
				taskWithNewCommentsAndMeAccomplice,
				TaskFilter.roleType.accomplice,
				TaskFilter.counterType.newComments,
			);
			expect(isTaskSuit).toEqual(true);
		});

		test('should correctly identify if task with new comments and user as auditor suits roleType.auditor and counterType.newComments', () => {
			const taskWithNewCommentsAndMeAuditor = {
				...TaskModel.getDefaultReduxTask(),
				creator: Number(env.userId) + 1,
				responsible: Number(env.userId) + 1,
				auditors: [Number(env.userId)],
				newCommentsCount: 1,
			};
			const isTaskSuit = taskFilter.isTaskSuitRoleCounter(
				taskWithNewCommentsAndMeAuditor,
				TaskFilter.roleType.auditor,
				TaskFilter.counterType.newComments,
			);
			expect(isTaskSuit).toEqual(true);
		});

		test('should correctly identify if task suits default preset in progress', () => {
			const task = TaskModel.getDefaultReduxTask();
			const presetId = 'filter_tasks_in_progress';

			taskFilter.loaded = true;
			taskFilter.presets = [
				{
					id: presetId,
					fields: {
						STATUS: ['2', '3', '4', '6'],
					},
				},
			];

			expect(taskFilter.isTaskSuitPreset(task, presetId)).toEqual(true);

			task.status = TaskStatus.COMPLETED;

			expect(taskFilter.isTaskSuitPreset(task, presetId)).toEqual(false);
		});
	});
})();
