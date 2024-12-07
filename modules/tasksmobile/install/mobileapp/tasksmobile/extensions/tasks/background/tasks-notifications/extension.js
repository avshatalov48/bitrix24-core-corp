(() => {
	const { TasksTabsOpenNotification } = jn.require('tasks/background/tasks-notifications/task-tab-open');
	const { TasksTabsOpenFromMoreNotification } = jn.require('tasks/background/tasks-notifications/task-tab-open-from-more');

	new TasksTabsOpenNotification();
	new TasksTabsOpenFromMoreNotification();
})();
