/**
 * @module tasks/statemanager/redux/slices/stage-counters/src/action
 */
jn.define('tasks/statemanager/redux/slices/stage-counters/src/action', (require, exports, module) => {
	const { sliceName } = require('tasks/statemanager/redux/slices/stage-counters/meta');
	const { createAction } = require('statemanager/redux/toolkit');

	const increaseStageCounter = createAction(`${sliceName}/stageCounterIncreased`);
	const decreaseStageCounter = createAction(`${sliceName}/stageCounterDecreased`);

	module.exports = {
		increaseStageCounter,
		decreaseStageCounter,
	};
});
