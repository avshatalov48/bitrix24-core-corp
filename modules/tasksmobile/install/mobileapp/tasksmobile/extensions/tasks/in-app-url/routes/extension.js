/**
 * @module tasks/in-app-url/routes
 */
jn.define('tasks/in-app-url/routes', (require, exports, module) => {
	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = (inAppUrl) => {
		inAppUrl.register(
			'/company/personal/user/:userId/tasks/task/view/:taskId/',
			async ({ taskId }) => {
				const { Entry } = await requireLazy('tasks:entry');

				const analyticsLabel = getAnalyticsData();

				Entry.openTask({ taskId }, { analyticsLabel });
			},
		).name('tasks:task:openForUser');

		inAppUrl.register(
			'/workgroups/group/:groupId/tasks/task/view/:taskId/',
			async ({ taskId }) => {
				const { Entry } = await requireLazy('tasks:entry');

				const analyticsLabel = getAnalyticsData();

				Entry.openTask({ taskId }, { analyticsLabel });
			},
		).name('tasks:task:openForGroup');

		inAppUrl.register(
			'/company/personal/user/:userId/tasks/effective/',
			async ({ userId }) => {
				const { Entry } = await requireLazy('tasks:entry');

				Entry.openEfficiency({ userId, groupId: 0 });
			},
		).name('tasks:efficiency:open');
	};

	function getAnalyticsData()
	{
		let analyticsLabel = {};

		const componentName = PageManager.getNavigator().getVisible()?.type;
		if (componentName === 'im.messenger')
		{
			analyticsLabel = {
				c_section: 'chat',
				c_element: 'title_click',
			};
		}

		return analyticsLabel;
	}
});
