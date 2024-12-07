/**
 * @module tasks/task/remove
 */
jn.define('tasks/task/remove', (require, exports, module) => {
	const { Feature } = require('feature');
	const { Loc } = require('loc');
	const { showRemoveToast } = require('toast/remove');

	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const {
		markAsRemoved,
		unmarkAsRemoved,
		remove,
		taskRemoved,
		selectById,
	} = require('tasks/statemanager/redux/slices/tasks');

	function removeTask(taskId)
	{
		const task = selectById(store.getState(), taskId);
		if (!task)
		{
			return;
		}

		if (!Feature.isToastSupported())
		{
			dispatch(
				(task.isCreationErrorExist ? taskRemoved : remove)({ taskId }),
			);

			return;
		}

		dispatch(
			markAsRemoved({ taskId }),
		);

		showRemoveToast(
			{
				message: Loc.getMessage('M_TASKS_TASK_REMOVE_TOAST_MESSAGE'),
				offset: 86,
				onButtonTap: () => {
					dispatch(
						unmarkAsRemoved({ taskId }),
					);
				},
				onTimerOver: () => {
					dispatch(
						(task.isCreationErrorExist ? taskRemoved : remove)({ taskId }),
					);
				},
			},
		);
	}

	module.exports = { removeTask };
});
