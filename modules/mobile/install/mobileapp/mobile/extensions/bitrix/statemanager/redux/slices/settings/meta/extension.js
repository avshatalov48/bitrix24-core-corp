/**
 * @module statemanager/redux/slices/settings/meta
 */
jn.define('statemanager/redux/slices/settings/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');
	const { StateCache } = require('statemanager/redux/state-cache');

	const sliceName = 'mobile:settings';
	const settingsAdapter = createEntityAdapter({
		selectId: (setting) => setting.id,
	});

	const initialState = StateCache.getReducerState(sliceName, settingsAdapter.getInitialState());

	module.exports = {
		sliceName,
		settingsAdapter,
		initialState,
	};
});
