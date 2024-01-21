/**
 * @module tasks/statemanager/redux/slices/tasks/model/task/status
 */
jn.define('tasks/statemanager/redux/slices/tasks/model/task/status', (require, exports, module) => {
	module.exports = {
		TaskStatus: {
			PENDING: 2,
			IN_PROGRESS: 3,
			SUPPOSEDLY_COMPLETED: 4,
			COMPLETED: 5,
			DEFERRED: 6,
		},
	};
});
