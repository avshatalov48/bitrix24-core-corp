/**
 * @module tasks/statemanager/redux/slices/tasks/extra-reducer
 */
jn.define('tasks/statemanager/redux/slices/tasks/extra-reducer', (require, exports, module) => {
	const { TaskFilter } = require('tasks/filter/task');
	const { FieldChangeRegistry } = require('tasks/statemanager/redux/slices/tasks/field-change-registry');
	const { tasksAdapter } = require('tasks/statemanager/redux/slices/tasks/meta');
	const { TaskModel } = require('tasks/statemanager/redux/slices/tasks/model/task');
	const { TaskStatus } = require('tasks/enum');
	const { selectIsExpired, selectIsMember, selectCounter } = require('tasks/statemanager/redux/slices/tasks/selector');
	const { isEqual } = require('utils/object');

	/**
	 * @param {Object} state
	 * @param {Object} action
	 * @param {TaskReduxModel} oldTaskState
	 * @param {TaskReduxModel} newTaskState
	 */
	const upsertOne = (state, action, oldTaskState, newTaskState) => {
		processFieldChanges(action.meta.requestId, oldTaskState, newTaskState);
		tasksAdapter.upsertOne(state, newTaskState);
	};

	/**
	 * @param {Object} state
	 * @param {Object} action
	 * @param {Array<TaskReduxModel>} oldTaskStates
	 * @param {Array<TaskReduxModel>} newTaskStates
	 */
	const upsertMany = (state, action, oldTaskStates, newTaskStates) => {
		oldTaskStates.forEach((oldTaskState, index) => {
			processFieldChanges(action.meta.requestId, oldTaskState, newTaskStates[index]);
		});
		tasksAdapter.upsertMany(state, newTaskStates);
	};

	/**
	 * @param {string} requestId
	 * @param {TaskReduxModel} oldTaskState
	 * @param {TaskReduxModel} newTaskState
	 */
	const processFieldChanges = (requestId, oldTaskState, newTaskState) => {
		const changedFields = [];
		Object.keys(newTaskState).forEach((field) => {
			if (newTaskState[field] !== oldTaskState[field])
			{
				changedFields.push(field);
			}
		});
		FieldChangeRegistry.registerFieldsChange(requestId, newTaskState.id, changedFields);

		const oldCounter = selectCounter(oldTaskState).value;
		const newCounter = selectCounter(newTaskState).value;

		const isMuteChanged = changedFields.includes('isMuted');
		const isParticipationChanged = selectIsMember(oldTaskState) !== selectIsMember(newTaskState);
		// we need to register counter changes only for tasks included in common counter
		const isIncludedInCommonCounter = (
			(isParticipationChanged || selectIsMember(newTaskState))
			&& (isMuteChanged || !newTaskState.isMuted)
		);

		if ((oldCounter !== newCounter) && isIncludedInCommonCounter)
		{
			FieldChangeRegistry.registerCounterChange(requestId, newCounter - oldCounter);
		}
	};

	const prepareUpdateDeadlineNewState = (oldTaskState, deadline, activityDate = Date.now()) => ({
		...oldTaskState,
		deadline,
		activityDate: Math.ceil(activityDate / 1000),
		isExpired: selectIsExpired({ ...oldTaskState, deadline }),
		isConsideredForCounterChange: true,
	});

	const updateDeadlinePending = (state, action) => {
		const { taskId, deadline } = action.meta.arg;
		const newDeadline = Math.ceil(deadline / 1000);

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareUpdateDeadlineNewState(oldTaskState, newDeadline);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareDelegateNewState = (oldTaskState, responsible, activityDate = Date.now()) => ({
		...oldTaskState,
		responsible,
		activityDate: Math.ceil(activityDate / 1000),
		isConsideredForCounterChange: true,
	});

	const delegatePending = (state, action) => {
		const { taskId, userId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareDelegateNewState(oldTaskState, Number(userId));

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareUnfollowNewState = (oldTaskState) => ({
		...oldTaskState,
		auditors: oldTaskState.auditors.filter((userId) => userId !== Number(env.userId)),
		isConsideredForCounterChange: true,
	});

	const unfollowPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareUnfollowNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareStartTimerNewState = (oldTaskState) => ({
		...oldTaskState,
		isTimerRunningForCurrentUser: true,
	});

	const startTimerPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareStartTimerNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const preparePauseTimerNewState = (oldTaskState) => ({
		...oldTaskState,
		isTimerRunningForCurrentUser: false,
	});

	const pauseTimerPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = preparePauseTimerNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareStartNewState = (oldTaskState) => ({
		...oldTaskState,
		status: TaskStatus.IN_PROGRESS,
		canStart: false,
		canPause: true,
	});

	const startPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareStartNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const preparePauseNewState = (oldTaskState) => ({
		...oldTaskState,
		status: TaskStatus.PENDING,
		canStart: true,
		canPause: false,
	});

	const pausePending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = preparePauseNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareCompleteNewState = (oldTaskState, activityDate = Date.now()) => ({
		...oldTaskState,
		activityDate: Math.ceil(activityDate / 1000),
		status: TaskStatus.COMPLETED,
		canUseTimer: false,
		canStart: false,
		canPause: false,
		canRenew: true,
		canComplete: false,
		isConsideredForCounterChange: true,
	});

	const completePending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareCompleteNewState(oldTaskState);

		if (!oldTaskState.isResultRequired || oldTaskState.isOpenResultExists)
		{
			upsertOne(state, action, oldTaskState, newTaskState);
		}
	};

	const prepareRenewNewState = (oldTaskState, activityDate = Date.now()) => ({
		...oldTaskState,
		activityDate: Math.ceil(activityDate / 1000),
		status: TaskStatus.PENDING,
		canStart: true,
		canRenew: false,
		canComplete: true,
		isConsideredForCounterChange: true,
	});

	const renewPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareRenewNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareApproveNewState = (oldTaskState, activityDate = Date.now()) => ({
		...oldTaskState,
		activityDate: Math.ceil(activityDate / 1000),
		status: TaskStatus.COMPLETED,
		canApprove: false,
		canDisapprove: false,
		canRenew: true,
	});

	const approvePending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareApproveNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareDisapproveNewState = (oldTaskState, activityDate = Date.now()) => ({
		...oldTaskState,
		activityDate: Math.ceil(activityDate / 1000),
		status: TaskStatus.PENDING,
		canStart: true,
		canApprove: false,
		canDisapprove: false,
		canComplete: true,
		isConsideredForCounterChange: true,
	});

	const disapprovePending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareDisapproveNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const preparePingNewState = (oldTaskState, activityDate = Date.now()) => ({
		...oldTaskState,
		activityDate: Math.ceil(activityDate / 1000),
	});

	const pingPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = preparePingNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const preparePinNewState = (oldTaskState) => ({
		...oldTaskState,
		isPinned: true,
	});

	const pinPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = preparePinNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareUnpinNewState = (oldTaskState) => ({
		...oldTaskState,
		isPinned: false,
	});

	const unpinPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareUnpinNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareMuteNewState = (oldTaskState) => ({
		...oldTaskState,
		isMuted: true,
		isConsideredForCounterChange: true,
	});

	const mutePending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareMuteNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareUnmuteNewState = (oldTaskState) => ({
		...oldTaskState,
		isMuted: false,
		isConsideredForCounterChange: true,
	});

	const unmutePending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareUnmuteNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareAddToFavoritesNewState = (oldTaskState) => ({
		...oldTaskState,
		isInFavorites: true,
	});

	const addToFavoritesPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareAddToFavoritesNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareRemoveFromFavoritesNewState = (oldTaskState) => ({
		...oldTaskState,
		isInFavorites: false,
	});

	const removeFromFavoritesPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareRemoveFromFavoritesNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const removePending = (state, action) => tasksAdapter.removeOne(state, action.meta.arg.taskId);

	const prepareReadNewState = (oldTaskState) => ({
		...oldTaskState,
		newCommentsCount: 0,
		isConsideredForCounterChange: true,
	});

	const readPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareReadNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareReadAllNewStates = (oldTaskStates) => {
		return [...oldTaskStates].map((oldTaskState) => ({ ...oldTaskState, newCommentsCount: 0 }));
	};

	const readAllForRolePending = (state, action) => {
		const { groupId, role } = action.meta.arg.fields;
		const userId = Number(env.userId);

		let oldTaskStates = Object.values(state.entities);
		if (groupId)
		{
			oldTaskStates = oldTaskStates.filter((task) => task.groupId === groupId);
		}
		oldTaskStates = oldTaskStates.filter((task) => {
			const isCreator = task.creator === userId;
			const isResponsible = task.responsible === userId;
			const isAccomplice = task.accomplices.includes(userId);
			const isAuditor = task.auditors.includes(userId);
			const roleMap = {
				[TaskFilter.roleType.all]: (isCreator || isResponsible || isAccomplice || isAuditor),
				[TaskFilter.roleType.originator]: isCreator,
				[TaskFilter.roleType.responsible]: isResponsible,
				[TaskFilter.roleType.accomplice]: isAccomplice,
				[TaskFilter.roleType.auditor]: isAuditor,
			};

			return roleMap[role];
		});
		const newTaskStates = prepareReadAllNewStates(oldTaskStates);

		upsertMany(state, action, oldTaskStates, newTaskStates);
	};

	const readAllForProjectPending = (state, action) => {
		const { groupId } = action.meta.arg.fields;

		const oldTaskStates = Object.values(state.entities).filter((task) => task.groupId === groupId);
		const newTaskStates = prepareReadAllNewStates(oldTaskStates);

		upsertMany(state, action, oldTaskStates, newTaskStates);
	};

	/**
	 * @param {string} requestId
	 */
	const unregisterRegistryChanges = (requestId) => {
		FieldChangeRegistry.unregisterCounterChange(requestId);
		FieldChangeRegistry.unregisterFieldsChange(requestId);
	};

	const onCommonActionFulfilled = (state, action) => {
		const { meta, payload } = action;
		const { requestId, arg } = meta;

		unregisterRegistryChanges(requestId);

		const existingTask = state.entities[arg.taskId];
		const preparedTask = TaskModel.prepareReduxTaskFromServerTask(payload.data.task, existingTask);

		if (!isEqual(existingTask, preparedTask))
		{
			tasksAdapter.upsertOne(state, preparedTask);
		}
	};

	const updateDeadlineFulfilled = onCommonActionFulfilled;

	const delegateFulfilled = onCommonActionFulfilled;

	const unfollowFulfilled = onCommonActionFulfilled;

	const startTimerFulfilled = onCommonActionFulfilled;

	const pauseTimerFulfilled = onCommonActionFulfilled;

	const startFulfilled = onCommonActionFulfilled;

	const pauseFulfilled = onCommonActionFulfilled;

	const completeFulfilled = onCommonActionFulfilled;

	const renewFulfilled = onCommonActionFulfilled;

	const approveFulfilled = onCommonActionFulfilled;

	const disapproveFulfilled = onCommonActionFulfilled;

	const pingFulfilled = (state, action) => unregisterRegistryChanges(action.meta.requestId);

	const pinFulfilled = onCommonActionFulfilled;

	const unpinFulfilled = onCommonActionFulfilled;

	const muteFulfilled = onCommonActionFulfilled;

	const unmuteFulfilled = onCommonActionFulfilled;

	const readFulfilled = (state, action) => {
		const { requestId, arg } = action.meta;

		unregisterRegistryChanges(requestId);

		const existingTask = state.entities[arg.taskId];

		tasksAdapter.upsertOne(state, {
			...existingTask,
			isConsideredForCounterChange: false,
		});
	};

	const readAllForRoleFulfilled = (state, action) => unregisterRegistryChanges(action.meta.requestId);

	const readAllForProjectFulfilled = (state, action) => unregisterRegistryChanges(action.meta.requestId);

	module.exports = {
		prepareUpdateDeadlineNewState,
		prepareDelegateNewState,
		prepareUnfollowNewState,
		prepareStartTimerNewState,
		preparePauseTimerNewState,
		prepareStartNewState,
		preparePauseNewState,
		prepareCompleteNewState,
		prepareRenewNewState,
		prepareApproveNewState,
		prepareDisapproveNewState,
		preparePingNewState,
		preparePinNewState,
		prepareUnpinNewState,
		prepareMuteNewState,
		prepareUnmuteNewState,
		prepareAddToFavoritesNewState,
		prepareRemoveFromFavoritesNewState,
		prepareReadNewState,
		prepareReadAllNewStates,

		processFieldChanges,
		unregisterRegistryChanges,

		updateDeadlinePending,
		updateDeadlineFulfilled,
		delegatePending,
		delegateFulfilled,
		unfollowPending,
		unfollowFulfilled,
		startTimerPending,
		startTimerFulfilled,
		pauseTimerPending,
		pauseTimerFulfilled,
		startPending,
		startFulfilled,
		pausePending,
		pauseFulfilled,
		completePending,
		completeFulfilled,
		renewPending,
		renewFulfilled,
		approvePending,
		approveFulfilled,
		disapprovePending,
		disapproveFulfilled,
		pingPending,
		pingFulfilled,
		pinPending,
		pinFulfilled,
		unpinPending,
		unpinFulfilled,
		mutePending,
		muteFulfilled,
		unmutePending,
		unmuteFulfilled,
		addToFavoritesPending,
		removeFromFavoritesPending,
		removePending,
		readPending,
		readFulfilled,
		readAllForRolePending,
		readAllForRoleFulfilled,
		readAllForProjectPending,
		readAllForProjectFulfilled,
	};
});
