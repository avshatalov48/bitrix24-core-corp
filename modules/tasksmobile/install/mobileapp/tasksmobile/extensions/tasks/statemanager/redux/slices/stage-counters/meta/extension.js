/**
 * @module tasks/statemanager/redux/slices/stage-counters/meta
 */
jn.define('tasks/statemanager/redux/slices/stage-counters/meta', (require, exports, module) =>
{
	const { createEntityAdapter } = require('statemanager/redux/toolkit');

	const sliceName = 'tasks:stageCounters';
	const allStagesId = 'total';
	const adapter = createEntityAdapter({});

	module.exports = {
		allStagesId,
		sliceName,
		adapter,
	};
});
