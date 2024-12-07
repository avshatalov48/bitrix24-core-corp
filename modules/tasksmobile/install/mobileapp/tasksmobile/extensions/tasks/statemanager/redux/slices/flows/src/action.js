/**
 * @module tasks/statemanager/redux/slices/flows/src/action
 */
jn.define('tasks/statemanager/redux/slices/flows/src/action', (require, exports, module) => {
	const { sliceName } = require('tasks/statemanager/redux/slices/flows/meta');
	const { createAction } = require('statemanager/redux/toolkit');

	const upsertFlows = createAction(`${sliceName}/flowsUpserted`);
	const addFlows = createAction(`${sliceName}/flowsAdded`);

	module.exports = {
		upsertFlows,
		addFlows,
	};
});
