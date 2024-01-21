/**
 * @module tasks/enum
 */
jn.define('tasks/enum', (require, exports, module) => {
	const ViewMode = {
		LIST: 'LIST',
		KANBAN: 'KANBAN',
		PLANNER: 'PLANNER',
		DEADLINE: 'DEADLINE',
	};

	const DeadlinePeriod = {
		PERIOD_OVERDUE: 'PERIOD1',
		PERIOD_NO_DEADLINE: 'PERIOD5',
		PERIOD_OVER_TWO_WEEKS: 'PERIOD6',
	};

	module.exports = {
		ViewMode,
		DeadlinePeriod,
	};
});
