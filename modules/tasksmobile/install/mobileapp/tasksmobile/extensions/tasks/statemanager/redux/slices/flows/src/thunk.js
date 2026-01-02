/**
 * @module tasks/statemanager/redux/slices/flows/src/thunk
 */
jn.define('tasks/statemanager/redux/slices/flows/src/thunk', (require, exports, module) => {
	const { sliceName } = require('tasks/statemanager/redux/slices/flows/meta');
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { isOnline } = require('device/connection');

	const condition = () => isOnline();

	const flowTogglePin = createAsyncThunk(
		`${sliceName}/togglePin`,
		async ({ flowId }, { rejectWithValue }) => {
			const response = await BX.ajax.runAction('tasks.flow.Flow.pin', {
				data: {
					flowId,
				},
			})
				.catch(console.error);

			if (!response || response.status === 'error')
			{
				const errors = response?.errors ?? [new Error('Flow pin toggle failed')];

				return rejectWithValue({
					errors,
					...response,
				});
			}

			return response;
		},
		{ condition },
	);

	module.exports = {
		flowTogglePin,
	};
});
