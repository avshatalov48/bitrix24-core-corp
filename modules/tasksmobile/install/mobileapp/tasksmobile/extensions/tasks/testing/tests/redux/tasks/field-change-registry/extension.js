(() => {
	const require = (ext) => jn.require(ext);
	const { describe, test, expect, beforeEach } = require('testing');
	const { FieldChangeRegistry } = require('tasks/statemanager/redux/slices/tasks/field-change-registry');
	const { TaskModel } = require('tasks/statemanager/redux/slices/tasks/model/task');
	const { TaskStatus } = require('tasks/enum');
	const {
		prepareUpdateDeadlineNewState,
		prepareDelegateNewState,
		processFieldChanges,
		unregisterRegistryChanges,
	} = require('tasks/statemanager/redux/slices/tasks/extra-reducer');

	const getOldTaskState = (now) => ({
		id: 1,
		newCommentsCount: 0,
		status: TaskStatus.PENDING,
		creator: 2,
		responsible: 1,
		accomplices: [],
		auditors: [],

		isMuted: false,
		isPinned: false,
		isInFavorites: false,
		isTimerRunningForCurrentUser: false,

		deadline: Math.ceil(now / 1000) - 3600,
		activityDate: Math.ceil(now / 1000) - 2 * 3600,

		canUseTimer: false,
		canStart: false,
		canPause: false,
		canComplete: false,
		canRenew: false,
		canApprove: false,
		canDisapprove: false,

		isRemoved: false,
		isExpired: true,
		isConsideredForCounterChange: false,
	});
	const fulfillRequest = (requestId) => unregisterRegistryChanges(requestId);

	describe('tasks:redux/tasks/field-change-registry', () => {
		beforeEach(() => FieldChangeRegistry.clear());

		// region update deadline

		test('should correctly prepare new task state after deadline updates', () => {
			const now = Date.now();
			const oldTaskState = getOldTaskState(now);

			const firstNewDeadline = Math.ceil(now / 1000) + 3600;
			const firstNewActivityDate = now;
			const firstNewTaskState = prepareUpdateDeadlineNewState(oldTaskState, firstNewDeadline, firstNewActivityDate);
			expect(firstNewTaskState).toEqual({
				...oldTaskState,
				deadline: firstNewDeadline,
				activityDate: Math.ceil(firstNewActivityDate / 1000),
				isExpired: false,
				isConsideredForCounterChange: true,
			});

			const secondNewDeadline = firstNewDeadline + 3600;
			const secondNewActivityDate = firstNewActivityDate + 1000;
			const secondNewTaskState = prepareUpdateDeadlineNewState(
				firstNewTaskState,
				secondNewDeadline,
				secondNewActivityDate,
			);
			expect(secondNewTaskState).toEqual({
				...oldTaskState,
				deadline: secondNewDeadline,
				activityDate: Math.ceil(secondNewActivityDate / 1000),
				isExpired: false,
				isConsideredForCounterChange: true,
			});
		});

		test('should correctly process field changes after successive deadline updates', () => {
			const now = Date.now();
			const oldTaskState = getOldTaskState(now);

			// pending 1
			const firstRequestId = 'request_1';
			const firstNewDeadline = Math.ceil(now / 1000) + 3600;
			const firstNewActivityDate = now;
			const firstNewTaskState = prepareUpdateDeadlineNewState(oldTaskState, firstNewDeadline, firstNewActivityDate);
			processFieldChanges(firstRequestId, oldTaskState, firstNewTaskState);
			expect(FieldChangeRegistry.fieldsRegistry).toEqual(
				new Map([
					[firstRequestId, { 1: ['deadline', 'activityDate', 'isExpired', 'isConsideredForCounterChange'] }],
				]),
			);

			// pending 2
			const secondRequestId = 'request_2';
			const secondNewDeadline = firstNewDeadline + 3600;
			const secondNewActivityDate = firstNewActivityDate + 1000;
			const secondNewTaskState = prepareUpdateDeadlineNewState(
				firstNewTaskState,
				secondNewDeadline,
				secondNewActivityDate,
			);
			processFieldChanges(secondRequestId, firstNewTaskState, secondNewTaskState);
			expect(FieldChangeRegistry.fieldsRegistry).toEqual(
				new Map([
					[firstRequestId, { 1: ['deadline', 'activityDate', 'isExpired', 'isConsideredForCounterChange'] }],
					[secondRequestId, { 1: ['deadline', 'activityDate'] }],
				]),
			);

			// fulfill 1
			fulfillRequest(firstRequestId);
			expect(FieldChangeRegistry.fieldsRegistry).toEqual(
				new Map([
					[secondRequestId, { 1: ['deadline', 'activityDate'] }],
				]),
			);

			// fulfill 2
			fulfillRequest(secondRequestId);
			expect(FieldChangeRegistry.fieldsRegistry).toEqual(new Map());
		});

		test('should correctly prepare task from server while having changed fields from single deadline update', () => {
			const now = Date.now();
			const oldTaskState = getOldTaskState(now);
			const serverTask = {
				id: 1,
				deadline: Math.ceil(now / 1000) + 10 * 3600,
				activityDate: Math.ceil(now / 1000) + 5 * 3600,
			};

			// pending
			const requestId = 'request';
			const newDeadline = Math.ceil(now / 1000) + 3600;
			const newActivityDate = now;
			const newTaskState = prepareUpdateDeadlineNewState(oldTaskState, newDeadline, newActivityDate);
			processFieldChanges(requestId, oldTaskState, newTaskState);

			// check task on push
			let preparedServerTask = TaskModel.prepareReduxTaskFromServerTask(serverTask, newTaskState);
			expect(preparedServerTask).toEqual({
				...newTaskState,
				isConsideredForCounterChange: false,
			});

			// fulfill
			fulfillRequest(requestId);

			// check task on push
			preparedServerTask = TaskModel.prepareReduxTaskFromServerTask(serverTask, newTaskState);
			expect(preparedServerTask).toEqual({
				...newTaskState,
				...serverTask,
				isConsideredForCounterChange: false,
			});
		});

		test('should correctly prepare task from server while having changed fields from multiple deadline updates', () => {
			const now = Date.now();
			const oldTaskState = getOldTaskState(now);
			const serverTask = {
				id: 1,
				deadline: Math.ceil(now / 1000) + 10 * 3600,
				activityDate: Math.ceil(now / 1000) + 5 * 3600,
			};

			// pending 1
			const firstRequestId = 'request_1';
			const firstNewDeadline = Math.ceil(now / 1000) + 3600;
			const firstNewActivityDate = now;
			const firstNewTaskState = prepareUpdateDeadlineNewState(oldTaskState, firstNewDeadline, firstNewActivityDate);
			processFieldChanges(firstRequestId, oldTaskState, firstNewTaskState);

			// pending 2
			const secondRequestId = 'request_2';
			const secondNewDeadline = firstNewDeadline + 3600;
			const secondNewActivityDate = firstNewActivityDate + 1000;
			const secondNewTaskState = prepareUpdateDeadlineNewState(
				firstNewTaskState,
				secondNewDeadline,
				secondNewActivityDate,
			);
			processFieldChanges(secondRequestId, firstNewTaskState, secondNewTaskState);

			// check task on push
			let preparedServerTask = TaskModel.prepareReduxTaskFromServerTask(serverTask, secondNewTaskState);
			expect(preparedServerTask).toEqual({
				...secondNewTaskState,
				isConsideredForCounterChange: false,
			});

			// fulfill 1
			fulfillRequest(firstRequestId);

			// check task on push
			preparedServerTask = TaskModel.prepareReduxTaskFromServerTask(serverTask, secondNewTaskState);
			expect(preparedServerTask).toEqual({
				...secondNewTaskState,
				isConsideredForCounterChange: false,
			});

			// fulfill 2
			fulfillRequest(secondRequestId);

			// check task on push
			preparedServerTask = TaskModel.prepareReduxTaskFromServerTask(serverTask, secondNewTaskState);
			expect(preparedServerTask).toEqual({
				...secondNewTaskState,
				...serverTask,
				isConsideredForCounterChange: false,
			});
		});

		// endregion

		// region delegate

		test('should correctly prepare new task state after delegates', () => {
			const now = Date.now();
			const oldTaskState = getOldTaskState(now);

			// pending 1
			const firstNewResponsible = 5;
			const firstNewActivityDate = now;
			const firstNewTaskState = prepareDelegateNewState(oldTaskState, firstNewResponsible, firstNewActivityDate);
			expect(firstNewTaskState).toEqual({
				...oldTaskState,
				responsible: firstNewResponsible,
				activityDate: Math.ceil(firstNewActivityDate / 1000),
				isConsideredForCounterChange: true,
			});

			// pending 1
			const secondNewResponsible = 10;
			const secondNewActivityDate = firstNewActivityDate + 1000;
			const secondNewTaskState = prepareDelegateNewState(
				firstNewTaskState,
				secondNewResponsible,
				secondNewActivityDate,
			);
			expect(secondNewTaskState).toEqual({
				...oldTaskState,
				responsible: secondNewResponsible,
				activityDate: Math.ceil(secondNewActivityDate / 1000),
				isConsideredForCounterChange: true,
			});
		});

		test('should correctly process field changes after successive delegates', () => {
			const now = Date.now();
			const oldTaskState = getOldTaskState(now);

			// pending 1
			const firstRequestId = 'request_1';
			const firstNewResponsible = 5;
			const firstNewActivityDate = now;
			const firstNewTaskState = prepareDelegateNewState(oldTaskState, firstNewResponsible, firstNewActivityDate);
			processFieldChanges(firstRequestId, oldTaskState, firstNewTaskState);
			expect(FieldChangeRegistry.fieldsRegistry).toEqual(
				new Map([
					[firstRequestId, { 1: ['responsible', 'activityDate', 'isConsideredForCounterChange'] }],
				]),
			);

			// pending 2
			const secondRequestId = 'request_2';
			const secondNewResponsible = 10;
			const secondNewActivityDate = firstNewActivityDate + 1000;
			const secondNewTaskState = prepareDelegateNewState(
				firstNewTaskState,
				secondNewResponsible,
				secondNewActivityDate,
			);
			processFieldChanges(secondRequestId, firstNewTaskState, secondNewTaskState);
			expect(FieldChangeRegistry.fieldsRegistry).toEqual(
				new Map([
					[firstRequestId, { 1: ['responsible', 'activityDate', 'isConsideredForCounterChange'] }],
					[secondRequestId, { 1: ['responsible', 'activityDate'] }],
				]),
			);

			// fulfill 1
			fulfillRequest(firstRequestId);
			expect(FieldChangeRegistry.fieldsRegistry).toEqual(
				new Map([
					[secondRequestId, { 1: ['responsible', 'activityDate'] }],
				]),
			);

			// fulfill 2
			fulfillRequest(secondRequestId);
			expect(FieldChangeRegistry.fieldsRegistry).toEqual(new Map());
		});

		test('should correctly prepare task from server while having changed fields from single delegate', () => {
			const now = Date.now();
			const oldTaskState = getOldTaskState(now);
			const serverTask = {
				id: 1,
				responsible: 69,
				activityDate: Math.ceil(now / 1000) + 10 * 3600,
			};

			// pending
			const requestId = 'request';
			const newResponsible = 5;
			const newActivityDate = now;
			const newTaskState = prepareDelegateNewState(oldTaskState, newResponsible, newActivityDate);
			processFieldChanges(requestId, oldTaskState, newTaskState);

			// check task on push
			let preparedServerTask = TaskModel.prepareReduxTaskFromServerTask(serverTask, newTaskState);
			expect(preparedServerTask).toEqual({
				...newTaskState,
				isConsideredForCounterChange: false,
			});

			// fulfill
			fulfillRequest(requestId);

			// check task on push
			preparedServerTask = TaskModel.prepareReduxTaskFromServerTask(serverTask, newTaskState);
			expect(preparedServerTask).toEqual({
				...newTaskState,
				...serverTask,
				isConsideredForCounterChange: false,
			});
		});

		test('should correctly prepare task from server while having changed fields from multiple delegates', () => {
			const now = Date.now();
			const oldTaskState = getOldTaskState(now);
			const serverTask = {
				id: 1,
				responsible: 69,
				activityDate: Math.ceil(now / 1000) + 10 * 3600,
			};

			// pending 1
			const firstRequestId = 'request_1';
			const firstNewResponsible = 5;
			const firstNewActivityDate = now;
			const firstNewTaskState = prepareDelegateNewState(oldTaskState, firstNewResponsible, firstNewActivityDate);
			processFieldChanges(firstRequestId, oldTaskState, firstNewTaskState);

			// pending 2
			const secondRequestId = 'request_2';
			const secondNewResponsible = 10;
			const secondNewActivityDate = firstNewActivityDate + 1000;
			const secondNewTaskState = prepareDelegateNewState(
				firstNewTaskState,
				secondNewResponsible,
				secondNewActivityDate,
			);
			processFieldChanges(secondRequestId, firstNewTaskState, secondNewTaskState);

			// check task on push
			let preparedServerTask = TaskModel.prepareReduxTaskFromServerTask(serverTask, secondNewTaskState);
			expect(preparedServerTask).toEqual({
				...secondNewTaskState,
				isConsideredForCounterChange: false,
			});

			// fulfill 1
			fulfillRequest(firstRequestId);

			// check task on push
			preparedServerTask = TaskModel.prepareReduxTaskFromServerTask(serverTask, secondNewTaskState);
			expect(preparedServerTask).toEqual({
				...secondNewTaskState,
				isConsideredForCounterChange: false,
			});

			// fulfill 2
			fulfillRequest(secondRequestId);

			// check task on push
			preparedServerTask = TaskModel.prepareReduxTaskFromServerTask(serverTask, secondNewTaskState);
			expect(preparedServerTask).toEqual({
				...secondNewTaskState,
				...serverTask,
				isConsideredForCounterChange: false,
			});
		});

		// endregion
	});
})();
