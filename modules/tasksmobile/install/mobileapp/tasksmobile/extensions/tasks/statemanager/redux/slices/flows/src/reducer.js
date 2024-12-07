/**
 * @module tasks/statemanager/redux/slices/flows/src/reducer
 */
jn.define('tasks/statemanager/redux/slices/flows/src/reducer', (require, exports, module) => {
	const { entityAdapter } = require('tasks/statemanager/redux/slices/flows/meta');
	const { prepareFlow } = require('tasks/statemanager/redux/slices/flows/src/tool');

	const flowsUpserted = (state, action) => {
		if (action.payload)
		{
			entityAdapter.upsertMany(state, action.payload.map((flow) => prepareFlow(flow)));
		}
	};

	const flowsAdded = (state, action) => {
		if (action.payload)
		{
			entityAdapter.addMany(state, action.payload.map((flow) => prepareFlow(flow)));
		}
	};

	module.exports = {
		flowsUpserted,
		flowsAdded,
	};
});
