/**
 * @module tasks/statemanager/redux/slices/flows/src/extra-reducer
 */
jn.define('tasks/statemanager/redux/slices/flows/src/extra-reducer', (require, exports, module) => {
	const flowTogglePin = (state, action) => {
		const { flowId } = action.meta.arg;
		if (flowId)
		{
			const flow = state.entities[flowId];
			if (flow)
			{
				flow.isPinned = !flow.isPinned;
			}
		}
	};

	module.exports = {
		flowTogglePinPending: flowTogglePin,
		flowTogglePinRejected: flowTogglePin,
	};
});
