/* eslint-disable no-param-reassign */
/**
 * @module statemanager/redux/slices/tariff-plan-restrictions/extra-reducer
 */
jn.define('statemanager/redux/slices/tariff-plan-restrictions/extra-reducer', (require, exports, module) => {
	const fetchFulfilled = (state, action) => {
		const { status, data } = action.payload;
		if (status === 'success' && data.restrictions)
		{
			state.isLoaded = true;
			state.restrictions = data.restrictions;
		}
	};

	module.exports = { fetchFulfilled };
});
