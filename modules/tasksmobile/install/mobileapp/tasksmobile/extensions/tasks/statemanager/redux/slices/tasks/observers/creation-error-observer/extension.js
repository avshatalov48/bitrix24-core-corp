/**
 * @module tasks/statemanager/redux/slices/tasks/observers/creation-error-observer
 */
jn.define('tasks/statemanager/redux/slices/tasks/observers/creation-error-observer', (require, exports, module) => {
	const { selectEntities } = require('tasks/statemanager/redux/slices/tasks/selector');

	const observeCreationError = (store, onChange) => {
		let prevTasks = selectEntities(store.getState());

		return store.subscribe(() => {
			const nextTasks = selectEntities(store.getState());

			const { added, removed } = getDiffForCreationErrorObserver(prevTasks, nextTasks);
			if (added.length > 0 || removed.length > 0)
			{
				onChange({ added, removed });
			}

			prevTasks = nextTasks;
		});
	};

	/**
	 * @private
	 * @param {Object.<string, TaskReduxModel>} prevTasks
	 * @param {Object.<string, TaskReduxModel>} nextTasks
	 * @return {{added: TaskReduxModel[], removed: TaskReduxModel[]}}
	 */
	const getDiffForCreationErrorObserver = (prevTasks, nextTasks) => {
		const added = [];
		const removed = [];

		Object.values(nextTasks).forEach((nextTask) => {
			const prevTask = prevTasks[nextTask.id];
			if (!prevTask)
			{
				return;
			}

			if (!prevTask.isCreationErrorExist && nextTask.isCreationErrorExist)
			{
				added.push(nextTask);
			}

			if (prevTask.isCreationErrorExist && !nextTask.isCreationErrorExist)
			{
				removed.push(nextTask);
			}
		});

		return { added, removed };
	};

	module.exports = { observeCreationError, getDiffForCreationErrorObserver };
});
