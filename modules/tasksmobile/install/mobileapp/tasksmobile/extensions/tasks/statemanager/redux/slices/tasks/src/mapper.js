/**
 * @module tasks/statemanager/redux/slices/tasks/mapper
 */
jn.define('tasks/statemanager/redux/slices/tasks/mapper', (require, exports, module) => {
	const { usersSelector } = require('statemanager/redux/slices/users');
	const { selectGroupById } = require('tasks/statemanager/redux/slices/groups');
	const store = require('statemanager/redux/store');

	/**
	 * @param {TaskReduxModel} task
	 * @returns {object}
	 */
	const mapStateToTaskModel = (task) => {
		return {
			...task,
			temporaryId: task.guid,
			title: task.name,
			parsedDescription: task.parsedDescription,
			group: selectGroupById(store.getState(), task.groupId),
			creator: mapUser(usersSelector.selectById(store.getState(), task.creator)),
			responsible: mapUser(usersSelector.selectById(store.getState(), task.responsible)),
			accomplicesData: mapUsersCollection(task.accomplices),
			auditorsData: mapUsersCollection(task.auditors),
			action: task.actionsOld,
			isPinned: task.isPinned ? 'Y' : 'N',
			isMuted: task.isMuted ? 'Y' : 'N',
			taskRequireResult: task.isResultRequired ? 'Y' : 'N',
			taskHasResult: task.isResultExists ? 'Y' : 'N',
			taskHasOpenResult: task.isOpenResultExists ? 'Y' : 'N',
			matchWorkTime: task.isMatchWorkTime ? 'Y' : 'N',
			allowChangeDeadline: task.allowChangeDeadline ? 'Y' : 'N',
			taskControl: task.allowTaskControl ? 'Y' : 'N',
			allowTimeTracking: task.allowTimeTracking ? 'Y' : 'N',
			timerIsRunningForCurrentUser: task.isTimerRunningForCurrentUser ? 'Y' : 'N',
			deadline: task.deadline ? String(new Date(task.deadline * 1000)) : null,
			activityDate: task.activityDate ? String(new Date(task.activityDate * 1000)) : null,
			startDatePlan: task.startDatePlan ? String(new Date(task.startDatePlan * 1000)) : null,
			endDatePlan: task.endDatePlan ? String(new Date(task.endDatePlan * 1000)) : null,
		};
	};

	const mapUsersCollection = (userIds = []) => Object.fromEntries(
		userIds
			.map((userId) => usersSelector.selectById(store.getState(), userId))
			.filter(Boolean)
			.map((user) => [user.id, mapUser(user)]),
	);

	// eslint-disable-next-line consistent-return
	const mapUser = (user) => {
		if (user)
		{
			return {
				id: user.id,
				name: user.fullName,
				icon: user.avatarSize100,
				link: user.link,
				workPosition: user.workPosition,
			};
		}
	};

	module.exports = { mapStateToTaskModel };
});
