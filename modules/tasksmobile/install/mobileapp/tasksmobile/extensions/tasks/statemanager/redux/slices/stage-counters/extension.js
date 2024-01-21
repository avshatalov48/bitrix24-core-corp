/**
 * @module tasks/statemanager/redux/slices/stage-counters
 */
jn.define('tasks/statemanager/redux/slices/stage-counters', (require, exports, module) => {
	const {
		selectByStageIdAndFilterParams,
		selectEntities,
		selectAll,
		selectById,
		selectStatus,
	} = require('tasks/statemanager/redux/slices/stage-counters/src/selector');

	const { updateCounter	} = require('tasks/statemanager/redux/slices/stage-counters/src/reducer');

	const { allStagesId } = require('tasks/statemanager/redux/slices/stage-counters/meta');
	const { increaseStageCounter, decreaseStageCounter } = require('tasks/statemanager/redux/slices/stage-counters/src/action');
	const { slice } = require('tasks/statemanager/redux/slices/stage-counters/src/slice');

	module.exports = {
		selectEntities,
		selectAll,
		selectById,
		selectStatus,
		updateCounter,
		increaseStageCounter,
		decreaseStageCounter,
		selectByStageIdAndFilterParams,
		allStagesId,
		slice,
	};
});
