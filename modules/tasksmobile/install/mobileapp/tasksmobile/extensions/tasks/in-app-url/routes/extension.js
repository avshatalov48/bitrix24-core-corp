/**
 * @module tasks/in-app-url/routes
 */
jn.define('tasks/in-app-url/routes', (require, exports, module) => {
	const { Entry } = require('tasks/entry');
	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = (inAppUrl) => {
		inAppUrl.register(
			'/company/personal/user/:userId/tasks/task/view/:taskId/',
			({ taskId }) => Entry.openTask({ taskId }, {}),
		).name('tasks:task:openForUser');

		inAppUrl.register(
			'/workgroups/group/:groupId/tasks/task/view/:taskId/',
			({ taskId }) => Entry.openTask({ taskId }, {}),
		).name('tasks:task:openForGroup');

		inAppUrl.register(
			'/company/personal/user/:userId/tasks/effective/',
			({ userId }) => Entry.openEfficiency({ userId, groupId: 0 }),
		).name('tasks:efficiency:open');
	};
});
