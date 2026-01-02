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

		inAppUrl.register('/projects/', (params, { context }) => {
			const { title } = context;

			PageManager.openComponent('JSStackComponent', {
				componentCode: 'tasks:tasks.project.list',
				// eslint-disable-next-line no-undef
				scriptPath: availableComponents['tasks:tasks.project.list'].publicUrl,
				params: {
					SITE_ID: env.siteId,
					SITE_DIR: env.siteDir,
					USER_ID: env.userId,
					MODE: 'tasks_project',
					NAVIGATION_TITLE: title,
				},
				rootWidget: {
					name: 'tasks.list',
					settings: {
						objectName: 'list',
						title,
						useSearch: true,
						useLargeTitleMode: true,
						emptyListMode: true,
					},
				},
			});
		}).name('tasks:projects');

		inAppUrl.register('/flow/', (params, { context }) => {
			const { title } = context;

			PageManager.openComponent('JSStackComponent', {
				componentCode: 'tasks:tasks.flow.list',
				// eslint-disable-next-line no-undef
				scriptPath: availableComponents['tasks:tasks.flow.list'].publicUrl,
				params: {
					NAVIGATION_TITLE: title,
				},
				rootWidget: {
					name: 'layout',
					settings: {
						objectName: 'layout',
						title,
						useSearch: true,
						useLargeTitleMode: true,
						emptyListMode: true,
					},
				},
			});
		}).name('tasks:flow');
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
