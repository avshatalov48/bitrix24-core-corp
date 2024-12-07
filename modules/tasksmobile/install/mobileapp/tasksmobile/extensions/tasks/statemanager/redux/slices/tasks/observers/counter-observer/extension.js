/**
 * @module tasks/statemanager/redux/slices/tasks/observers/counter-observer
 */
jn.define('tasks/statemanager/redux/slices/tasks/observers/counter-observer', (require, exports, module) => {
	const { isEqual } = require('utils/object');
	const { selectEntities, selectIsMember, selectCounter } = require('tasks/statemanager/redux/slices/tasks');

	const getCounterChangeValueForAdded = (added) => {
		let counterChangeValue = 0;

		added.forEach((task) => {
			if (task.isConsideredForCounterChange && !task.isMuted && selectIsMember(task))
			{
				counterChangeValue += selectCounter(task).value;
			}
		});

		return counterChangeValue;
	};

	const getCounterChangeValueForUpdated = (prevTasks, updated) => {
		let counterChangeValue = 0;

		updated.forEach((nextTask) => {
			if (!nextTask.isConsideredForCounterChange)
			{
				return;
			}

			const prevTask = prevTasks[nextTask.id];

			const prevIsMember = selectIsMember(prevTask);
			const prevCounterValue = selectCounter(prevTask).value;

			const nextIsMember = selectIsMember(nextTask);
			const nextCounterValue = selectCounter(nextTask).value;

			if (!nextTask.isMuted)
			{
				if (prevIsMember && !nextIsMember)
				{
					counterChangeValue -= prevCounterValue;
				}

				if (!prevIsMember && nextIsMember)
				{
					counterChangeValue += nextCounterValue;
				}
			}

			if (nextIsMember)
			{
				if (!prevTask.isMuted && nextTask.isMuted)
				{
					counterChangeValue -= prevCounterValue;
				}

				if (prevTask.isMuted && !nextTask.isMuted)
				{
					counterChangeValue += nextCounterValue;
				}
			}

			if (nextIsMember && !nextTask.isMuted)
			{
				if (!prevTask.isRemoved && nextTask.isRemoved)
				{
					counterChangeValue -= prevCounterValue;
				}

				if (prevTask.isRemoved && !nextTask.isRemoved)
				{
					counterChangeValue += nextCounterValue;
				}

				if (!prevTask.isExpired && nextTask.isExpired)
				{
					counterChangeValue += 1;
				}

				if (prevTask.isExpired && !nextTask.isExpired)
				{
					counterChangeValue -= 1;
				}

				if (prevTask.newCommentsCount !== nextTask.newCommentsCount)
				{
					counterChangeValue += (nextTask.newCommentsCount - prevTask.newCommentsCount);
				}
			}
		});

		return counterChangeValue;
	};

	const getCounterChangeValue = (prevTasks, nextTasks) => {
		if (nextTasks === prevTasks)
		{
			return 0;
		}

		const added = [];
		const updated = Object.values(nextTasks).filter((nextTask) => {
			const prevTask = prevTasks[nextTask.id];
			if (!prevTask)
			{
				added.push(nextTask);

				return false;
			}

			return !isEqual(prevTask, nextTask);
		});

		return getCounterChangeValueForAdded(added) + getCounterChangeValueForUpdated(prevTasks, updated);
	};

	const observeCounterChange = (store, onChange) => {
		let prevTasks = selectEntities(store.getState());

		return store.subscribe(() => {
			const nextTasks = selectEntities(store.getState());
			const counterChangeValue = getCounterChangeValue(prevTasks, nextTasks);

			if (counterChangeValue !== 0)
			{
				onChange(counterChangeValue);
			}

			prevTasks = nextTasks;
		});
	};

	module.exports = { observeCounterChange, getCounterChangeValue };
});
