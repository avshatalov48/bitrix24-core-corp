(() => {
	const { Entry } = jn.require('tasks/entry');

	class TaskBackgroundAction
	{
		static bindEvents()
		{
			BX.addCustomEvent('taskbackground::task::open', (data, params = {}) => Entry.openTask(data, params));
			BX.addCustomEvent('taskbackground::taskList::open', (data) => Entry.openTaskList(data));
			BX.addCustomEvent('taskbackground::efficiency::open', (data) => Entry.openEfficiency(data));
		}

		constructor()
		{
			TaskBackgroundAction.bindEvents();
		}
	}

	return new TaskBackgroundAction();
})();
