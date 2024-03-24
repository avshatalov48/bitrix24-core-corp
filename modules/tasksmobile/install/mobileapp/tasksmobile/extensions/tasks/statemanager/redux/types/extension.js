/**
 * @module tasks/statemanager/redux/types
 */
jn.define('tasks/statemanager/redux/types', (require, exports, module) => {
	const Views = {
		LIST: 'LIST',
		KANBAN: 'KANBAN',
		DEADLINE: 'DEADLINE',
		PLANNER: 'PLANNER',
	};

	const statusTypes = {
		success: 'success',
		failure: 'failure',
		pending: 'pending',
	};

	module.exports = {
		Views,
		statusTypes,
	};
});
