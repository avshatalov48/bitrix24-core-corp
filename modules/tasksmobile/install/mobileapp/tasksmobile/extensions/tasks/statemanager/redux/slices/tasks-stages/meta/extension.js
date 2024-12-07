/**
 * @module tasks/statemanager/redux/slices/tasks-stages/meta
 */
jn.define('tasks/statemanager/redux/slices/tasks-stages/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');
	const { StateCache } = require('statemanager/redux/state-cache');

	const sliceName = 'tasks:tasksStages';

	const selectId = ({ taskId, viewMode, userId }) => `${taskId}_${viewMode}_${userId}`;

	const entityAdapter = createEntityAdapter({ selectId });

	const initialState = StateCache.getReducerState(sliceName, entityAdapter.getInitialState());

	module.exports = {
		sliceName,
		selectId,
		entityAdapter,
		initialState,
	};
});
