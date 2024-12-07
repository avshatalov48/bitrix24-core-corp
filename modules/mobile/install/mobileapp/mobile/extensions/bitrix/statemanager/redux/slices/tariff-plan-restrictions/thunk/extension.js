/**
 * @module statemanager/redux/slices/tariff-plan-restrictions/thunk
 */
jn.define('statemanager/redux/slices/tariff-plan-restrictions/thunk', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { sliceName } = require('statemanager/redux/slices/tariff-plan-restrictions/meta');

	const fetch = createAsyncThunk(
		`${sliceName}/fetch`,
		() => new Promise((resolve) => {
			new RunActionExecutor('mobile.tariffplanrestriction.getTariffPlanRestrictions')
				.setHandler(resolve)
				.call(false)
			;
		}),
	);

	module.exports = { fetch };
});
