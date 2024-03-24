/**
 * @module tasks/statemanager/redux/slices/tasks/selector
 */
jn.define('tasks/statemanager/redux/slices/tasks/selector', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');
	const { sliceName, tasksAdapter } = require('tasks/statemanager/redux/slices/tasks/meta');
	const { TaskStatus } = require('tasks/enum');

	const {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,
	} = tasksAdapter.getSelectors((state) => state[sliceName]);

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

	const selectIsDeferred = createDraftSafeSelector(
		(task) => task.status,
		(status) => (status === TaskStatus.DEFERRED),
	);

	const selectIsExpired = createDraftSafeSelector(
		selectIsCompleted,
		selectIsDeferred,
		() => Date.now(),
		(task) => task.deadline,
		(isCompleted, isDeferred, currentTime, deadline) => {
			return (!isCompleted && !isDeferred && deadline && (deadline * 1000 < currentTime));
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
				color: AppTheme.colors.base4,
			};

			if (isMuted || !isMember)
			{
				counter.isDouble = false;
			}
			else
			{
				counter.color = (isExpired ? AppTheme.colors.accentMainAlert : AppTheme.colors.accentMainSuccess);
			}

			return counter;
		},
	);

	const selectActions = createDraftSafeSelector(
		(task) => task,
		selectIsAuditor,
		(task, isAuditor) => ({
			updateDeadline: task.canUpdateDeadline,
			delegate: task.canDelegate,
			remove: task.canRemove,
			startTimer: task.canUseTimer && !task.isTimerRunningForCurrentUser,
			pauseTimer: task.canUseTimer && task.isTimerRunningForCurrentUser,
			start: task.canStart,
			pause: task.canPause,
			complete: task.canComplete && !task.canApprove,
			renew: task.canRenew,
			approve: task.canApprove,
			disapprove: task.canDisapprove,
			defer: task.canDefer,
			unfollow: isAuditor,
			pin: !task.isPinned,
			unpin: task.isPinned,
			mute: !task.isMuted,
			unmute: task.isMuted,
			favoriteAdd: !task.isInFavorites,
			favoriteDelete: task.isInFavorites,
			read: true,
			ping: true,
			addTask: true,
			addSubTask: true,
			share: true,
		}),
	);

	module.exports = {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,

		selectIsCreator,
		selectIsResponsible,
		selectIsAccomplice,
		selectIsAuditor,
		selectIsMember,

		selectIsCompleted,
		selectIsDeferred,
		selectIsExpired,
		selectWillExpire,
		selectCounter,

		selectActions,
	};
});
