(() => {

	const require = (ext) => jn.require(ext);
	const { removeTask } = require('tasks/task/remove');

	class TaskBackgroundAction
	{
		static bindEvents()
		{
			BX.addCustomEvent('taskbackground::task::open', async (data, params = {}) => {
				const { Entry } = await requireLazy('tasks:entry');

				Entry.openTask(data, params);
			});

			BX.addCustomEvent('taskbackground::taskList::open', async (data) => {
				const { Entry } = await requireLazy('tasks:entry');

				Entry.openTaskList(data);
			});

			BX.addCustomEvent('taskbackground::efficiency::open', async (data) => {
				const { Entry } = await requireLazy('tasks:entry');

				Entry.openEfficiency(data, { isBackground: true });
			});

			BX.addCustomEvent('taskbackground::removeTask', (taskId) => removeTask(taskId));
		}

		constructor()
		{
			TaskBackgroundAction.bindEvents();
		}
	}

	return new TaskBackgroundAction();
})();
