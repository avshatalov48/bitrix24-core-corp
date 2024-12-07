/**
 * @module tasks/statemanager/redux/slices/tasks/observers/stateful-list-observer
 */
jn.define('tasks/statemanager/redux/slices/tasks/observers/stateful-list-observer', (require, exports, module) => {
	const { isEqual } = require('utils/object');
	const { selectEntities } = require('tasks/statemanager/redux/slices/tasks/selector');

	const observeListChange = (store, onChange) => {
		let prevTasks = selectEntities(store.getState());

		return store.subscribe(() => {
			const nextTasks = selectEntities(store.getState());

			const { moved, removed, added, created } = getDiffForTasksObserver(prevTasks, nextTasks);
			if (moved.length > 0 || removed.length > 0 || added.length > 0 || created.length > 0)
			{
				onChange({ moved, removed, added, created });
			}

			prevTasks = nextTasks;
		});
	};

	/**
	 * Exported for tests
	 *
	 * @private
	 * @param {Object.<string, TaskReduxModel>} prevTasks
	 * @param {Object.<string, TaskReduxModel>} nextTasks
	 * @return {{moved: TaskReduxModel[], removed: TaskReduxModel[], added: TaskReduxModel[]}}
	 */
	const getDiffForTasksObserver = (prevTasks, nextTasks) => {
		const moved = [];
		const removed = [];
		const added = [];
		const created = [];

		if (prevTasks === nextTasks)
		{
			return { moved, removed, added, created };
		}

		// Find added or restored tasks
		Object.values(nextTasks).forEach((nextTask) => {
			if (!nextTask.isRemoved)
			{
				const prevTask = prevTasks[nextTask.id];
				if (!prevTask || prevTask.isRemoved)
				{
					added.push(nextTask);
				}
			}
		});

		// Find removed tasks
		Object.values(prevTasks).forEach((prevTask) => {
			if (!prevTask.isRemoved)
			{
				const nextTask = nextTasks[prevTask.id];
				if (!nextTask || nextTask.isRemoved)
				{
					// Add the removed task to the array, or a new task if it exists in nextTasks
					removed.push(nextTask || prevTask);
				}
			}
		});

		const processedTaskIds = new Set([...removed, ...added].map(({ id }) => id));
		Object.values(nextTasks).forEach((nextTask) => {
			const prevTask = prevTasks[nextTask.id];
			if (!prevTask || processedTaskIds.has(nextTask.id))
			{
				return;
			}

			const { isRemoved: prevIsRemoved, ...prevTaskWithoutIsRemoved } = prevTask;
			const { isRemoved: nextIsRemoved, ...nextTaskWithoutIsRemoved } = nextTask;

			if (!isEqual(prevTaskWithoutIsRemoved, nextTaskWithoutIsRemoved))
			{
				moved.push(nextTask);
			}
		});

		added.forEach((addedTask, index) => {
			if (addedTask.guid)
			{
				const removedIndex = removed.findIndex((removedTask) => removedTask.id === addedTask.guid);
				if (removedIndex !== -1)
				{
					created.push(addedTask);
					added.splice(index, 1);
					removed.splice(removedIndex, 1);
				}
			}
		});

		return { moved, removed, added, created };
	};

	module.exports = { observeListChange, getDiffForTasksObserver };
});
