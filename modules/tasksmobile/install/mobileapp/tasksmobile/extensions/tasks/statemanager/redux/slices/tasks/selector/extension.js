/**
 * @module tasks/statemanager/redux/slices/tasks/selector
 */
jn.define('tasks/statemanager/redux/slices/tasks/selector', (require, exports, module) => {
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');
	const { sliceName, tasksAdapter } = require('tasks/statemanager/redux/slices/tasks/meta');
	const { TaskStatus, TaskCounter, TimerState } = require('tasks/enum');

	const {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,
	} = tasksAdapter.getSelectors((state) => state[sliceName]);

	const selectByTaskIdOrGuid = createDraftSafeSelector(
		(state, taskId) => selectById(state, taskId),
		(state, taskId) => {
			return Object.values(selectEntities(state)).find((entity) => {
				return entity.guid && entity.guid === taskId;
			});
		},
		(taskById, taskByGuid) => (taskById || taskByGuid),
	);

	const selectIsCreator = createDraftSafeSelector(
		(task) => task.creator,
		(task, userId = env.userId) => Number(userId),
		(creator, userId) => (creator === userId),
	);

	const selectIsResponsible = createDraftSafeSelector(
		(task) => task.responsible,
		(task, userId = env.userId) => Number(userId),
		(responsible, userId) => (responsible === userId),
	);

	const selectIsPureCreator = createDraftSafeSelector(
		selectIsCreator,
		selectIsResponsible,
		(isCreator, isResponsible) => (isCreator && !isResponsible),
	);

	const selectIsAccomplice = createDraftSafeSelector(
		(task) => task.accomplices,
		(task, userId = env.userId) => Number(userId),
		(accomplices, userId) => accomplices.includes(userId),
	);

	const selectIsAuditor = createDraftSafeSelector(
		(task) => task.auditors,
		(task, userId = env.userId) => Number(userId),
		(auditors, userId) => auditors.includes(userId),
	);

	const selectIsMember = createDraftSafeSelector(
		selectIsCreator,
		selectIsResponsible,
		selectIsAccomplice,
		selectIsAuditor,
		(isCreator, isResponsible, isAccomplice, isAuditor) => {
			return isCreator || isResponsible || isAccomplice || isAuditor;
		},
	);

	const selectIsCreating = createDraftSafeSelector(
		(task) => task.id,
		(task) => task.guid,
		(id, guid) => (id === guid),
	);

	const selectHasChecklist = createDraftSafeSelector(
		(task) => task.checklist?.completed || 0,
		(task) => task.checklist?.uncompleted || 0,
		(completed, uncompleted) => (completed + uncompleted) > 0,
	);

	const selectIsSubTasksLoaded = createDraftSafeSelector(
		(state, taskId) => selectById(state, taskId),
		(task) => Array.isArray(task?.subTasks),
	);

	const selectIsRelatedTasksLoaded = createDraftSafeSelector(
		(state, taskId) => selectById(state, taskId),
		(task) => Array.isArray(task?.relatedTasks),
	);

	const selectSubTasksById = createDraftSafeSelector(
		(state, taskId) => taskId,
		(state) => selectAll(state),
		(taskId, allTasks) => {
			return allTasks
				.filter((task) => task.parentId === taskId)
				.map((task) => ({
					id: task.id,
					title: task.name,
					customData: {
						responsible: task.responsible,
						deadline: task.deadline,
						isCompleted: selectIsCompleted(task),
						status: task.status,
					},
				}))
				.sort((a, b) => a.customData.isCompleted - b.customData.isCompleted);
		},
	);

	const selectSubTasksIdsByTaskId = createDraftSafeSelector(
		(state, taskId) => taskId,
		(state) => selectAll(state),
		(taskId, allTasks) => {
			return allTasks
				.filter((task) => task.parentId === taskId)
				.map((task) => task.id);
		},
	);

	const selectRelatedTasksById = createDraftSafeSelector(
		(state, taskId) => selectById(state, taskId),
		(state) => state,
		(task, state) => {
			if (task && Array.isArray(task.relatedTasks))
			{
				return task.relatedTasks.map((relatedTaskId) => {
					const relatedTask = selectById(state, relatedTaskId);
					if (!relatedTask)
					{
						return {
							customData: {
								isLoading: true,
								isCompleted: false,
							},
						};
					}

					return {
						id: relatedTask.id,
						title: relatedTask.name,
						customData: {
							responsible: relatedTask.responsible,
							deadline: relatedTask.deadline,
							isCompleted: selectIsCompleted(relatedTask),
							status: relatedTask.status,
						},
					};
				})
					.sort((a, b) => a.customData.isCompleted - b.customData.isCompleted);
			}

			return [];
		},
	);

	const selectIsSupposedlyCompleted = createDraftSafeSelector(
		(task) => task.status,
		(status) => (status === TaskStatus.SUPPOSEDLY_COMPLETED),
	);

	const selectIsCompleted = createDraftSafeSelector(
		(task) => task.status,
		selectIsCreator,
		(status, isCreator) => {
			return (
				status === TaskStatus.COMPLETED
				|| (status === TaskStatus.SUPPOSEDLY_COMPLETED && !isCreator)
			);
		},
	);

	const selectInProgress = createDraftSafeSelector(
		(task) => task.status,
		(status) => (status === TaskStatus.IN_PROGRESS),
	);

	const selectIsDeferred = createDraftSafeSelector(
		(task) => task.status,
		(status) => (status === TaskStatus.DEFERRED),
	);

	const selectMarkedAsRemoved = createDraftSafeSelector(
		(state) => selectAll(state),
		(allTasks) => allTasks.filter((task) => task.isRemoved === true),
	);

	const selectWithCreationError = createDraftSafeSelector(
		(state) => selectAll(state),
		(allTasks) => allTasks.filter((task) => task.isCreationErrorExist),
	);

	const selectIsExpiredSoon = createDraftSafeSelector(
		selectIsCompleted,
		selectIsDeferred,
		() => Date.now(),
		(task) => task.deadline,
		(isCompleted, isDeferred, currentTime, deadline) => {
			return Boolean(
				!isCompleted
				&& !isDeferred
				&& deadline
				&& (deadline * 1000 - currentTime > 0)
				&& (deadline * 1000 - currentTime < 86_400_000),
			);
		},
	);

	const selectIsExpired = createDraftSafeSelector(
		selectIsCompleted,
		selectIsDeferred,
		() => Date.now(),
		(task) => task.deadline,
		(isCompleted, isDeferred, currentTime, deadline) => {
			return Boolean(!isCompleted && !isDeferred && deadline && (deadline * 1000 < currentTime));
		},
	);

	const selectWillExpire = createDraftSafeSelector(
		selectIsCompleted,
		selectIsDeferred,
		(task) => task.isExpired,
		(task) => task.deadline,
		(isCompleted, isDeferred, isExpired, deadline) => {
			return Boolean(!isCompleted && !isDeferred && !isExpired && deadline);
		},
	);

	const selectCounter = createDraftSafeSelector(
		(task) => task.isExpired,
		(task) => task.newCommentsCount,
		(task) => task.isMuted,
		selectIsMember,
		(isExpired, newCommentsCount, isMuted, isMember) => {
			const counter = {
				isDouble: (isExpired && newCommentsCount > 0),
				value: newCommentsCount + Number(isExpired),
				type: TaskCounter.GRAY,
			};

			if (isMuted || !isMember)
			{
				counter.isDouble = false;
			}
			else
			{
				counter.type = isExpired ? TaskCounter.ALERT : TaskCounter.SUCCESS;
			}

			return counter;
		},
	);

	const selectDatePlan = createDraftSafeSelector(
		(state, taskId) => selectByTaskIdOrGuid(state, taskId),
		(task) => {
			if (!task)
			{
				return {};
			}

			return {
				startDatePlan: task.startDatePlan,
				endDatePlan: task.endDatePlan,
			};
		},
	);

	const selectExtraSettings = createDraftSafeSelector(
		(state, taskId) => selectByTaskIdOrGuid(state, taskId),
		(task) => {
			if (!task)
			{
				return {};
			}

			return {
				isMatchWorkTime: task.isMatchWorkTime,
				allowChangeDeadline: task.allowChangeDeadline,
				isResultRequired: task.isResultRequired,
				allowTaskControl: task.allowTaskControl,
			};
		},
	);

	const selectActions = createDraftSafeSelector(
		(task) => task,
		selectIsAuditor,
		(task, isAuditor) => ({
			read: task.canRead,
			update: task.canUpdate,
			updateDeadline: task.canUpdateDeadline,
			updateCreator: task.canUpdateCreator,
			updateResponsible: task.canUpdateResponsible,
			updateAccomplices: task.canUpdateAccomplices,
			updateProject: task.canUpdate && !task.flowId,
			delegate: task.canDelegate,
			take: task.canTake,
			updateMark: task.canUpdateMark,
			updateReminder: task.canUpdateReminder,
			updateElapsedTime: task.canUpdateElapsedTime,
			addChecklist: task.canAddChecklist,
			updateChecklist: task.canUpdateChecklist,
			remove: task.canRemove,
			startTimer: (task.canUseTimer && !task.isTimerRunningForCurrentUser),
			pauseTimer: (task.canUseTimer && task.isTimerRunningForCurrentUser),
			start: task.canStart,
			pause: task.canPause,
			complete: (task.canComplete && !task.canApprove),
			renew: task.canRenew,
			approve: task.canApprove,
			disapprove: task.canDisapprove,
			defer: task.canDefer,
			follow: !isAuditor,
			unfollow: isAuditor,
			pin: !task.isPinned,
			unpin: task.isPinned,
			mute: !task.isMuted,
			unmute: task.isMuted,
			favoriteAdd: !task.isInFavorites,
			favoriteDelete: task.isInFavorites,
			ping: true,
			share: true,
			copy: true,
			copyId: true,
			extraSettings: task.canUpdate,
		}),
	);

	const selectTimerState = createDraftSafeSelector(
		(task) => task.timeEstimate > 0 && task.timeElapsed > task.timeEstimate,
		(task) => task.isTimerRunningForCurrentUser,
		(isOverdue, isTimerRunningForCurrentUser) => {
			if (isOverdue)
			{
				return TimerState.OVERDUE;
			}

			return isTimerRunningForCurrentUser ? TimerState.RUNNING : TimerState.PAUSED;
		},
	);

	module.exports = {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,
		selectByTaskIdOrGuid,

		selectIsCreator,
		selectIsResponsible,
		selectIsPureCreator,
		selectIsAccomplice,
		selectIsAuditor,
		selectIsMember,

		selectIsCreating,
		selectIsSupposedlyCompleted,
		selectIsCompleted,
		selectInProgress,
		selectIsDeferred,
		selectIsExpiredSoon,
		selectIsExpired,
		selectMarkedAsRemoved,
		selectWillExpire,
		selectCounter,
		selectHasChecklist,
		selectIsSubTasksLoaded,
		selectIsRelatedTasksLoaded,
		selectSubTasksById,
		selectSubTasksIdsByTaskId,
		selectRelatedTasksById,
		selectWithCreationError,

		selectDatePlan,
		selectExtraSettings,

		selectActions,

		selectTimerState,
	};
});
