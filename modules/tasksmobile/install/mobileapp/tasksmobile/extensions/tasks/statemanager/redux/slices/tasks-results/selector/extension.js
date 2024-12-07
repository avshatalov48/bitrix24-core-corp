/**
 * @module tasks/statemanager/redux/slices/tasks-results/selector
 */
jn.define('tasks/statemanager/redux/slices/tasks-results/selector', (require, exports, module) => {
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');
	const { sliceName, tasksResultsAdapter } = require('tasks/statemanager/redux/slices/tasks-results/meta');

	const {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,
	} = tasksResultsAdapter.getSelectors((state) => state[sliceName]);

	const selectByTaskId = createDraftSafeSelector(
		(state) => Object.values(selectEntities(state)),
		(state, taskId) => Number(taskId),
		(entities, taskId) => (
			entities
				.filter((entity) => entity.taskId === taskId)
				.sort((a, b) => b.id - a.id)
		),
	);

	const selectIdsByTaskId = createDraftSafeSelector(
		selectByTaskId,
		(results) => results.map((result) => result.id),
	);

	const selectLastResult = createDraftSafeSelector(
		selectByTaskId,
		(results) => (results[0] ?? {}),
	);

	module.exports = {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,

		selectByTaskId,
		selectIdsByTaskId,
		selectLastResult,
	};
});
