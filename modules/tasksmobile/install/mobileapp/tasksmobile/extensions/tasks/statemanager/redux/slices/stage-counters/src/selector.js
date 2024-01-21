/**
 * @module tasks/statemanager/redux/slices/stage-counters/src/selector
 */
jn.define('tasks/statemanager/redux/slices/stage-counters/src/selector', (require, exports, module) => {
	const {	createDraftSafeSelector } = require('statemanager/redux/toolkit');
	const { stringifyWithKeysSort } = require('tasks/statemanager/redux/utils');
	const { md5 } = require('utils/hash');
	const { isNil } = require('utils/type');
	const {
		adapter,
		sliceName,
	} = require('tasks/statemanager/redux/slices/stage-counters/meta');

	const {
		selectEntities,
		selectAll,
		selectById,
	} = adapter.getSelectors((state) => state[sliceName]);

	const selectByStageIdAndFilterParams = createDraftSafeSelector(
		(state, stageIdAndParams) => selectById(
			state,
			md5(stringifyWithKeysSort(stageIdAndParams)),
		),
		(counter) => (isNil(counter) ? null : counter.count),
	);

	const selectStatus = createDraftSafeSelector(
		(state) => state[sliceName],
		(stageCounters) => stageCounters.status,
	);

	module.exports = {
		selectById,
		selectStatus,
		selectEntities,
		selectAll,
		selectByStageIdAndFilterParams,
	};
});
