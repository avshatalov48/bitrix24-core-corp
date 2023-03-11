(() => {
	const {Entry} = jn.require('tasks/entry');

	class TaskBackgroundAction
	{
		constructor()
		{
			this.bindEvents();
		}

		bindEvents()
		{
			const entry = new Entry();

			BX.addCustomEvent('taskbackground::task::open', (data, params = {}) => entry.openTask(data, params));
			BX.addCustomEvent('taskbackground::taskList::open', data => entry.openTaskList(data));
			BX.addCustomEvent('taskbackground::efficiency::open', data => entry.openEfficiency(data));
		}
	}

	return new TaskBackgroundAction();
})();