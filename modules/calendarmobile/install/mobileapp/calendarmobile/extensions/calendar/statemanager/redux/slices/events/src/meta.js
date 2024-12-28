/**
 * @module calendar/statemanager/redux/slices/events/meta
 */
jn.define('calendar/statemanager/redux/slices/events/meta', (require, exports, module) => {
	const { StateCache } = require('statemanager/redux/state-cache');
	const { createEntityAdapter } = require('statemanager/redux/toolkit');

	const sliceName = 'calendar:events';
	const eventsAdapter = createEntityAdapter();
	const initialState = StateCache.getReducerState(sliceName, eventsAdapter.getInitialState());

	module.exports = {
		sliceName,
		eventsAdapter,
		initialState,
	};
});
