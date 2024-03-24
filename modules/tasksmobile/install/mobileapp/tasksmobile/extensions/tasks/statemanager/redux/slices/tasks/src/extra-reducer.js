/**
 * @module tasks/statemanager/redux/slices/tasks/extra-reducer
 */
jn.define('tasks/statemanager/redux/slices/tasks/extra-reducer', (require, exports, module) => {
	const { TaskFilter } = require('tasks/filter/task');
	const { tasksAdapter } = require('tasks/statemanager/redux/slices/tasks/meta');
	const { TaskModel } = require('tasks/statemanager/redux/slices/tasks/model/task');
	const { TaskStatus } = require('tasks/enum');
	const { selectIsExpired } = require('tasks/statemanager/redux/slices/tasks/selector');
	const { isEqual } = require('utils/object');

	const updateDeadlinePending = (state, action) => {
		const { taskId, deadline } = action.meta.arg;
		const task = state.entities[taskId];
		const newDeadline = Math.ceil(deadline / 1000);

		tasksAdapter.upsertOne(state, {
			...task,
			isExpired: selectIsExpired({
				...task,
				deadline: newDeadline,
			}),
			isConsideredForCounterChange: true,
			deadline: newDeadline,
			activityDate: Math.ceil(Date.now() / 1000),
		});
	};

	const delegatePending = (state, action) => {
		const { taskId, userId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			isConsideredForCounterChange: true,
			responsible: Number(userId),
			activityDate: Math.ceil(Date.now() / 1000),
		});
	};

	const unfollowPending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			isConsideredForCounterChange: true,
			auditors: task.auditors.filter((userId) => userId !== Number(env.userId)),
		});
	};

	const startTimerPending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			isTimerRunningForCurrentUser: true,
		});
	};

	const pauseTimerPending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			isTimerRunningForCurrentUser: false,
		});
	};

	const startPending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			status: TaskStatus.IN_PROGRESS,
			canStart: false,
			canPause: true,
		});
	};

	const pausePending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			status: TaskStatus.PENDING,
			canStart: true,
			canPause: false,
		});
	};

	const completePending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		if (!task.isResultRequired || task.isOpenResultExists)
		{
			tasksAdapter.upsertOne(state, {
				...task,
				isConsideredForCounterChange: true,
				status: TaskStatus.COMPLETED,
				activityDate: Math.ceil(Date.now() / 1000),
				canUseTimer: false,
				canStart: false,
				canPause: false,
				canRenew: true,
				canComplete: false,
			});
		}
	};

	const renewPending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			isConsideredForCounterChange: true,
			status: TaskStatus.PENDING,
			activityDate: Math.ceil(Date.now() / 1000),
			canStart: true,
			canRenew: false,
			canComplete: true,
		});
	};

	const approvePending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			status: TaskStatus.COMPLETED,
			activityDate: Math.ceil(Date.now() / 1000),
			canApprove: false,
			canDisapprove: false,
			canRenew: true,
		});
	};

	const disapprovePending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			isConsideredForCounterChange: true,
			status: TaskStatus.PENDING,
			activityDate: Math.ceil(Date.now() / 1000),
			canStart: true,
			canApprove: false,
			canDisapprove: false,
			canComplete: true,
		});
	};

	const pingPending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			activityDate: Math.ceil(Date.now() / 1000),
		});
	};

	const pinPending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			isPinned: true,
		});
	};

	const unpinPending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			isPinned: false,
		});
	};

	const mutePending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			isConsideredForCounterChange: true,
			isMuted: true,
		});
	};

	const unmutePending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			isConsideredForCounterChange: true,
			isMuted: false,
		});
	};

	const addToFavoritesPending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			isInFavorites: true,
		});
	};

	const removeFromFavoritesPending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			isInFavorites: false,
		});
	};

	const removePending = (state, action) => {
		const { taskId } = action.meta.arg;

		tasksAdapter.removeOne(state, taskId);
	};

	const readPending = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];

		tasksAdapter.upsertOne(state, {
			...task,
			isConsideredForCounterChange: true,
			newCommentsCount: 0,
		});
	};

	const readAllForRolePending = (state, action) => {
		const { groupId, role } = action.meta.arg.fields;
		const userId = Number(env.userId);
		let tasksToRead = Object.values(state.entities);

		if (groupId)
		{
			tasksToRead = tasksToRead.filter((task) => task.groupId === groupId);
		}
		tasksToRead.filter((task) => {
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

		tasksAdapter.updateMany(
			state,
			tasksToRead.map((task) => ({
				id: task.id,
				changes: {
					newCommentsCount: 0,
				},
			})),
		);
	};

	const readAllForProjectPending = (state, action) => {
		const { groupId } = action.meta.arg.fields;
		const tasksToRead = state.entities.filter((task) => task.groupId === groupId);

		tasksAdapter.updateMany(
			state,
			tasksToRead.map((task) => ({
				id: task.id,
				changes: {
					newCommentsCount: 0,
				},
			})),
		);
	};

	const upsertTaskFromRequest = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];
		const preparedTask = TaskModel.prepareReduxTaskFromServerTask(action.payload.data.task, task);

		if (!isEqual(task, preparedTask))
		{
			tasksAdapter.upsertOne(state, preparedTask);
		}
	};

	const updateDeadlineFulfilled = upsertTaskFromRequest;

	const delegateFulfilled = upsertTaskFromRequest;

	const unfollowFulfilled = upsertTaskFromRequest;

	const startTimerFulfilled = upsertTaskFromRequest;

	const pauseTimerFulfilled = upsertTaskFromRequest;

	const startFulfilled = upsertTaskFromRequest;

	const pauseFulfilled = upsertTaskFromRequest;

	const completeFulfilled = upsertTaskFromRequest;

	const renewFulfilled = upsertTaskFromRequest;

	const approveFulfilled = upsertTaskFromRequest;

	const disapproveFulfilled = upsertTaskFromRequest;

	const pinFulfilled = upsertTaskFromRequest;

	const unpinFulfilled = upsertTaskFromRequest;

	const muteFulfilled = upsertTaskFromRequest;

	const unmuteFulfilled = upsertTaskFromRequest;

	const readFulfilled = (state, action) => {
		const { taskId } = action.meta.arg;
		const task = state.entities[taskId];
		tasksAdapter.upsertOne(state, {
			...task,
			isConsideredForCounterChange: false,
		});
	};

	module.exports = {
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
		readAllForProjectPending,
	};
});
