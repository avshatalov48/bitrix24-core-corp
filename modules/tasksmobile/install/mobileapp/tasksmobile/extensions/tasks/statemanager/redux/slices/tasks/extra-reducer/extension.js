/**
 * @module tasks/statemanager/redux/slices/tasks/extra-reducer
 */
jn.define('tasks/statemanager/redux/slices/tasks/extra-reducer', (require, exports, module) => {
	const { TasksDashboardFilter } = require('tasks/dashboard/filter');
	const { FieldChangeRegistry } = require('tasks/statemanager/redux/slices/tasks/field-change-registry');
	const { tasksAdapter } = require('tasks/statemanager/redux/slices/tasks/meta');
	const { TaskModel } = require('tasks/statemanager/redux/slices/tasks/model/task');
	const { TaskStatus } = require('tasks/enum');
	const {
		selectIsExpired,
		selectIsMember,
		selectCounter,
		selectIsPureCreator,
		selectIsCreator,
	} = require('tasks/statemanager/redux/slices/tasks/selector');
	const { isEqual } = require('utils/object');
	const { Type } = require('type');
	const { Views } = require('tasks/statemanager/redux/types');

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
		const changedFields = {};
		Object.keys(newTaskState).forEach((field) => {
			if (newTaskState[field] !== oldTaskState[field])
			{
				changedFields[field] = oldTaskState[field];
			}
		});
		FieldChangeRegistry.registerFieldsChange(requestId, newTaskState.id, changedFields);

		const oldCounter = selectCounter(oldTaskState).value;
		const newCounter = selectCounter(newTaskState).value;

		const isMuteChanged = Object.keys(changedFields).includes('isMuted');
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

	const createPending = (state, action) => {
		const { reduxFields } = action.meta.arg;

		return tasksAdapter.upsertOne(state, { ...TaskModel.getDefaultReduxTask(), ...reduxFields });
	};

	const prepareUpdateNewState = (oldTaskState, reduxFields) => {
		const newTaskState = {
			...oldTaskState,
			...reduxFields,
		};

		if (
			newTaskState.creator === oldTaskState.creator
			&& newTaskState.responsible !== oldTaskState.responsible
			&& !newTaskState.auditors.includes(oldTaskState.responsible)
		)
		{
			newTaskState.auditors = [...newTaskState.auditors, oldTaskState.responsible];
		}

		return prepareNewReduxState(newTaskState);
	};

	const updatePending = (state, action) => {
		const { taskId, reduxFields } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		if (oldTaskState)
		{
			const newTaskState = prepareUpdateNewState(oldTaskState, reduxFields);

			upsertOne(state, action, oldTaskState, newTaskState);
		}
	};

	const prepareNewReduxState = (reduxFields) => {
		const preparedFields = { ...reduxFields };

		if (preparedFields.accomplices?.length > 0)
		{
			preparedFields.accomplices = preparedFields.accomplices.map((userId) => Number(userId));
			preparedFields.accomplices.sort((a, b) => a - b);
		}

		if (preparedFields.auditors?.length > 0)
		{
			preparedFields.auditors = preparedFields.auditors.map((userId) => Number(userId));
			preparedFields.auditors.sort((a, b) => a - b);
		}

		return preparedFields;
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
		auditors: (
			oldTaskState.auditors.includes(oldTaskState.responsible)
				? oldTaskState.auditors
				: [...oldTaskState.auditors, oldTaskState.responsible]
		),
		activityDate: Math.ceil(activityDate / 1000),
		isConsideredForCounterChange: true,
	});

	const delegatePending = (state, action) => {
		const { taskId, userId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareDelegateNewState(oldTaskState, Number(userId));

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareFollowNewState = (oldTaskState) => {
		const newTaskState = {
			...oldTaskState,
			auditors: [...oldTaskState.auditors, Number(env.userId)],
			isConsideredForCounterChange: true,
		};

		return prepareNewReduxState(newTaskState);
	};

	const followPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareFollowNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareUnfollowNewState = (oldTaskState) => {
		const newTaskState = ({
			...oldTaskState,
			auditors: oldTaskState.auditors.filter((userId) => userId !== Number(env.userId)),
			isConsideredForCounterChange: true,
		});

		return prepareNewReduxState(newTaskState);
	};

	const unfollowPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareUnfollowNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareStartTimerNewState = (oldTaskState) => ({
		...oldTaskState,
		status: TaskStatus.IN_PROGRESS,
		canDefer: false,
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
		canDefer: false,
	});

	const startPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareStartNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareTakeNewState = (oldTaskState) => {
		return {
			...oldTaskState,
			status: TaskStatus.IN_PROGRESS,
			canStart: false,
			canPause: true,
			canComplete: true,
			canDefer: false,
			responsible: Number(env.userId),
		};
	};

	const takePending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareTakeNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const preparePauseNewState = (oldTaskState) => ({
		...oldTaskState,
		status: TaskStatus.PENDING,
		canStart: true,
		canPause: false,
		canDefer: true,
	});

	const pausePending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = preparePauseNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareCompleteNewState = (oldTaskState, activityDate = Date.now()) => {
		let status = TaskStatus.COMPLETED;

		if (oldTaskState.allowTaskControl && !selectIsPureCreator(oldTaskState))
		{
			status = TaskStatus.SUPPOSEDLY_COMPLETED;
		}

		return {
			...oldTaskState,
			activityDate: Math.ceil(activityDate / 1000),
			status,
			canUseTimer: false,
			canStart: false,
			canPause: false,
			canRenew: true,
			canComplete: false,
			isConsideredForCounterChange: true,
		};
	};

	const completePending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareCompleteNewState(oldTaskState);

		if (
			env.isAdmin
			|| selectIsCreator(newTaskState)
			|| !oldTaskState.isResultRequired
			|| oldTaskState.isOpenResultExists
		)
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
		canDefer: true,
		isConsideredForCounterChange: true,
	});

	const renewPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareRenewNewState(oldTaskState);

		upsertOne(state, action, oldTaskState, newTaskState);
	};

	const prepareDeferNewState = (oldTaskState, activityDate = Date.now()) => ({
		...oldTaskState,
		activityDate: Math.ceil(activityDate / 1000),
		status: TaskStatus.DEFERRED,
		canStart: false,
		canRenew: true,
		canComplete: true,
		canDefer: false,
		isConsideredForCounterChange: true,
	});

	const deferPending = (state, action) => {
		const { taskId } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		const newTaskState = prepareDeferNewState(oldTaskState);

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
				[TasksDashboardFilter.roleType.all]: (isCreator || isResponsible || isAccomplice || isAuditor),
				[TasksDashboardFilter.roleType.originator]: isCreator,
				[TasksDashboardFilter.roleType.responsible]: isResponsible,
				[TasksDashboardFilter.roleType.accomplice]: isAccomplice,
				[TasksDashboardFilter.roleType.auditor]: isAuditor,
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

	const updateSubTasksPending = (state, action) => {
		const { parentId, newSubTasks, deletedSubTasks } = action.meta.arg;

		// Prepare old and new states for new subtasks
		const oldNewSubTaskStates = newSubTasks.map((subTaskId) => state.entities[subTaskId]).filter(Boolean);
		const newNewSubTaskStates = newSubTasks.map((subTaskId) => {
			const subTask = state.entities[subTaskId];

			return subTask ? { ...subTask, parentId } : null;
		}).filter(Boolean);

		// Prepare old and new states for deleted subtasks
		const oldDeletedSubTaskStates = deletedSubTasks.map((subTaskId) => state.entities[subTaskId]);
		const newDeletedSubTaskStates = deletedSubTasks.map((subTaskId) => {
			const subTask = state.entities[subTaskId];

			return subTask ? { ...subTask, parentId: 0 } : null;
		});

		// Combine old and new states
		const oldTaskStates = [...oldNewSubTaskStates, ...oldDeletedSubTaskStates];
		const newTaskStates = [...newNewSubTaskStates, ...newDeletedSubTaskStates];
		// Update the state for all subtasks
		upsertMany(state, action, oldTaskStates, newTaskStates);
	};

	const updateSubTasksFulfilled = (state, action) => {
		const { requestId, arg } = action.meta;
		const { status, data } = action.payload;
		const { updatedNewSubTasks = [], updatedDeletedSubTasks = [] } = data;
		const { newSubTasks, deletedSubTasks } = arg;
		const {
			items: updatedNewSubTasksItems = [],
		} = updatedNewSubTasks;

		const updatedNewSubTasksIds = updatedNewSubTasksItems.map((task) => task.id);

		const newSubTasksIdentical = isEqual(new Set(newSubTasks), new Set(updatedNewSubTasksIds));
		const deletedSubTasksIdentical = isEqual(new Set(deletedSubTasks), new Set(updatedDeletedSubTasks));

		const existingTasks = deletedSubTasks.map((taskId) => state.entities[taskId]);

		// eslint-disable-next-line init-declarations
		let preparedTasks;

		if (status === 'success' && newSubTasksIdentical && deletedSubTasksIdentical)
		{
			preparedTasks = onUpdateSubTasksActionSuccess(requestId, existingTasks);
		}
		else
		{
			const tasksToRevert = existingTasks.filter(
				(existingTask) => ![...updatedNewSubTasks, ...updatedDeletedSubTasks].includes(existingTask.id),
			);

			preparedTasks = onUpdateSubTasksActionError(requestId, tasksToRevert);
		}

		const updates = preparedTasks.map((preparedTask, index) => {
			if (!isEqual(existingTasks[index], preparedTask))
			{
				return { id: existingTasks[index].id, changes: preparedTask };
			}

			return null;
		}).filter(Boolean);

		tasksAdapter.upsertMany(state, [...updates, ...updatedNewSubTasksItems]);
	};

	const onUpdateSubTasksActionError = (requestId, existingTasks) => {
		return existingTasks.map((existingTask) => {
			const sourceFields = FieldChangeRegistry.getChangedFields(requestId, existingTask.id);

			FieldChangeRegistry.updateChangedFieldsAfterRequest(requestId, existingTask.id, sourceFields);
			unregisterRegistryChanges(requestId);

			return {
				...existingTask,
				...FieldChangeRegistry.removeChangedFields(existingTask.id, sourceFields),
				isConsideredForCounterChange: false,
			};
		});
	};

	const onUpdateSubTasksActionSuccess = (requestId, existingTasks) => {
		unregisterRegistryChanges(requestId);

		return existingTasks.map((existingTask) => {
			const sourceFields = FieldChangeRegistry.getChangedFields(requestId, existingTask.id);
			FieldChangeRegistry.updateChangedFieldsAfterRequest(requestId, existingTask.id, sourceFields);

			const preparedTask = TaskModel.prepareReduxTaskFromServerTask(existingTask);

			return {
				...existingTask,
				...FieldChangeRegistry.removeChangedFields(existingTask.id, sourceFields),
				...preparedTask,
				isConsideredForCounterChange: false,
			};
		});
	};

	const updateRelatedTasksPending = (state, action) => {
		const { taskId, relatedTasks } = action.meta.arg;

		const oldTaskState = state.entities[taskId];
		if (oldTaskState)
		{
			const newTaskState = {
				...oldTaskState,
				relatedTasks: relatedTasks.map((relatedTaskId) => Number(relatedTaskId)),
			};

			upsertOne(state, action, oldTaskState, newTaskState);
		}
	};

	const updateRelatedTasksFulfilled = (state, action) => {
		const { requestId, arg } = action.meta;
		const { status, data } = action.payload;
		const { updatedNewRelatedTasks = [], updatedDeletedRelatedTasks = [] } = data;
		const { taskId, newRelatedTasks, deletedRelatedTasks } = arg;

		const {
			items: updatedNewRelatedTasksItems = [],
		} = updatedNewRelatedTasks;

		const updatedNewRelatedTasksIds = updatedNewRelatedTasksItems.map((task) => task.id);

		const newRelatedTasksIdentical = isEqual(new Set(newRelatedTasks), new Set(updatedNewRelatedTasksIds));
		const deletedRelatedTasksIdentical = isEqual(new Set(deletedRelatedTasks), new Set(updatedDeletedRelatedTasks));

		if (status === 'success' && newRelatedTasksIdentical && deletedRelatedTasksIdentical)
		{
			unregisterRegistryChanges(requestId);
			// add new tasks to the state and update existing ones
			tasksAdapter.upsertMany(state, updatedNewRelatedTasksItems);
		}
		else
		{
			const existingTask = state.entities[taskId];
			const sourceFields = FieldChangeRegistry.getChangedFields(requestId, existingTask.id);

			FieldChangeRegistry.updateChangedFieldsAfterRequest(requestId, existingTask.id, sourceFields);
			unregisterRegistryChanges(requestId);

			let relatedTasks = [...existingTask.relatedTasks];

			if (deletedRelatedTasks.length > 0 && updatedDeletedRelatedTasks.length === 0)
			{
				relatedTasks.push(...deletedRelatedTasks);
			}

			if (newRelatedTasks.length > 0 && updatedNewRelatedTasks.length === 0)
			{
				relatedTasks = relatedTasks.filter((id) => !newRelatedTasks.includes(id));
			}

			const preparedTask = {
				...existingTask,
				...FieldChangeRegistry.removeChangedFields(existingTask.id, sourceFields),
				relatedTasks, // revert related tasks
				isConsideredForCounterChange: false,
			};

			if (!isEqual(existingTask, preparedTask))
			{
				tasksAdapter.upsertMany(state, [
					...updatedNewRelatedTasksItems,
					preparedTask,
				]);
			}
		}
	};

	/**
	 * @param {string} requestId
	 */
	const unregisterRegistryChanges = (requestId) => {
		FieldChangeRegistry.unregisterCounterChange(requestId);
		FieldChangeRegistry.unregisterFieldsChange(requestId);
	};

	const onCommonActionSuccess = (requestId, existingTask, data) => {
		unregisterRegistryChanges(requestId);

		return TaskModel.prepareReduxTaskFromServerTask(data.task, existingTask);
	};

	const onCommonActionError = (requestId, existingTask) => {
		const sourceFields = FieldChangeRegistry.getChangedFields(requestId, existingTask.id);

		FieldChangeRegistry.updateChangedFieldsAfterRequest(requestId, existingTask.id, sourceFields);
		unregisterRegistryChanges(requestId);

		return {
			...existingTask,
			...FieldChangeRegistry.removeChangedFields(existingTask.id, sourceFields),
			isConsideredForCounterChange: false,
		};
	};

	const onCommonActionFulfilled = (state, action) => {
		const { requestId, arg } = action.meta;
		const { status, data } = action.payload;
		const { taskId } = arg;

		const existingTask = state.entities[taskId];
		const preparedTask = (
			status === 'success'
				? onCommonActionSuccess(requestId, existingTask, data)
				: onCommonActionError(requestId, existingTask)
		);

		if (!isEqual(existingTask, preparedTask))
		{
			tasksAdapter.updateOne(state, { id: taskId, changes: preparedTask });
		}
	};

	const onCreateError = (requestId, existingTask, errors) => ({
		...onCommonActionError(requestId, existingTask),
		isCreationErrorExist: true,
		creationErrorText: (Type.isArrayFilled(errors) ? errors[0].message : ''),
	});

	const createFulfilled = (state, action) => {
		const { requestId, arg } = action.meta;
		const { status, data, errors } = action.payload;
		const { taskId } = arg;

		const existingTask = state.entities[taskId];
		const preparedTask = (
			status === 'success'
				? onCommonActionSuccess(requestId, existingTask, data)
				: onCreateError(requestId, existingTask, errors)
		);

		if (!isEqual(existingTask, preparedTask))
		{
			tasksAdapter.updateOne(state, { id: taskId, changes: preparedTask });
		}
	};

	const updateFulfilled = onCommonActionFulfilled;

	const updateDeadlineFulfilled = onCommonActionFulfilled;

	const delegateFulfilled = onCommonActionFulfilled;

	const followFulfilled = onCommonActionFulfilled;

	const unfollowFulfilled = onCommonActionFulfilled;

	const startTimerFulfilled = onCommonActionFulfilled;

	const pauseTimerFulfilled = onCommonActionFulfilled;

	const startFulfilled = onCommonActionFulfilled;

	const takeFulfilled = onCommonActionFulfilled;

	const pauseFulfilled = onCommonActionFulfilled;

	const completeFulfilled = onCommonActionFulfilled;

	const renewFulfilled = onCommonActionFulfilled;

	const deferFulfilled = onCommonActionFulfilled;

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

	const taskResultFetchedFulfilled = (state, action) => {
		const { taskId } = action.meta.arg;
		const { results } = action.payload.data;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			resultsCount: results.length,
		});
	};

	const taskResultCreatedFulfilled = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			resultsCount: task.resultsCount + 1,
		});
	};

	const taskResultRemovedFulfilled = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			resultsCount: Math.max(task.resultsCount - 1, 0),
		});
	};

	const updateTaskStagePending = (state, action) => {
		const { taskId, view } = action.meta.arg;
		const {
			stage,
		} = action.meta;

		const task = state.entities[taskId];
		if (task && stage && view === Views.DEADLINE)
		{
			const oldTaskState = state.entities[taskId];
			const newTaskState = prepareUpdateDeadlineNewState(oldTaskState, stage.deadline);

			upsertOne(state, action, oldTaskState, newTaskState);
		}
	};

	const updateTaskStageFulfilled = (state, action) => {
		const { status, data } = action.payload;
		const { taskId, view } = action.meta.arg;
		const {
			requestId,
		} = action.meta;

		if (view === Views.DEADLINE)
		{
			if (data === true && status === 'success')
			{
				unregisterRegistryChanges(requestId);
			}
			else
			{
				const preparedTask = onCommonActionError(requestId, state.entities[taskId]);
				tasksAdapter.upsertOne(state, preparedTask);
			}
		}
	};

	const updateTaskStageRejected = (state, action) => {
		const { taskId } = action.meta.arg;
		const {
			requestId,
		} = action.meta;

		const preparedTask = onCommonActionError(requestId, state.entities[taskId]);
		tasksAdapter.upsertOne(state, preparedTask);
	};

	module.exports = {
		prepareUpdateDeadlineNewState,
		prepareDelegateNewState,
		prepareFollowNewState,
		prepareUnfollowNewState,
		prepareStartTimerNewState,
		preparePauseTimerNewState,
		prepareStartNewState,
		preparePauseNewState,
		prepareCompleteNewState,
		prepareRenewNewState,
		prepareDeferNewState,
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
		onCommonActionSuccess,
		onCommonActionError,

		createPending,
		createFulfilled,
		updatePending,
		updateFulfilled,
		updateDeadlinePending,
		updateDeadlineFulfilled,
		delegatePending,
		delegateFulfilled,
		followPending,
		followFulfilled,
		unfollowPending,
		unfollowFulfilled,
		startTimerPending,
		startTimerFulfilled,
		pauseTimerPending,
		pauseTimerFulfilled,
		startPending,
		startFulfilled,
		takePending,
		takeFulfilled,
		pausePending,
		pauseFulfilled,
		completePending,
		completeFulfilled,
		renewPending,
		renewFulfilled,
		deferPending,
		deferFulfilled,
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
		taskResultFetchedFulfilled,
		taskResultCreatedFulfilled,
		taskResultRemovedFulfilled,
		updateSubTasksPending,
		updateSubTasksFulfilled,
		updateRelatedTasksPending,
		updateRelatedTasksFulfilled,
		updateTaskStagePending,
		updateTaskStageFulfilled,
		updateTaskStageRejected,
	};
});
