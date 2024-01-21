/**
 * @module tasks/statemanager/redux/slices/tasks
 */
jn.define('tasks/statemanager/redux/slices/tasks', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');
	const { isOffline } = require('device/connection');

	const { ExpirationRegistry } = require('tasks/statemanager/redux/slices/tasks/expiration-registry');
	const { sliceName, tasksAdapter } = require('tasks/statemanager/redux/slices/tasks/meta');
	const { TaskModel } = require('tasks/statemanager/redux/slices/tasks/model/task');
	const { mapStateToTaskModel } = require('tasks/statemanager/redux/slices/tasks/mapper');
	const {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,
		selectIsMember,
		selectIsCreator,
		selectIsResponsible,
		selectIsAccomplice,
		selectIsAuditor,
		selectIsCompleted,
		selectIsDeferred,
		selectWillExpire,
		selectCounter,
		selectActions,
	} = require('tasks/statemanager/redux/slices/tasks/selector');
	const {
		updateDeadline,
		delegate,
		unfollow,
		startTimer,
		pauseTimer,
		start,
		pause,
		complete,
		renew,
		approve,
		disapprove,
		ping,
		pin,
		unpin,
		mute,
		unmute,
		addToFavorites,
		removeFromFavorites,
		remove,
		read,
		readAllForRole,
		readAllForProject,
	} = require('tasks/statemanager/redux/slices/tasks/thunk');
	const {
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
	} = require('tasks/statemanager/redux/slices/tasks/extra-reducer');

	const tasksSlice = createSlice({
		name: sliceName,
		initialState: tasksAdapter.getInitialState(),
		reducers: {
			tasksUpserted: (state, { payload }) => {
				const tasks = payload.map((task) => {
					return TaskModel.prepareReduxTaskFromServerTask(task, state.entities[task.id]);
				});

				tasksAdapter.upsertMany(state, tasks);
			},
			tasksAdded: (state, { payload }) => {
				const tasks = payload.map((task) => {
					return TaskModel.prepareReduxTaskFromServerTask(task, state.entities[task.id]);
				});

				tasksAdapter.addMany(state, tasks);
			},
			taskUpdatedFromOldTaskModel: (state, { payload }) => {
				const { task: oldTaskModel } = payload;
				// eslint-disable-next-line no-underscore-dangle
				const task = state.entities[oldTaskModel._id];

				tasksAdapter.upsertOne(state, TaskModel.prepareReduxTaskFromOldTaskModel(oldTaskModel, task));
			},
			taskExpired: (state, { payload }) => {
				const { taskId } = payload;
				const task = state.entities[taskId];
				tasksAdapter.upsertOne(state, {
					...task,
					isExpired: true,
					isConsideredForCounterChange: true,
				});
			},
			markAsRemoved: (state, { payload }) => {
				if (isOffline())
				{
					return;
				}

				const { taskId } = payload;
				const task = state.entities[taskId];
				tasksAdapter.upsertOne(state, {
					...task,
					isConsideredForCounterChange: true,
					isRemoved: true,
				});
			},
			unmarkAsRemoved: (state, { payload }) => {
				if (isOffline())
				{
					return;
				}

				const { taskId } = payload;
				const task = state.entities[taskId];
				tasksAdapter.upsertOne(state, {
					...task,
					isConsideredForCounterChange: true,
					isRemoved: false,
				});
			},
		},
		extraReducers: (builder) => {
			builder
				.addCase(updateDeadline.pending, updateDeadlinePending)
				.addCase(updateDeadline.fulfilled, updateDeadlineFulfilled)
				.addCase(delegate.pending, delegatePending)
				.addCase(delegate.fulfilled, delegateFulfilled)
				.addCase(unfollow.pending, unfollowPending)
				.addCase(unfollow.fulfilled, unfollowFulfilled)
				.addCase(startTimer.pending, startTimerPending)
				.addCase(startTimer.fulfilled, startTimerFulfilled)
				.addCase(pauseTimer.pending, pauseTimerPending)
				.addCase(pauseTimer.fulfilled, pauseTimerFulfilled)
				.addCase(start.pending, startPending)
				.addCase(start.fulfilled, startFulfilled)
				.addCase(pause.pending, pausePending)
				.addCase(pause.fulfilled, pauseFulfilled)
				.addCase(complete.pending, completePending)
				.addCase(complete.fulfilled, completeFulfilled)
				.addCase(renew.pending, renewPending)
				.addCase(renew.fulfilled, renewFulfilled)
				.addCase(approve.pending, approvePending)
				.addCase(approve.fulfilled, approveFulfilled)
				.addCase(disapprove.pending, disapprovePending)
				.addCase(disapprove.fulfilled, disapproveFulfilled)
				.addCase(ping.pending, pingPending)
				.addCase(pin.pending, pinPending)
				.addCase(pin.fulfilled, pinFulfilled)
				.addCase(unpin.pending, unpinPending)
				.addCase(unpin.fulfilled, unpinFulfilled)
				.addCase(mute.pending, mutePending)
				.addCase(mute.fulfilled, muteFulfilled)
				.addCase(unmute.pending, unmutePending)
				.addCase(unmute.fulfilled, unmuteFulfilled)
				.addCase(addToFavorites.pending, addToFavoritesPending)
				.addCase(removeFromFavorites.pending, removeFromFavoritesPending)
				.addCase(remove.pending, removePending)
				.addCase(read.pending, readPending)
				.addCase(read.fulfilled, readFulfilled)
				.addCase(readAllForRole.pending, readAllForRolePending)
				.addCase(readAllForProject.pending, readAllForProjectPending)
			;
		},
	});

	const { reducer: tasksReducer, actions } = tasksSlice;
	const {
		tasksUpserted,
		tasksAdded,
		taskUpdatedFromOldTaskModel,
		taskExpired,
		markAsRemoved,
		unmarkAsRemoved,
	} = actions;

	ExpirationRegistry.setReducers({ taskExpired });
	ExpirationRegistry.setSelectors({ selectWillExpire });
	ReducerRegistry.register(sliceName, tasksReducer);

	module.exports = {
		tasksReducer,
		mapStateToTaskModel,

		tasksUpserted,
		tasksAdded,
		taskUpdatedFromOldTaskModel,
		taskExpired,
		markAsRemoved,
		unmarkAsRemoved,

		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,
		selectIsMember,
		selectIsCreator,
		selectIsResponsible,
		selectIsAccomplice,
		selectIsAuditor,
		selectIsCompleted,
		selectIsDeferred,
		selectWillExpire,
		selectCounter,
		selectActions,

		updateDeadline,
		delegate,
		unfollow,
		startTimer,
		pauseTimer,
		start,
		pause,
		complete,
		renew,
		approve,
		disapprove,
		ping,
		pin,
		unpin,
		mute,
		unmute,
		addToFavorites,
		removeFromFavorites,
		remove,
		read,
		readAllForRole,
		readAllForProject,
	};
});
