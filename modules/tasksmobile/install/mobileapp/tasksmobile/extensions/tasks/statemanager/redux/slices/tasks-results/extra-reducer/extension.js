/**
 * @module tasks/statemanager/redux/slices/tasks-results/extra-reducer
 */
jn.define('tasks/statemanager/redux/slices/tasks-results/extra-reducer', (require, exports, module) => {
	const { tasksResultsAdapter } = require('tasks/statemanager/redux/slices/tasks-results/meta');

	const fetchFulfilled = (state, action) => {
		const { taskId } = action.meta.arg;
		const { results } = action.payload.data;
		const taskResultsIds = (
			Object.values(state.entities).filter((item) => item.taskId === taskId).map((item) => item.id)
		);

		tasksResultsAdapter.removeMany(state, taskResultsIds);
		tasksResultsAdapter.addMany(state, results);
	};

	const createFulfilled = (state, action) => tasksResultsAdapter.addOne(state, action.payload.data);

	const updateFulFilled = (state, action) => tasksResultsAdapter.upsertOne(state, action.payload.data);

	const removeFulfilled = (state, action) => {
		const { commentId } = action.meta.arg;
		const result = Object.values(state.entities).find((entity) => entity.commentId === commentId);

		if (result)
		{
			tasksResultsAdapter.removeOne(state, result.id);
		}
	};

	module.exports = {
		fetchFulfilled,
		createFulfilled,
		updateFulFilled,
		removeFulfilled,
	};
});
